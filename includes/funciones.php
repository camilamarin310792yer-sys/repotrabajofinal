<?php
/* ============================================================
   FUNCIONES DE APOYO: seguridad, mensajes, formato
   ============================================================ */
require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escapa texto para mostrarlo en HTML (evita inyección de código) */
function e($texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/** Token CSRF: impide que otra web envíe formularios en nombre del usuario */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!hash_equals(csrf_token(), $enviado)) {
        http_response_code(400);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
}

/** Mensajes que sobreviven a una redirección (éxito / error) */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function leer_flash(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Fecha y hora actual calculada en PHP (zona Bogotá) */
function ahora(): string
{
    return date('Y-m-d H:i:s');
}

function hoy(): string
{
    return date('Y-m-d');
}

/** Formato de fecha legible: 17 sep 2026 */
function fecha_corta(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($fecha);
    return date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

/** Convierte un número a numeral romano (decorativo) */
function romano(int $n): string
{
    if ($n <= 0) {
        return 'N';   // "nulla": los romanos usaban N para el cero
    }
    $mapa = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90,
             'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $r = '';
    foreach ($mapa as $letra => $valor) {
        while ($n >= $valor) {
            $r .= $letra;
            $n -= $valor;
        }
    }
    return $r;
}

/** Traduce errores de MySQL a mensajes entendibles */
function mensaje_error_bd(PDOException $e, string $duplicado): string
{
    // 23000 = violación de restricción (clave única duplicada)
    if ($e->getCode() === '23000' && strpos($e->getMessage(), '1062') !== false) {
        return $duplicado;
    }
    return 'Error en la base de datos: ' . $e->getMessage();
}
