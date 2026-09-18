<?php
require_once __DIR__ . '/db.php';
$mensaje=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $accion=$_POST['accion']??'';
        if($accion==='crear'){
            $stmt=$pdo->prepare("INSERT INTO libros(codigo,titulo,autor,unidades) VALUES(?,?,?,?)");
            $stmt->execute([trim($_POST['codigo']),trim($_POST['titulo']),trim($_POST['autor']),max(0,(int)$_POST['unidades'])]);
            $mensaje='Libro agregado correctamente.';
        } elseif($accion==='editar'){
            $stmt=$pdo->prepare("UPDATE libros SET codigo=?,titulo=?,autor=?,unidades=? WHERE id=?");
            $stmt->execute([trim($_POST['codigo']),trim($_POST['titulo']),trim($_POST['autor']),max(0,(int)$_POST['unidades']),(int)$_POST['id']]);
            $mensaje='Libro actualizado correctamente.';
        } elseif($accion==='eliminar'){
            $stmt=$pdo->prepare("DELETE FROM libros WHERE id=?"); $stmt->execute([(int)$_POST['id']]);
            $mensaje='Libro eliminado correctamente.';
        }
    } catch(PDOException $e){ $error='No se pudo completar la operación. Verifica que el código sea único y que el libro no tenga préstamos asociados.'; }
}
$editar=null;
if(isset($_GET['editar'])){$stmt=$pdo->prepare("SELECT * FROM libros WHERE id=?");$stmt->execute([(int)$_GET['editar']]);$editar=$stmt->fetch();}
$lista=$pdo->query("SELECT * FROM libros ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Libros · Biblioteca Aurora</title><link rel="stylesheet" href="assets/style.css"></head>
<body><nav class="topbar"><a class="brand darkbrand" href="index.php">📚 Biblioteca Aurora</a><div class="navlinks"><a href="usuarios.php">👥 Usuarios</a><a class="active" href="libros.php">📖 Libros</a><a href="prestamos.php">🔖 Préstamos</a></div></nav>
<main class="container page"><div class="page-title"><div><span class="eyebrow dark">CATÁLOGO</span><h1>Gestión de libros</h1><p>Organiza títulos, autores y existencias del catálogo.</p></div><span class="big-icon">📚</span></div>
<?php if($mensaje):?><div class="alert success">✓ <?=htmlspecialchars($mensaje)?></div><?php endif;?><?php if($error):?><div class="alert danger">⚠ <?=htmlspecialchars($error)?></div><?php endif;?>
<section class="form-panel"><h2><?= $editar?'Editar libro':'Nuevo libro' ?></h2><form method="post" class="form-grid">
<input type="hidden" name="accion" value="<?=$editar?'editar':'crear'?>"><?php if($editar):?><input type="hidden" name="id" value="<?=$editar['id']?>"><?php endif;?>
<label>Código<input required name="codigo" value="<?=htmlspecialchars($editar['codigo']??'')?>" placeholder="Ej. LIB-004"></label>
<label>Título<input required name="titulo" value="<?=htmlspecialchars($editar['titulo']??'')?>" placeholder="Ej. Don Quijote de la Mancha"></label>
<label>Autor<input required name="autor" value="<?=htmlspecialchars($editar['autor']??'')?>" placeholder="Ej. Miguel de Cervantes"></label>
<label>Unidades<input required type="number" min="0" name="unidades" value="<?=htmlspecialchars($editar['unidades']??'1')?>"></label>
<div class="form-actions"><button class="btn btn-primary" type="submit"><?=$editar?'Guardar cambios':'Agregar libro'?></button><?php if($editar):?><a class="btn btn-light" href="libros.php">Cancelar</a><?php endif;?></div>
</form></section>
<section class="panel"><div class="panel-head"><h2>Catálogo</h2><span class="counter"><?=count($lista)?> títulos</span></div><div class="book-grid">
<?php foreach($lista as $l): ?><article class="book-card"><div class="book-cover"><span>📖</span><small><?=htmlspecialchars($l['codigo'])?></small></div><div class="book-info"><h3><?=htmlspecialchars($l['titulo'])?></h3><p><?=htmlspecialchars($l['autor'])?></p><span class="stock">📚 <?=$l['unidades']?> unidades</span><div class="actions"><a class="btn-small edit" href="?editar=<?=$l['id']?>">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar este libro?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=$l['id']?>"><button class="btn-small delete">Eliminar</button></form></div></div></article><?php endforeach;?>
</div></section></main><footer>Biblioteca Aurora · Libros</footer></body></html>
