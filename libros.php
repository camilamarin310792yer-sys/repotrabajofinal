<?php
require_once __DIR__ . '/config/db.php';
session_start();

$pageTitle = 'Libros';
$action = $_GET['action'] ?? 'list';
$errores = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Galería de portadas creativas (temática libros/biblioteca) usadas cuando no se indica una imagen propia
$galeriaPortadas = [
    'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1476275466078-4007374efbbe?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1526243741027-444d633d7365?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1553729784-e91953dec042?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=400&q=80',
];

// ===== Guardar (crear o actualizar) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','edit'])) {
    $codigo = trim($_POST['codigo'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $unidades = (int)($_POST['unidades'] ?? 0);
    $portada = trim($_POST['portada_url'] ?? '');
    $id = $_POST['id'] ?? null;

    if ($codigo === '') $errores[] = 'El código es obligatorio.';
    if ($titulo === '') $errores[] = 'El título es obligatorio.';
    if ($autor === '') $errores[] = 'El autor es obligatorio.';
    if ($unidades < 0) $errores[] = 'Las unidades no pueden ser negativas.';

    if ($portada === '') {
        $portada = $galeriaPortadas[array_rand($galeriaPortadas)];
    }

    if (empty($errores)) {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO libros (codigo, titulo, autor, unidades, unidades_disponibles, portada_url) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$codigo, $titulo, $autor, $unidades, $unidades, $portada]);
                $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Libro creado correctamente.'];
            } else {
                // Al editar, ajustamos disponibles proporcionalmente si cambian las unidades totales
                $stmtActual = $pdo->prepare("SELECT unidades, unidades_disponibles FROM libros WHERE id=?");
                $stmtActual->execute([$id]);
                $actual = $stmtActual->fetch();
                $prestados = $actual['unidades'] - $actual['unidades_disponibles'];
                $nuevasDisponibles = max(0, $unidades - $prestados);

                $stmt = $pdo->prepare("UPDATE libros SET codigo=?, titulo=?, autor=?, unidades=?, unidades_disponibles=?, portada_url=? WHERE id=?");
                $stmt->execute([$codigo, $titulo, $autor, $unidades, $nuevasDisponibles, $portada, $id]);
                $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Libro actualizado correctamente.'];
            }
            header('Location: libros.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errores[] = 'Ya existe un libro con ese código.';
            } else {
                $errores[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// ===== Eliminar =====
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM libros WHERE id=?");
        $stmt->execute([$_GET['id']]);
        $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Libro eliminado.'];
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['tipo' => 'danger', 'msg' => 'No se pudo eliminar: tiene préstamos asociados.'];
    }
    header('Location: libros.php');
    exit;
}

// ===== Obtener libro para editar =====
$libroEdit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM libros WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $libroEdit = $stmt->fetch();
}

// ===== Búsqueda / listado =====
$buscar = trim($_GET['q'] ?? '');
if ($buscar !== '') {
    $stmt = $pdo->prepare("SELECT * FROM libros WHERE titulo LIKE ? OR autor LIKE ? OR codigo LIKE ? ORDER BY id DESC");
    $like = "%$buscar%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM libros ORDER BY id DESC");
}
$libros = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="hero-biblioteca py-5">
    <div class="container">
        <h1><i class="fa-solid fa-book"></i> Catálogo de Libros</h1>
        <p>Administra los títulos, autores y ejemplares disponibles.</p>
    </div>
</div>

<div class="container">

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] ?> panel-biblioteca py-3"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (in_array($action, ['create','edit'])): ?>
    <div class="panel-biblioteca">
        <h3 class="section-title">
            <i class="fa-solid <?= $action==='create' ? 'fa-book-medical':'fa-pen-to-square' ?>"></i>
            <?= $action === 'create' ? 'Nuevo libro' : 'Editar libro' ?>
        </h3>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="libros.php?action=<?= $action ?>" class="row g-3">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($libroEdit['id']) ?>">
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control" required
                       value="<?= htmlspecialchars($libroEdit['codigo'] ?? $_POST['codigo'] ?? '') ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">Título</label>
                <input type="text" name="titulo" class="form-control" required
                       value="<?= htmlspecialchars($libroEdit['titulo'] ?? $_POST['titulo'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Autor</label>
                <input type="text" name="autor" class="form-control" required
                       value="<?= htmlspecialchars($libroEdit['autor'] ?? $_POST['autor'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Unidades totales</label>
                <input type="number" min="0" name="unidades" class="form-control" required
                       value="<?= htmlspecialchars($libroEdit['unidades'] ?? $_POST['unidades'] ?? 1) ?>">
            </div>
            <div class="col-md-9">
                <label class="form-label">URL de portada (opcional — se asigna una automática si se deja vacío)</label>
                <input type="text" name="portada_url" class="form-control" placeholder="https://..."
                       value="<?= htmlspecialchars($libroEdit['portada_url'] ?? $_POST['portada_url'] ?? '') ?>">
            </div>

            <div class="col-12">
                <p class="mb-1 text-muted" style="font-size:0.9rem;">Elige rápidamente una portada de la galería:</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($galeriaPortadas as $g): ?>
                        <img src="<?= $g ?>" class="book-cover" style="cursor:pointer;"
                             onclick="document.querySelector('[name=portada_url]').value='<?= $g ?>'">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-dorado"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                <a href="libros.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="panel-biblioteca">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0" style="border:none;"><i class="fa-solid fa-list"></i> Catálogo</h3>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <input type="text" name="q" class="form-control me-2" placeholder="Buscar título, autor o código" value="<?= htmlspecialchars($buscar) ?>">
                    <button class="btn btn-marron"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <a href="libros.php?action=create" class="btn btn-dorado text-nowrap"><i class="fa-solid fa-plus"></i> Nuevo</a>
            </div>
        </div>

        <div class="row g-3">
            <?php if (empty($libros)): ?>
                <p class="text-center text-muted py-4">No hay libros registrados.</p>
            <?php endif; ?>
            <?php foreach ($libros as $l): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card-biblioteca h-100">
                    <img src="<?= htmlspecialchars($l['portada_url']) ?>" class="w-100" style="height:180px;object-fit:cover;">
                    <div class="p-3">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-secondary"><?= htmlspecialchars($l['codigo']) ?></span>
                            <span class="badge <?= $l['unidades_disponibles']>0 ? 'badge-disponible':'badge-agotado' ?>">
                                <?= $l['unidades_disponibles'] ?>/<?= $l['unidades'] ?>
                            </span>
                        </div>
                        <h6 class="mt-2 mb-0"><?= htmlspecialchars($l['titulo']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($l['autor']) ?></small>
                        <div class="mt-3 d-flex justify-content-end gap-1">
                            <a href="libros.php?action=edit&id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
                            <a href="libros.php?action=delete&id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($l['titulo'])) ?>»?');">
                               <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
