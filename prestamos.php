<?php
require_once __DIR__ . '/db.php';
$mensaje=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $accion=$_POST['accion']??'';
        if($accion==='crear'){
            $libro=(int)$_POST['libro_id']; $usuario=(int)$_POST['usuario_id'];
            $stmt=$pdo->prepare("SELECT unidades FROM libros WHERE id=?"); $stmt->execute([$libro]); $disp=$stmt->fetchColumn();
            if($disp===false) throw new Exception('Libro no encontrado.');
            if((int)$disp<=0) throw new Exception('No hay unidades disponibles de este libro.');
            $pdo->beginTransaction();
            $stmt=$pdo->prepare("INSERT INTO prestamos(libro_id,usuario_id,fecha_prestamo,estado) VALUES(?,?,CURDATE(),'Prestado')");
            $stmt->execute([$libro,$usuario]);
            $pdo->prepare("UPDATE libros SET unidades=unidades-1 WHERE id=?")->execute([$libro]);
            $pdo->commit(); $mensaje='Préstamo registrado correctamente.';
        } elseif($accion==='devolver'){
            $id=(int)$_POST['id'];
            $pdo->beginTransaction();
            $stmt=$pdo->prepare("SELECT libro_id,estado FROM prestamos WHERE id=? FOR UPDATE"); $stmt->execute([$id]); $p=$stmt->fetch();
            if(!$p || $p['estado']==='Devuelto') throw new Exception('El préstamo ya fue devuelto o no existe.');
            $pdo->prepare("UPDATE prestamos SET estado='Devuelto', fecha_devolucion=CURDATE() WHERE id=?")->execute([$id]);
            $pdo->prepare("UPDATE libros SET unidades=unidades+1 WHERE id=?")->execute([(int)$p['libro_id']]);
            $pdo->commit(); $mensaje='Devolución registrada correctamente.';
        }
    } catch(Exception $e){ if($pdo->inTransaction())$pdo->rollBack(); $error=$e->getMessage(); }
}
$usuarios=$pdo->query("SELECT id,nombre,cedula FROM usuarios ORDER BY nombre")->fetchAll();
$libros=$pdo->query("SELECT id,codigo,titulo,unidades FROM libros ORDER BY titulo")->fetchAll();
$prestamos=$pdo->query("SELECT p.*,l.codigo,l.titulo,u.nombre,u.cedula FROM prestamos p JOIN libros l ON l.id=p.libro_id JOIN usuarios u ON u.id=p.usuario_id ORDER BY p.id DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Préstamos · Biblioteca Aurora</title><link rel="stylesheet" href="assets/style.css"></head>
<body><nav class="topbar"><a class="brand darkbrand" href="index.php">📚 Biblioteca Aurora</a><div class="navlinks"><a href="usuarios.php">👥 Usuarios</a><a href="libros.php">📖 Libros</a><a class="active" href="prestamos.php">🔖 Préstamos</a></div></nav>
<main class="container page"><div class="page-title"><div><span class="eyebrow dark">CIRCULACIÓN</span><h1>Préstamos y devoluciones</h1><p>Controla quién tiene cada libro y registra su devolución.</p></div><span class="big-icon">🔖</span></div>
<?php if($mensaje):?><div class="alert success">✓ <?=htmlspecialchars($mensaje)?></div><?php endif;?><?php if($error):?><div class="alert danger">⚠ <?=htmlspecialchars($error)?></div><?php endif;?>
<section class="form-panel"><h2>Registrar nuevo préstamo</h2><?php if(!$usuarios || !$libros): ?><div class="alert danger">Primero registra al menos un usuario y un libro disponible.</div><?php endif;?>
<form method="post" class="form-grid"><input type="hidden" name="accion" value="crear">
<label>Usuario<select name="usuario_id" required <?=!$usuarios?'disabled':''?>><option value="">Selecciona un usuario</option><?php foreach($usuarios as $u):?><option value="<?=$u['id']?>"><?=htmlspecialchars($u['nombre'])?> · <?=htmlspecialchars($u['cedula'])?></option><?php endforeach;?></select></label>
<label>Libro<select name="libro_id" required <?=!$libros?'disabled':''?>><option value="">Selecciona un libro</option><?php foreach($libros as $l):?><option value="<?=$l['id']?>" <?=$l['unidades']<=0?'disabled':''?>><?=htmlspecialchars($l['titulo'])?> (<?=$l['unidades']?> disponibles)</option><?php endforeach;?></select></label>
<div class="form-actions"><button class="btn btn-primary" type="submit" <?=(!$usuarios||!$libros)?'disabled':''?>>🔖 Registrar préstamo</button></div>
</form></section>
<section class="panel"><div class="panel-head"><h2>Historial de préstamos</h2><span class="counter"><?=count($prestamos)?> movimientos</span></div><div class="table-wrap"><table><thead><tr><th>Libro</th><th>Usuario</th><th>Préstamo</th><th>Devolución</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php foreach($prestamos as $p):?><tr><td><strong><?=htmlspecialchars($p['titulo'])?></strong><small class="sub"><?=htmlspecialchars($p['codigo'])?></small></td><td><?=htmlspecialchars($p['nombre'])?></td><td><?=$p['fecha_prestamo']?></td><td><?=$p['fecha_devolucion']?:'—'?></td><td><span class="badge <?=$p['estado']==='Devuelto'?'ok':'warning'?>"><?=$p['estado']?></span></td><td><?php if($p['estado']==='Prestado'):?><form method="post"><input type="hidden" name="accion" value="devolver"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn-small return">↩ Devolver</button></form><?php else:?><span class="muted">Completado</span><?php endif;?></td></tr><?php endforeach;?>
</tbody></table></div></section></main><footer>Biblioteca Aurora · Préstamos</footer></body></html>
