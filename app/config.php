<?php
declare(strict_types=1);

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
