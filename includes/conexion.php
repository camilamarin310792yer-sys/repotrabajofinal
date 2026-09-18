<?php
/* ============================================================
   CONEXIÓN A MYSQL (PDO) + CREACIÓN AUTOMÁTICA DE TABLAS
   ============================================================ */
require_once __DIR__ . '/config.php';

/**
 * Devuelve una única conexión PDO para toda la petición.
 * La primera vez que se llama, crea las tablas si no existen.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // filas como arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // consultas preparadas reales
        ]);
    } catch (PDOException $e) {
        // Pantalla de error legible en lugar de un error fatal crudo
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error de conexión</title>'
           . '<link rel="stylesheet" href="assets/estilo.css"></head><body><main class="contenido">'
           . '<section class="pergamino"><h2 class="titulo-seccion">El archivo está cerrado</h2>'
           . '<p>No fue posible conectar con la base de datos.</p>'
           . '<p class="detalle-error">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
           . '<p>Revisa las credenciales en <code>includes/config.php</code>.</p></section></main></body></html>';
        exit;
    }

    instalar_tablas($pdo);
    return $pdo;
}

/**
 * Crea las tablas si no existen. Es seguro ejecutarlo en cada petición:
 * "IF NOT EXISTS" hace que no toque tablas que ya tienen datos.
 */
function instalar_tablas(PDO $pdo): void
{
    // Lectores (usuarios de la biblioteca)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre     VARCHAR(120) NOT NULL,
            cedula     VARCHAR(20)  NOT NULL,
            telefono   VARCHAR(20)  NOT NULL,
            creado_en  DATETIME     NOT NULL,
            UNIQUE KEY uk_usuarios_cedula (cedula)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Libros: 'unidades' es el total de ejemplares que posee la biblioteca
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS libros (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            codigo     VARCHAR(30)  NOT NULL,
            titulo     VARCHAR(200) NOT NULL,
            autor      VARCHAR(150) NOT NULL,
            unidades   INT UNSIGNED NOT NULL DEFAULT 1,
            creado_en  DATETIME     NOT NULL,
            UNIQUE KEY uk_libros_codigo (codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Préstamos: si fecha_devolucion es NULL, el préstamo sigue activo.
    // Disponibles de un libro = unidades - préstamos activos (no se descuenta 'unidades').
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prestamos (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id       INT UNSIGNED NOT NULL,
            libro_id         INT UNSIGNED NOT NULL,
            fecha_prestamo   DATETIME     NOT NULL,
            fecha_limite     DATE         NOT NULL,
            fecha_devolucion DATETIME     NULL DEFAULT NULL,
            KEY idx_prestamos_activos (fecha_devolucion),
            CONSTRAINT fk_prestamos_usuario FOREIGN KEY (usuario_id)
                REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_prestamos_libro FOREIGN KEY (libro_id)
                REFERENCES libros(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
