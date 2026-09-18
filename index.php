<?php
require_once "config.php";

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$mensaje = "";
$tipo = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    try {
        if ($accion === "crear_usuario") {
            $stmt = $conn->prepare("INSERT INTO usuarios(nombre, cedula, telefono) VALUES(?,?,?)");
            $stmt->bind_param("sss", $_POST["nombre"], $_POST["cedula"], $_POST["telefono"]);
            $stmt->execute();
            $mensaje = "Usuario registrado correctamente.";
        } elseif ($accion === "crear_libro") {
            $stmt = $conn->prepare("INSERT INTO libros(codigo, titulo, autor, unidades) VALUES(?,?,?,?)");
            $u = max(0, (int)$_POST["unidades"]);
            $stmt->bind_param("sssi", $_POST["codigo"], $_POST["titulo"], $_POST["autor"], $u);
            $stmt->execute();
            $mensaje = "Libro agregado correctamente.";
        } elseif ($accion === "crear_prestamo") {
            $usuario = (int)$_POST["usuario_id"];
            $libro = (int)$_POST["libro_id"];
            $fecha = $_POST["fecha_prestamo"] ?: date("Y-m-d");

            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT unidades FROM libros WHERE id=? FOR UPDATE");
            $stmt->bind_param("i", $libro);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();

            if (!$row) throw new Exception("El libro no existe.");
            if ((int)$row["unidades"] <= 0) throw new Exception("No hay unidades disponibles de este libro.");

            $stmt = $conn->prepare("INSERT INTO prestamos(usuario_id, libro_id, fecha_prestamo, estado) VALUES(?,?,?,'Prestado')");
            $stmt->bind_param("iis", $usuario, $libro, $fecha);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE libros SET unidades = unidades - 1 WHERE id=?");
            $stmt->bind_param("i", $libro);
            $stmt->execute();

            $conn->commit();
            $mensaje = "Préstamo registrado. La unidad disponible fue descontada.";
        } elseif ($accion === "devolver") {
            $id = (int)$_POST["prestamo_id"];
            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT libro_id, estado FROM prestamos WHERE id=? FOR UPDATE");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();

            if (!$p) throw new Exception("Préstamo no encontrado.");
            if ($p["estado"] === "Devuelto") throw new Exception("Este préstamo ya fue devuelto.");

            $stmt = $conn->prepare("UPDATE prestamos SET estado='Devuelto', fecha_devolucion=CURDATE() WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE libros SET unidades = unidades + 1 WHERE id=?");
            $stmt->bind_param("i", $p["libro_id"]);
            $stmt->execute();

            $conn->commit();
            $mensaje = "Libro devuelto correctamente.";
        } elseif ($accion === "eliminar_usuario") {
            $id = (int)$_POST["id"];
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $mensaje = "Usuario eliminado.";
        } elseif ($accion === "eliminar_libro") {
            $id = (int)$_POST["id"];
            $stmt = $conn->prepare("DELETE FROM libros WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $mensaje = "Libro eliminado.";
        }
    } catch (Throwable $ex) {
        if ($conn->errno) { try { $conn->rollback(); } catch(Throwable $x) {} }
        $mensaje = $ex->getMessage();
        $tipo = "error";
    }
}

