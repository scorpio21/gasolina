<?php
declare(strict_types=1);

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
        // Quita comillas envolventes si existen
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
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
function getDb(): mysqli {
    static $db = null;
    if ($db instanceof mysqli) {
        return $db;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $name = getenv('DB_NAME') ?: 'gasolina';

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
