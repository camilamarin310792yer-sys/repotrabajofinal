CREATE DATABASE IF NOT EXISTS biblioteca_alejandria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE biblioteca_alejandria;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  cedula VARCHAR(30) NOT NULL UNIQUE,
  telefono VARCHAR(30) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE autores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(140) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE titulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  autor_id INT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_titulo_autor (titulo, autor_id),
  CONSTRAINT fk_titulos_autor
    FOREIGN KEY (autor_id) REFERENCES autores(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE libros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  titulo_id INT NOT NULL,
  unidades INT NOT NULL DEFAULT 1,
  unidades_disponibles INT NOT NULL DEFAULT 1,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_libros_titulo
    FOREIGN KEY (titulo_id) REFERENCES titulos(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT chk_unidades CHECK (unidades >= 0),
  CONSTRAINT chk_disponibles CHECK (unidades_disponibles >= 0 AND unidades_disponibles <= unidades),
  CONSTRAINT chk_precio CHECK (precio >= 0)
) ENGINE=InnoDB;

CREATE TABLE prestamos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  libro_id INT NOT NULL,
  fecha_prestamo DATE NOT NULL,
  fecha_devolucion DATE NULL,
  estado ENUM('activo', 'devuelto') NOT NULL DEFAULT 'activo',
  observaciones VARCHAR(255) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_prestamos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_prestamos_libro
    FOREIGN KEY (libro_id) REFERENCES libros(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO usuarios (nombre, cedula, telefono) VALUES
('Hipatia de Alejandria', '100000001', '300-111-0001'),
('Eratostenes de Cirene', '100000002', '300-111-0002');

INSERT INTO autores (nombre) VALUES
('Euclides'),
('Homero'),
('Aristoteles');

INSERT INTO titulos (titulo, autor_id) VALUES
('Elementos', 1),
('La Iliada', 2),
('Metafisica', 3);

INSERT INTO libros (codigo, titulo_id, unidades, unidades_disponibles, precio) VALUES
('ALX-EUC-001', 1, 5, 5, 120000.00),
('ALX-HOM-001', 2, 3, 3, 85000.00),
('ALX-ARI-001', 3, 4, 4, 99000.00);
