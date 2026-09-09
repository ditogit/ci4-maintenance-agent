<?php

declare(strict_types=1);

const INSTALLER_TOKEN = 'CHANGE_ME_GENERATE_RANDOM_32';

if (PHP_VERSION_ID < 80300) {
    http_response_code(500);
    exit('PHP >=8.3 required');
}

$token = $_GET['token'] ?? '';
if (!hash_equals(INSTALLER_TOKEN, $token)) {
    http_response_code(403);
    exit('Forbidden: invalid token. Edit INSTALLER_TOKEN in this file and access ?token=YOUR_TOKEN');
}

if (INSTALLER_TOKEN === 'CHANGE_ME_GENERATE_RANDOM_32') {
    http_response_code(500);
    exit('Edit INSTALLER_TOKEN in this file first');
}

$root = dirname(__DIR__, 1);
if (!defined('ROOTPATH')) {
    $probe = realpath(__DIR__ . '/..');
    if ($probe !== false) {
        $root = $probe;
    }
    define('ROOTPATH', rtrim($root, '/\\') . DIRECTORY_SEPARATOR);
}
if (!defined('APPPATH')) {
    define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
}
if (!defined('WRITEPATH')) {
    define('WRITEPATH', ROOTPATH . 'writable' . DIRECTORY_SEPARATOR);
}
if (!defined('SYSTEMPATH')) {
    define('SYSTEMPATH', ROOTPATH . 'vendor/codeigniter4/framework/system' . DIRECTORY_SEPARATOR);
}
if (!defined('FCPATH')) {
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
}

$autoload = ROOTPATH . 'vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

$results = [];
$ok = true;

function addResult(string $step, bool $success, string $msg): void
{
    global $results, $ok;
    $results[] = ['step' => $step, 'ok' => $success, 'msg' => $msg];
    if (!$success) {
        $ok = false;
    }
}

$srcConfig = ROOTPATH . 'vendor/ditogit/ci4-agent/src/Config/Maintenance.php';
if (!is_file($srcConfig)) {
    $alt = ROOTPATH . 'app/Libraries/MaintenanceAgent/src/Config/Maintenance.php';
    if (is_file($alt)) {
        $srcConfig = $alt;
    } else {
        $alt2 = ROOTPATH . 'src/Config/Maintenance.php';
        if (is_file($alt2)) {
            $srcConfig = $alt2;
        }
    }
}
$destConfig = APPPATH . 'Config/Maintenance.php';
if (!is_file($destConfig)) {
    if (!is_dir(APPPATH . 'Config')) {
        @mkdir(APPPATH . 'Config', 0755, true);
    }
    if (is_file($srcConfig) && @copy($srcConfig, $destConfig)) {
        addResult('Config', true, 'Published to app/Config/Maintenance.php');
    } else {
        addResult('Config', false, 'Failed to publish config — copy manually from ' . $srcConfig);
    }
} else {
    addResult('Config', true, 'Already exists, skipped');
}

$envPath = ROOTPATH . '.env';
$genKey = 'project_' . bin2hex(random_bytes(8));
$genSecret = bin2hex(random_bytes(32));
$credsMsg = '';
if (!is_file($envPath)) {
    $credsMsg = "MAINTENANCE_API_KEY={$genKey}<br>MAINTENANCE_API_SECRET={$genSecret}<br><b>.env not found — create .env and paste above</b>";
    addResult('Credentials', true, 'Generated (save manually, .env missing)');
} else {
    $content = (string) @file_get_contents($envPath);
    if (!str_contains($content, 'MAINTENANCE_API_KEY')) {
        $append = PHP_EOL . "MAINTENANCE_API_KEY={$genKey}" . PHP_EOL . "MAINTENANCE_API_SECRET={$genSecret}" . PHP_EOL;
        if (@file_put_contents($envPath, $append, FILE_APPEND) !== false) {
            $credsMsg = "MAINTENANCE_API_KEY={$genKey}<br>MAINTENANCE_API_SECRET={$genSecret}<br>Appended to .env";
            addResult('Credentials', true, 'Appended to .env');
        } else {
            $credsMsg = "MAINTENANCE_API_KEY={$genKey}<br>MAINTENANCE_API_SECRET={$genSecret}<br><b>Write failed — add to .env manually, ensure writable</b>";
            addResult('Credentials', false, '.env not writable — add manually');
        }
    } else {
        $credsMsg = 'Already in .env — use rotate-key if needed';
        addResult('Credentials', true, 'Already exists');
    }
}

$routesFile = APPPATH . 'Config/Routes.php';
if (is_file($routesFile)) {
    $c = (string) @file_get_contents($routesFile);
    if (str_contains($c, 'ci4-agent') || str_contains($c, 'MaintenanceAgent')) {
        addResult('Routes', true, 'Already patched, skipped');
    } else {
        $patch = PHP_EOL . "// CI4 Maintenance Agent — added by web installer" . PHP_EOL
            . "if (file_exists(ROOTPATH . 'vendor/ditogit/ci4-agent/src/Config/Routes.php')) {" . PHP_EOL
            . "    require ROOTPATH . 'vendor/ditogit/ci4-agent/src/Config/Routes.php';" . PHP_EOL
            . "}" . PHP_EOL;
        if (@file_put_contents($routesFile, $patch, FILE_APPEND) !== false) {
            addResult('Routes', true, 'Patched app/Config/Routes.php');
        } else {
            addResult('Routes', false, 'Failed to patch Routes.php — add require manually');
        }
    }
} else {
    addResult('Routes', false, 'app/Config/Routes.php not found');
}

