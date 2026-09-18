<?php
declare(strict_types=1);

final class Database
{
    public static function connection(): PDO
    {
        $host = getenv('DB_HOST') ?: 'mysql-naderzonweb.alwaysdata.net';
        $db = getenv('DB_NAME') ?: 'naderzonweb_biblioteca_alejandria';
        $user = getenv('DB_USER') ?: 'naderzonweb';
        $pass = getenv('DB_PASS') ?: 'Nm265526%';

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
