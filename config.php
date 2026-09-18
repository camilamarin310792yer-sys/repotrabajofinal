<?php
declare(strict_types=1);

const DB_HOST = 'mysql-naderzonweb.alwaysdata.net';
const DB_NAME = 'naderzonweb_trabajofinal';
const DB_USER = 'naderzonweb';
const DB_PASS = 'clase1234';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $pdo;
}
