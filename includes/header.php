<?php if (!isset($pageTitle)) { $pageTitle = 'Biblioteca'; } ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> · Biblioteca Central</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📚</text></svg>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-biblioteca sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fa-solid fa-book-open"></i> Biblioteca Central</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='index.php'?'active':'' ?>" href="index.php"><i class="fa-solid fa-house"></i> Inicio</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='usuarios.php'?'active':'' ?>" href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='libros.php'?'active':'' ?>" href="libros.php"><i class="fa-solid fa-book"></i> Libros</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='prestamos.php'?'active':'' ?>" href="prestamos.php"><i class="fa-solid fa-right-left"></i> Préstamos</a></li>
            </ul>
        </div>
    </div>
</nav>
