<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = Database::connection();
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    route($pdo, $method, $resource, $id);
} catch (Throwable $error) {
    jsonResponse(['error' => $error->getMessage()], 500);
}

function route(PDO $pdo, string $method, string $resource, ?int $id): void
{
    if ($resource === 'usuarios') {
        handleUsuarios($pdo, $method, $id);
        return;
    }

    if ($resource === 'libros') {
        handleLibros($pdo, $method, $id);
        return;
    }

    if ($resource === 'prestamos') {
        handlePrestamos($pdo, $method, $id);
        return;
    }

    if ($resource === 'inventario') {
        handleInventario($pdo);
        return;
    }

    jsonResponse(['error' => 'Recurso no encontrado'], 404);
}

function handleUsuarios(PDO $pdo, string $method, ?int $id): void
{
    if ($method === 'GET') {
        $sql = 'SELECT id, nombre, cedula, telefono FROM usuarios ORDER BY nombre';
        jsonResponse($pdo->query($sql)->fetchAll());
        return;
    }

    $data = input();

    if ($method === 'POST') {
        requireFields($data, ['nombre', 'cedula', 'telefono']);
        $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, cedula, telefono) VALUES (?, ?, ?)');
        $stmt->execute([$data['nombre'], $data['cedula'], $data['telefono']]);
        jsonResponse(['id' => (int) $pdo->lastInsertId()], 201);
        return;
    }

    if ($method === 'PUT' && $id) {
        requireFields($data, ['nombre', 'cedula', 'telefono']);
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, cedula = ?, telefono = ? WHERE id = ?');
        $stmt->execute([$data['nombre'], $data['cedula'], $data['telefono'], $id]);
        jsonResponse(['ok' => true]);
        return;
    }

    if ($method === 'DELETE' && $id) {
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
        return;
    }

    jsonResponse(['error' => 'Metodo no permitido'], 405);
}

