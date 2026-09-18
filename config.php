<?php
// Configuración de conexión MySQL para AlwaysData
$host = 'mysql-yerissas.alwaysdata.net';
$db   = 'yerissas_biblioteca';
$user = 'yerissas';
$pass = '25455120';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión con MySQL: " . htmlspecialchars($e->getMessage()));
}
?>
