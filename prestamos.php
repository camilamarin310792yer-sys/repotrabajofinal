<?php
require_once __DIR__ . '/config/db.php';
session_start();

$pageTitle = 'Préstamos';
$action = $_GET['action'] ?? 'list';
$errores = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===== Registrar nuevo préstamo =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $libro_id = (int)($_POST['libro_id'] ?? 0);
    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? date('Y-m-d');
    $fecha_devolucion = $_POST['fecha_devolucion_esperada'] ?? '';

    if (!$libro_id) $errores[] = 'Selecciona un libro.';
    if (!$usuario_id) $errores[] = 'Selecciona un usuario.';
    if (!$fecha_devolucion) $errores[] = 'Indica la fecha de devolución esperada.';

    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT unidades_disponibles FROM libros WHERE id=?");
        $stmt->execute([$libro_id]);
        $libro = $stmt->fetch();

        if (!$libro || $libro['unidades_disponibles'] < 1) {
            $errores[] = 'No hay ejemplares disponibles de este libro.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO prestamos (libro_id, usuario_id, fecha_prestamo, fecha_devolucion_esperada, estado) VALUES (?, ?, ?, ?, 'prestado')");
                $stmt->execute([$libro_id, $usuario_id, $fecha_prestamo, $fecha_devolucion]);

                $stmt = $pdo->prepare("UPDATE libros SET unidades_disponibles = unidades_disponibles - 1 WHERE id = ?");
                $stmt->execute([$libro_id]);

                $pdo->commit();
                $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Préstamo registrado correctamente.'];
                header('Location: prestamos.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errores[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// ===== Marcar como devuelto =====
if ($action === 'devolver' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM prestamos WHERE id=? AND estado='prestado'");
        $stmt->execute([$id]);
        $prestamo = $stmt->fetch();

        if ($prestamo) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE prestamos SET estado='devuelto', fecha_devolucion_real=? WHERE id=?");
            $stmt->execute([date('Y-m-d'), $id]);

            $stmt = $pdo->prepare("UPDATE libros SET unidades_disponibles = unidades_disponibles + 1 WHERE id=?");
            $stmt->execute([$prestamo['libro_id']]);
            $pdo->commit();

            $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Devolución registrada. ¡Gracias!'];
        } else {
            $_SESSION['flash'] = ['tipo' => 'danger', 'msg' => 'Préstamo no encontrado o ya devuelto.'];
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['tipo' => 'danger', 'msg' => 'Error: ' . $e->getMessage()];
    }
    header('Location: prestamos.php');
    exit;
}

// ===== Eliminar registro de préstamo =====
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM prestamos WHERE id=?");
        $stmt->execute([$id]);
        $prestamo = $stmt->fetch();

        $pdo->beginTransaction();
        if ($prestamo && $prestamo['estado'] === 'prestado') {
            $stmt = $pdo->prepare("UPDATE libros SET unidades_disponibles = unidades_disponibles + 1 WHERE id=?");
            $stmt->execute([$prestamo['libro_id']]);
        }
        $stmt = $pdo->prepare("DELETE FROM prestamos WHERE id=?");
        $stmt->execute([$id]);
        $pdo->commit();

        $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Registro de préstamo eliminado.'];
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['tipo' => 'danger', 'msg' => 'Error: ' . $e->getMessage()];
    }
    header('Location: prestamos.php');
    exit;
}

// ===== Datos para formulario =====
$libros = $pdo->query("SELECT * FROM libros WHERE unidades_disponibles > 0 ORDER BY titulo ASC")->fetchAll();
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC")->fetchAll();

// ===== Listado con filtro por estado =====
$filtroEstado = $_GET['estado'] ?? 'todos';
$sql = "
    SELECT p.*, u.nombre AS usuario_nombre, u.cedula, l.titulo AS libro_titulo, l.autor, l.codigo, l.portada_url
    FROM prestamos p
    JOIN usuarios u ON u.id = p.usuario_id
    JOIN libros l ON l.id = p.libro_id
";
if ($filtroEstado === 'prestado' || $filtroEstado === 'devuelto') {
    $sql .= " WHERE p.estado = :estado ";
}
$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
if ($filtroEstado === 'prestado' || $filtroEstado === 'devuelto') {
    $stmt->bindValue(':estado', $filtroEstado);
}
$stmt->execute();
$prestamos = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="hero-biblioteca py-5">
    <div class="container">
        <h1><i class="fa-solid fa-right-left"></i> Préstamos</h1>
        <p>Registra préstamos y devoluciones de ejemplares.</p>
    </div>
</div>

