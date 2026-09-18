<?php
/* ============================================================
   ATRIUM — Panel de inicio con resumen de la biblioteca
   ============================================================ */
require_once __DIR__ . '/includes/funciones.php';
$pdo = db();

// Totales generales
$totalUsuarios  = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalTitulos   = (int)$pdo->query('SELECT COUNT(*) FROM libros')->fetchColumn();
$totalEjemplares = (int)$pdo->query('SELECT COALESCE(SUM(unidades),0) FROM libros')->fetchColumn();
$totalActivos   = (int)$pdo->query('SELECT COUNT(*) FROM prestamos WHERE fecha_devolucion IS NULL')->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM prestamos WHERE fecha_devolucion IS NULL AND fecha_limite < ?');
$st->execute([hoy()]);
$totalVencidos = (int)$st->fetchColumn();

$disponibles = max(0, $totalEjemplares - $totalActivos);

// Préstamos vencidos (los más urgentes primero)
$st = $pdo->prepare('
    SELECT p.id, p.fecha_limite, u.nombre, u.telefono, l.titulo
    FROM prestamos p
    JOIN usuarios u ON u.id = p.usuario_id
    JOIN libros   l ON l.id = p.libro_id
    WHERE p.fecha_devolucion IS NULL AND p.fecha_limite < ?
    ORDER BY p.fecha_limite ASC
    LIMIT 8
');
$st->execute([hoy()]);
$vencidos = $st->fetchAll();

// Últimos movimientos
$recientes = $pdo->query('
    SELECT p.fecha_prestamo, p.fecha_devolucion, p.fecha_limite, u.nombre, l.titulo
    FROM prestamos p
    JOIN usuarios u ON u.id = p.usuario_id
    JOIN libros   l ON l.id = p.libro_id
    ORDER BY p.id DESC
    LIMIT 8
')->fetchAll();

$titulo = 'Atrium';
$pagina = 'index.php';
require __DIR__ . '/includes/cabecera.php';
?>

<section class="teselas" aria-label="Resumen">
    <article class="tesela">
        <span class="tesela-romano"><?= romano($totalTitulos) ?></span>
        <span class="tesela-numero"><?= $totalTitulos ?></span>
        <span class="tesela-etiqueta">Títulos</span>
    </article>
    <article class="tesela">
        <span class="tesela-romano"><?= romano($totalEjemplares) ?></span>
        <span class="tesela-numero"><?= $totalEjemplares ?></span>
        <span class="tesela-etiqueta">Ejemplares</span>
    </article>
    <article class="tesela">
        <span class="tesela-romano"><?= romano($disponibles) ?></span>
        <span class="tesela-numero"><?= $disponibles ?></span>
        <span class="tesela-etiqueta">Disponibles</span>
    </article>
    <article class="tesela">
        <span class="tesela-romano"><?= romano($totalUsuarios) ?></span>
        <span class="tesela-numero"><?= $totalUsuarios ?></span>
        <span class="tesela-etiqueta">Lectores</span>
    </article>
    <article class="tesela">
        <span class="tesela-romano"><?= romano($totalActivos) ?></span>
        <span class="tesela-numero"><?= $totalActivos ?></span>
        <span class="tesela-etiqueta">En préstamo</span>
    </article>
    <article class="tesela<?= $totalVencidos ? ' tesela-alerta' : '' ?>">
        <span class="tesela-romano"><?= romano($totalVencidos) ?></span>
        <span class="tesela-numero"><?= $totalVencidos ?></span>
        <span class="tesela-etiqueta">Vencidos</span>
    </article>
</section>

<div class="acciones-rapidas">
    <a class="btn btn-terracota" href="prestamos.php">Registrar préstamo</a>
    <a class="btn btn-oro" href="libros.php">Añadir libro</a>
    <a class="btn btn-negro" href="usuarios.php">Inscribir lector</a>
</div>

<div class="rejilla-dos">
    <section class="pergamino">
        <h2 class="titulo-seccion">Plazos vencidos</h2>
        <?php if (!$vencidos): ?>
            <p class="vacio">Ningún volumen fuera de plazo. Minerva está satisfecha.</p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead><tr><th>Lector</th><th>Libro</th><th>Límite</th></tr></thead>
                    <tbody>
                    <?php foreach ($vencidos as $v): ?>
                        <tr>
                            <td><?= e($v['nombre']) ?><small class="sub"><?= e($v['telefono']) ?></small></td>
                            <td><?= e($v['titulo']) ?></td>
                            <td><span class="sello sello-vencido"><?= e(fecha_corta($v['fecha_limite'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a class="enlace-mas" href="prestamos.php?filtro=vencidos">Ver todos los vencidos →</a>
        <?php endif; ?>
    </section>

    <section class="pergamino">
        <h2 class="titulo-seccion">Últimos movimientos</h2>
        <?php if (!$recientes): ?>
            <p class="vacio">Aún no hay préstamos registrados.</p>
        <?php else: ?>
            <ul class="bitacora">
                <?php foreach ($recientes as $r):
                    $devuelto = $r['fecha_devolucion'] !== null; ?>
                    <li>
                        <span class="sello <?= $devuelto ? 'sello-devuelto' : 'sello-activo' ?>">
                            <?= $devuelto ? 'Devuelto' : 'Prestado' ?>
                        </span>
                        <span><strong><?= e($r['titulo']) ?></strong> — <?= e($r['nombre']) ?></span>
                        <time><?= e(fecha_corta($devuelto ? $r['fecha_devolucion'] : $r['fecha_prestamo'])) ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/pie.php'; ?>
