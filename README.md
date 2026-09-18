# Biblioteca Nova — PHP + MySQL

Sistema sencillo para gestionar usuarios, libros y préstamos mediante una API REST en PHP y una interfaz HTML/CSS/JavaScript.

## Publicación

Suba todos los archivos al directorio web configurado en Alwaysdata. La página será accesible en:

`http://hectorapi.alwaysdata.net/index.html`

La API se encuentra en:

`http://hectorapi.alwaysdata.net/api/index.php`

## Base de datos

Ejecute `database.sql` en MySQL/phpMyAdmin. El archivo crea las tablas `usuarios`, `libros` y `prestamos`.

## Endpoints

- `GET api/index.php?resource=usuarios` — listar usuarios.
- `POST api/index.php?resource=usuarios` — crear usuario: `nombre, cedula, correo, telefono`.
- `PUT api/index.php?resource=usuarios&id=1` — actualizar usuario.
- `DELETE api/index.php?resource=usuarios&id=1` — eliminar usuario.
- `GET/POST/PUT/DELETE api/index.php?resource=libros` — CRUD de libros: `codigo, titulo, autor, unidades`.
- `GET api/index.php?resource=prestamos` — listar préstamos con datos del libro y usuario.
- `POST api/index.php?resource=prestamos` — crear préstamo: `libro_id, usuario_id, fecha_devolucion`.
- `PUT api/index.php?resource=prestamos&id=1` — actualizar devolución: `fecha_devolucion, estado`.
- `DELETE api/index.php?resource=prestamos&id=1` — eliminar préstamo.

## Nota de conexión

La configuración está en `api/config.php`. Por seguridad, en un proyecto real se recomienda usar variables de entorno y cambiar las credenciales si fueron compartidas públicamente.