function handleLibros(PDO $pdo, string $method, ?int $id): void
{
    if ($method === 'GET') {
        $sql = "SELECT l.id, l.codigo, t.titulo, a.nombre AS autor, l.unidades,
                       l.unidades_disponibles, l.precio
                FROM libros l
                JOIN titulos t ON t.id = l.titulo_id
                JOIN autores a ON a.id = t.autor_id
                ORDER BY t.titulo, l.codigo";
        jsonResponse($pdo->query($sql)->fetchAll());
        return;
    }

    $data = input();

    if ($method === 'POST') {
        requireFields($data, ['codigo', 'titulo', 'autor', 'unidades', 'precio']);
        $bookId = saveBook($pdo, $data);
        jsonResponse(['id' => $bookId], 201);
        return;
    }

    if ($method === 'PUT' && $id) {
        requireFields($data, ['codigo', 'titulo', 'autor', 'unidades', 'precio']);
        updateBook($pdo, $id, $data);
        jsonResponse(['ok' => true]);
        return;
    }

    if ($method === 'DELETE' && $id) {
        $stmt = $pdo->prepare('DELETE FROM libros WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
        return;
    }

    jsonResponse(['error' => 'Metodo no permitido'], 405);
}

function handlePrestamos(PDO $pdo, string $method, ?int $id): void
{
    if ($method === 'GET') {
        $sql = "SELECT p.id, p.usuario_id, p.libro_id, p.fecha_prestamo, p.fecha_devolucion,
                       p.estado, p.observaciones, u.nombre AS usuario, u.cedula,
                       l.codigo, t.titulo
                FROM prestamos p
                JOIN usuarios u ON u.id = p.usuario_id
                JOIN libros l ON l.id = p.libro_id
                JOIN titulos t ON t.id = l.titulo_id
                ORDER BY p.fecha_prestamo DESC, p.id DESC";
        jsonResponse($pdo->query($sql)->fetchAll());
        return;
    }

    $data = input();

    if ($method === 'POST') {
        requireFields($data, ['usuario_id', 'libro_id', 'fecha_prestamo']);
        createLoan($pdo, $data);
        jsonResponse(['ok' => true], 201);
        return;
    }

    if ($method === 'PUT' && $id) {
        requireFields($data, ['usuario_id', 'libro_id', 'fecha_prestamo', 'estado']);
        updateLoan($pdo, $id, $data);
        jsonResponse(['ok' => true]);
        return;
    }

    if ($method === 'DELETE' && $id) {
        deleteLoan($pdo, $id);
        jsonResponse(['ok' => true]);
        return;
    }

    jsonResponse(['error' => 'Metodo no permitido'], 405);
}

function handleInventario(PDO $pdo): void
{
    $sql = "SELECT l.id, l.codigo, t.titulo, a.nombre AS autor, l.unidades,
                   l.unidades_disponibles, (l.unidades - l.unidades_disponibles) AS prestadas,
                   l.precio, (l.unidades * l.precio) AS valor_inventario
            FROM libros l
            JOIN titulos t ON t.id = l.titulo_id
            JOIN autores a ON a.id = t.autor_id
            ORDER BY t.titulo";
    jsonResponse($pdo->query($sql)->fetchAll());
}

function saveBook(PDO $pdo, array $data): int
{
    $pdo->beginTransaction();
    try {
        $authorId = authorId($pdo, trim((string) $data['autor']));
        $titleId = titleId($pdo, trim((string) $data['titulo']), $authorId);
        $units = max(0, (int) $data['unidades']);
        $price = max(0, (float) $data['precio']);

        $stmt = $pdo->prepare(
            'INSERT INTO libros (codigo, titulo_id, unidades, unidades_disponibles, precio)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([trim((string) $data['codigo']), $titleId, $units, $units, $price]);
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function updateBook(PDO $pdo, int $id, array $data): void
{
    $pdo->beginTransaction();
    try {
        $current = rowById($pdo, 'libros', $id);
        $authorId = authorId($pdo, trim((string) $data['autor']));
        $titleId = titleId($pdo, trim((string) $data['titulo']), $authorId);
        $units = max(0, (int) $data['unidades']);
        $borrowed = max(0, (int) $current['unidades'] - (int) $current['unidades_disponibles']);
        $available = max(0, $units - $borrowed);
        $price = max(0, (float) $data['precio']);

        $stmt = $pdo->prepare(
            'UPDATE libros
             SET codigo = ?, titulo_id = ?, unidades = ?, unidades_disponibles = ?, precio = ?
             WHERE id = ?'
        );
        $stmt->execute([trim((string) $data['codigo']), $titleId, $units, $available, $price, $id]);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function createLoan(PDO $pdo, array $data): void
{
    $pdo->beginTransaction();
    try {
        $book = rowById($pdo, 'libros', (int) $data['libro_id']);
        if ((int) $book['unidades_disponibles'] <= 0) {
            throw new RuntimeException('No hay unidades disponibles para este libro.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo, fecha_devolucion, estado, observaciones)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int) $data['usuario_id'],
            (int) $data['libro_id'],
            $data['fecha_prestamo'],
            empty($data['fecha_devolucion']) ? null : $data['fecha_devolucion'],
            $data['estado'] ?? 'activo',
            $data['observaciones'] ?? null,
        ]);

        if (($data['estado'] ?? 'activo') === 'activo') {
            changeAvailableUnits($pdo, (int) $data['libro_id'], -1);
        }

        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function updateLoan(PDO $pdo, int $id, array $data): void
{
    $pdo->beginTransaction();
    try {
        $old = rowById($pdo, 'prestamos', $id);

        if ($old['estado'] === 'activo') {
            changeAvailableUnits($pdo, (int) $old['libro_id'], 1);
        }

        if ($data['estado'] === 'activo') {
            $book = rowById($pdo, 'libros', (int) $data['libro_id']);
            if ((int) $book['unidades_disponibles'] <= 0) {
                throw new RuntimeException('No hay unidades disponibles para este libro.');
            }
            changeAvailableUnits($pdo, (int) $data['libro_id'], -1);
        }

        $stmt = $pdo->prepare(
            'UPDATE prestamos
             SET usuario_id = ?, libro_id = ?, fecha_prestamo = ?, fecha_devolucion = ?,
                 estado = ?, observaciones = ?
             WHERE id = ?'
        );
        $stmt->execute([
            (int) $data['usuario_id'],
            (int) $data['libro_id'],
            $data['fecha_prestamo'],
            empty($data['fecha_devolucion']) ? null : $data['fecha_devolucion'],
            $data['estado'],
            $data['observaciones'] ?? null,
            $id,
        ]);

        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function deleteLoan(PDO $pdo, int $id): void
{
    $pdo->beginTransaction();
    try {
        $loan = rowById($pdo, 'prestamos', $id);
        if ($loan['estado'] === 'activo') {
            changeAvailableUnits($pdo, (int) $loan['libro_id'], 1);
        }
        $stmt = $pdo->prepare('DELETE FROM prestamos WHERE id = ?');
        $stmt->execute([$id]);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function authorId(PDO $pdo, string $name): int
{
    $stmt = $pdo->prepare('SELECT id FROM autores WHERE nombre = ?');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }

    $stmt = $pdo->prepare('INSERT INTO autores (nombre) VALUES (?)');
    $stmt->execute([$name]);
    return (int) $pdo->lastInsertId();
}

function titleId(PDO $pdo, string $title, int $authorId): int
{
    $stmt = $pdo->prepare('SELECT id FROM titulos WHERE titulo = ? AND autor_id = ?');
    $stmt->execute([$title, $authorId]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }

    $stmt = $pdo->prepare('INSERT INTO titulos (titulo, autor_id) VALUES (?, ?)');
    $stmt->execute([$title, $authorId]);
    return (int) $pdo->lastInsertId();
}

function rowById(PDO $pdo, string $table, int $id): array
{
    $allowed = ['libros', 'prestamos'];
    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Tabla no permitida.');
    }

    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('Registro no encontrado.');
    }
    return $row;
}

function changeAvailableUnits(PDO $pdo, int $bookId, int $delta): void
{
    $stmt = $pdo->prepare(
        'UPDATE libros
         SET unidades_disponibles = unidades_disponibles + ?
         WHERE id = ? AND unidades_disponibles + ? BETWEEN 0 AND unidades'
    );
    $stmt->execute([$delta, $bookId, $delta]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('No fue posible actualizar el inventario.');
    }
}

function input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) {
        jsonResponse(['error' => 'JSON invalido'], 400);
    }
    return $data;
}

function requireFields(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!array_key_exists($field, $data) || $data[$field] === '') {
            jsonResponse(['error' => "Campo requerido: {$field}"], 422);
        }
    }
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
