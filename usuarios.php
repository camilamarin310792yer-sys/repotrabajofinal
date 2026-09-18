<?php
require_once 'config.php';
$page_title='Usuarios'; $active='usuarios';
$msg=''; $type='success';

if (!isset($db_error)) {
    try {
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $accion=$_POST['accion'] ?? '';
            $nombre=trim($_POST['nombre'] ?? '');
            $cedula=trim($_POST['cedula'] ?? '');
            $telefono=trim($_POST['telefono'] ?? '');
            if ($accion==='guardar') {
                if ($nombre==='' || $cedula==='' || $telefono==='') throw new Exception('Completa todos los campos.');
                $stmt=$pdo->prepare("INSERT INTO usuarios(nombre,cedula,telefono) VALUES(?,?,?)");
                $stmt->execute([$nombre,$cedula,$telefono]);
                $msg='Usuario registrado correctamente.';
            } elseif ($accion==='editar') {
                $id=(int)$_POST['id'];
                if ($nombre==='' || $cedula==='' || $telefono==='') throw new Exception('Completa todos los campos.');
                $stmt=$pdo->prepare("UPDATE usuarios SET nombre=?,cedula=?,telefono=? WHERE id=?");
                $stmt->execute([$nombre,$cedula,$telefono,$id]);
                $msg='Usuario actualizado correctamente.';
            } elseif ($accion==='eliminar') {
                $id=(int)$_POST['id'];
                $stmt=$pdo->prepare("DELETE FROM usuarios WHERE id=?"); $stmt->execute([$id]);
                $msg='Usuario eliminado correctamente.';
            }
        }
    } catch (PDOException $e) {
        $msg = $e->getCode()==='23000' ? 'La cédula ya existe o el usuario tiene préstamos asociados.' : 'No fue posible completar la operación.';
        $type='error';
    } catch (Exception $e) { $msg=$e->getMessage(); $type='error'; }
}
$editar=null;
if (!isset($db_error) && isset($_GET['editar'])) {
    $st=$pdo->prepare("SELECT * FROM usuarios WHERE id=?"); $st->execute([(int)$_GET['editar']]); $editar=$st->fetch();
}
$lista=!isset($db_error)?$pdo->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll():[];
include 'header.php';
?>
<div class="page-head"><div><span class="eyebrow">Directorio</span><h1>Usuarios</h1><p>Administra las personas que utilizan la biblioteca.</p></div></div>
<?php if($msg): ?><div class="alert <?= $type ?>"><?= $type==='success'?'✓':'⚠️' ?> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="two-col">
<section class="panel">
<h2><?= $editar?'Editar usuario':'Registrar usuario' ?></h2>
<form method="post" class="form-grid">
<input type="hidden" name="accion" value="<?= $editar?'editar':'guardar' ?>">
<?php if($editar): ?><input type="hidden" name="id" value="<?= $editar['id'] ?>"><?php endif; ?>
<label>Nombre completo<input required name="nombre" value="<?= htmlspecialchars($editar['nombre']??'') ?>"></label>
<label>Cédula<input required name="cedula" value="<?= htmlspecialchars($editar['cedula']??'') ?>"></label>
<label>Teléfono<input required name="telefono" value="<?= htmlspecialchars($editar['telefono']??'') ?>"></label>
<div class="form-actions"><button class="btn primary" type="submit"><?= $editar?'Guardar cambios':'Registrar usuario' ?></button><?php if($editar): ?><a class="btn ghost" href="usuarios.php">Cancelar</a><?php endif; ?></div>
</form>
</section>
<section class="panel">
<div class="panel-head"><h2>Usuarios registrados</h2><span class="counter"><?= count($lista) ?></span></div>
<?php if(!$lista): ?><div class="empty">No hay usuarios registrados.</div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($lista as $u): ?><tr><td><?=htmlspecialchars($u['nombre'])?></td><td><?=htmlspecialchars($u['cedula'])?></td><td><?=htmlspecialchars($u['telefono'])?></td><td class="actions"><a class="icon-btn" href="?editar=<?=$u['id']?>" title="Editar">✏️</a><form method="post" onsubmit="return confirmDelete('¿Eliminar este usuario?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="icon-btn danger" title="Eliminar">🗑️</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section>
</div>
<?php include 'footer.php'; ?>