<div class="container">

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] ?> panel-biblioteca py-3"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if ($action === 'create'): ?>
    <div class="panel-biblioteca">
        <h3 class="section-title"><i class="fa-solid fa-hand-holding"></i> Nuevo préstamo</h3>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if (empty($libros)): ?>
            <p class="text-muted">No hay libros con unidades disponibles en este momento.</p>
        <?php elseif (empty($usuarios)): ?>
            <p class="text-muted">Primero debes <a href="usuarios.php?action=create">registrar un usuario</a>.</p>
        <?php else: ?>
        <form method="POST" action="prestamos.php?action=create" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Libro</label>
                <select name="libro_id" class="form-select" required>
                    <option value="">-- Selecciona un libro --</option>
                    <?php foreach ($libros as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= (($_POST['libro_id'] ?? '') == $l['id']) ? 'selected':'' ?>>
                            <?= htmlspecialchars($l['titulo']) ?> — <?= htmlspecialchars($l['autor']) ?> (<?= $l['unidades_disponibles'] ?> disp.)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Usuario</label>
                <select name="usuario_id" class="form-select" required>
                    <option value="">-- Selecciona un usuario --</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (($_POST['usuario_id'] ?? '') == $u['id']) ? 'selected':'' ?>>
                            <?= htmlspecialchars($u['nombre']) ?> — CC <?= htmlspecialchars($u['cedula']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha de préstamo</label>
                <input type="date" name="fecha_prestamo" class="form-control" value="<?= $_POST['fecha_prestamo'] ?? date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha de devolución esperada</label>
                <input type="date" name="fecha_devolucion_esperada" class="form-control" value="<?= $_POST['fecha_devolucion_esperada'] ?? date('Y-m-d', strtotime('+8 days')) ?>" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-dorado"><i class="fa-solid fa-floppy-disk"></i> Registrar préstamo</button>
                <a href="prestamos.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="panel-biblioteca">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0" style="border:none;"><i class="fa-solid fa-list"></i> Historial de préstamos</h3>
            <div class="d-flex gap-2 align-items-center">
                <div class="btn-group">
                    <a href="prestamos.php?estado=todos" class="btn btn-sm <?= $filtroEstado=='todos'?'btn-marron':'btn-outline-secondary' ?>">Todos</a>
                    <a href="prestamos.php?estado=prestado" class="btn btn-sm <?= $filtroEstado=='prestado'?'btn-marron':'btn-outline-secondary' ?>">Prestados</a>
                    <a href="prestamos.php?estado=devuelto" class="btn btn-sm <?= $filtroEstado=='devuelto'?'btn-marron':'btn-outline-secondary' ?>">Devueltos</a>
                </div>
                <a href="prestamos.php?action=create" class="btn btn-dorado text-nowrap"><i class="fa-solid fa-plus"></i> Nuevo</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-biblioteca align-middle">
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Usuario</th>
                        <th>Préstamo</th>
                        <th>Devolución esperada</th>
                        <th>Devolución real</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prestamos)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay préstamos registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($prestamos as $p): ?>
                    <?php
                        $vencido = $p['estado'] === 'prestado' && strtotime($p['fecha_devolucion_esperada']) < strtotime(date('Y-m-d'));
                    ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($p['portada_url']) ?>" class="badge-cover-mini me-2">
                            <?= htmlspecialchars($p['libro_titulo']) ?>
                            <div class="text-muted" style="font-size:0.8rem;"><?= htmlspecialchars($p['codigo']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($p['usuario_nombre']) ?><div class="text-muted" style="font-size:0.8rem;">CC <?= htmlspecialchars($p['cedula']) ?></div></td>
                        <td><?= htmlspecialchars($p['fecha_prestamo']) ?></td>
                        <td><?= htmlspecialchars($p['fecha_devolucion_esperada']) ?></td>
                        <td><?= $p['fecha_devolucion_real'] ? htmlspecialchars($p['fecha_devolucion_real']) : '—' ?></td>
                        <td>
                            <?php if ($p['estado'] === 'prestado'): ?>
                                <span class="badge badge-prestado">Prestado</span>
                                <?php if ($vencido): ?><span class="badge bg-danger">Vencido</span><?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-devuelto">Devuelto</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($p['estado'] === 'prestado'): ?>
                                <a href="prestamos.php?action=devolver&id=<?= $p['id'] ?>" class="btn btn-sm btn-dorado"
                                   onclick="return confirm('¿Registrar la devolución de este libro?');">
                                   <i class="fa-solid fa-check"></i> Devolver
                                </a>
                            <?php endif; ?>
                            <a href="prestamos.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar este registro de préstamo?');">
                               <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
