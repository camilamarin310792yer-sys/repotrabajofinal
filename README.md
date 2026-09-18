# 📚 Biblioteca — PHP + MySQL

Aplicación web para administrar usuarios, libros y préstamos de una biblioteca. Está preparada para desplegarse en un servidor PHP/MySQL como AlwaysData.

## Tecnologías
- PHP 8+ recomendado
- MySQL / MariaDB
- PDO con consultas preparadas
- HTML5 + CSS3
- JavaScript
- Diseño responsive

## Estructura
```text
biblioteca/
├── index.php
├── config.php
├── header.php
├── footer.php
├── usuarios.php
├── libros.php
├── prestamos.php
├── devolucion.php
├── css/style.css
├── js/script.js
├── sql/biblioteca.sql
└── README.md
```

## Base de datos
La aplicación usa:
- Host: `mysql-jojoapp.alwaysdata.net`
- Usuario: `jojoapp`
- Base de datos: `jojoapp_biblioteca`

La clave se encuentra en `config.php`. Por seguridad, en un proyecto real conviene utilizar variables de entorno o un mecanismo de secretos del proveedor.

Al cargar `config.php`, la aplicación crea automáticamente las tres tablas si todavía no existen.

## Despliegue en AlwaysData
1. Crea/usa la base de datos MySQL `jojoapp_biblioteca` en AlwaysData.
2. Comprueba que el usuario `jojoapp` tenga permisos sobre esa base de datos.
3. Sube el contenido de esta carpeta al directorio web de tu sitio mediante FileZilla, Git o el administrador de archivos disponible.
4. Abre `index.php` desde la URL de tu sitio.
5. Si aparece un error de conexión, revisa host, usuario, clave, nombre de base de datos y permisos.
6. No es necesario importar el SQL para el primer arranque, porque `config.php` crea las tablas automáticamente. El archivo SQL se incluye para instalación manual/respaldo.

## Módulos
### Dashboard
Muestra totales de usuarios, libros, unidades disponibles, préstamos activos y devueltos, además de accesos rápidos y actividad reciente.

### Usuarios
CRUD completo:
- Crear
- Consultar/listar
- Editar
- Eliminar

La cédula es única.

### Libros
CRUD completo:
- Crear
- Consultar/listar
- Editar
- Eliminar

El código es único y las unidades no pueden ser negativas.

### Préstamos
Un préstamo relaciona un usuario y un libro. Al prestar:
- Se verifica que ambos registros existan.
- Se bloquea temporalmente la fila del libro durante la operación.
- Se comprueba disponibilidad.
- Se crea el préstamo.
- Se descuenta una unidad.

La operación se realiza en una transacción.

### Devoluciones
Desde `prestamos.php` se puede devolver un préstamo activo. La aplicación:
- Verifica que exista.
- Impide devolverlo nuevamente.
- Marca el estado como `Devuelto`.
- Guarda la fecha de devolución.
- Incrementa una unidad del libro.
Todo se realiza en una transacción.

## Relaciones
`prestamos.usuario_id` referencia `usuarios.id` y `prestamos.libro_id` referencia `libros.id`.

Las eliminaciones de usuarios/libros relacionados con préstamos son restringidas para conservar la integridad histórica.

## Rutas principales
- `index.php` — Dashboard
- `usuarios.php` — CRUD de usuarios
- `libros.php` — CRUD de libros
- `prestamos.php` — préstamos, historial y devoluciones
- `prestamos.php?accion=nuevo` — formulario de préstamo
- `prestamos.php?estado=Activo` — préstamos activos
- `prestamos.php?estado=Devuelto` — préstamos devueltos
- `devolucion.php` — redirección de compatibilidad al módulo de préstamos

## Prueba recomendada
1. Registra un usuario.
2. Registra un libro con 2 unidades.
3. Crea un préstamo para ese usuario/libro.
4. Comprueba que las unidades bajen a 1.
5. Devuelve el préstamo.
6. Comprueba que las unidades vuelvan a 2.
7. Intenta devolverlo otra vez: la aplicación debe impedirlo.
8. Intenta registrar una cédula o código duplicado: debe aparecer un mensaje de error.

## Nota de seguridad
Las credenciales entregadas para este trabajo quedan configuradas en `config.php` para facilitar el despliegue solicitado. Si el repositorio será público, se recomienda cambiar la contraseña y no publicar credenciales reales.
