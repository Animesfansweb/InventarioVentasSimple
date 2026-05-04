<?php

declare(strict_types=1);

// Devuelve una única instancia PDO (singleton) para no abrir conexiones repetidas.
// Host, usuario y contraseña se toman de variables de entorno; si no existen, se usan valores por defecto para desarrollo local.
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'inventario';
    $user = getenv('DB_USER') ?: 'inventario';
    $pass = getenv('DB_PASS') ?: '12345';

    // utf8mb4 permite el conjunto completo Unicode; el tipo utf8 antiguo de MySQL es más limitado.
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ]);

    return $pdo;
}
