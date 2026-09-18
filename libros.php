<?php
require_once 'config.php';
$page_title='Libros'; $active='libros';
$msg=''; $type='success';
if(!isset($db_error)){
 try{
  if($_SERVER['REQUEST_METHOD']==='POST'){
   $accion=$_POST['accion']??''; $codigo=trim($_POST['codigo']??''); $titulo=trim($_POST['titulo']??''); $autor=trim($_POST['autor']??''); $unidades=$_POST['unidades']??'';
   if($accion==='guardar'||$accion==='editar'){
    if($codigo===''||$titulo===''||$autor===''||$unidades===''||!is_numeric($unidades)||intval($unidades)<0) throw new Exception('Completa los campos y usa unidades iguales o mayores que cero.');
    if($accion==='guardar'){ $st=$pdo->prepare("INSERT INTO libros(codigo,titulo,autor,unidades) VALUES(?,?,?,?)"); $st->execute([$codigo,$titulo,$autor,(int)$unidades]); $msg='Libro registrado correctamente.'; }
    else { $st=$pdo->prepare("UPDATE libros SET codigo=?,titulo=?,autor=?,unidades=? WHERE id=?"); $st->execute([$codigo,$titulo,$autor,(int)$unidades,(int)$_POST['id']]); $msg='Libro actualizado correctamente.'; }
   } elseif($accion==='eliminar'){ $st=$pdo->prepare("DELETE FROM libros WHERE id=?"); $st->execute([(int)$_POST['id']]); $msg='Libro eliminado correctamente.'; }
  }
 }catch(PDOException $e){$msg=$e->getCode()==='23000'?'El código ya existe o el libro tiene préstamos asociados.':'No fue posible completar la operación.';$type='error';}
 catch(Exception $e){$msg=$e->getMessage();$type='error';}
}
$editar=null;if(!isset($db_error)&&isset($_GET['editar'])){$st=$pdo->prepare("SELECT * FROM libros WHERE id=?");$st->execute([(int)$_GET['editar']]);$editar=$st->fetch();}
$lista=!isset($db_error)?$pdo->query("SELECT * FROM libros ORDER BY id DESC")->fetchAll():[];
include 'header.php';
?>
<div class="page-head"><div><span class="eyebrow">Catálogo</span><h1>Libros</h1><p>Controla títulos, autores e inventario disponible.</p></div></div>
<?php if($msg): ?><div class="alert <?=$type?>"><?= $type==='success'?'✓':'⚠️' ?> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="two-col">
<section class="panel"><h2><?=$editar?'Editar libro':'Registrar libro'?></h2><form method="post" class="form-grid">
<input type="hidden" name="accion" value="<?=$editar?'editar':'guardar'?>"><?php if($editar):?><input type="hidden" name="id" value="<?=$editar['id']?>"><?php endif;?>
<label>Código<input required name="codigo" value="<?=htmlspecialchars($editar['codigo']??'')?>"></label>
<label>Título<input required name="titulo" value="<?=htmlspecialchars($editar['titulo']??'')?>"></label>
<label>Autor<input required name="autor" value="<?=htmlspecialchars($editar['autor']??'')?>"></label>
<label>Unidades disponibles<input required type="number" min="0" name="unidades" value="<?=htmlspecialchars($editar['unidades']??'0')?>"></label>
<div class="form-actions"><button class="btn primary"> <?=$editar?'Guardar cambios':'Registrar libro'?> </button><?php if($editar):?><a class="btn ghost" href="libros.php">Cancelar</a><?php endif;?></div>
</form></section>
<section class="panel"><div class="panel-head"><h2>Catálogo registrado</h2><span class="counter"><?=count($lista)?></span></div>
<?php if(!$lista):?><div class="empty">No hay libros registrados.</div><?php else:?><div class="table-wrap"><table><thead><tr><th>Código</th><th>Título</th><th>Autor</th><th>Unidades</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($lista as $l):?><tr><td><b><?=htmlspecialchars($l['codigo'])?></b></td><td><?=htmlspecialchars($l['titulo'])?></td><td><?=htmlspecialchars($l['autor'])?></td><td><span class="stock <?=$l['unidades']>0?'ok':'zero'?>"><?=$l['unidades']?></span></td><td class="actions"><a class="icon-btn" href="?editar=<?=$l['id']?>">✏️</a><form method="post" onsubmit="return confirmDelete('¿Eliminar este libro?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=$l['id']?>"><button class="icon-btn danger">🗑️</button></form></td></tr><?php endforeach;?>
</tbody></table></div><?php endif;?></section></div>
<?php include 'footer.php'; ?>