<?php
if (!isset($page_title)) $page_title = 'Biblioteca';
if (!isset($active)) $active = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Biblioteca</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php">
        <span class="brand-icon">📚</span>
        <span>
            <strong>Biblioteca</strong>
            <small>Gestión inteligente</small>
        </span>
    </a>
    <button class="menu-toggle" aria-label="Abrir menú" onclick="toggleMenu()">☰</button>
    <nav id="mainNav">
        <a class="<?= $active==='inicio'?'active':'' ?>" href="index.php">Inicio</a>
        <a class="<?= $active==='usuarios'?'active':'' ?>" href="usuarios.php">Usuarios</a>
        <a class="<?= $active==='libros'?'active':'' ?>" href="libros.php">Libros</a>
        <a class="<?= $active==='prestamos'?'active':'' ?>" href="prestamos.php">Préstamos</a>
    </nav>
</header>
<main class="container">
<?php if (isset($db_error)): ?>
    <div class="alert error">⚠️ <?= htmlspecialchars($db_error) ?></div>
<?php endif; ?>
