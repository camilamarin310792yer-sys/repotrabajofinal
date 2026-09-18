<?php
/* ============================================================
   LECTORES — Gestión de usuarios (crear, editar, eliminar, buscar)
   ============================================================ */
require_once __DIR__ . '/includes/funciones.php';
$pdo = db();

/* ---------- 1. Procesar formularios (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    try {
        if ($accion === 'guardar') {
            $nombre   = trim(preg_replace('/\s+/', ' ', $_POST['nombre'] ?? ''));
            $cedula   = preg_replace('/\D/', '', $_POST['cedula'] ?? '');          // solo dígitos
            $telefono = trim(preg_replace('/[^\d+\-\s]/', '', $_POST['telefono'] ?? ''));

            // Validaciones
            $errores = [];
            if ($nombre === '' || mb_strlen($nombre) > 120) $errores[] = 'El nombre es obligatorio (máx. 120 caracteres).';
            if (strlen($cedula) < 5 || strlen($cedula) > 15) $errores[] = 'La cédula debe tener entre 5 y 15 dígitos.';
            if (strlen(preg_replace('/\D/', '', $telefono)) < 7 || strlen($telefono) > 20) $errores[] = 'El teléfono debe tener al menos 7 dígitos.';

            if ($errores) {
                flash('error', implode(' ', $errores));
                $_SESSION['form_usuario'] = $_POST;
                redirigir('usuarios.php' . ($id ? "?editar=$id" : ''));
            }

            if ($id) {
                $st = $pdo->prepare('UPDATE usuarios SET nombre = ?, cedula = ?, telefono = ? WHERE id = ?');
                $st->execute([$nombre, $cedula, $telefono, $id]);
                flash('exito', "Lector «{$nombre}» actualizado.");
            } else {
                $st = $pdo->prepare('INSERT INTO usuarios (nombre, cedula, telefono, creado_en) VALUES (?, ?, ?, ?)');
                $st->execute([$nombre, $cedula, $telefono, ahora()]);
                flash('exito', "Lector «{$nombre}» inscrito.");
            }
        }

        if ($accion === 'eliminar' && $id) {
            // No se elimina un lector que tenga libros en su poder
            $st = $pdo->prepare('SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND fecha_devolucion IS NULL');
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                flash('error', 'No se puede eliminar: el lector tiene libros sin devolver.');
            } else {
                $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
                flash('exito', 'Lector eliminado junto con su historial de préstamos.');
            }
        }
    } catch (PDOException $e) {
        flash('error', mensaje_error_bd($e, 'Ya existe un lector con esa cédula.'));
        $_SESSION['form_usuario'] = $_POST;
        redirigir('usuarios.php' . ($id ? "?editar=$id" : ''));
    }

    redirigir('usuarios.php');
}

/* ---------- 2. Datos para mostrar (GET) ---------- */
$edicion = null;
if (isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $edicion = $st->fetch() ?: null;
}

$form = $_SESSION['form_usuario'] ?? $edicion ?? ['nombre' => '', 'cedula' => '', 'telefono' => ''];
unset($_SESSION['form_usuario']);

// Búsqueda por nombre, cédula o teléfono
$q = trim($_GET['q'] ?? '');
$sql = '
    SELECT u.*,
           (SELECT COUNT(*) FROM prestamos p WHERE p.usuario_id = u.id AND p.fecha_devolucion IS NULL) AS activos,
           (SELECT COUNT(*) FROM prestamos p WHERE p.usuario_id = u.id AND p.fecha_devolucion IS NULL AND p.fecha_limite < ?) AS vencidos
    FROM usuarios u';
$parametros = [hoy()];
if ($q !== '') {
    $sql .= ' WHERE u.nombre LIKE ? OR u.cedula LIKE ? OR u.telefono LIKE ?';
    $like = '%' . $q . '%';
    array_push($parametros, $like, $like, $like);
}
$sql .= ' ORDER BY u.nombre ASC';
$st = $pdo->prepare($sql);
$st->execute($parametros);
$usuarios = $st->fetchAll();

$titulo = 'Lectores';
$pagina = 'usuarios.php';
require __DIR__ . '/includes/cabecera.php';
?>

<div class="rejilla-formulario">
    <section class="pergamino tablilla">
        <h2 class="titulo-seccion"><?= $edicion ? 'Editar lector' : 'Nuevo lector' ?></h2>
        <form method="post" class="formulario" autocomplete="off">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?= (int)($edicion['id'] ?? 0) ?>">

            <label>Nombre completo
                <input type="text" name="nombre" maxlength="120" required value="<?= e($form['nombre']) ?>" placeholder="Ej: Marco Tulio Cicerón">
            </label>
            <label>Cédula
                <input type="text" name="cedula" inputmode="numeric" pattern="[0-9.\s]{5,20}" maxlength="20" required value="<?= e($form['cedula']) ?>" placeholder="Solo números">
            </label>
            <label>Teléfono
                <input type="tel" name="telefono" maxlength="20" required value="<?= e($form['telefono']) ?>" placeholder="Ej: 300 123 4567">
            </label>

            <div class="botonera">
                <button type="submit" class="btn btn-terracota"><?= $edicion ? 'Guardar cambios' : 'Inscribir lector' ?></button>
                <?php if ($edicion): ?>
                    <a href="usuarios.php" class="btn btn-marmol">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="pergamino">
        <div class="cabecera-lista">
            <h2 class="titulo-seccion">Registro de lectores <small>(<?= count($usuarios) ?>)</small></h2>
            <form method="get" class="buscador" role="search">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar nombre, cédula o teléfono" aria-label="Buscar lectores">
                <button type="submit" class="btn btn-oro btn-chico">Buscar</button>
                <?php if ($q !== ''): ?><a href="usuarios.php" class="btn btn-marmol btn-chico">Limpiar</a><?php endif; ?>
            </form>
        </div>

        <?php if (!$usuarios): ?>
            <p class="vacio"><?= $q !== '' ? 'Ningún lector coincide con la búsqueda.' : 'Aún no hay lectores inscritos.' ?></p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th class="num">Préstamos</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr<?= $edicion && $edicion['id'] == $u['id'] ? ' class="fila-activa"' : '' ?>>
                            <td><strong><?= e($u['nombre']) ?></strong></td>
                            <td><span class="codigo"><?= e(number_format((float)$u['cedula'], 0, ',', '.')) ?></span></td>
                            <td><?= e($u['telefono']) ?></td>
                            <td class="num">
                                <?php if ((int)$u['vencidos'] > 0): ?>
                                    <span class="sello sello-vencido" title="Con préstamos vencidos"><?= (int)$u['activos'] ?></span>
                                <?php elseif ((int)$u['activos'] > 0): ?>
                                    <span class="sello sello-activo"><?= (int)$u['activos'] ?></span>
                                <?php else: ?>
                                    <span class="sub-inline">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="acciones">
                                <a class="btn btn-oro btn-chico" href="usuarios.php?editar=<?= (int)$u['id'] ?>">Editar</a>
                                <a class="btn btn-marmol btn-chico" href="prestamos.php?filtro=todos&usuario=<?= (int)$u['id'] ?>">Historial</a>
                                <form method="post" data-confirmar="¿Eliminar a <?= e($u['nombre']) ?> y su historial?">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="btn btn-negro btn-chico">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/pie.php'; ?>
