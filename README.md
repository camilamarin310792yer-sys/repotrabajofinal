# 📚 Biblioteca Aurora — PHP + MySQL

Aplicativo web para gestionar una biblioteca con **usuarios, libros y préstamos**.

## Funcionalidades

- Registrar usuarios: nombre, cédula y teléfono.
- Registrar libros: código, título, autor y unidades.
- Crear préstamos entre usuarios y libros.
- Descontar automáticamente una unidad al prestar.
- Registrar devolución y recuperar automáticamente una unidad.
- Consultar catálogo, lectores e historial.
- Eliminar usuarios y libros cuando no tengan préstamos que impidan la eliminación.
- Diseño visual creativo con fondos e imágenes de biblioteca/libros.
- Creación automática de las tablas si no existen.

## Instalación en AlwaysData

1. Sube `index.php`, `config.php` y este `README.md` a tu espacio web.
2. Comprueba que PHP esté habilitado.
3. Abre `index.php` desde tu dominio.
4. El archivo `config.php` intenta conectarse con:
   - Host: `ftp-hectorapi.alwaysdata.net`
   - Usuario: `hectorapi`
   - Base de datos: `hectorapi_usuario2db`
5. Al abrir la aplicación, las tablas `usuarios`, `libros` y `prestamos` se crean automáticamente si no existen.

## Estructura

```text
biblioteca_hectorapi/
├── config.php
├── index.php
└── README.md
```

## Tablas

### usuarios
- id
- nombre
- cedula
- telefono
- created_at

### libros
- id
- codigo
- titulo
- autor
- unidades
- created_at

### prestamos
- id
- usuario_id
- libro_id
- fecha_prestamo
- fecha_devolucion
- estado
- created_at

## Nota sobre imágenes

El diseño utiliza imágenes públicas de Unsplash mediante URLs externas para mantener el ZIP liviano. Si el servidor bloquea imágenes externas, puedes descargar imágenes libres y reemplazar las URLs `images.unsplash.com` del CSS por archivos locales.

## Seguridad

Para un proyecto académico se incluyen las credenciales entregadas para la conexión. En un sistema real, conviene guardar las credenciales fuera del directorio público y utilizar variables de entorno.
