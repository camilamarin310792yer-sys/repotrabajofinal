<?php
require_once __DIR__ . '/config.php';
$message = '';
$error = '';
try {
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            cedula VARCHAR(40) NOT NULL UNIQUE,
            telefono VARCHAR(40) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        CREATE TABLE IF NOT EXISTS libros (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50) NOT NULL UNIQUE,
            titulo VARCHAR(200) NOT NULL,
            autor VARCHAR(150) NOT NULL,
            unidades INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        CREATE TABLE IF NOT EXISTS prestamos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT UNSIGNED NOT NULL,
            libro_id INT UNSIGNED NOT NULL,
            cantidad INT UNSIGNED NOT NULL DEFAULT 1,
            fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_devolucion DATETIME NULL,
            estado ENUM('prestado','devuelto') NOT NULL DEFAULT 'prestado',
            INDEX(usuario_id), INDEX(libro_id),
            CONSTRAINT fk_prestamo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
            CONSTRAINT fk_prestamo_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $message = 'Las tablas fueron creadas o ya existían. La biblioteca está lista.';
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Instalación | Biblioteca Romana</title>
<link rel="stylesheet" href="assets/style.css"></head><body class="roman-bg"><main class="install card">
<h1>🏛 Biblioteca Romana</h1><h2>Instalación</h2>
<?php if ($message): ?><div class="alert ok"><?=htmlspecialchars($message)?></div><a class="btn" href="index.php">Entrar a la biblioteca</a><?php endif; ?>
<?php if ($error): ?><div class="alert error">No se pudo completar la instalación: <?=htmlspecialchars($error)?></div><?php endif; ?>
<p>Este instalador crea automáticamente las tablas <b>usuarios</b>, <b>libros</b> y <b>prestamos</b>.</p>
</main></body></html>