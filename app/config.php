<?php
declare(strict_types=1);

// Sesión para preferencias (p.ej., vehiculo activo)
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    // Evita sesiones fijadas y rechaza identificadores enviados por URL.
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Carga variables locales si existe app/env.php (no se versiona)
if (file_exists(__DIR__ . '/env.php')) {
    require __DIR__ . '/env.php';
}

// Carga variables desde .env si existe (solo claves en formato KEY=VALUE)
// Nota: .env no debe subirse al repositorio (mantener en .gitignore)
$dotenvPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (is_file($dotenvPath) && is_readable($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // Quita comillas envolventes si existen (compatible con PHP 7.x)
        $len = strlen($val);
        if ($len >= 2) {
            $first = $val[0];
            $last  = $val[$len - 1];
            if (($first === '"' && $last === '"') || ($first === '\'' && $last === '\'')) {
                $val = substr($val, 1, -1);
            }
        }
        if ($key !== '') {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

/**
 * Retorna una conexión mysqli reutilizable usando variables de entorno.
 * Variables esperadas: DB_HOST, DB_USER, DB_PASS, DB_NAME
 */
function env(string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val === false || $val === '') {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string) $_SERVER[$key];
        }
        return $default;
    }
    return (string) $val;
}

/**
 * Genera y devuelve el token CSRF de la sesión actual.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

/**
 * Rechaza peticiones POST que no procedan de un formulario de esta aplicación.
 */
function verify_csrf_token(): void {
    $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    if ($token === '' || empty($_SESSION['csrf_token']) ||
        !hash_equals((string) $_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }
}

/**
 * Indica si la autenticación está activada mediante configuración.
 */
function auth_is_enabled(): bool {
    return env('AUTH_ENABLED', '0') === '1';
}

/**
 * Exige una sesión autenticada cuando AUTH_ENABLED=1.
 */
function require_auth(): void {
    if (!auth_is_enabled() || !empty($_SESSION['authenticated'])) {
        return;
    }

    $loginPath = (strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/pages/') !== false)
        ? 'login.php'
        : 'pages/login.php';
    $return = (string)($_SERVER['REQUEST_URI'] ?? '');
    $query = $return !== '' ? '?return=' . rawurlencode($return) : '';
    header('Location: ' . $loginPath . $query);
    exit;
}

function getDb(): mysqli {
    static $db = null;
    if ($db instanceof mysqli) {
        return $db;
    }

    $host = env('DB_HOST', 'localhost');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');
    $name = env('DB_NAME', 'gasolina');

    $db = new mysqli($host, $user, $pass, $name);
    if ($db->connect_error) {
        die('Error de conexión a la base de datos.');
    }
    $db->set_charset('utf8mb4');
    return $db;
}

/**
 * Escapa texto para salida HTML.
 */
function e(string $texto): string {
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Comprueba si existe una tabla en la BD actual.
 */
function hasTable(string $table): bool {
    $db = getDb();
    $dbname = $db->real_escape_string(env('DB_NAME', 'gasolina'));
    $tableEsc = $db->real_escape_string($table);
    $sql = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$tableEsc}' LIMIT 1";
    $res = $db->query($sql);
    return $res ? (bool)$res->num_rows : false;
}

/**
 * Comprueba si una columna existe en una tabla.
 */
function hasColumn(string $table, string $column): bool {
    $db = getDb();
    $dbname = $db->real_escape_string(env('DB_NAME', 'gasolina'));
    $tableEsc = $db->real_escape_string($table);
    $colEsc = $db->real_escape_string($column);
    $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$tableEsc}' AND COLUMN_NAME='{$colEsc}' LIMIT 1";
    $res = $db->query($sql);
    return $res ? (bool)$res->num_rows : false;
}

/**
 * Gestión de vehículo activo en sesión.
 */
function getActiveVehiculoId(): ?int {
    if (isset($_SESSION['vehiculo_id']) && is_numeric($_SESSION['vehiculo_id'])) {
        return (int) $_SESSION['vehiculo_id'];
    }
    return null;
}

function setActiveVehiculoId(?int $id): void {
    if ($id === null) {
        unset($_SESSION['vehiculo_id']);
    } else {
        $_SESSION['vehiculo_id'] = $id;
    }
}

/**
 * Obtiene lista de vehículos si existe la tabla. Devuelve array vacía si no.
 */
function getVehiculos(): array {
    if (!hasTable('vehiculos')) return [];
    $db = getDb();
    $capacitySelect = hasColumn('vehiculos', 'capacidad_deposito_l')
        ? 'capacidad_deposito_l'
        : 'NULL AS capacidad_deposito_l';
    $res = $db->query("SELECT id, marca, modelo, anio, combustible, activo, {$capacitySelect}, COALESCE(foto_url,'') AS foto_url FROM vehiculos ORDER BY activo DESC, marca, modelo");
    $out = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) { $out[] = $r; }
    }
    return $out;
}
