# 📚 Biblioteca Aurora — PHP + MySQL

Aplicativo web para gestionar una biblioteca con tres módulos:

- **Usuarios:** nombre, cédula y teléfono.
- **Libros:** código, título, autor y unidades.
- **Préstamos:** relación entre libros y usuarios, control de préstamos y devoluciones.

## Requisitos

- PHP 7.4+ (recomendado PHP 8.x)
- MySQL/MariaDB
- PDO MySQL habilitado
- Hosting compatible con PHP, como AlwaysData.

## Instalación en AlwaysData

1. Crea o verifica la base de datos `yerissas_biblioteca`.
2. Sube todo el contenido de este ZIP a la carpeta pública de tu sitio.
3. Verifica que `config.php` tenga las credenciales proporcionadas.
4. Abre `index.php` desde tu dominio.
5. La aplicación ejecutará automáticamente `db.php` y creará las tablas si no existen.
6. También cargará algunos registros de demostración si las tablas están completamente vacías.

## Credenciales configuradas

- Host: `mysql-yerissas.alwaysdata.net`
- Usuario: `yerissas`
- Base de datos: `yerissas_biblioteca`

> La contraseña está configurada en `config.php` según las credenciales proporcionadas. Si compartes el proyecto con terceros, se recomienda cambiar la contraseña y no publicar credenciales reales.

## Tablas

### usuarios
`id`, `nombre`, `cedula`, `telefono`, `creado_en`

### libros
`id`, `codigo`, `titulo`, `autor`, `unidades`, `creado_en`

### prestamos
`id`, `libro_id`, `usuario_id`, `fecha_prestamo`, `fecha_devolucion`, `estado`, `creado_en`

Las relaciones usan claves foráneas entre préstamos, libros y usuarios.

## Funcionamiento

Cuando se registra un préstamo, las unidades disponibles del libro disminuyen en 1. Al registrar una devolución, aumentan en 1 y se almacena la fecha de devolución.

## Estructura

```text
biblioteca_php_mysql/
├── index.php
├── usuarios.php
├── libros.php
├── prestamos.php
├── config.php
├── db.php
├── README.md
└── assets/
    └── style.css
```

## Nota

Las imágenes decorativas de la interfaz se cargan desde Unsplash mediante CSS. Para un sitio que deba funcionar sin Internet, sustituye esas URLs por imágenes locales.
