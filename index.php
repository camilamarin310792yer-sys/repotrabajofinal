<?php
require_once __DIR__.'/config.php';
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(150) NOT NULL,cedula VARCHAR(40) NOT NULL UNIQUE,telefono VARCHAR(40) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS libros (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,codigo VARCHAR(50) NOT NULL UNIQUE,titulo VARCHAR(200) NOT NULL,autor VARCHAR(150) NOT NULL,unidades INT UNSIGNED NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS prestamos (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,usuario_id INT UNSIGNED NOT NULL,libro_id INT UNSIGNED NOT NULL,cantidad INT UNSIGNED NOT NULL DEFAULT 1,fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,fecha_devolucion DATETIME NULL,estado ENUM('prestado','devuelto') NOT NULL DEFAULT 'prestado',INDEX(usuario_id),INDEX(libro_id),CONSTRAINT fk_prestamo_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id),CONSTRAINT fk_prestamo_libro FOREIGN KEY(libro_id) REFERENCES libros(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$section=$_GET['section']??'inicio';
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$flash='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=$_POST['action']??'';
    try {
        if($action==='usuario_add'){
            $s=$pdo->prepare("INSERT INTO usuarios(nombre,cedula,telefono) VALUES(?,?,?)"); $s->execute([trim($_POST['nombre']),trim($_POST['cedula']),trim($_POST['telefono'])]); $flash='Usuario registrado.';
        } elseif($action==='usuario_delete'){
            $s=$pdo->prepare("DELETE FROM usuarios WHERE id=?"); $s->execute([(int)$_POST['id']]); $flash='Usuario eliminado.';
        } elseif($action==='libro_add'){
            $s=$pdo->prepare("INSERT INTO libros(codigo,titulo,autor,unidades) VALUES(?,?,?,?)"); $s->execute([trim($_POST['codigo']),trim($_POST['titulo']),trim($_POST['autor']),(int)$_POST['unidades']]); $flash='Libro registrado.';
        } elseif($action==='libro_delete'){
            $s=$pdo->prepare("DELETE FROM libros WHERE id=?"); $s->execute([(int)$_POST['id']]); $flash='Libro eliminado.';
        } elseif($action==='prestamo_add'){
            $pdo->beginTransaction();
            $libro=(int)$_POST['libro_id']; $cant=max(1,(int)$_POST['cantidad']);
            $s=$pdo->prepare("SELECT unidades FROM libros WHERE id=? FOR UPDATE"); $s->execute([$libro]); $stock=(int)$s->fetchColumn();
            if($stock<$cant) throw new Exception("No hay suficientes unidades disponibles.");
            $s=$pdo->prepare("INSERT INTO prestamos(usuario_id,libro_id,cantidad) VALUES(?,?,?)"); $s->execute([(int)$_POST['usuario_id'],$libro,$cant]);
            $s=$pdo->prepare("UPDATE libros SET unidades=unidades-? WHERE id=?"); $s->execute([$cant,$libro]);
            $pdo->commit(); $flash='Préstamo registrado.';
        } elseif($action==='devolver'){
            $pdo->beginTransaction();
            $id=(int)$_POST['id']; $s=$pdo->prepare("SELECT libro_id,cantidad,estado FROM prestamos WHERE id=? FOR UPDATE"); $s->execute([$id]); $p=$s->fetch();
            if(!$p || $p['estado']!=='prestado') throw new Exception('El préstamo ya fue devuelto o no existe.');
            $pdo->prepare("UPDATE prestamos SET estado='devuelto',fecha_devolucion=NOW() WHERE id=?")->execute([$id]);
            $pdo->prepare("UPDATE libros SET unidades=unidades+? WHERE id=?")->execute([(int)$p['cantidad'],(int)$p['libro_id']]);
            $pdo->commit(); $flash='Libro devuelto correctamente.';
        }
    } catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); $flash='Error: '.$e->getMessage(); }
}
$usuarios=$pdo->query("SELECT * FROM usuarios ORDER BY nombre")->fetchAll();
$libros=$pdo->query("SELECT * FROM libros ORDER BY titulo")->fetchAll();
$prestamos=$pdo->query("SELECT p.*,u.nombre usuario,l.titulo libro,l.codigo FROM prestamos p JOIN usuarios u ON u.id=p.usuario_id JOIN libros l ON l.id=p.libro_id ORDER BY p.id DESC")->fetchAll();
$stats=[
'usuarios'=>(int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
'libros'=>(int)$pdo->query("SELECT COUNT(*) FROM libros")->fetchColumn(),
'unidades'=>(int)$pdo->query("SELECT COALESCE(SUM(unidades),0) FROM libros")->fetchColumn(),
'activos'=>(int)$pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado='prestado'")->fetchColumn()
];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Biblioteca Romana</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="roman-bg"><div class="columns"></div><header class="top"><div class="brand"><span class="laurel">❦</span><div><div class="eyebrow">SENATUS LIBRARIA</div><h1>Biblioteca Romana</h1></div></div>
<nav><a class="<?= $section==='inicio'?'active':''?>" href="?section=inicio">🏛 Inicio</a><a class="<?= $section==='usuarios'?'active':''?>" href="?section=usuarios">👥 Usuarios</a><a class="<?= $section==='libros'?'active':''?>" href="?section=libros">📜 Libros</a><a class="<?= $section==='prestamos'?'active':''?>" href="?section=prestamos">⚖ Préstamos</a></nav></header>
<main class="wrap"><?php if($flash): ?><div class="alert <?=str_starts_with($flash,'Error')?'error':'ok'?>"><?=h($flash)?></div><?php endif; ?>
<?php if($section==='inicio'): ?><section class="hero card"><div><span class="seal">SPQR</span><h2>Sabiduría, memoria y conocimiento.</h2><p>Administra usuarios, libros y préstamos desde una biblioteca inspirada en la arquitectura clásica de Roma.</p><a class="btn" href="?section=prestamos">Gestionar préstamos</a></div><div class="statue">🏛️</div></section>
<div class="grid4"><div class="stat card"><b><?=$stats['usuarios']?></b><span>Usuarios</span></div><div class="stat card"><b><?=$stats['libros']?></b><span>Títulos</span></div><div class="stat card"><b><?=$stats['unidades']?></b><span>Unidades disponibles</span></div><div class="stat card"><b><?=$stats['activos']?></b><span>Préstamos activos</span></div></div>
<section class="card"><h2>Cómo comenzar</h2><div class="steps"><div><b>I</b>Registra usuarios</div><div><b>II</b>Registra libros y existencias</div><div><b>III</b>Genera préstamos</div><div><b>IV</b>Registra las devoluciones</div></div></section>
<?php elseif($section==='usuarios'): ?><section class="card"><h2>👥 Ciudadanos de la Biblioteca</h2><form method="post" class="formgrid"><input type="hidden" name="action" value="usuario_add"><label>Nombre<input required name="nombre"></label><label>Cédula<input required name="cedula"></label><label>Teléfono<input required name="telefono"></label><button class="btn" type="submit">Registrar usuario</button></form></section>
<section class="card"><h2>Registro de usuarios</h2><div class="tablewrap"><table><tr><th>ID</th><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Acción</th></tr><?php foreach($usuarios as $u): ?><tr><td><?=h($u['id'])?></td><td><?=h($u['nombre'])?></td><td><?=h($u['cedula'])?></td><td><?=h($u['telefono'])?></td><td><form method="post" onsubmit="return confirm('¿Eliminar usuario?')"><input type="hidden" name="action" value="usuario_delete"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn danger">Eliminar</button></form></td></tr><?php endforeach; ?></table></div></section>
<?php elseif($section==='libros'): ?><section class="card"><h2>📜 Catálogo de pergaminos</h2><form method="post" class="formgrid"><input type="hidden" name="action" value="libro_add"><label>Código<input required name="codigo"></label><label>Título<input required name="titulo"></label><label>Autor<input required name="autor"></label><label>Unidades<input required min="0" type="number" name="unidades"></label><button class="btn" type="submit">Agregar libro</button></form></section>
<section class="card"><h2>Inventario</h2><div class="tablewrap"><table><tr><th>Código</th><th>Título</th><th>Autor</th><th>Disponibles</th><th>Acción</th></tr><?php foreach($libros as $l): ?><tr><td><?=h($l['codigo'])?></td><td><?=h($l['titulo'])?></td><td><?=h($l['autor'])?></td><td><span class="pill <?=$l['unidades']==0?'empty':''?>"><?=h($l['unidades'])?></span></td><td><form method="post" onsubmit="return confirm('¿Eliminar libro?')"><input type="hidden" name="action" value="libro_delete"><input type="hidden" name="id" value="<?=$l['id']?>"><button class="btn danger">Eliminar</button></form></td></tr><?php endforeach; ?></table></div></section>
<?php elseif($section==='prestamos'): ?><section class="card"><h2>⚖ Registrar nuevo préstamo</h2><form method="post" class="formgrid"><input type="hidden" name="action" value="prestamo_add"><label>Usuario<select required name="usuario_id"><option value="">Seleccione...</option><?php foreach($usuarios as $u): ?><option value="<?=$u['id']?>"><?=h($u['nombre'])?> — <?=h($u['cedula'])?></option><?php endforeach; ?></select></label><label>Libro<select required name="libro_id"><option value="">Seleccione...</option><?php foreach($libros as $l): ?><option value="<?=$l['id']?>" <?=$l['unidades']==0?'disabled':''?>><?=h($l['titulo'])?> — disponibles: <?=$l['unidades']?></option><?php endforeach; ?></select></label><label>Cantidad<input required min="1" type="number" name="cantidad" value="1"></label><button class="btn" type="submit">Otorgar préstamo</button></form></section>
<section class="card"><h2>Libro mayor de préstamos</h2><div class="tablewrap"><table><tr><th>Usuario</th><th>Libro</th><th>Cant.</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr><?php foreach($prestamos as $p): ?><tr><td><?=h($p['usuario'])?></td><td><?=h($p['titulo'])?><small><?=h($p['codigo'])?></small></td><td><?=h($p['cantidad'])?></td><td><?=h($p['fecha_prestamo'])?></td><td><span class="pill <?=$p['estado']==='devuelto'?'returned':'borrowed'?>"><?=h($p['estado'])?></span></td><td><?php if($p['estado']==='prestado'): ?><form method="post"><input type="hidden" name="action" value="devolver"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn">Registrar devolución</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?></table></div></section><?php endif; ?></main>
<footer>✦ SPQR · Biblioteca Romana · Sistema PHP + MySQL ✦</footer></body></html>