$filtersFile = APPPATH . 'Config/Filters.php';
if (is_file($filtersFile)) {
    $c = (string) @file_get_contents($filtersFile);
    $needed = [
        "'maintenance'" => "'maintenance'          => \\MaintenanceAgent\\Filters\\MaintenanceFilter::class,",
        "'maintenanceEnabled'" => "'maintenanceEnabled'   => \\MaintenanceAgent\\Filters\\MaintenanceEnabledFilter::class,",
        "'maintenanceAuth'" => "'maintenanceAuth'      => \\MaintenanceAgent\\Filters\\MaintenanceAuthFilter::class,",
        "'maintenanceRateLimit'" => "'maintenanceRateLimit' => \\MaintenanceAgent\\Filters\\MaintenanceRateLimitFilter::class,",
    ];
    $missing = [];
    foreach ($needed as $k => $line) {
        if (!str_contains($c, $k)) {
            $missing[] = $line;
        }
    }
    if ($missing === []) {
        addResult('Filters', true, 'All aliases present');
    } elseif (str_contains($c, '$aliases = [')) {
        $injected = implode(PHP_EOL . '        ', $missing);
        $new = str_replace('$aliases = [', '$aliases = [' . PHP_EOL . '        ' . $injected, $c);
        if (@file_put_contents($filtersFile, $new) !== false) {
            addResult('Filters', true, 'Patched: ' . implode(', ', array_keys(array_filter($needed, fn($k) => !str_contains($c, $k), ARRAY_FILTER_USE_KEY))));
        } else {
            addResult('Filters', false, 'Failed to patch Filters.php — add aliases manually');
        }
    } else {
        addResult('Filters', false, 'Cannot patch Filters.php — add aliases manually');
    }
} else {
    addResult('Filters', false, 'app/Config/Filters.php not found');
}

$migrateMsg = '';
$migrateOk = false;
try {
    if (class_exists(\Config\Database::class)) {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        $forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'request_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 64],
            'status' => ['type' => 'VARCHAR', 'constraint' => 32],
            'affected_rows' => ['type' => 'INT', 'default' => 0],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'metadata' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('action');
        $forge->addKey('created_at');
        $forge->createTable('maintenance_audit_logs', true);
        $migrateOk = true;
        $migrateMsg = 'Table maintenance_audit_logs ready';
    } else {
        throw new \RuntimeException('Config\Database not found');
    }
} catch (\Throwable $e) {
    try {
        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS `maintenance_audit_logs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `request_id` VARCHAR(64) NULL,
            `action` VARCHAR(64) NOT NULL,
            `status` VARCHAR(32) NOT NULL,
            `affected_rows` INT DEFAULT 0,
            `ip_address` VARCHAR(45) NULL,
            `metadata` TEXT NULL,
            `created_at` DATETIME NULL,
            KEY `action` (`action`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $migrateOk = true;
        $migrateMsg = 'Table created via raw SQL fallback';
    } catch (\Throwable $e2) {
        $migrateMsg = 'Migration failed: ' . $e->getMessage() . ' / ' . $e2->getMessage() . ' — run SQL via phpMyAdmin';
    }
}
addResult('Database', $migrateOk, $migrateMsg);

$selfDelete = false;
if (isset($_GET['delete']) && $_GET['delete'] === '1' && $ok) {
    @unlink(__FILE__);
    $selfDelete = true;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CI4 Maintenance Agent — Web Installer v1.1.0</title>
<style>body{font-family:system-ui,monospace;max-width:800px;margin:40px auto;padding:0 16px} .ok{color:green} .fail{color:red} pre{background:#f5f5f5;padding:12px;overflow:auto} a.btn{display:inline-block;padding:8px 14px;background:#111;color:#fff;text-decoration:none;border-radius:6px}</style>
<h1>CI4 Maintenance Agent — Web Installer v1.1.0</h1>
<ul>
<?php foreach ($results as $r): ?>
<li class="<?= $r['ok'] ? 'ok' : 'fail' ?>"><?= $r['ok'] ? '✓' : '✗' ?> <b><?= htmlspecialchars($r['step']) ?></b>: <?= htmlspecialchars($r['msg']) ?></li>
<?php endforeach; ?>
</ul>
<?php if ($credsMsg): ?>
<h3>Credentials</h3>
<pre><?= $credsMsg ?></pre>
<p>Simpan key/secret ini ke Hub. Jika sudah ada di .env, abaikan.</p>
<?php endif; ?>
<?php if ($ok): ?>
<p class="ok"><b>Status: READY</b> — test <code>GET /api/v1/maintenance/health</code> dengan header HMAC.</p>
<p><a class="btn" href="?token=<?= htmlspecialchars($token) ?>&delete=1" onclick="return confirm('Hapus installer? WAJIB hapus setelah install')">Hapus installer (delete)</a> — atau hapus manual via File Manager.</p>
<?php else: ?>
<p class="fail"><b>Beberapa langkah gagal</b> — perbaiki manual sesuai pesan di atas, lalu refresh.</p>
<?php endif; ?>
<?php if ($selfDelete): ?><p class="ok">Installer deleted.</p><?php endif; ?>
<hr><small>Token: ganti INSTALLER_TOKEN lalu akses <code>?token=TOKEN</code>. Hapus file setelah selesai. Jangan biarkan di production.</small>
