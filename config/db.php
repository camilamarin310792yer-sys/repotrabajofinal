<?php
/**
 * Conexión a la base de datos MySQL (PDO) + creación automática de tablas.
 * Sistema de Gestión de Biblioteca
 */

// ==== Credenciales de conexión ====
$DB_HOST = 'ftp-hectorapi.alwaysdata.net';
$DB_NAME = 'hectorapi_usuario2db';
$DB_USER = 'hectorapi';
$DB_PASS = 'clase1234';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;background:#2c0b0b;color:#fff;">
            <h2>⚠️ Error de conexión a la base de datos</h2>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
         </div>');
}

/**
 * Creación automática de tablas si no existen.
 */
try {
    // Tabla usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            cedula VARCHAR(30) NOT NULL UNIQUE,
            telefono VARCHAR(30) NOT NULL,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Tabla libros
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS libros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(30) NOT NULL UNIQUE,
            titulo VARCHAR(255) NOT NULL,
            autor VARCHAR(150) NOT NULL,
            unidades INT NOT NULL DEFAULT 0,
            unidades_disponibles INT NOT NULL DEFAULT 0,
            portada_url VARCHAR(500) DEFAULT NULL,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Tabla préstamos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prestamos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            libro_id INT NOT NULL,
            usuario_id INT NOT NULL,
            fecha_prestamo DATE NOT NULL,
            fecha_devolucion_esperada DATE NOT NULL,
            fecha_devolucion_real DATE DEFAULT NULL,
            estado ENUM('prestado','devuelto') NOT NULL DEFAULT 'prestado',
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_prestamo_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
            CONSTRAINT fk_prestamo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;background:#2c0b0b;color:#fff;">
            <h2>⚠️ Error creando las tablas</h2>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
         </div>');
}
