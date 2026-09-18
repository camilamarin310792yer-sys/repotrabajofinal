<?php
/* ============================================================
   COMMODATA — Préstamos: prestar, devolver, consultar historial
   Regla clave: disponibles = unidades del libro - préstamos activos
   ============================================================ */
require_once __DIR__ . '/includes/funciones.php';
$pdo = db();

/* ---------- 1. Procesar formularios (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    $volver = 'prestamos.php?filtro=' . urlencode($_POST['filtro'] ?? 'activos');
    if (!empty($_POST['usuario'])) {
        $volver .= '&usuario=' . (int)$_POST['usuario'];   // conserva el filtro por lector
    }

    try {
        /* --- Registrar un préstamo --- */
        if ($accion === 'prestar') {
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $libroId   = (int)($_POST['libro_id'] ?? 0);
            $limite    = $_POST['fecha_limite'] ?? '';

            $fechaValida = DateTime::createFromFormat('Y-m-d', $limite);
            if (!$usuarioId || !$libroId) {
                flash('error', 'Selecciona un lector y un libro.');
                redirigir($volver);
            }
            if (!$fechaValida || $fechaValida->format('Y-m-d') !== $limite || $limite < hoy()) {
                flash('error', 'La fecha límite debe ser hoy o una fecha futura.');
                redirigir($volver);
            }

            // Transacción: bloquea la fila del libro para que dos préstamos
            // simultáneos no se lleven el último ejemplar al mismo tiempo.
            $pdo->beginTransaction();

            $st = $pdo->prepare('SELECT titulo, unidades FROM libros WHERE id = ? FOR UPDATE');
            $st->execute([$libroId]);
            $libro = $st->fetch();

            $st = $pdo->prepare('SELECT nombre FROM usuarios WHERE id = ?');
            $st->execute([$usuarioId]);
            $usuario = $st->fetch();

            if (!$libro || !$usuario) {
                $pdo->rollBack();
                flash('error', 'El lector o el libro ya no existen.');
                redirigir($volver);
            }

            $st = $pdo->prepare('SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND fecha_devolucion IS NULL');
            $st->execute([$libroId]);
            $disponibles = (int)$libro['unidades'] - (int)$st->fetchColumn();

            if ($disponibles < 1) {
                $pdo->rollBack();
                flash('error', "No quedan ejemplares disponibles de «{$libro['titulo']}».");
                redirigir($volver);
            }

            // Un lector no puede tener dos ejemplares del mismo libro a la vez
            $st = $pdo->prepare('SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND usuario_id = ? AND fecha_devolucion IS NULL');
            $st->execute([$libroId, $usuarioId]);
            if ((int)$st->fetchColumn() > 0) {
                $pdo->rollBack();
                flash('error', "{$usuario['nombre']} ya tiene un ejemplar de «{$libro['titulo']}».");
                redirigir($volver);
            }

            $st = $pdo->prepare('INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo, fecha_limite) VALUES (?, ?, ?, ?)');
            $st->execute([$usuarioId, $libroId, ahora(), $limite]);
            $pdo->commit();

            flash('exito', "«{$libro['titulo']}» prestado a {$usuario['nombre']} hasta el " . fecha_corta($limite) . '.');
        }

        /* --- Registrar devolución --- */
        if ($accion === 'devolver') {
            $st = $pdo->prepare('UPDATE prestamos SET fecha_devolucion = ? WHERE id = ? AND fecha_devolucion IS NULL');
            $st->execute([ahora(), (int)($_POST['id'] ?? 0)]);
            flash($st->rowCount() ? 'exito' : 'error',
                  $st->rowCount() ? 'Devolución registrada. El ejemplar vuelve al estante.' : 'Ese préstamo ya estaba devuelto.');
        }

        /* --- Borrar un registro del historial (solo devueltos) --- */
        if ($accion === 'eliminar') {
            $st = $pdo->prepare('DELETE FROM prestamos WHERE id = ? AND fecha_devolucion IS NOT NULL');
            $st->execute([(int)($_POST['id'] ?? 0)]);
            flash($st->rowCount() ? 'exito' : 'error',
                  $st->rowCount() ? 'Registro eliminado del historial.' : 'Solo se pueden eliminar préstamos ya devueltos.');
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', mensaje_error_bd($e, 'Registro duplicado.'));
    }

    redirigir($volver);
}

/* ---------- 2. Datos para mostrar (GET) ---------- */

