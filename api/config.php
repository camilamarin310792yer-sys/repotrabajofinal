<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$host = 'mysql-hectorapi.alwaysdata.net';
$user = 'hectorapi';
$pass = 'clase1234';
$db   = 'hectorapi_usuario2db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar a la base de datos.']);
    exit;
}

function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function respond($data, int $status = 200): void { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function requireFields(array $data, array $fields): void { foreach ($fields as $f) if (!isset($data[$f]) || trim((string)$data[$f]) === '') respond(['error' => "Campo requerido: $f"], 422); }
