<?php
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Inicio';

$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalLibros = $pdo->query("SELECT COUNT(*) FROM libros")->fetchColumn();
$totalUnidades = $pdo->query("SELECT COALESCE(SUM(unidades),0) FROM libros")->fetchColumn();
$prestamosActivos = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'prestado'")->fetchColumn();

$ultimosLibros = $pdo->query("SELECT * FROM libros ORDER BY id DESC LIMIT 6")->fetchAll();
$ultimosPrestamos = $pdo->query("
    SELECT p.*, u.nombre AS usuario_nombre, l.titulo AS libro_titulo, l.portada_url
    FROM prestamos p
    JOIN usuarios u ON u.id = p.usuario_id
    JOIN libros l ON l.id = p.libro_id
    ORDER BY p.id DESC LIMIT 5
")->fetchAll();

$portadasDefault = [
    'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1476275466078-4007374efbbe?auto=format&fit=crop&w=400&q=80',
];

include __DIR__ . '/includes/header.php';
?>

<div class="hero-biblioteca">
    <div class="container">
        <h1><i class="fa-solid fa-book-open-reader"></i> Bienvenido a la Biblioteca Central</h1>
        <p>Gestiona usuarios, libros y préstamos en un solo lugar, con el aroma de una biblioteca clásica.</p>
        <div class="mt-4">
            <a href="libros.php?action=create" class="btn btn-dorado btn-lg me-2"><i class="fa-solid fa-plus"></i> Nuevo libro</a>
            <a href="prestamos.php?action=create" class="btn btn-marron btn-lg"><i class="fa-solid fa-hand-holding"></i> Registrar préstamo</a>
        </div>
    </div>
</div>

<div class="container">

    <div class="row g-4 mb-5">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <i class="fa-solid fa-users"></i>
                <h2><?= (int)$totalUsuarios ?></h2>
                <p>Usuarios registrados</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <i class="fa-solid fa-book"></i>
                <h2><?= (int)$totalLibros ?></h2>
                <p>Títulos distintos</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <i class="fa-solid fa-layer-group"></i>
                <h2><?= (int)$totalUnidades ?></h2>
                <p>Ejemplares totales</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <i class="fa-solid fa-right-left"></i>
                <h2><?= (int)$prestamosActivos ?></h2>
                <p>Préstamos activos</p>
            </div>
        </div>
    </div>

    <div class="panel-biblioteca">
        <h3 class="section-title"><i class="fa-solid fa-star"></i> Últimos libros agregados</h3>
        <?php if (empty($ultimosLibros)): ?>
            <p class="text-muted">Aún no hay libros registrados. <a href="libros.php?action=create">Agrega el primero</a>.</p>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($ultimosLibros as $i => $libro): ?>
                <?php $portada = $libro['portada_url'] ?: $portadasDefault[$i % count($portadasDefault)]; ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card-biblioteca h-100 text-center p-2">
                        <img src="<?= htmlspecialchars($portada) ?>" class="w-100" style="height:140px;object-fit:cover;border-radius:10px;" alt="<?= htmlspecialchars($libro['titulo']) ?>">
                        <div class="p-2">
                            <strong style="font-size:0.9rem;"><?= htmlspecialchars($libro['titulo']) ?></strong>
                            <div class="text-muted" style="font-size:0.8rem;"><?= htmlspecialchars($libro['autor']) ?></div>
                            <span class="badge <?= $libro['unidades_disponibles']>0 ? 'badge-disponible':'badge-agotado' ?> mt-1">
                                <?= $libro['unidades_disponibles'] ?> disp.
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="panel-biblioteca">
        <h3 class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Últimos préstamos</h3>
        <?php if (empty($ultimosPrestamos)): ?>
            <p class="text-muted">Aún no se han registrado préstamos.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-biblioteca align-middle">
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Usuario</th>
                        <th>Fecha préstamo</th>
                        <th>Devolución esperada</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimosPrestamos as $i => $p): ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($p['portada_url'] ?: $portadasDefault[$i % count($portadasDefault)]) ?>" class="badge-cover-mini me-2">
                            <?= htmlspecialchars($p['libro_titulo']) ?>
                        </td>
                        <td><?= htmlspecialchars($p['usuario_nombre']) ?></td>
                        <td><?= htmlspecialchars($p['fecha_prestamo']) ?></td>
                        <td><?= htmlspecialchars($p['fecha_devolucion_esperada']) ?></td>
                        <td>
                            <?php if ($p['estado'] === 'prestado'): ?>
                                <span class="badge badge-prestado">Prestado</span>
                            <?php else: ?>
                                <span class="badge badge-devuelto">Devuelto</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
