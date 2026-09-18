<?php
require_once 'config.php';
$page_title = 'Dashboard';
$active = 'inicio';

$stats = ['usuarios'=>0,'libros'=>0,'disponibles'=>0,'activos'=>0,'devueltos'=>0];
$recent = [];

if (!isset($db_error)) {
    $stats['usuarios'] = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $stats['libros'] = (int)$pdo->query("SELECT COUNT(*) FROM libros")->fetchColumn();
    $stats['disponibles'] = (int)$pdo->query("SELECT COALESCE(SUM(unidades),0) FROM libros")->fetchColumn();
    $stats['activos'] = (int)$pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado='Activo'")->fetchColumn();
    $stats['devueltos'] = (int)$pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado='Devuelto'")->fetchColumn();
    $recent = $pdo->query("
        SELECT p.id, u.nombre AS usuario, l.titulo, p.fecha_prestamo, p.estado
        FROM prestamos p
        INNER JOIN usuarios u ON u.id=p.usuario_id
        INNER JOIN libros l ON l.id=p.libro_id
        ORDER BY p.id DESC LIMIT 5
    ")->fetchAll();
}
include 'header.php';
?>
<section class="hero">
    <div>
        <span class="eyebrow">Panel de control</span>
        <h1>Tu biblioteca, <em>organizada.</em></h1>
        <p>Administra usuarios, libros y préstamos desde un solo lugar.</p>
        <div class="hero-actions">
            <a class="btn primary" href="prestamos.php?accion=nuevo">＋ Registrar préstamo</a>
            <a class="btn ghost" href="libros.php">Ver catálogo →</a>
        </div>
    </div>
    <div class="hero-art">📖</div>
</section>

<section class="stats-grid">
    <div class="stat-card"><span>👥</span><div><small>Usuarios</small><strong><?= $stats['usuarios'] ?></strong></div></div>
    <div class="stat-card"><span>📚</span><div><small>Libros registrados</small><strong><?= $stats['libros'] ?></strong></div></div>
    <div class="stat-card"><span>✓</span><div><small>Unidades disponibles</small><strong><?= $stats['disponibles'] ?></strong></div></div>
    <div class="stat-card"><span>↗</span><div><small>Préstamos activos</small><strong><?= $stats['activos'] ?></strong></div></div>
    <div class="stat-card"><span>↩</span><div><small>Devueltos</small><strong><?= $stats['devueltos'] ?></strong></div></div>
</section>

<section class="quick-grid">
    <a href="usuarios.php" class="quick-card"><span>👤</span><h3>Gestionar usuarios</h3><p>Registrar, consultar, editar y eliminar.</p></a>
    <a href="libros.php" class="quick-card"><span>📕</span><h3>Gestionar libros</h3><p>Administra el catálogo y sus unidades.</p></a>
    <a href="prestamos.php?accion=nuevo" class="quick-card"><span>🔖</span><h3>Nuevo préstamo</h3><p>Entrega un libro disponible a un usuario.</p></a>
    <a href="prestamos.php" class="quick-card"><span>📋</span><h3>Consultar préstamos</h3><p>Revisa activos y devoluciones.</p></a>
</section>

<section class="panel">
    <div class="panel-head"><div><span class="eyebrow">Actividad</span><h2>Préstamos recientes</h2></div><a href="prestamos.php">Ver todos →</a></div>
    <?php if (!$recent): ?>
        <div class="empty">Aún no hay préstamos registrados.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Usuario</th><th>Libro</th><th>Fecha</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['usuario']) ?></td>
                <td><?= htmlspecialchars($r['titulo']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($r['fecha_prestamo'])) ?></td>
                <td><span class="badge <?= $r['estado']==='Activo'?'active':'returned' ?>"><?= $r['estado'] ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
<?php include 'footer.php'; ?>