// Lectores y libros para los selectores del formulario
$usuarios = $pdo->query('SELECT id, nombre, cedula FROM usuarios ORDER BY nombre')->fetchAll();
$libros = $pdo->query('
    SELECT l.id, l.codigo, l.titulo, l.autor,
           l.unidades - (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.fecha_devolucion IS NULL) AS disponibles
    FROM libros l
    ORDER BY l.titulo
')->fetchAll();

// Filtros del listado
$filtros = ['activos' => 'Activos', 'vencidos' => 'Vencidos', 'devueltos' => 'Devueltos', 'todos' => 'Todos'];
$filtro = $_GET['filtro'] ?? 'activos';
if (!isset($filtros[$filtro])) {
    $filtro = 'activos';
}
$usuarioFiltro = (int)($_GET['usuario'] ?? 0);

$condiciones = [];
$parametros  = [];
if ($filtro === 'activos') {
    $condiciones[] = 'p.fecha_devolucion IS NULL';
} elseif ($filtro === 'vencidos') {
    $condiciones[] = 'p.fecha_devolucion IS NULL AND p.fecha_limite < ?';
    $parametros[]  = hoy();
} elseif ($filtro === 'devueltos') {
    $condiciones[] = 'p.fecha_devolucion IS NOT NULL';
}
if ($usuarioFiltro) {
    $condiciones[] = 'p.usuario_id = ?';
    $parametros[]  = $usuarioFiltro;
}

$sql = '
    SELECT p.*, u.nombre, u.cedula, l.codigo, l.titulo
    FROM prestamos p
    JOIN usuarios u ON u.id = p.usuario_id
    JOIN libros   l ON l.id = p.libro_id';
if ($condiciones) {
    $sql .= ' WHERE ' . implode(' AND ', $condiciones);
}
$sql .= ' ORDER BY (p.fecha_devolucion IS NULL) DESC, p.fecha_limite ASC, p.id DESC LIMIT 500';
$st = $pdo->prepare($sql);
$st->execute($parametros);
$prestamos = $st->fetchAll();

// Nombre del lector filtrado (para el subtítulo)
$nombreFiltro = '';
foreach ($usuarios as $u) {
    if ((int)$u['id'] === $usuarioFiltro) {
        $nombreFiltro = $u['nombre'];
    }
}

$limitePorDefecto = date('Y-m-d', strtotime('+' . DIAS_PRESTAMO . ' days'));

$titulo = 'Commodata';
$pagina = 'prestamos.php';
require __DIR__ . '/includes/cabecera.php';
?>

<div class="rejilla-formulario">
    <section class="pergamino tablilla">
        <h2 class="titulo-seccion">Nuevo préstamo</h2>

        <?php if (!$usuarios || !$libros): ?>
            <p class="vacio">
                Para prestar necesitas al menos
                <?= !$usuarios ? '<a href="usuarios.php">un lector</a>' : '' ?>
                <?= !$usuarios && !$libros ? ' y ' : '' ?>
                <?= !$libros ? '<a href="libros.php">un libro</a>' : '' ?>.
            </p>
        <?php else: ?>
            <form method="post" class="formulario">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="prestar">
                <input type="hidden" name="filtro" value="activos">

                <label>Lector
                    <select name="usuario_id" required>
                        <option value="">— Selecciona —</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= $usuarioFiltro === (int)$u['id'] ? 'selected' : '' ?>>
                                <?= e($u['nombre']) ?> · CC <?= e($u['cedula']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Libro
                    <select name="libro_id" required>
                        <option value="">— Selecciona —</option>
                        <?php foreach ($libros as $l): $d = (int)$l['disponibles']; ?>
                            <option value="<?= (int)$l['id'] ?>" <?= $d < 1 ? 'disabled' : '' ?>>
                                <?= e($l['titulo']) ?> — <?= e($l['autor']) ?> (<?= $d > 0 ? "$d disp." : 'agotado' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Fecha límite de devolución
                    <input type="date" name="fecha_limite" required min="<?= e(hoy()) ?>" value="<?= e($limitePorDefecto) ?>">
                </label>

                <div class="botonera">
                    <button type="submit" class="btn btn-terracota">Prestar libro</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="pergamino">
        <div class="cabecera-lista">
            <h2 class="titulo-seccion">
                Registro de préstamos
                <?php if ($nombreFiltro): ?><small>· <?= e($nombreFiltro) ?></small><?php endif; ?>
            </h2>
            <nav class="pestanas" aria-label="Filtrar préstamos">
                <?php foreach ($filtros as $clave => $nombre): ?>
                    <a href="prestamos.php?filtro=<?= $clave ?><?= $usuarioFiltro ? '&usuario=' . $usuarioFiltro : '' ?>"
                       class="btn btn-chico <?= $filtro === $clave ? 'btn-terracota' : 'btn-marmol' ?>"><?= $nombre ?></a>
                <?php endforeach; ?>
                <?php if ($usuarioFiltro): ?>
                    <a href="prestamos.php?filtro=<?= $filtro ?>" class="btn btn-chico btn-negro" title="Quitar filtro de lector">✕ Lector</a>
                <?php endif; ?>
            </nav>
        </div>

        <?php if (!$prestamos): ?>
            <p class="vacio">No hay préstamos en esta vista.</p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr><th>Libro</th><th>Lector</th><th>Prestado</th><th>Límite</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($prestamos as $p):
                        $devuelto = $p['fecha_devolucion'] !== null;
                        $vencido  = !$devuelto && $p['fecha_limite'] < hoy(); ?>
                        <tr<?= $vencido ? ' class="fila-vencida"' : '' ?>>
                            <td><strong><?= e($p['titulo']) ?></strong><small class="sub"><?= e($p['codigo']) ?></small></td>
                            <td><?= e($p['nombre']) ?><small class="sub">CC <?= e($p['cedula']) ?></small></td>
                            <td><?= e(fecha_corta($p['fecha_prestamo'])) ?></td>
                            <td><?= e(fecha_corta($p['fecha_limite'])) ?></td>
                            <td>
                                <?php if ($devuelto): ?>
                                    <span class="sello sello-devuelto">Devuelto</span><small class="sub"><?= e(fecha_corta($p['fecha_devolucion'])) ?></small>
                                <?php elseif ($vencido): ?>
                                    <span class="sello sello-vencido">Vencido</span>
                                <?php else: ?>
                                    <span class="sello sello-activo">Activo</span>
                                <?php endif; ?>
                            </td>
                            <td class="acciones">
                                <?php if (!$devuelto): ?>
                                    <form method="post" data-confirmar="¿Registrar la devolución de «<?= e($p['titulo']) ?>»?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="accion" value="devolver">
                                        <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
                                        <input type="hidden" name="usuario" value="<?= $usuarioFiltro ?: '' ?>">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" class="btn btn-oro btn-chico">Devolver</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" data-confirmar="¿Eliminar este registro del historial?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
                                        <input type="hidden" name="usuario" value="<?= $usuarioFiltro ?: '' ?>">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" class="btn btn-negro btn-chico">Borrar</button>
                                    </form>
                                <?php endif; ?>
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
