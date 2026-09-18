<?php
/* ============================================================
   DIAGNÓSTICO — Prueba la base de datos paso a paso.
   Úsalo solo para resolver problemas y BÓRRALO después.
   ============================================================ */
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/html; charset=utf-8');

$pasos = [];

// Guarda el resultado de cada paso: [ok?, título, detalle]
function paso(array &$pasos, bool $ok, string $titulo, string $detalle = ''): void
{
    $pasos[] = [$ok, $titulo, $detalle];
}

// 1. ¿PHP tiene el conector de MySQL?
$tienePdo = extension_loaded('pdo_mysql');
paso($pasos, $tienePdo, 'Extensión pdo_mysql', $tienePdo ? 'Instalada (PHP ' . PHP_VERSION . ')' : 'Falta: actívala en la configuración de PHP del sitio.');

$pdo = null;
if ($tienePdo) {
    // 2. ¿Se puede conectar al servidor (sin elegir base de datos)?
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        paso($pasos, true, 'Conexión al servidor', DB_HOST . ' con usuario ' . DB_USER);
    } catch (PDOException $e) {
        paso($pasos, false, 'Conexión al servidor', $e->getMessage() . ' → Revisa host, usuario y clave en includes/config.php.');
    }
}

if ($pdo) {
    // 3. ¿Existe la base de datos y el usuario tiene acceso?
    try {
        $pdo->exec('USE `' . str_replace('`', '', DB_NAME) . '`');
        paso($pasos, true, 'Base de datos ' . DB_NAME, 'Existe y el usuario tiene acceso.');
    } catch (PDOException $e) {
        paso($pasos, false, 'Base de datos ' . DB_NAME, $e->getMessage() . ' → Créala en el panel de alwaysdata (Bases de datos > MySQL) y da permisos al usuario ' . DB_USER . '.');
        $pdo = null;
    }
}

if ($pdo) {
    // 4. ¿El usuario puede crear tablas?
    try {
        $pdo->exec('CREATE TABLE IF NOT EXISTS _prueba_permisos (id INT) ENGINE=InnoDB');
        $pdo->exec('DROP TABLE _prueba_permisos');
        paso($pasos, true, 'Permiso para crear tablas', 'Correcto.');
    } catch (PDOException $e) {
        paso($pasos, false, 'Permiso para crear tablas', $e->getMessage() . ' → En alwaysdata, edita la base de datos y marca permisos de administrador para ' . DB_USER . '.');
    }

    // 5. ¿Hay tablas previas con otra estructura?
    $esperadas = [
        'usuarios'  => ['id', 'nombre', 'cedula', 'telefono', 'creado_en'],
        'libros'    => ['id', 'codigo', 'titulo', 'autor', 'unidades', 'creado_en'],
        'prestamos' => ['id', 'usuario_id', 'libro_id', 'fecha_prestamo', 'fecha_limite', 'fecha_devolucion'],
    ];
    foreach ($esperadas as $tabla => $columnas) {
        $existe = $pdo->query("SHOW TABLES LIKE '$tabla'")->fetchColumn();
        if (!$existe) {
            paso($pasos, true, "Tabla $tabla", 'No existe todavía: la app la creará.');
            continue;
        }
        $reales = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
        $faltan = array_diff($columnas, $reales);
        paso($pasos, !$faltan, "Tabla $tabla",
            $faltan
                ? 'Ya existe con OTRA estructura. Faltan: ' . implode(', ', $faltan) . ' → Si sus datos no importan, bórrala en phpMyAdmin y recarga la app.'
                : 'Existe con la estructura correcta.');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Diagnóstico</title><link rel="stylesheet" href="assets/estilo.css"></head>
<body>
<main class="contenido">
    <section class="pergamino">
        <h2 class="titulo-seccion">Diagnóstico de la base de datos</h2>
        <table class="tabla">
            <thead><tr><th>Estado</th><th>Paso</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php foreach ($pasos as [$ok, $titulo, $detalle]): ?>
                <tr>
                    <td><span class="sello <?= $ok ? 'sello-devuelto' : 'sello-vencido' ?>"><?= $ok ? 'OK' : 'Falla' ?></span></td>
                    <td><strong><?= htmlspecialchars($titulo) ?></strong></td>
                    <td><?= htmlspecialchars($detalle) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="vacio">Cuando todo salga OK, abre <a href="index.php">index.php</a> y elimina este archivo del servidor.</p>
    </section>
</main>
</body>
</html>
