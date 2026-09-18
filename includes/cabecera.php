<?php
/* ============================================================
   CABECERA COMÚN: frontón del templo + menú
   Antes de incluirla, cada página define $titulo y $pagina.
   ============================================================ */
$titulo = $titulo ?? APP_NOMBRE;
$pagina = $pagina ?? '';

// Menú: archivo => [nombre latino, nombre en español]
$menu = [
    'index.php'     => ['Atrium',    'Inicio'],
    'libros.php'    => ['Volumina',  'Libros'],
    'usuarios.php'  => ['Lectores',  'Usuarios'],
    'prestamos.php' => ['Commodata', 'Préstamos'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(APP_NOMBRE) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛️</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/estilo.css">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<!-- Columnas dóricas decorativas a los lados (solo pantallas anchas) -->
<div class="columna columna-izq" aria-hidden="true"></div>
<div class="columna columna-der" aria-hidden="true"></div>

<header class="templo">
    <div class="fronton" aria-hidden="true">
        <div class="fronton-interior">
            <span class="laurel">❦</span>
            <span class="fronton-texto">S · P · Q · R</span>
            <span class="laurel laurel-der">❦</span>
        </div>
    </div>
    <div class="entablamento">
        <h1><?= e(APP_NOMBRE) ?></h1>
        <p class="lema">Scientia potentia est · Gestión de libros, lectores y préstamos</p>
    </div>
    <div class="greca greca-oro" aria-hidden="true"></div>

    <nav class="menu" aria-label="Menú principal">
        <?php foreach ($menu as $archivo => [$latin, $espanol]): ?>
            <a href="<?= e($archivo) ?>"
               class="btn btn-nav<?= $pagina === $archivo ? ' activo' : '' ?>"
               <?= $pagina === $archivo ? 'aria-current="page"' : '' ?>>
                <span class="btn-latin"><?= e($latin) ?></span>
                <span class="btn-sub"><?= e($espanol) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</header>

<main class="contenido" id="contenido">
    <?php foreach (leer_flash() as $f): ?>
        <div class="aviso aviso-<?= e($f['tipo']) ?>" role="status">
            <span class="aviso-icono"><?= $f['tipo'] === 'exito' ? '✦' : '✖' ?></span>
            <?= e($f['mensaje']) ?>
        </div>
    <?php endforeach; ?>
