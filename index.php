<?php
require_once __DIR__ . '/db.php';

$usuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$libros = (int)$pdo->query("SELECT COUNT(*) FROM libros")->fetchColumn();
$prestamos = (int)$pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado='Prestado'")->fetchColumn();
$unidades = (int)$pdo->query("SELECT COALESCE(SUM(unidades),0) FROM libros")->fetchColumn();

$ultimos = $pdo->query("
    SELECT p.id, p.fecha_prestamo, p.estado, l.titulo, u.nombre
    FROM prestamos p
    INNER JOIN libros l ON l.id=p.libro_id
    INNER JOIN usuarios u ON u.id=p.usuario_id
    ORDER BY p.id DESC LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biblioteca Aurora</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="hero">
    <div class="overlay"></div>
    <nav class="navbar">
        <a class="brand" href="index.php"><span>📚</span> Biblioteca Aurora</a>
        <div class="navlinks">
            <a href="usuarios.php">👥 Usuarios</a>
            <a href="libros.php">📖 Libros</a>
            <a href="prestamos.php">🔖 Préstamos</a>
        </div>
    </nav>
    <div class="hero-content">
        <span class="eyebrow">TU RINCÓN DE CONOCIMIENTO</span>
        <h1>Una biblioteca para<br><strong>imaginar, aprender y descubrir.</strong></h1>
        <p>Gestiona usuarios, libros y préstamos desde un solo lugar.</p>
        <div class="hero-buttons">
            <a class="btn btn-primary" href="libros.php">Explorar libros →</a>
            <a class="btn btn-glass" href="prestamos.php">Registrar préstamo</a>
        </div>
    </div>
</header>

<main class="container">
    <section class="stats">
        <div class="stat-card"><span>👥</span><div><b><?= $usuarios ?></b><small>Usuarios</small></div></div>
        <div class="stat-card"><span>📚</span><div><b><?= $libros ?></b><small>Títulos</small></div></div>
        <div class="stat-card"><span>🔖</span><div><b><?= $prestamos ?></b><small>Préstamos activos</small></div></div>
        <div class="stat-card"><span>✨</span><div><b><?= $unidades ?></b><small>Unidades</small></div></div>
    </section>

    <section class="welcome-grid">
        <div>
            <span class="eyebrow dark">TODO EN ORDEN</span>
            <h2>Tu biblioteca,<br><em>más sencilla.</em></h2>
            <p class="lead">Registra lectores, organiza el catálogo y controla cada préstamo de manera rápida y visual.</p>
            <div class="quick-grid">
                <a class="feature-card" href="usuarios.php"><span>👤</span><b>Gestionar usuarios</b><small>Nombre, cédula y teléfono</small></a>
                <a class="feature-card" href="libros.php"><span>📕</span><b>Gestionar libros</b><small>Código, título, autor y unidades</small></a>
                <a class="feature-card" href="prestamos.php"><span>🔖</span><b>Gestionar préstamos</b><small>Asigna y registra devoluciones</small></a>
            </div>
        </div>
        <div class="quote-card">
            <div class="quote-icon">“</div>
            <p>Un libro es un sueño que tienes en tus manos.</p>
            <small>— Neil Gaiman</small>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><div><span class="eyebrow dark">MOVIMIENTO</span><h2>Últimos préstamos</h2></div><a class="text-link" href="prestamos.php">Ver todos →</a></div>
        <?php if (!$ultimos): ?>
            <div class="empty">Todavía no hay préstamos registrados.</div>
        <?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Libro</th><th>Usuario</th><th>Fecha</th><th>Estado</th></tr></thead><tbody>
        <?php foreach ($ultimos as $p): ?>
            <tr><td><?= htmlspecialchars($p['titulo']) ?></td><td><?= htmlspecialchars($p['nombre']) ?></td><td><?= htmlspecialchars($p['fecha_prestamo']) ?></td><td><span class="badge <?= $p['estado']==='Devuelto'?'ok':'warning' ?>"><?= htmlspecialchars($p['estado']) ?></span></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>
</main>
<footer>Biblioteca Aurora · Sistema de gestión PHP + MySQL</footer>
</body>
</html>
