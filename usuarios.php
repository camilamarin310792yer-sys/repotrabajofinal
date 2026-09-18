<?php
require_once __DIR__ . '/db.php';
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear') {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, cedula, telefono) VALUES (?, ?, ?)");
            $stmt->execute([trim($_POST['nombre']), trim($_POST['cedula']), trim($_POST['telefono'])]);
            $mensaje = 'Usuario registrado correctamente.';
        } elseif ($accion === 'editar') {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, cedula=?, telefono=? WHERE id=?");
            $stmt->execute([trim($_POST['nombre']), trim($_POST['cedula']), trim($_POST['telefono']), (int)$_POST['id']]);
            $mensaje = 'Usuario actualizado correctamente.';
        } elseif ($accion === 'eliminar') {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=?");
            $stmt->execute([(int)$_POST['id']]);
            $mensaje = 'Usuario eliminado correctamente.';
        }
    } catch (PDOException $e) {
        $error = 'No se pudo completar la operación. Verifica que la cédula sea única y que el usuario no tenga préstamos asociados.';
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id=?");
    $stmt->execute([(int)$_GET['editar']]);
    $editar = $stmt->fetch();
}
$lista = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Usuarios · Biblioteca Aurora</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<nav class="topbar"><a class="brand darkbrand" href="index.php">📚 Biblioteca Aurora</a><div class="navlinks"><a class="active" href="usuarios.php">👥 Usuarios</a><a href="libros.php">📖 Libros</a><a href="prestamos.php">🔖 Préstamos</a></div></nav>
<main class="container page">
<div class="page-title"><div><span class="eyebrow dark">LECTORES</span><h1>Gestión de usuarios</h1><p>Registra y administra las personas que utilizan la biblioteca.</p></div><span class="big-icon">👥</span></div>
<?php if ($mensaje): ?><div class="alert success">✓ <?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert danger">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<section class="form-panel">
<h2><?= $editar ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
<form method="post" class="form-grid">
<input type="hidden" name="accion" value="<?= $editar?'editar':'crear' ?>">
<?php if ($editar): ?><input type="hidden" name="id" value="<?= (int)$editar['id'] ?>"><?php endif; ?>
<label>Nombre completo<input required name="nombre" value="<?= htmlspecialchars($editar['nombre'] ?? '') ?>" placeholder="Ej. María López"></label>
<label>Cédula<input required name="cedula" value="<?= htmlspecialchars($editar['cedula'] ?? '') ?>" placeholder="Ej. 1020304050"></label>
<label>Teléfono<input required name="telefono" value="<?= htmlspecialchars($editar['telefono'] ?? '') ?>" placeholder="Ej. 3001234567"></label>
<div class="form-actions"><button class="btn btn-primary" type="submit"><?= $editar?'Guardar cambios':'Registrar usuario' ?></button><?php if ($editar): ?><a class="btn btn-light" href="usuarios.php">Cancelar</a><?php endif; ?></div>
</form>
</section>
<section class="panel"><div class="panel-head"><h2>Usuarios registrados</h2><span class="counter"><?= count($lista) ?> registros</span></div>
<div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($lista as $u): ?><tr><td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td><td><?= htmlspecialchars($u['cedula']) ?></td><td><?= htmlspecialchars($u['telefono']) ?></td><td class="actions"><a class="btn-small edit" href="?editar=<?= $u['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar este usuario?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn-small delete">Eliminar</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section>
</main><footer>Biblioteca Aurora · Usuarios</footer></body></html>
