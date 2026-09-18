<?php
require __DIR__ . '/config.php';
$resource = $_GET['resource'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$method = $_SERVER['REQUEST_METHOD'];
$data = body();

$tables = ['usuarios'=>'usuarios','libros'=>'libros','prestamos'=>'prestamos'];
if (!isset($tables[$resource])) respond(['error'=>'Recurso no válido. Use usuarios, libros o prestamos.'], 404);
$table = $tables[$resource];

try {
    if ($method === 'GET') {
        if ($resource === 'prestamos') {
            $sql = "SELECT p.*, l.titulo, u.nombre AS usuario_nombre FROM prestamos p JOIN libros l ON l.id=p.libro_id JOIN usuarios u ON u.id=p.usuario_id";
        } else $sql = "SELECT * FROM `$table`";
        if ($id) $sql .= ' WHERE ' . ($resource === 'prestamos' ? 'p.id' : 'id') . ' = ?';
        $sql .= ' ORDER BY ' . ($resource === 'libros' ? 'titulo' : 'id') . ' DESC';
        $stmt = $pdo->prepare($sql); $stmt->execute($id ? [$id] : []);
        $rows = $id ? $stmt->fetch() : $stmt->fetchAll();
        respond($rows ?: ($id ? ['error'=>'Registro no encontrado.'] : []), $id && !$rows ? 404 : 200);
    }

    if ($resource === 'usuarios') {
        requireFields($data, ['nombre','cedula','correo','telefono']);
        if ($method === 'POST') { $s=$pdo->prepare('INSERT INTO usuarios(nombre,cedula,correo,telefono) VALUES(?,?,?,?)'); $s->execute([$data['nombre'],$data['cedula'],$data['correo'],$data['telefono']]); respond(['message'=>'Usuario creado','id'=>$pdo->lastInsertId()],201); }
        if ($method === 'PUT' && $id) { $s=$pdo->prepare('UPDATE usuarios SET nombre=?,cedula=?,correo=?,telefono=? WHERE id=?'); $s->execute([$data['nombre'],$data['cedula'],$data['correo'],$data['telefono'],$id]); respond(['message'=>'Usuario actualizado']); }
    }
    if ($resource === 'libros') {
        requireFields($data, ['codigo','titulo','autor','unidades']);
        if ($method === 'POST') { $s=$pdo->prepare('INSERT INTO libros(codigo,titulo,autor,unidades) VALUES(?,?,?,?)'); $s->execute([$data['codigo'],$data['titulo'],$data['autor'],(int)$data['unidades']]); respond(['message'=>'Libro creado','id'=>$pdo->lastInsertId()],201); }
        if ($method === 'PUT' && $id) { $s=$pdo->prepare('UPDATE libros SET codigo=?,titulo=?,autor=?,unidades=? WHERE id=?'); $s->execute([$data['codigo'],$data['titulo'],$data['autor'],(int)$data['unidades'],$id]); respond(['message'=>'Libro actualizado']); }
    }
    if ($resource === 'prestamos') {
        requireFields($data, ['libro_id','usuario_id']);
        if ($method === 'POST') {
            $pdo->beginTransaction();
            $s=$pdo->prepare('SELECT unidades FROM libros WHERE id=? FOR UPDATE'); $s->execute([(int)$data['libro_id']]); $book=$s->fetch();
            if (!$book || $book['unidades'] < 1) { $pdo->rollBack(); respond(['error'=>'El libro no tiene unidades disponibles.'],422); }
            $s=$pdo->prepare('INSERT INTO prestamos(libro_id,usuario_id,fecha_prestamo,fecha_devolucion,estado) VALUES(?,?,CURDATE(),?,?)'); $s->execute([(int)$data['libro_id'],(int)$data['usuario_id'],$data['fecha_devolucion'] ?? null,'Activo']);
            $pdo->prepare('UPDATE libros SET unidades=unidades-1 WHERE id=?')->execute([(int)$data['libro_id']]); $pdo->commit(); respond(['message'=>'Préstamo registrado','id'=>$pdo->lastInsertId()],201);
        }
        if ($method === 'PUT' && $id) {
            $s=$pdo->prepare('UPDATE prestamos SET fecha_devolucion=?,estado=? WHERE id=?'); $s->execute([$data['fecha_devolucion'] ?? date('Y-m-d'),$data['estado'] ?? 'Devuelto',$id]);
            if (($data['estado'] ?? '') === 'Devuelto') { $q=$pdo->prepare('SELECT libro_id FROM prestamos WHERE id=?'); $q->execute([$id]); if($r=$q->fetch()) $pdo->prepare('UPDATE libros SET unidades=unidades+1 WHERE id=?')->execute([$r['libro_id']]); }
            respond(['message'=>'Préstamo actualizado']);
        }
    }
    if ($method === 'DELETE' && $id) { $s=$pdo->prepare("DELETE FROM `$table` WHERE id=?"); $s->execute([$id]); respond(['message'=>'Registro eliminado']); }
    respond(['error'=>'Método o parámetros no válidos.'],405);
} catch (PDOException $e) { respond(['error'=>'Error de operación. Verifique datos duplicados o relaciones.'],400); }


