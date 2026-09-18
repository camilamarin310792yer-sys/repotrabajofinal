# 📚 Sistema de Gestión de Biblioteca — PHP + MySQL

Aplicación web para gestionar una biblioteca: **usuarios**, **libros** y **préstamos**, con diseño temático (colores madera/cuero, portadas de libros e imágenes de biblioteca).

## Estructura del proyecto

```
biblioteca/
├── config/
│   └── db.php            # Conexión PDO + creación automática de tablas
├── includes/
│   ├── header.php        # Navbar y <head> común
│   └── footer.php        # Pie de página común
├── assets/
│   └── css/style.css     # Estilos con temática de biblioteca
├── index.php              # Dashboard con estadísticas
├── usuarios.php           # CRUD de usuarios (nombre, cédula, teléfono)
├── libros.php              # CRUD de libros (código, título, autor, unidades) + portadas
├── prestamos.php           # Registro de préstamos y devoluciones
└── README.md
```

## Requisitos

- PHP 7.4 o superior con extensión **PDO MySQL** habilitada.
- Acceso a un servidor MySQL (ya configurado con tus credenciales en `config/db.php`).
- Conexión a internet en el navegador del usuario final (las imágenes de portadas y fondos se cargan desde Unsplash mediante CDN, no se almacenan localmente).

## Instalación

1. Descomprime el archivo `biblioteca.zip`.
2. Sube la carpeta `biblioteca/` completa a tu hosting (por ejemplo, a la carpeta `www/` de AlwaysData, o cualquier servidor con PHP).
3. Las credenciales de conexión ya están configuradas en `config/db.php`:
   - Host: `ftp-hectorapi.alwaysdata.net`
   - Base de datos: `hectorapi_usuario2db`
   - Usuario: `hectorapi`
4. Abre `index.php` desde el navegador (por ejemplo `https://tu-dominio/biblioteca/index.php`).
5. **Las tablas `usuarios`, `libros` y `prestamos` se crean automáticamente** la primera vez que se ejecuta cualquier página (no necesitas ejecutar ningún script SQL manual).

## Funcionalidades

### 🧑 Usuarios
- Registrar, editar, eliminar y buscar usuarios (nombre, cédula, teléfono).
- La cédula es única: no se permiten duplicados.

### 📘 Libros
- Registrar, editar, eliminar y buscar libros (código, título, autor, unidades totales).
- El sistema calcula automáticamente las **unidades disponibles**.
- Puedes asignar una URL de portada personalizada o elegir una de la galería incluida; si no seleccionas ninguna, se asigna una portada automática de la galería temática.

### 🔄 Préstamos
- Registrar un préstamo seleccionando libro y usuario (solo libros con unidades disponibles > 0 aparecen en la lista).
- Al prestar, se descuenta 1 unidad disponible; al devolver, se repone.
- Filtro por estado: Todos / Prestados / Devueltos.
- Se marca automáticamente como **Vencido** si la fecha de devolución esperada ya pasó y sigue prestado.

## Notas técnicas

- Conexión mediante **PDO** con sentencias preparadas (protección contra inyección SQL).
- Manejo de transacciones (`beginTransaction`/`commit`/`rollBack`) al prestar/devolver libros para mantener la consistencia entre `libros` y `prestamos`.
- Mensajes de confirmación/error mostrados mediante sesiones PHP (`$_SESSION['flash']`).
- Diseño responsivo con Bootstrap 5 + Font Awesome + Google Fonts (todo vía CDN).
- Imágenes de fondo y portadas cargadas desde Unsplash (puedes reemplazarlas por tus propias URLs en cualquier momento editando `assets/css/style.css` o la galería en `libros.php`).

## Créditos de imágenes

Todas las imágenes provienen de [Unsplash](https://unsplash.com) (uso libre) y se cargan dinámicamente vía URL — no se incluyen archivos binarios de imagen en el proyecto.
