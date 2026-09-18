<?php
/* ============================================================
   VOLUMINA — Gestión de libros (crear, editar, eliminar, buscar)
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
            $codigo   = strtoupper(trim($_POST['codigo'] ?? ''));
            $titulo   = trim($_POST['titulo'] ?? '');
            $autor    = trim($_POST['autor'] ?? '');
            $unidades = filter_var($_POST['unidades'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);

            // Validaciones
            $errores = [];
            if ($codigo === '' || mb_strlen($codigo) > 30) $errores[] = 'El código es obligatorio (máx. 30 caracteres).';
            if ($titulo === '' || mb_strlen($titulo) > 200) $errores[] = 'El título es obligatorio (máx. 200 caracteres).';
            if ($autor === ''  || mb_strlen($autor) > 150)  $errores[] = 'El autor es obligatorio (máx. 150 caracteres).';
            if ($unidades === false) $errores[] = 'Las unidades deben ser un número entero entre 0 y 9999.';

            // Al editar, no se puede dejar menos unidades que las que están prestadas
            if (!$errores && $id) {
                $st = $pdo->prepare('SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND fecha_devolucion IS NULL');
                $st->execute([$id]);
                $prestados = (int)$st->fetchColumn();
                if ($unidades < $prestados) {
                    $errores[] = "Hay $prestados ejemplar(es) prestado(s); las unidades no pueden ser menores.";
                }
            }

            if ($errores) {
                flash('error', implode(' ', $errores));
                $_SESSION['form_libro'] = $_POST;   // conserva lo escrito
                redirigir('libros.php' . ($id ? "?editar=$id" : ''));
            }

            if ($id) {
                $st = $pdo->prepare('UPDATE libros SET codigo = ?, titulo = ?, autor = ?, unidades = ? WHERE id = ?');
                $st->execute([$codigo, $titulo, $autor, $unidades, $id]);
                flash('exito', "Libro «{$titulo}» actualizado.");
            } else {
                $st = $pdo->prepare('INSERT INTO libros (codigo, titulo, autor, unidades, creado_en) VALUES (?, ?, ?, ?, ?)');
                $st->execute([$codigo, $titulo, $autor, $unidades, ahora()]);
                flash('exito', "Libro «{$titulo}» añadido al catálogo.");
            }
        }

        if ($accion === 'eliminar' && $id) {
            // No se elimina un libro que tenga ejemplares prestados
            $st = $pdo->prepare('SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND fecha_devolucion IS NULL');
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                flash('error', 'No se puede eliminar: el libro tiene préstamos activos.');
            } else {
                $pdo->prepare('DELETE FROM libros WHERE id = ?')->execute([$id]);
                flash('exito', 'Libro eliminado junto con su historial de préstamos.');
            }
        }
    } catch (PDOException $e) {
        flash('error', mensaje_error_bd($e, 'Ya existe un libro con ese código.'));
        $_SESSION['form_libro'] = $_POST;
        redirigir('libros.php' . ($id ? "?editar=$id" : ''));
    }

    redirigir('libros.php');
}

/* ---------- 2. Datos para mostrar (GET) ---------- */

// Libro en edición (si viene ?editar=ID)
$edicion = null;
if (isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM libros WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $edicion = $st->fetch() ?: null;
}

// Valores del formulario: lo que falló al guardar > lo que se edita > vacío
$form = $_SESSION['form_libro'] ?? $edicion ?? ['codigo' => '', 'titulo' => '', 'autor' => '', 'unidades' => 1];
unset($_SESSION['form_libro']);

// Búsqueda por código, título o autor
$q = trim($_GET['q'] ?? '');
$sql = '
    SELECT l.*,
           (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.fecha_devolucion IS NULL) AS prestados
    FROM libros l';
$parametros = [];
if ($q !== '') {
    $sql .= ' WHERE l.codigo LIKE ? OR l.titulo LIKE ? OR l.autor LIKE ?';
    $like = '%' . $q . '%';
    $parametros = [$like, $like, $like];
}
$sql .= ' ORDER BY l.titulo ASC';
$st = $pdo->prepare($sql);
$st->execute($parametros);
$libros = $st->fetchAll();

$titulo = 'Volumina';
$pagina = 'libros.php';
require __DIR__ . '/includes/cabecera.php';
?>

<div class="rejilla-formulario">
    <!-- Formulario de alta / edición -->
    <section class="pergamino tablilla">
        <h2 class="titulo-seccion"><?= $edicion ? 'Editar libro' : 'Nuevo libro' ?></h2>
        <form method="post" class="formulario" autocomplete="off">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?= (int)($edicion['id'] ?? 0) ?>">

            <label>Código
                <input type="text" name="codigo" maxlength="30" required value="<?= e($form['codigo']) ?>" placeholder="Ej: LIT-001">
            </label>
            <label>Título
                <input type="text" name="titulo" maxlength="200" required value="<?= e($form['titulo']) ?>" placeholder="Ej: La Eneida">
            </label>
            <label>Autor
                <input type="text" name="autor" maxlength="150" required value="<?= e($form['autor']) ?>" placeholder="Ej: Virgilio">
            </label>
            <label>Unidades (ejemplares totales)
                <input type="number" name="unidades" min="0" max="9999" required value="<?= e($form['unidades']) ?>">
            </label>

            <div class="botonera">
                <button type="submit" class="btn btn-terracota"><?= $edicion ? 'Guardar cambios' : 'Añadir libro' ?></button>
                <?php if ($edicion): ?>
                    <a href="libros.php" class="btn btn-marmol">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- Listado -->
    <section class="pergamino">
        <div class="cabecera-lista">
            <h2 class="titulo-seccion">Catálogo <small>(<?= count($libros) ?>)</small></h2>
            <form method="get" class="buscador" role="search">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar código, título o autor" aria-label="Buscar libros">
                <button type="submit" class="btn btn-oro btn-chico">Buscar</button>
                <?php if ($q !== ''): ?><a href="libros.php" class="btn btn-marmol btn-chico">Limpiar</a><?php endif; ?>
            </form>
        </div>

        <?php if (!$libros): ?>
            <p class="vacio"><?= $q !== '' ? 'Ningún libro coincide con la búsqueda.' : 'Los estantes están vacíos. Añade el primer libro.' ?></p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr><th>Código</th><th>Título</th><th>Autor</th><th class="num">Unid.</th><th class="num">Disp.</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($libros as $l):
                        $disp = (int)$l['unidades'] - (int)$l['prestados']; ?>
                        <tr<?= $edicion && $edicion['id'] == $l['id'] ? ' class="fila-activa"' : '' ?>>
                            <td><span class="codigo"><?= e($l['codigo']) ?></span></td>
                            <td><strong><?= e($l['titulo']) ?></strong></td>
                            <td><em><?= e($l['autor']) ?></em></td>
                            <td class="num"><?= (int)$l['unidades'] ?></td>
                            <td class="num"><span class="sello <?= $disp > 0 ? 'sello-devuelto' : 'sello-vencido' ?>"><?= $disp ?></span></td>
                            <td class="acciones">
                                <a class="btn btn-oro btn-chico" href="libros.php?editar=<?= (int)$l['id'] ?>">Editar</a>
                                <form method="post" data-confirmar="¿Eliminar «<?= e($l['titulo']) ?>» y su historial de préstamos?">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
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