$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id DESC");
$libros = $conn->query("SELECT * FROM libros ORDER BY id DESC");
$prestamos = $conn->query("
    SELECT p.*, u.nombre AS usuario, u.cedula, l.titulo, l.codigo
    FROM prestamos p
    INNER JOIN usuarios u ON u.id=p.usuario_id
    INNER JOIN libros l ON l.id=p.libro_id
    ORDER BY p.id DESC
");

$totalUsuarios = (int)$conn->query("SELECT COUNT(*) c FROM usuarios")->fetch_assoc()["c"];
$totalLibros = (int)$conn->query("SELECT COUNT(*) c FROM libros")->fetch_assoc()["c"];
$disponibles = (int)$conn->query("SELECT COALESCE(SUM(unidades),0) c FROM libros")->fetch_assoc()["c"];
$activos = (int)$conn->query("SELECT COUNT(*) c FROM prestamos WHERE estado='Prestado'")->fetch_assoc()["c"];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Biblioteca Aurora | Gestión</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--ink:#172033;--cream:#f8f1df;--gold:#d7a84c;--wine:#5b2637;--green:#315f4b;--card:#fffdf7;--muted:#687080}
*{box-sizing:border-box} body{margin:0;font-family:Inter,sans-serif;color:var(--ink);background:linear-gradient(135deg,#efe5ce,#faf8f0 55%,#e5d4b4);min-height:100vh}
.hero{min-height:430px;position:relative;overflow:hidden;color:#fff;background:linear-gradient(90deg,rgba(15,20,31,.92),rgba(41,24,27,.62)),url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1800&q=85') center/cover}
.hero:after{content:"";position:absolute;inset:auto 0 0;height:90px;background:linear-gradient(transparent,var(--cream))}
.nav{position:relative;z-index:2;max-width:1180px;margin:auto;padding:25px 22px;display:flex;justify-content:space-between;align-items:center}.brand{font-family:Cinzel,serif;font-size:24px;letter-spacing:1px}.badge{padding:10px 15px;border:1px solid #ffffff55;border-radius:30px;background:#ffffff15;backdrop-filter:blur(8px)}
.hero-content{position:relative;z-index:2;max-width:1180px;margin:55px auto 0;padding:0 22px}.eyebrow{text-transform:uppercase;letter-spacing:4px;font-size:12px;color:#f2d58d;font-weight:800}.hero h1{font-family:Cinzel,serif;font-size:clamp(42px,7vw,78px);max-width:850px;margin:12px 0}.hero p{font-size:18px;max-width:650px;line-height:1.7;color:#eee}
.wrap{max-width:1180px;margin:-25px auto 60px;position:relative;z-index:3;padding:0 22px}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:15px}.stat{background:rgba(255,253,247,.94);border:1px solid #e4dac5;border-radius:18px;padding:22px;box-shadow:0 12px 35px #45351c14}.stat strong{display:block;font-size:30px}.stat span{color:var(--muted);font-size:13px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-top:25px}.card{background:rgba(255,253,247,.97);border:1px solid #e1d6c0;border-radius:22px;padding:25px;box-shadow:0 14px 40px #3e2a1712}.wide{grid-column:1/-1}.card h2{font-family:Cinzel,serif;margin:0 0 5px;color:var(--wine)}.sub{color:var(--muted);font-size:13px;margin-bottom:20px}
form{display:grid;gap:12px}.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}label{font-size:12px;font-weight:800;color:#4e5665}input,select{width:100%;padding:13px;border:1px solid #d9d0bf;border-radius:11px;background:#fff;font:inherit}button{border:0;border-radius:12px;padding:13px 17px;background:var(--wine);color:white;font-weight:800;cursor:pointer}button:hover{filter:brightness(1.1)}.green{background:var(--green)}.danger{background:#8a3b42}.small{padding:8px 10px;font-size:12px}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:13px 9px;border-bottom:1px solid #eee5d6;font-size:13px}th{font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:#777}.pill{display:inline-block;padding:5px 9px;border-radius:20px;background:#eee7d6;font-size:11px;font-weight:800}.ok{background:#dceee3;color:#23563d}.out{background:#f6dfe1;color:#78323a}.empty{padding:25px;text-align:center;color:#777}
.alert{max-width:1180px;margin:25px auto 0;padding:14px 18px;border-radius:13px}.alert.success{background:#e0f0e6;color:#23563d}.alert.error{background:#f5dfe1;color:#762e38}
.cover{height:145px;border-radius:16px;margin-bottom:18px;background:linear-gradient(90deg,#24182277,#24182222),url('https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=900&q=80') center/cover;display:flex;align-items:end;padding:18px;color:white;font-family:Cinzel,serif;font-size:22px}
footer{text-align:center;padding:30px;color:#726c61;font-size:12px}
@media(max-width:800px){.stats{grid-template-columns:1fr 1fr}.grid{grid-template-columns:1fr}.wide{grid-column:auto}.row{grid-template-columns:1fr}table{display:block;overflow:auto}.hero{min-height:470px}}
</style>
</head>
<body>
<header class="hero">
 <div class="nav"><div class="brand">📚 Biblioteca Aurora</div><div class="badge">Panel de gestión</div></div>
 <div class="hero-content"><div class="eyebrow">Historias · conocimiento · comunidad</div><h1>Un lugar para cada historia.</h1><p>Administra lectores, libros y préstamos desde un solo espacio, con una experiencia inspirada en las grandes bibliotecas clásicas.</p></div>
</header>

<?php if($mensaje): ?><div class="alert <?=$tipo?>"><?=e($mensaje)?></div><?php endif; ?>

<main class="wrap">
<section class="stats">
 <div class="stat"><strong><?=$totalUsuarios?></strong><span>Lectores registrados</span></div>
 <div class="stat"><strong><?=$totalLibros?></strong><span>Títulos en catálogo</span></div>
 <div class="stat"><strong><?=$disponibles?></strong><span>Unidades disponibles</span></div>
 <div class="stat"><strong><?=$activos?></strong><span>Préstamos activos</span></div>
</section>

<section class="grid">
<div class="card">
 <div class="cover">📖 Registro de lectores</div>
 <form method="post"><input type="hidden" name="accion" value="crear_usuario">
  <div class="row"><div><label>Nombre</label><input name="nombre" required placeholder="Nombre completo"></div><div><label>Cédula</label><input name="cedula" required placeholder="Documento"></div></div>
  <div><label>Teléfono</label><input name="telefono" required placeholder="300 000 0000"></div>
  <button>+ Registrar usuario</button>
 </form>
</div>

<div class="card">
 <div class="cover" style="background-image:linear-gradient(90deg,#24182277,#24182222),url('https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=900&q=80')">✨ Nuevo libro</div>
 <form method="post"><input type="hidden" name="accion" value="crear_libro">
  <div class="row"><div><label>Código</label><input name="codigo" required placeholder="LIB-001"></div><div><label>Unidades</label><input type="number" min="0" name="unidades" value="1" required></div></div>
  <div><label>Título</label><input name="titulo" required placeholder="Título del libro"></div>
  <div><label>Autor</label><input name="autor" required placeholder="Nombre del autor"></div>
  <button>+ Agregar al catálogo</button>
 </form>
</div>

<div class="card wide">
 <h2>Nuevo préstamo</h2><div class="sub">Selecciona un lector y un libro con unidades disponibles.</div>
 <form method="post"><input type="hidden" name="accion" value="crear_prestamo">
  <div class="row"><div><label>Usuario</label><select name="usuario_id" required><option value="">Selecciona...</option><?php $usuarios2=$conn->query("SELECT id,nombre,cedula FROM usuarios ORDER BY nombre"); while($u=$usuarios2->fetch_assoc()): ?><option value="<?=$u['id']?>"><?=e($u['nombre'])?> · <?=e($u['cedula'])?></option><?php endwhile; ?></select></div>
  <div><label>Libro</label><select name="libro_id" required><option value="">Selecciona...</option><?php $libros2=$conn->query("SELECT id,codigo,titulo,unidades FROM libros WHERE unidades>0 ORDER BY titulo"); while($l=$libros2->fetch_assoc()): ?><option value="<?=$l['id']?>"><?=e($l['titulo'])?> · <?=e($l['codigo'])?> (<?=$l['unidades']?> disponibles)</option><?php endwhile; ?></select></div></div>
  <div><label>Fecha del préstamo</label><input type="date" name="fecha_prestamo" value="<?=date('Y-m-d')?>" required></div>
  <button class="green">📚 Registrar préstamo</button>
 </form>
</div>

<div class="card">
 <h2>Lectores</h2><div class="sub">Personas que pueden solicitar libros.</div>
 <?php if($usuarios->num_rows): ?><table><tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th></th></tr><?php while($u=$usuarios->fetch_assoc()): ?><tr><td><?=e($u['nombre'])?></td><td><?=e($u['cedula'])?></td><td><?=e($u['telefono'])?></td><td><form method="post" onsubmit="return confirm('¿Eliminar este usuario?');"><input type="hidden" name="accion" value="eliminar_usuario"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="danger small">Eliminar</button></form></td></tr><?php endwhile; ?></table><?php else: ?><div class="empty">Aún no hay lectores.</div><?php endif; ?>
</div>

<div class="card">
 <h2>Catálogo</h2><div class="sub">Inventario de libros y unidades.</div>
 <?php if($libros->num_rows): ?><table><tr><th>Código</th><th>Libro</th><th>Autor</th><th>Stock</th><th></th></tr><?php while($l=$libros->fetch_assoc()): ?><tr><td><?=e($l['codigo'])?></td><td><?=e($l['titulo'])?></td><td><?=e($l['autor'])?></td><td><span class="pill <?=$l['unidades']>0?'ok':'out'?>"><?=$l['unidades']?></span></td><td><form method="post" onsubmit="return confirm('¿Eliminar este libro?');"><input type="hidden" name="accion" value="eliminar_libro"><input type="hidden" name="id" value="<?=$l['id']?>"><button class="danger small">Eliminar</button></form></td></tr><?php endwhile; ?></table><?php else: ?><div class="empty">Aún no hay libros.</div><?php endif; ?>
</div>

<div class="card wide">
 <h2>Historial de préstamos</h2><div class="sub">Control de libros prestados y devueltos.</div>
 <?php if($prestamos->num_rows): ?><table><tr><th>Usuario</th><th>Libro</th><th>Préstamo</th><th>Devolución</th><th>Estado</th><th>Acción</th></tr><?php while($p=$prestamos->fetch_assoc()): ?><tr><td><?=e($p['usuario'])?></td><td><?=e($p['titulo'])?></td><td><?=e($p['fecha_prestamo'])?></td><td><?=e($p['fecha_devolucion'] ?: '—')?></td><td><span class="pill <?=$p['estado']=='Prestado'?'out':'ok'?>"><?=e($p['estado'])?></span></td><td><?php if($p['estado']=='Prestado'): ?><form method="post"><input type="hidden" name="accion" value="devolver"><input type="hidden" name="prestamo_id" value="<?=$p['id']?>"><button class="green small">↩ Devolver</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endwhile; ?></table><?php else: ?><div class="empty">No hay préstamos registrados.</div><?php endif; ?>
</div>
</section>
</main>
<footer>Biblioteca Aurora · Aplicativo PHP + MySQL · Las tablas se crean automáticamente al iniciar.</footer>
</body>
</html>