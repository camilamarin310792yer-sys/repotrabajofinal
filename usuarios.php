<?php
require_once __DIR__ . '/config/db.php';
session_start();

$pageTitle = 'Usuarios';
$action = $_GET['action'] ?? 'list';
$errores = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===== Guardar (crear o actualizar) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','edit'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $id = $_POST['id'] ?? null;

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if ($cedula === '') $errores[] = 'La cédula es obligatoria.';
    if ($telefono === '') $errores[] = 'El teléfono es obligatorio.';

    if (empty($errores)) {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, cedula, telefono) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $cedula, $telefono]);
                $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Usuario creado correctamente.'];
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, cedula=?, telefono=? WHERE id=?");
                $stmt->execute([$nombre, $cedula, $telefono, $id]);
                $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Usuario actualizado correctamente.'];
            }
            header('Location: usuarios.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errores[] = 'Ya existe un usuario con esa cédula.';
            } else {
                $errores[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// ===== Eliminar =====
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=?");
        $stmt->execute([$_GET['id']]);
        $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Usuario eliminado.'];
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['tipo' => 'danger', 'msg' => 'No se pudo eliminar: tiene préstamos asociados.'];
    }
    header('Location: usuarios.php');
    exit;
}

// ===== Obtener usuario para editar =====
$usuarioEdit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $usuarioEdit = $stmt->fetch();
}

// ===== Búsqueda / listado =====
$buscar = trim($_GET['q'] ?? '');
if ($buscar !== '') {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre LIKE ? OR cedula LIKE ? ORDER BY id DESC");
    $like = "%$buscar%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
}
$usuarios = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="hero-biblioteca py-5">
    <div class="container">
        <h1><i class="fa-solid fa-users"></i> Usuarios</h1>
        <p>Administra a los lectores registrados en la biblioteca.</p>
    </div>
</div>

<div class="container">

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] ?> panel-biblioteca py-3"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (in_array($action, ['create','edit'])): ?>
    <div class="panel-biblioteca">
        <h3 class="section-title">
            <i class="fa-solid <?= $action==='create' ? 'fa-user-plus':'fa-user-pen' ?>"></i>
            <?= $action === 'create' ? 'Nuevo usuario' : 'Editar usuario' ?>
        </h3>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="usuarios.php?action=<?= $action ?>" class="row g-3">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($usuarioEdit['id']) ?>">
            <?php endif; ?>
            <div class="col-md-4">
                <label class="form-label">Nombre completo</label>
                <input type="text" name="nombre" class="form-control" required
                       value="<?= htmlspecialchars($usuarioEdit['nombre'] ?? $_POST['nombre'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cédula</label>
                <input type="text" name="cedula" class="form-control" required
                       value="<?= htmlspecialchars($usuarioEdit['cedula'] ?? $_POST['cedula'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" class="form-control" required
                       value="<?= htmlspecialchars($usuarioEdit['telefono'] ?? $_POST['telefono'] ?? '') ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-dorado"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                <a href="usuarios.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="panel-biblioteca">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0" style="border:none;"><i class="fa-solid fa-list"></i> Listado de usuarios</h3>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <input type="text" name="q" class="form-control me-2" placeholder="Buscar por nombre o cédula" value="<?= htmlspecialchars($buscar) ?>">
                    <button class="btn btn-marron"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <a href="usuarios.php?action=create" class="btn btn-dorado text-nowrap"><i class="fa-solid fa-plus"></i> Nuevo</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-biblioteca align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>#<?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td><?= htmlspecialchars($u['cedula']) ?></td>
                        <td><?= htmlspecialchars($u['telefono']) ?></td>
                        <td class="text-end">
                            <a href="usuarios.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
                            <a href="usuarios.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($u['nombre'])) ?>?');">
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
