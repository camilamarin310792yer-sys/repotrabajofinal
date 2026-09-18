<?php
require_once __DIR__ . '/config.php';

/*
 * Crea las tablas automáticamente si no existen.
 * También agrega algunos datos de demostración si las tablas están vacías.
 */
$pdo->exec("
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    cedula VARCHAR(30) NOT NULL UNIQUE,
    telefono VARCHAR(30) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS libros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    titulo VARCHAR(180) NOT NULL,
    autor VARCHAR(150) NOT NULL,
    unidades INT NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    libro_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_prestamo DATE NOT NULL,
    fecha_devolucion DATE NULL,
    estado ENUM('Prestado','Devuelto') NOT NULL DEFAULT 'Prestado',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prestamo_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_prestamo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Datos iniciales: se insertan solo cuando no hay registros.
if ((int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() === 0) {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, cedula, telefono) VALUES (?, ?, ?)");
    $stmt->execute(['Ana Martínez', '1001001001', '3001234567']);
    $stmt->execute(['Carlos Gómez', '1002002002', '3017654321']);
}

if ((int)$pdo->query("SELECT COUNT(*) FROM libros")->fetchColumn() === 0) {
    $stmt = $pdo->prepare("INSERT INTO libros (codigo, titulo, autor, unidades) VALUES (?, ?, ?, ?)");
    $stmt->execute(['LIB-001', 'Cien años de soledad', 'Gabriel García Márquez', 5]);
    $stmt->execute(['LIB-002', 'El principito', 'Antoine de Saint-Exupéry', 8]);
    $stmt->execute(['LIB-003', 'Orgullo y prejuicio', 'Jane Austen', 4]);
}
?>
