<?php

namespace App\Services;

use App\Models\McVersion;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * HTTP-клиент к Pterodactyl Panel.
 *
 * STUB-режим: если PTERODACTYL_API_KEY не задан — возвращаем fake-данные
 * и пишем в лог "[PTERO STUB] ...". Это позволяет разрабатывать без панели.
 *
 * Переключение в боевой режим — заполнить .env и `php artisan config:clear`.
 */
class PterodactylService
{
    private string $baseUrl;
    private string $appKey;
    private string $clientKey;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('pterodactyl.url'), '/');
        $this->appKey    = (string) config('pterodactyl.app_key');
        $this->clientKey = (string) config('pterodactyl.client_key');
    }

    public function isStub(): bool
    {
        return $this->appKey === '';
    }

    /**
     * Создаёт сервер в Pterodactyl.
     *
     * @return array{identifier:string, ip:string, port:int, sftp_password:string, node_id:?int}
     */
    public function createServer(Server $server): array
    {
        if ($this->isStub()) {
            return $this->stubCreateServer($server);
        }

        $tariff = $server->tariff;
        $eggId  = $this->resolveEggId($server->mc_version);
        $node   = $this->pickNode();

        // Извлекаем чистую версию MC из slug (например vanilla_1.20.4 → 1.20.4)
        $mcVersion = preg_replace('/^(vanilla|paper|forge|fabric|sponge)_/', '', $server->mc_version);

        // Каждое яйцо Pterodactyl требует свои переменные окружения
        $environment = $this->buildEnvironment($eggId, $mcVersion);

        // Docker-образ зависит от версии MC
        $dockerImage = $this->resolveDockerImage($mcVersion);

        try {
            $response = $this->appApi()->post('/servers?include=allocations', [
                'name'         => "CraftHost #{$server->id}",
                'user'         => $this->getOrCreatePteroUser($server->user),
                'egg'          => $eggId,
                'docker_image' => $dockerImage,
                'startup'      => config('pterodactyl.startup'),
                'environment'  => $environment,
                'limits' => [
                    'memory' => $tariff->ram_mb,
                    'swap'   => 0,
                    'disk'   => $tariff->disk_mb,
                    'io'     => 500,
                    'cpu'    => $tariff->cpu_percent,
                ],
                'feature_limits' => [
                    'databases'   => 1,
                    'backups'     => 5,
                    'allocations' => 1,
                ],
                'allocation' => [
                    'default' => $this->getFreeAllocation($node),
                ],
            ])->throw();
        } catch (RequestException $e) {
            Log::error('Pterodactyl createServer failed', [
                'server_id' => $server->id,
                'status'    => $e->response?->status(),
                'body'      => $e->response?->body(),
            ]);
            throw new \RuntimeException(
                'Не удалось создать сервер в Pterodactyl: ' . ($e->response?->body() ?? $e->getMessage())
            );
        }

        $attrs = $response->json('attributes');
        $alloc = $attrs['relationships']['allocations']['data'][0]['attributes'] ?? [];

        // Pterodactyl возвращает 0.0.0.0 если слушает на всех интерфейсах — заменяем на реальный IP VPS
        $ip = $alloc['ip'] ?? '';
        if ($ip === '0.0.0.0' || $ip === '' || $ip === '127.0.0.1') {
            $ip = config('app.vps_ip', '144.31.48.179');
        }

        return [
            'identifier'    => (string) $attrs['identifier'],
            'ip'            => (string) $ip,
            'port'          => (int)    ($alloc['port'] ?? 0),
            'sftp_password' => (string) ($attrs['sftp_details']['password'] ?? Str::random(16)),
            'node_id'       => $node?->id,
        ];
    }

    public function sendPowerSignal(string $serverIdentifier, string $signal): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] power signal', compact('serverIdentifier', 'signal'));
            return;
        }
        $this->clientApi()
            ->post("/servers/{$serverIdentifier}/power", ['signal' => $signal])
            ->throw();
    }

    /**
     * Отправляет консольную команду на сервер.
     */
    public function sendCommand(string $serverIdentifier, string $command): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] command', compact('serverIdentifier', 'command'));
            return;
        }
        $this->clientApi()
            ->post("/servers/{$serverIdentifier}/command", ['command' => $command])
            ->throw();
    }

    /**
     * Возвращает строки консольного лога.
     *
     * Pterodactyl отдаёт живой лог только по WebSocket, поэтому в боевом режиме
     * здесь возвращаем пустой массив (живой лог тянет фронт через сокет/поллинг
     * статистики), а в stub-режиме генерируем правдоподобные строки загрузки.
     *
     * @return array<int, array{time:string, level:string, text:string}>
     */
    public function getConsoleLog(string $serverIdentifier): array
    {
        if (!$this->isStub()) {
            return [];
        }

        $now   = now();
        $lines = [
            ['INFO', 'Starting minecraft server version 1.20.4'],
            ['INFO', 'Loading properties'],
            ['INFO', 'Default game type: SURVIVAL'],
            ['INFO', 'Preparing level "world"'],
            ['INFO', 'Preparing spawn area: 92%'],
            ['DONE', 'Done (4.812s)! For help, type "help"'],
            ['INFO', 'Player_Steve joined the game'],
            ['INFO', 'Player_Alex joined the game'],
        ];

        $out = [];
        foreach ($lines as $i => [$level, $text]) {
            $out[] = [
                'time'  => $now->copy()->subSeconds((count($lines) - $i) * 2)->format('H:i:s'),
                'level' => $level,
                'text'  => $text,
            ];
        }
        return $out;
    }

    public function suspendServer(string $serverIdentifier): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] suspend', compact('serverIdentifier'));
            return;
        }
        $this->appApi()->post("/servers/{$serverIdentifier}/suspend")->throw();
    }

    public function unsuspendServer(string $serverIdentifier): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] unsuspend', compact('serverIdentifier'));
            return;
        }
        $this->appApi()->post("/servers/{$serverIdentifier}/unsuspend")->throw();
    }

    /**
     * Принимает EULA за сервер (записывает eula=true через File API).
     */
    public function acceptEula(string $serverIdentifier): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] acceptEula', compact('serverIdentifier'));
            return;
        }

        // Несколько попыток с увеличивающейся задержкой — контейнер может ещё инициализироваться
        $delays = [5, 5, 10];
        $accepted = false;

        foreach ($delays as $i => $delay) {
            sleep($delay);

            try {
                $this->clientApi()
                    ->withBody("eula=true\n", 'text/plain')
                    ->post("/servers/{$serverIdentifier}/files/write?file=%2Feula.txt")
                    ->throw();
                $accepted = true;
                Log::info('EULA accepted successfully', ['server' => $serverIdentifier, 'attempt' => $i + 1]);
                break;
            } catch (\Throwable $e) {
                Log::warning('EULA accept attempt failed', [
                    'server'  => $serverIdentifier,
                    'attempt' => $i + 1,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        if (!$accepted) {
            // Последний шанс — прямая запись через shell на хосте (если Wings на том же сервере)
            Log::error('Failed to auto-accept EULA via API after all retries', ['server' => $serverIdentifier]);
        }
    }

    public function deleteServer(string $serverIdentifier): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] delete', compact('serverIdentifier'));
            return;
        }

        // Application API работает по числовому ID, а не identifier
        $pteroId = $this->getPteroInternalId($serverIdentifier);
        if (!$pteroId) {
            Log::warning("deleteServer: Pterodactyl server not found by identifier {$serverIdentifier}");
            return;
        }

        // force=true — удаляет даже если сервер запущен, и освобождает allocation
        $this->appApi()->delete("/servers/{$pteroId}/force")->throw();
        Log::info("Pterodactyl server #{$pteroId} (identifier={$serverIdentifier}) deleted, allocation freed");
    }

    /**
     * Получает внутренний числовой ID сервера Pterodactyl по short identifier.
     */
    private function getPteroInternalId(string $identifier): ?int
    {
        try {
            $response = $this->appApi()->get("/servers/external/{$identifier}");
            if ($response->successful()) {
                return $response->json('attributes.id');
            }
        } catch (\Throwable $e) {
            // fallback: перебрать список серверов
        }

        // Fallback: ищем в списке
        try {
            $page = 1;
            do {
                $response = $this->appApi()->get("/servers?page={$page}")->throw();
                $data = $response->json();
                foreach ($data['data'] ?? [] as $srv) {
                    if ($srv['attributes']['identifier'] === $identifier) {
                        return $srv['attributes']['id'];
                    }
                }
                $page++;
            } while ($page <= ($data['meta']['pagination']['total_pages'] ?? 1));
        } catch (\Throwable $e) {
            Log::error("getPteroInternalId failed: " . $e->getMessage());
        }

        return null;
    }

    public function createBackup(string $serverIdentifier): array
    {
        if ($this->isStub()) {
            return [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'stub-backup-' . now()->format('Ymd-His'),
                'bytes'      => random_int(50, 500) * 1024 * 1024,
                'created_at' => now()->toIso8601String(),
            ];
        }
        return $this->clientApi()
            ->post("/servers/{$serverIdentifier}/backups")
            ->throw()
            ->json('attributes') ?? [];
    }

    /**
     * Восстанавливает бэкап на сервере.
     */
    public function restoreBackup(string $serverIdentifier, string $backupUuid): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] restoreBackup', compact('serverIdentifier', 'backupUuid'));
            return;
        }
        $this->clientApi()
            ->post("/servers/{$serverIdentifier}/backups/{$backupUuid}/restore", [
                'truncate' => true,
            ])
            ->throw();
    }

    /**
     * Пересоздаёт мир на сервере (удаляет папку world и перезапускает).
     */
    public function regenMap(string $serverIdentifier): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] regenMap', compact('serverIdentifier'));
            return;
        }
        // Останавливаем сервер
        $this->clientApi()
            ->post("/servers/{$serverIdentifier}/power", ['signal' => 'stop'])
            ->throw();

        // Ждём немного для остановки
        sleep(3);

        // Удаляем файлы мира через Pterodactyl File API
        $this->clientApi()
            ->post("/servers/{$serverIdentifier}/files/delete", [
                'root' => '/',
                'files' => ['world', 'world_nether', 'world_the_end'],
            ])
            ->throw();

        // Запускаем сервер — мир сгенерируется заново
        $this->clientApi()
            ->post("/servers/{$serverIdentifier}/power", ['signal' => 'start'])
            ->throw();
    }

    public function getServerStats(string $serverIdentifier): array
    {
        if ($this->isStub()) {
            return [
                'current_state' => 'running',
                'resources'     => [
                    'cpu_absolute'   => random_int(5, 50),
                    'memory_bytes'   => random_int(512, 2048) * 1024 * 1024,
                    'disk_bytes'     => random_int(1, 5)    * 1024 * 1024 * 1024,
                ],
            ];
        }
        return $this->clientApi()
            ->get("/servers/{$serverIdentifier}/resources")
            ->throw()
            ->json('attributes') ?? [];
    }

    // --------------------------------------------------------------------
    // Внутренние хелперы
    // --------------------------------------------------------------------

    /**
     * Формирует переменные окружения в зависимости от яйца.
     * Vanilla: VANILLA_VERSION, SERVER_JARFILE
     * Paper:   MINECRAFT_VERSION, BUILD_NUMBER, SERVER_JARFILE
     * Forge:   MC_VERSION, BUILD_TYPE, SERVER_JARFILE
     */
    private function buildEnvironment(int $eggId, string $mcVersion): array
    {
        return match ($eggId) {
            1 => [ // Vanilla
                'SERVER_JARFILE'  => 'server.jar',
                'VANILLA_VERSION' => $mcVersion,
            ],
            2 => [ // Paper
                'SERVER_JARFILE'    => 'server.jar',
                'MINECRAFT_VERSION' => $mcVersion,
                'BUILD_NUMBER'      => 'latest',
                'DL_PATH'           => '',
            ],
            3 => [ // Forge
                'SERVER_JARFILE' => 'server.jar',
                'MC_VERSION'     => $mcVersion,
                'BUILD_TYPE'     => 'recommended',
                'FORGE_VERSION'  => '',
            ],
            default => [
                'SERVER_JARFILE'  => 'server.jar',
                'VANILLA_VERSION' => $mcVersion,
            ],
        };
    }

    /**
     * Выбирает Docker-образ с нужной версией Java.
     * MC 1.17+ требует Java 17, MC 1.16.5 и ниже — Java 8.
     */
    private function resolveDockerImage(string $mcVersion): string
    {
        $parts = explode('.', $mcVersion);
        $minor = (int) ($parts[1] ?? 0);

        if ($minor >= 21) {
            return 'ghcr.io/pterodactyl/yolks:java_21';
        }
        if ($minor >= 17) {
            return 'ghcr.io/pterodactyl/yolks:java_17';
        }
        return 'ghcr.io/pterodactyl/yolks:java_8';
    }

    /**
     * Egg ID берём из таблицы mc_versions (если запись есть), иначе из config.
     */
    private function resolveEggId(string $mcVersion): int
    {
        $row = McVersion::where('slug', $mcVersion)->first();
        if ($row && $row->ptero_egg_id) {
            return (int) $row->ptero_egg_id;
        }
        $eggs = config('pterodactyl.eggs');
        return (int) ($eggs[$mcVersion] ?? $eggs['1.20.4']);
    }

    /**
     * Выбираем ноду: первую активную с минимальной загрузкой.
     * Если в таблице nodes пусто — используем config('pterodactyl.default_node_id').
     */
    private function pickNode(): ?Node
    {
        $node = Node::query()
            ->where('is_active', true)
            ->withCount('servers')
            ->orderBy('servers_count', 'asc')
            ->first();

        if ($node && $node->max_servers > 0 && $node->servers_count >= $node->max_servers) {
            throw new \RuntimeException("На всех нодах закончилось место. Добавьте ноду в админке.");
        }

        return $node;
    }

    private function getOrCreatePteroUser(User $user): int
    {
        $existing = $this->appApi()
            ->get('/users', ['filter[email]' => $user->email])
            ->throw()
            ->json('data', []);

        if (!empty($existing)) {
            return (int) $existing[0]['attributes']['id'];
        }

        $created = $this->appApi()->post('/users', [
            'email'      => $user->email,
            'username'   => 'user_' . $user->id,
            'first_name' => 'User',
            'last_name'  => '#' . $user->id,
            'password'   => Str::random(32),
        ])->throw();

        return (int) $created->json('attributes.id');
    }

    private function getFreeAllocation(?Node $node): int
    {
        $nodeId = $node?->ptero_node_id ?? (int) config('pterodactyl.default_node_id');

        // Получаем все allocations и фильтруем незанятые вручную
        // (фильтр assigned не поддерживается в некоторых версиях Pterodactyl)
        $allocations = $this->appApi()
            ->get("/nodes/{$nodeId}/allocations")
            ->throw()
            ->json('data', []);

        $free = collect($allocations)->filter(function ($alloc) {
            return !($alloc['attributes']['assigned'] ?? false);
        })->values();

        if ($free->isEmpty()) {
            throw new \RuntimeException(
                "Нет свободных allocation на ноде #{$nodeId}. " .
                "Добавьте порты в Pterodactyl: Admin → Nodes → Allocations."
            );
        }
        return (int) $free[0]['attributes']['id'];
    }

    private function appApi(): PendingRequest
    {
        return Http::withToken($this->appKey)
            ->acceptJson()->asJson()
            ->timeout(config('pterodactyl.timeout', 15))
            ->baseUrl($this->baseUrl . '/api/application');
    }

    private function clientApi(): PendingRequest
    {
        return Http::withToken($this->clientKey ?: $this->appKey)
            ->acceptJson()->asJson()
            ->timeout(config('pterodactyl.timeout', 15))
            ->baseUrl($this->baseUrl . '/api/client');
    }

    /**
     * STUB createServer — возвращает правдоподобные данные «как от панели».
     * Использует пер-серверный детерминированный IP/port + случайную ноду из БД,
     * чтобы и Node-модель тоже задействовать в stub-режиме.
     */
    private function stubCreateServer(Server $server): array
    {
        $node = Node::query()->where('is_active', true)->inRandomOrder()->first();

        $octet = (($server->id ?? random_int(1, 254)) % 254) + 1;
        $ip    = "10.0.0.{$octet}";
        $port  = 25565 + (($server->id ?? 0) % 100);

        $identifier  = 'stub-' . Str::lower(Str::random(8));
        $sftpPasswd  = Str::random(16);

        Log::info('[PTERO STUB] createServer', [
            'server_id'  => $server->id,
            'identifier' => $identifier,
            'ip'         => $ip,
            'port'       => $port,
            'node'       => $node?->name,
        ]);

        return [
            'identifier'    => $identifier,
            'ip'            => $ip,
            'port'          => $port,
            'sftp_password' => $sftpPasswd,
            'node_id'       => $node?->id,
        ];
    }
}
