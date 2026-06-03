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
                'startup'      => $this->resolveStartup($eggId),
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
    public function getConsoleLog(string $serverIdentifier, int $lastLines = 200): array
    {
        if ($this->isStub()) {
            return $this->stubConsoleLog();
        }

        // Тянем последние N строк из logs/latest.log через File API Pterodactyl.
        try {
            $resp = $this->clientApi()
                ->get("/servers/{$serverIdentifier}/files/contents", ['file' => '/logs/latest.log']);

            if (!$resp->successful()) {
                return [];
            }
            $raw = (string) $resp->body();
        } catch (\Throwable $e) {
            Log::debug('getConsoleLog: ' . $e->getMessage());
            return [];
        }

        // Берём только последние N строк, чтобы не тащить мегабайты.
        $allLines = preg_split("/\r?\n/", $raw) ?: [];
        $allLines = array_filter($allLines, fn ($l) => $l !== '');
        $allLines = array_slice(array_values($allLines), -$lastLines);

        $out = [];
        foreach ($allLines as $line) {
            $out[] = $this->parseMinecraftLogLine($line);
        }
        return $out;
    }

    /**
     * Парсит строку лога Minecraft:
     *   [HH:MM:SS] [Thread/LEVEL]: текст
     */
    private function parseMinecraftLogLine(string $line): array
    {
        if (preg_match('/^\[(\d{2}:\d{2}:\d{2})\]\s*\[(?:[^\/\]]+\/)?([A-Z]+)\]:\s*(.*)$/u', $line, $m)) {
            return ['time' => $m[1], 'level' => $m[2], 'text' => $m[3]];
        }
        // Не распарсилось — отдаём как есть
        return ['time' => '', 'level' => 'INFO', 'text' => $line];
    }

    private function stubConsoleLog(): array
    {
        $now   = now();
        $lines = [
            ['INFO', 'Starting minecraft server version 1.20.4'],
            ['INFO', 'Loading properties'],
            ['INFO', 'Default game type: SURVIVAL'],
            ['INFO', 'Preparing level "world"'],
            ['INFO', 'Preparing spawn area: 92%'],
            ['INFO', 'Done (4.812s)! For help, type "help"'],
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
        $pteroId = $this->getPteroInternalId($serverIdentifier);
        if (!$pteroId) {
            Log::warning("suspendServer: Pterodactyl server not found by identifier {$serverIdentifier}");
            return;
        }
        $this->appApi()->post("/servers/{$pteroId}/suspend")->throw();
    }

    public function unsuspendServer(string $serverIdentifier): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] unsuspend', compact('serverIdentifier'));
            return;
        }
        $pteroId = $this->getPteroInternalId($serverIdentifier);
        if (!$pteroId) {
            Log::warning("unsuspendServer: Pterodactyl server not found by identifier {$serverIdentifier}");
            return;
        }
        $this->appApi()->post("/servers/{$pteroId}/unsuspend")->throw();
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

    /**
     * Немедленно записывает eula=true (без задержек). Вызывается прямо перед
     * запуском сервера, когда установка уже завершена и запись всегда проходит.
     */
    public function acceptEulaNow(string $serverIdentifier): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] acceptEulaNow', compact('serverIdentifier'));
            return;
        }

        $this->clientApi()
            ->withBody("eula=true\n", 'text/plain')
            ->post("/servers/{$serverIdentifier}/files/write?file=%2Feula.txt")
            ->throw();
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

    /**
     * Меняет egg/startup/окружение существующего сервера и запускает reinstall.
     * Используется при смене ядра Minecraft.
     */
    public function changeCoreAndReinstall(Server $server, string $newMcVersion): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] changeCoreAndReinstall', [
                'server'      => $server->id,
                'mc_version'  => $newMcVersion,
            ]);
            return;
        }

        if (!$server->ptero_server_id) {
            throw new \RuntimeException('У сервера нет ptero_server_id');
        }

        $pteroId = $this->getPteroInternalId($server->ptero_server_id);
        if (!$pteroId) {
            throw new \RuntimeException('Не нашли сервер в Pterodactyl');
        }

        $eggId = $this->resolveEggId($newMcVersion);
        $clean = preg_replace('/^(vanilla|paper|forge|fabric|sponge)_/', '', $newMcVersion);
        $environment = $this->buildEnvironment($eggId, $clean);
        $dockerImage = $this->resolveDockerImage($clean);

        try {
            $this->appApi()
                ->patch("/servers/{$pteroId}/startup", [
                    'startup'      => $this->resolveStartup($eggId),
                    'environment'  => $environment,
                    'egg'          => $eggId,
                    'image'        => $dockerImage,
                    'skip_scripts' => false,
                ])
                ->throw();
        } catch (RequestException $e) {
            throw new \RuntimeException($this->humaniseEggError($e, $eggId));
        }

        $this->appApi()
            ->post("/servers/{$pteroId}/reinstall")
            ->throw();
    }

    /**
     * Превращает 422-ошибку Pterodactyl про недостающие переменные в понятное сообщение.
     */
    private function humaniseEggError(RequestException $e, int $eggId): string
    {
        $body  = (string) ($e->response?->body() ?? '');
        $json  = json_decode($body, true) ?: [];
        $first = $json['errors'][0]['detail'] ?? null;

        if ($first && stripos($first, 'variable') !== false) {
            $details = $this->fetchEggDetails($eggId);
            $vars = collect($details['variables'] ?? [])
                ->pluck('env_variable')
                ->filter()
                ->implode(', ');

            return "Egg #{$eggId} в Pterodactyl требует другие переменные ({$first}). "
                 . "Доступные переменные у этого egg: {$vars}. "
                 . 'Проверьте, что в админ-панели CraftHost для версии указан правильный Egg ID.';
        }

        return 'Pterodactyl: ' . ($first ?? $body);
    }

    /**
     * Возвращает список бэкапов сервера из Pterodactyl (с актуальными размерами).
     * Ключ — uuid, значение — массив атрибутов.
     */
    public function listBackups(string $serverIdentifier): array
    {
        if ($this->isStub()) {
            return [];
        }
        $data = $this->clientApi()
            ->get("/servers/{$serverIdentifier}/backups")
            ->throw()
            ->json('data', []);

        $out = [];
        foreach ($data as $row) {
            $a = $row['attributes'] ?? [];
            if (!empty($a['uuid'])) {
                $out[$a['uuid']] = $a;
            }
        }
        return $out;
    }

    /**
     * Возвращает временную ссылку на скачивание бэкапа.
     */
    public function getBackupDownloadUrl(string $serverIdentifier, string $backupUuid): string
    {
        if ($this->isStub()) {
            return 'data:text/plain,stub-backup-' . $backupUuid;
        }
        $r = $this->clientApi()
            ->get("/servers/{$serverIdentifier}/backups/{$backupUuid}/download")
            ->throw();
        return (string) ($r->json('attributes.url') ?? '');
    }

    /**
     * Читает текстовый файл с сервера через File API.
     */
    public function readFile(string $serverIdentifier, string $remotePath): ?string
    {
        if ($this->isStub()) {
            return '';
        }
        try {
            $resp = $this->clientApi()
                ->get("/servers/{$serverIdentifier}/files/contents", ['file' => $remotePath]);
            if (!$resp->successful()) {
                return null;
            }
            return (string) $resp->body();
        } catch (\Throwable $e) {
            Log::debug("readFile: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Загружает бинарный файл в Pterodactyl File API.
     * $remotePath — путь относительно корня сервера, например "/mods/foo.jar".
     */
    public function uploadFile(string $serverIdentifier, string $remotePath, string $contents): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] uploadFile', [
                'server' => $serverIdentifier,
                'path'   => $remotePath,
                'bytes'  => strlen($contents),
            ]);
            return;
        }

        $this->clientApi()
            ->withBody($contents, 'application/octet-stream')
            ->post("/servers/{$serverIdentifier}/files/write?file=" . rawurlencode($remotePath))
            ->throw();
    }

    /**
     * Удаляет файл(ы) на сервере. $files — пути относительно $root.
     */
    public function deleteFiles(string $serverIdentifier, string $root, array $files): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] deleteFiles', compact('serverIdentifier', 'root', 'files'));
            return;
        }
        $this->clientApi()
            ->post("/servers/{$serverIdentifier}/files/delete", [
                'root'  => $root,
                'files' => $files,
            ])
            ->throw();
    }

    /**
     * Создаёт каталог на сервере.
     */
    public function ensureDirectory(string $serverIdentifier, string $root, string $name): void
    {
        if ($this->isStub()) {
            Log::info('[PTERO STUB] ensureDirectory', compact('serverIdentifier', 'root', 'name'));
            return;
        }
        try {
            $this->clientApi()
                ->post("/servers/{$serverIdentifier}/files/create-folder", [
                    'root' => $root,
                    'name' => $name,
                ])
                ->throw();
        } catch (\Throwable $e) {
            // 422 / 409 если каталог уже существует — игнорируем
            Log::debug('ensureDirectory ignore: ' . $e->getMessage());
        }
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
     * Имена env-переменных Pterodactyl, в которые ПОДСТАВЛЯЕМ версию MC.
     * Только реальные «версия игры»; всё остальное (FABRIC_VERSION/LOADER_VERSION/
     * FORGE_VERSION/BUILD_NUMBER) — это версии установщиков/лоадеров,
     * их трогать нельзя, оставляем дефолты egg'а (обычно "latest").
     */
    private const VERSION_ENV_NAMES = [
        'MINECRAFT_VERSION', 'MC_VERSION', 'VANILLA_VERSION',
    ];

    /**
     * Кэш переменных egg'ов (на время одного запроса).
     */
    private array $eggCache = [];

    /**
     * Возвращает список всех eggs из Pterodactyl, пригодный для админ-UI.
     * Каждый элемент: { id, name, nest, suggested_type, env_variables[] }.
     * suggested_type — наша эвристика по имени egg (vanilla/paper/forge/fabric/spigot/sponge/bungee).
     */
    public function listAllEggs(): array
    {
        if ($this->isStub()) {
            return [];
        }

        try {
            $resp = $this->appApi()
                ->get('/nests', ['include' => 'eggs.variables', 'per_page' => 100])
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('listAllEggs failed: ' . $e->getMessage());
            return [];
        }

        $out = [];
        foreach ($resp->json('data', []) as $nest) {
            $nestName = $nest['attributes']['name'] ?? '';
            // Игнорируем nest'ы не про Minecraft (Source Engine, Voice Servers, Rust и т.п.)
            if (stripos($nestName, 'minecraft') === false) {
                continue;
            }
            foreach ($nest['attributes']['relationships']['eggs']['data'] ?? [] as $egg) {
                $a = $egg['attributes'];
                $vars = array_map(
                    fn ($v) => $v['attributes']['env_variable'] ?? null,
                    $a['relationships']['variables']['data'] ?? []
                );
                $vars = array_values(array_filter($vars));

                $out[] = [
                    'id'             => (int) $a['id'],
                    'name'           => (string) $a['name'],
                    'nest'           => $nestName,
                    'env_variables'  => $vars,
                    'suggested_type' => $this->guessEggType((string) $a['name'], $vars),
                ];
            }
        }

        usort($out, fn ($x, $y) => $x['id'] <=> $y['id']);
        return $out;
    }

    /**
     * Эвристика: к какому типу ядра CraftHost больше всего подходит egg.
     */
    private function guessEggType(string $name, array $envVars): ?string
    {
        $n = mb_strtolower($name);
        foreach (['fabric', 'forge', 'paper', 'sponge', 'spigot', 'bungee', 'vanilla'] as $kw) {
            if (str_contains($n, $kw)) return $kw;
        }
        // По переменным
        foreach ($envVars as $v) {
            $u = strtoupper($v);
            if (str_contains($u, 'FABRIC')) return 'fabric';
            if (str_contains($u, 'FORGE'))  return 'forge';
            if (str_contains($u, 'PAPER'))  return 'paper';
            if (str_contains($u, 'SPONGE')) return 'sponge';
        }
        return null;
    }

    /**
     * Получает atributes egg'а вместе с переменными и docker_images.
     * Pterodactyl API требует знать nest, поэтому пробегаем по всем nests
     * один раз с include=eggs.variables и складываем в кэш.
     *
     * @return array{startup:string, variables:array<int,array>, docker_images:array}|null
     */
    private function fetchEggDetails(int $eggId): ?array
    {
        if (isset($this->eggCache[$eggId])) {
            return $this->eggCache[$eggId];
        }

        try {
            $resp = $this->appApi()
                ->get('/nests', ['include' => 'eggs.variables', 'per_page' => 100])
                ->throw();

            foreach ($resp->json('data', []) as $nest) {
                $eggs = $nest['attributes']['relationships']['eggs']['data'] ?? [];
                foreach ($eggs as $egg) {
                    $attrs = $egg['attributes'] ?? [];
                    if ((int) ($attrs['id'] ?? 0) !== $eggId) continue;

                    $vars = array_map(
                        fn ($v) => $v['attributes'] ?? [],
                        $attrs['relationships']['variables']['data'] ?? []
                    );

                    $this->eggCache[$eggId] = [
                        'startup'       => (string) ($attrs['startup'] ?? ''),
                        'variables'     => $vars,
                        'docker_images' => (array)  ($attrs['docker_images'] ?? []),
                    ];
                    return $this->eggCache[$eggId];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('fetchEggDetails failed', ['egg' => $eggId, 'err' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Формирует environment для Pterodactyl: берёт реальные переменные egg'а
     * (default_value у каждой), подставляет $mcVersion в первую найденную
     * версионную переменную.
     */
    private function buildEnvironment(int $eggId, string $mcVersion): array
    {
        $details = $this->fetchEggDetails($eggId);

        // Fallback на старое поведение, если egg не достали
        if (!$details) {
            return [
                'SERVER_JARFILE'    => 'server.jar',
                'VANILLA_VERSION'   => $mcVersion,
                'MINECRAFT_VERSION' => $mcVersion,
                'MC_VERSION'        => $mcVersion,
                'FABRIC_VERSION'    => $mcVersion,
            ];
        }

        $env = [];
        foreach ($details['variables'] as $v) {
            $name = $v['env_variable'] ?? null;
            if (!$name) continue;
            $env[$name] = (string) ($v['default_value'] ?? '');
        }

        // Подставляем версию MC только в реально «игровые» переменные
        foreach (self::VERSION_ENV_NAMES as $key) {
            if (array_key_exists($key, $env)) {
                $env[$key] = $mcVersion;
            }
        }
        // SERVER_JARFILE по умолчанию server.jar если egg не задал
        if (!isset($env['SERVER_JARFILE']) || $env['SERVER_JARFILE'] === '') {
            $env['SERVER_JARFILE'] = 'server.jar';
        }

        return $env;
    }

    /**
     * Возвращает стартап-команду egg'а (или fallback из config).
     */
    private function resolveStartup(int $eggId): string
    {
        $details = $this->fetchEggDetails($eggId);
        return $details['startup']
            ?: (string) config('pterodactyl.startup', 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}} --nogui');
    }

    /**
     * Выбирает Docker-образ с нужной версией Java.
     * Для MC 1.17+ берём Java 21 (большинство свежих плагинов/модов уже
     * собраны под Java 21, а сам MC 1.17+ работает на Java 21 без проблем).
     * Для 1.16.5 и ниже — Java 8.
     */
    private function resolveDockerImage(string $mcVersion): string
    {
        $parts = explode('.', $mcVersion);
        $minor = (int) ($parts[1] ?? 0);

        if ($minor >= 17) {
            return 'ghcr.io/pterodactyl/yolks:java_21';
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
