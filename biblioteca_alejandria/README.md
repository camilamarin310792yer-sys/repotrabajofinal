# Biblioteca de Alejandria

Aplicacion web para gestionar una biblioteca con usuarios, libros, inventario, precios y prestamos. Incluye API REST en PHP, base de datos MySQL y frontend HTML/CSS/JavaScript sin dependencias externas.

## Estructura

```text
biblioteca_alejandria/
  api/
    index.php
  config/
    database.php
  database/
    schema.sql
  public/
    index.html
    assets/
      css/styles.css
      js/app.js
  .htaccess
  README.md
```

## Requisitos

- PHP 8.0 o superior
- MySQL 8.0 o MariaDB compatible
- Apache con `mod_rewrite` o un servidor PHP local

## Instalacion

1. Copia la carpeta `biblioteca_alejandria` en tu servidor local o hosting.
2. Crea la base de datos ejecutando:

```sql
SOURCE database/schema.sql;
```

Tambien puedes importar `database/schema.sql` desde phpMyAdmin.

3. Configura la conexion en variables de entorno o edita `config/database.php`.

Variables soportadas:

```text
DB_HOST=localhost
DB_NAME=biblioteca_alejandria
DB_USER=root
DB_PASS=
```

4. Abre el frontend:

```text
public/index.html
```

Si usas Apache, sirve la carpeta completa para que `public/index.html` pueda llamar a `../api/index.php`.

## Endpoints REST

La API usa el parametro `resource`.

```text
GET    api/index.php?resource=usuarios
POST   api/index.php?resource=usuarios
PUT    api/index.php?resource=usuarios&id=1
DELETE api/index.php?resource=usuarios&id=1

GET    api/index.php?resource=libros
POST   api/index.php?resource=libros
PUT    api/index.php?resource=libros&id=1
DELETE api/index.php?resource=libros&id=1

GET    api/index.php?resource=prestamos
POST   api/index.php?resource=prestamos
PUT    api/index.php?resource=prestamos&id=1
DELETE api/index.php?resource=prestamos&id=1

GET    api/index.php?resource=inventario
```

## Modelo normalizado

- `usuarios`: datos de lectores: nombre, cedula, telefono.
- `autores`: catalogo unico de autores.
- `titulos`: titulos relacionados con autores.
- `libros`: codigo, unidades, unidades disponibles y precio, asociados a un titulo.
- `prestamos`: relaciona usuario y libro, con fechas, estado y observaciones.

## Funcionalidades

- CRUD de usuarios.
- CRUD de libros con codigo, titulo, autor, unidades y precio.
- CRUD de prestamos.
- Inventario con unidades totales, disponibles, prestadas y valor monetario.
- Descuento automatico de unidades disponibles al crear prestamos activos.
- Reintegro automatico de unidades al devolver o eliminar prestamos activos.
- Interfaz responsive inspirada en la Biblioteca de la antigua Alejandria.

## Notas de despliegue

En hosting compartido, coloca el proyecto completo en `public_html` o equivalente y ajusta credenciales de MySQL. Para mayor seguridad en produccion, restringe `Access-Control-Allow-Origin` en `api/index.php` al dominio real del sitio.
