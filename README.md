# Biblioteca Romana — PHP + MySQL

Aplicativo web para gestionar una biblioteca con estética de biblioteca romana.

## Funciones
- Usuarios: nombre, cédula y teléfono.
- Libros: código, título, autor y unidades disponibles.
- Préstamos: asociación entre usuario y libro, cantidad y fecha.
- Devoluciones: cambia el estado y repone automáticamente las unidades.
- Validación de inventario para impedir préstamos por encima de las existencias.
- Creación automática de tablas si no existen.
- Diseño responsive inspirado en mármol, columnas y arquitectura clásica romana.

## Instalación
1. Sube todos los archivos a tu hosting PHP.
2. Verifica que el hosting permita conexión MySQL y que la base de datos exista.
3. Abre `install.php` una vez. El sistema creará las tablas.
4. Luego abre `index.php`.

Las credenciales están centralizadas en `config.php`.

## Tablas
- `usuarios`
- `libros`
- `prestamos`

## Requisitos
- PHP 7.4+ recomendado (PHP 8.x ideal).
- Extensión PDO MySQL habilitada.
- MySQL/MariaDB.

## Seguridad
Para producción, se recomienda mover las credenciales fuera del directorio público, usar variables de entorno y agregar autenticación de administradores.
