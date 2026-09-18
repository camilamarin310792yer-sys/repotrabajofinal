-- Opcional: las tablas se crean automáticamente desde config.php.
CREATE TABLE IF NOT EXISTS usuarios (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(120) NOT NULL,
 cedula VARCHAR(30) NOT NULL UNIQUE,
 telefono VARCHAR(30) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS libros (
 id INT AUTO_INCREMENT PRIMARY KEY,
 codigo VARCHAR(40) NOT NULL UNIQUE,
 titulo VARCHAR(180) NOT NULL,
 autor VARCHAR(120) NOT NULL,
 unidades INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS prestamos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 libro_id INT NOT NULL,
 fecha_prestamo DATE NOT NULL,
 fecha_devolucion DATE NULL,
 estado ENUM('Prestado','Devuelto') NOT NULL DEFAULT 'Prestado',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
 FOREIGN KEY (libro_id) REFERENCES libros(id)
);
