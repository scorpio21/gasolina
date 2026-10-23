<?php
// Copia este archivo como app/env.php y completa solo la configuración privada.
// No subas app/env.php a GitHub.
$vars = [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'gasolina',

    // Autenticación opcional para producción.
    // Genera el hash con: php -r "echo password_hash('TU_CONTRASEÑA', PASSWORD_DEFAULT), PHP_EOL;"
    'AUTH_ENABLED' => '0',
    'AUTH_USER' => '',
    'AUTH_PASSWORD_HASH' => '',
];

foreach ($vars as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
