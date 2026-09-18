<?php
declare(strict_types=1);

$host = 'mysql-jojoapp.alwaysdata.net';
$db   = 'jojoapp_biblioteca';
$user = 'jojoapp';
$pass = '3108787231Jc.';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL,
            cedula VARCHAR(30) NOT NULL UNIQUE,
            telefono VARCHAR(30) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS libros (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50) NOT NULL UNIQUE,
            titulo VARCHAR(200) NOT NULL,
            autor VARCHAR(150) NOT NULL,
            unidades INT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS prestamos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT UNSIGNED NOT NULL,
            libro_id INT UNSIGNED NOT NULL,
            fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_devolucion DATETIME NULL,
            estado ENUM('Activo','Devuelto') NOT NULL DEFAULT 'Activo',
            CONSTRAINT fk_prestamo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_prestamo_libro FOREIGN KEY (libro_id) REFERENCES libros(id)
                ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    $db_error = 'No fue posible conectar con la base de datos. Verifica la configuración de conexión.';
}
?>