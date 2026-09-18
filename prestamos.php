<?php
require_once 'config.php';
$page_title='Préstamos'; $active='prestamos';
$msg='';$type='success';
if(!isset($db_error)){
 try{
  if($_SERVER['REQUEST_METHOD']==='POST'){
   $accion=$_POST['accion']??'';
   if($accion==='prestar'){
    $uid=(int)$_POST['usuario_id'];$lid=(int)$_POST['libro_id'];
    $pdo->beginTransaction();
    $st=$pdo->prepare("SELECT unidades FROM libros WHERE id=? FOR UPDATE");$st->execute([$lid]);$lib=$st->fetch();
    if(!$lib) throw new Exception('El libro seleccionado no existe.');
    if((int)$lib['unidades']<1) throw new Exception('No existen unidades disponibles de este libro.');
    $st=$pdo->prepare("INSERT INTO prestamos(usuario_id,libro_id) VALUES(?,?)");$st->execute([$uid,$lid]);
    $st=$pdo->prepare("UPDATE libros SET unidades=unidades-1 WHERE id=?");$st->execute([$lid]);
    $pdo->commit();$msg='Préstamo registrado y disponibilidad actualizada.';
   }elseif($accion==='devolver'){
    $pid=(int)$_POST['id'];$pdo->beginTransaction();
    $st=$pdo->prepare("SELECT libro_id,estado FROM prestamos WHERE id=? FOR UPDATE");$st->execute([$pid]);$p=$st->fetch();
    if(!$p) throw new Exception('El préstamo no existe.');
    if($p['estado']==='Devuelto') throw new Exception('Este préstamo ya fue devuelto.');
    $st=$pdo->prepare("UPDATE prestamos SET estado='Devuelto',fecha_devolucion=NOW() WHERE id=?");$st->execute([$pid]);
    $st=$pdo->prepare("UPDATE libros SET unidades=unidades+1 WHERE id=?");$st->execute([$p['libro_id']]);
    $pdo->commit();$msg='Devolución registrada correctamente.';
   }
  }
 }catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();$msg='No fue posible completar el préstamo.';$type='error';}
 catch(Exception $e){if($pdo->inTransaction())$pdo->rollBack();$msg=$e->getMessage();$type='error';}
}
$usuarios=!isset($db_error)?$pdo->query("SELECT id,nombre,cedula FROM usuarios ORDER BY nombre")->fetchAll():[];
$libros=!isset($db_error)?$pdo->query("SELECT id,codigo,titulo,autor,unidades FROM libros ORDER BY titulo")->fetchAll():[];
$prestamos=!isset($db_error)?$pdo->query("SELECT p.*,u.nombre usuario,l.titulo,l.codigo FROM prestamos p JOIN usuarios u ON u.id=p.usuario_id JOIN libros l ON l.id=p.libro_id ORDER BY p.id DESC")->fetchAll():[];
$mostrar_form=isset($_GET['accion'])&&$_GET['accion']==='nuevo';
include 'header.php';
?>
<div class="page-head"><div><span class="eyebrow">Circulación</span><h1>Préstamos</h1><p>Registra entregas y controla devoluciones en tiempo real.</p></div><a class="btn primary" href="?accion=nuevo">＋ Nuevo préstamo</a></div>
<?php if($msg):?><div class="alert <?=$type?>"><?= $type==='success'?'✓':'⚠️' ?> <?=htmlspecialchars($msg)?></div><?php endif;?>
<?php if($mostrar_form):?>
<section class="panel loan-form"><div class="panel-head"><div><h2>Registrar préstamo</h2><p class="muted">Selecciona un usuario y un libro con disponibilidad.</p></div><a href="prestamos.php" class="btn ghost">Cerrar</a></div>
<?php if(!$usuarios):?><div class="alert error">Primero debes registrar al menos un usuario.</div><?php elseif(!$libros):?><div class="alert error">Primero debes registrar al menos un libro.</div><?php else:?><form method="post" class="form-grid two">
<input type="hidden" name="accion" value="prestar">
<label>Usuario<select required name="usuario_id"><option value="">Selecciona un usuario</option><?php foreach($usuarios as $u):?><option value="<?=$u['id']?>"><?=htmlspecialchars($u['nombre'])?> · <?=htmlspecialchars($u['cedula'])?></option><?php endforeach;?></select></label>
<label>Libro<select required name="libro_id"><option value="">Selecciona un libro</option><?php foreach($libros as $l):?><option value="<?=$l['id']?>" <?=$l['unidades']<1?'disabled':''?>><?=htmlspecialchars($l['titulo'])?> · disponibles: <?=$l['unidades']?></option><?php endforeach;?></select></label>
<div class="form-actions"><button class="btn primary">📖 Confirmar préstamo</button></div></form><?php endif;?></section>
<?php endif;?>
<section class="panel"><div class="panel-head"><div><h2>Historial de préstamos</h2><p class="muted">Consulta el estado de cada operación.</p></div><div class="filters"><a href="prestamos.php" class="filter active">Todos</a><a href="prestamos.php?estado=Activo" class="filter">Activos</a><a href="prestamos.php?estado=Devuelto" class="filter">Devueltos</a></div></div>
<?php $estado=$_GET['estado']??''; if($estado) $prestamos=array_values(array_filter($prestamos,fn($p)=>$p['estado']===$estado));?>
<?php if(!$prestamos):?><div class="empty">No hay préstamos para mostrar.</div><?php else:?><div class="table-wrap"><table><thead><tr><th>Usuario</th><th>Libro</th><th>Préstamo</th><th>Devolución</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php foreach($prestamos as $p):?><tr><td><?=htmlspecialchars($p['usuario'])?></td><td><?=htmlspecialchars($p['titulo'])?><small class="table-sub"><?=htmlspecialchars($p['codigo'])?></small></td><td><?=date('d/m/Y H:i',strtotime($p['fecha_prestamo']))?></td><td><?=$p['fecha_devolucion']?date('d/m/Y H:i',strtotime($p['fecha_devolucion'])):'—'?></td><td><span class="badge <?=$p['estado']==='Activo'?'active':'returned'?>"><?=$p['estado']?></span></td><td><?php if($p['estado']==='Activo'):?><form method="post" onsubmit="return confirmDelete('¿Confirmar devolución del libro?')"><input type="hidden" name="accion" value="devolver"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn small">↩ Devolver</button></form><?php else:?><span class="muted">Completado</span><?php endif;?></td></tr><?php endforeach;?>
</tbody></table></div><?php endif;?></section>
<?php include 'footer.php'; ?>