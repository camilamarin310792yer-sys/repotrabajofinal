<?php
/* ============================================================
   CONFIGURACIÓN GENERAL
   Cambia aquí las credenciales si mueves la base de datos.
   Este archivo NO debe quedar accesible desde el navegador
   (la carpeta includes/ trae un .htaccess que lo bloquea).
   ============================================================ */

define('DB_HOST', 'mysql-naderzonweb.alwaysdata.net');
define('DB_USER', 'naderzonweb');
define('DB_PASS', 'clase1234');
define('DB_NAME', 'naderzonweb_trabajofinal');

// Nombre visible de la biblioteca
define('APP_NOMBRE', 'Bibliotheca Palatina');

// Días de préstamo por defecto
define('DIAS_PRESTAMO', 15);

// Zona horaria de Colombia (las fechas se calculan en PHP, no en MySQL)
date_default_timezone_set('America/Bogota');
