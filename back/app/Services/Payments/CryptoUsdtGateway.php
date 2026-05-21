<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * USDT Gateway - direct blockchain payment (BEP-20 / ERC-20 / TRC-20).
 *
 * No third-party payment processor. Unique USDT amount = payment signature.
 * Blockchain polling via public RPCs (no API keys needed).
 */
class CryptoUsdtGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'crypto_usdt';
    }

    public function isConfigured(): bool
    {
        $cfg = config('payments.crypto_usdt', []);
        return !empty($cfg['rubToUsdt']) && !empty($cfg['networks']);
    }

    /**
     * Create crypto invoice with unique USDT amount.
     */
    public function initiate(Payment $payment, array $options = []): array
    {
        $cfg = config('payments.crypto_usdt', []);
        $rate = (float) ($cfg['rubToUsdt'] ?? 0);
        $networks = (array) ($cfg['networks'] ?? []);

        if ($rate <= 0 || empty($networks)) {
            throw new \RuntimeException('Криптоплатежи не настроены.');
        }

        // Unique amount: base + 0.0001..0.0099 (by payment id)
        $base = round((float) $payment->amount / $rate, 2);
        $offset = (($payment->id % 99) + 1) / 10000.0;
        $usdt = round($base + $offset, 4);

        // Store only public data in meta
        $publicNets = [];
        foreach ($networks as $key => $n) {
            if (empty($n['enabled'])) continue;
            $publicNets[$key] = [
                'label'     => $n['label'] ?? $key,
                'fullLabel' => $n['fullLabel'] ?? $key,
                'wallet'    => $n['wallet'] ?? '',
                'time'      => $n['time'] ?? '',
            ];
        }

        $frontUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');

        $payment->update([
            'external_id'      => (string) $usdt,
            'confirmation_url' => "{$frontUrl}/payment/usdt?payment_id={$payment->id}",
            'meta'             => [
                'amount_usdt' => $usdt,
                'rub_amount'  => (float) $payment->amount,
                'rub_to_usdt' => $rate,
                'networks'    => $publicNets,
                'invoice_ttl' => $cfg['invoiceTtlMinutes'] ?? 60,
                'created_ts'  => time(),
            ],
        ]);

        return [
            'payment_id'       => $payment->id,
            'confirmation_url' => $payment->confirmation_url,
            'external_id'      => $payment->external_id,
        ];
    }

    /**
     * Webhook not used for crypto — we poll the blockchain instead.
     */
    public function handleWebhook(Request $request): array
    {
        return [
            'payment'     => null,
            'status'      => 'pending',
            'external_id' => null,
        ];
    }

    /**
     * Check blockchain for incoming USDT transfer matching the expected amount.
     */
    public function checkStatus(Payment $payment): string
    {
        if ($payment->status !== 'pending' || !$payment->external_id) {
            return $payment->status;
        }

        $cfg = config('payments.crypto_usdt', []);
        $networks = (array) ($cfg['networks'] ?? []);
        $minConf = (int) ($cfg['minConfirmations'] ?? 1);

        foreach ($networks as $key => $net) {
            if (empty($net['enabled'])) continue;
            $kind = $net['kind'] ?? 'evm';

            try {
                if ($kind === 'evm' && $this->checkEvmTransfer($payment, $key, $net, $minConf)) {
                    return 'success';
                }
                if ($kind === 'tron' && $this->checkTronTransfer($payment, $key, $net, $minConf)) {
                    return 'success';
                }
            } catch (\Throwable $e) {
                Log::warning("CryptoUsdt sync [{$key}]: " . $e->getMessage());
            }
        }

        return 'pending';
    }

    // ─── EVM chain check (BSC, ETH, Polygon) ───

    private function checkEvmTransfer(Payment $payment, string $netKey, array $net, int $minConf): bool
    {
        $rpcUrls = is_array($net['rpcUrls'] ?? null) ? $net['rpcUrls'] : [];
        $contract = strtolower($net['contract'] ?? '');
        $wallet = strtolower($net['wallet'] ?? '');
        $decimals = (int) ($net['decimals'] ?? 18);
        $lookbackBlocks = (int) ($net['lookbackBlocks'] ?? 1200);

        if (empty($rpcUrls) || $contract === '' || $wallet === '') return false;

        $expectedRaw = $this->usdtToWei($payment->external_id, $decimals);
        if ($expectedRaw === '0') return false;

        $transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
        $topicTo = '0x' . str_pad(substr($wallet, 2), 64, '0', STR_PAD_LEFT);

        $logs = null;
        $latestBlock = 0;

        foreach ($rpcUrls as $rpcUrl) {
            $latestHex = $this->rpcCall($rpcUrl, 'eth_blockNumber', []);
            if (!is_string($latestHex)) continue;
            $candidate = $this->hexToInt($latestHex);
            if ($candidate <= 0) continue;

            $fromBlock = '0x' . dechex(max(0, $candidate - $lookbackBlocks));
            $resp = $this->rpcCall($rpcUrl, 'eth_getLogs', [[
                'address'   => $contract,
                'fromBlock' => $fromBlock,
                'toBlock'   => 'latest',
                'topics'    => [$transferTopic, null, $topicTo],
            ]]);
            if (is_array($resp)) {
                $latestBlock = $candidate;
                $logs = $resp;
                break;
            }
        }

        if (!is_array($logs)) return false;

        foreach ($logs as $log) {
            if (!is_array($log)) continue;
            $value = $this->hexToBig($log['data'] ?? '');
            if ($value !== $expectedRaw) continue;

            $block = $this->hexToInt($log['blockNumber'] ?? '0x0');
            $confirmations = $latestBlock - $block + 1;
            if ($confirmations < $minConf) continue;

            $txHash = $log['transactionHash'] ?? '';
            if ($txHash === '') continue;

            // Double-spend protection
            $usedByOther = Payment::where('id', '!=', $payment->id)
                ->where('meta', 'like', '%"tx_hash":"' . $txHash . '"%')
                ->exists();
            if ($usedByOther) continue;

            $meta = $payment->meta ?? [];
            $meta['tx_hash'] = $txHash;
            $meta['tx_block'] = $block;
            $meta['tx_confirmations'] = $confirmations;
            $meta['tx_network'] = $net['label'] ?? $netKey;
            $payment->update(['meta' => $meta]);

            return true;
        }

        return false;
    }

    // ─── TRON chain check (TRC-20) ───

    private function checkTronTransfer(Payment $payment, string $netKey, array $net, int $minConf): bool
    {
        $apiUrl = rtrim($net['apiUrl'] ?? '', '/');
        $wallet = $net['wallet'] ?? '';
        $contract = $net['contract'] ?? '';
        $decimals = (int) ($net['decimals'] ?? 6);

        if ($apiUrl === '' || $wallet === '' || $contract === '') return false;

        $expectedRaw = $this->usdtToWei($payment->external_id, $decimals);
        if ($expectedRaw === '0') return false;

        $url = $apiUrl . '/v1/accounts/' . urlencode($wallet) . '/transactions/trc20'
            . '?limit=30&only_to=true&contract_address=' . urlencode($contract);

        $resp = Http::timeout(15)->get($url)->json();
        if (!is_array($resp) || !isset($resp['data'])) return false;

        $paymentCreated = $payment->meta['created_ts'] ?? ($payment->created_at?->timestamp ?? 0);

        foreach ($resp['data'] as $tx) {
            if (!is_array($tx)) continue;
            if (strtolower($tx['to'] ?? '') !== strtolower($wallet)) continue;
            if (strtolower($tx['token_info']['address'] ?? '') !== strtolower($contract)) continue;
            if ((string) ($tx['value'] ?? '') !== $expectedRaw) continue;

            $tsMs = (int) ($tx['block_timestamp'] ?? 0);
            if ($tsMs <= 0) continue;
            $ts = (int) ($tsMs / 1000);
            if ($ts < $paymentCreated - 600) continue;

            $confirmed = (bool) ($tx['confirmed'] ?? false);
            if (!$confirmed && $minConf > 0) continue;

            $txHash = $tx['transaction_id'] ?? '';
            if ($txHash === '') continue;

            $usedByOther = Payment::where('id', '!=', $payment->id)
                ->where('meta', 'like', '%"tx_hash":"' . $txHash . '"%')
                ->exists();
            if ($usedByOther) continue;

            $meta = $payment->meta ?? [];
            $meta['tx_hash'] = $txHash;
            $meta['tx_network'] = $net['label'] ?? $netKey;
            $payment->update(['meta' => $meta]);

            return true;
        }

        return false;
    }

    // ─── Helpers ───

    private function rpcCall(string $url, string $method, array $params): mixed
    {
        try {
            $resp = Http::timeout(10)->post($url, [
                'jsonrpc' => '2.0',
                'method'  => $method,
                'params'  => $params,
                'id'      => 1,
            ])->json();
            return $resp['result'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function usdtToWei(string $amount, int $decimals): string
    {
        $amount = trim($amount);
        if ($amount === '' || !preg_match('/^\d+(\.\d+)?$/', $amount)) return '0';

        if (strpos($amount, '.') === false) {
            $whole = $amount;
            $frac = '';
        } else {
            [$whole, $frac] = explode('.', $amount, 2);
        }
        $frac = substr($frac . str_repeat('0', $decimals), 0, $decimals);
        $raw = ltrim($whole . $frac, '0');
        return $raw === '' ? '0' : $raw;
    }

    private function hexToBig(string $hex): string
    {
        if ($hex === '') return '0';
        if (str_starts_with($hex, '0x') || str_starts_with($hex, '0X')) {
            $hex = substr($hex, 2);
        }
        $hex = ltrim(strtolower($hex), '0');
        if ($hex === '') return '0';

        $dec = '0';
        for ($i = 0, $n = strlen($hex); $i < $n; $i++) {
            $dec = bcmul($dec, '16');
            $dec = bcadd($dec, (string) hexdec($hex[$i]));
        }
        return $dec;
    }

    private function hexToInt(string $hex): int
    {
        if ($hex === '') return 0;
        if (str_starts_with($hex, '0x') || str_starts_with($hex, '0X')) {
            $hex = substr($hex, 2);
        }
        return (int) hexdec($hex);
    }
}
