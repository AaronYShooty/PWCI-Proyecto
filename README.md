# PWCI-Proyecto
Proyecto de Programación Web de Capa Intermedia

Hecho por:

Aarón Ernesto Ibarra Villarreal - 2053029

Sinuhe Martinez Hernandez - 1955659

## Walter, portal de cursos en línea

Portal web de cursos en línea con tres perfiles (estudiante, instructor y administrador). Está hecho en PHP puro con programación orientada a objetos y el patrón MVC, con MySQL como base de datos y sin frameworks de PHP. El documento de propuesta está en `PWCI documento Propuesta.pdf`.

## Qué incluye

- Registro e inicio de sesión por rol, con contraseña cifrada y bloqueo de la cuenta al tercer intento fallido.
- Middleware de control de acceso (sesión y rol) y de CORS.
- API propia en JSON para cursos (consulta, alta, modificación y baja) y categorías (consulta y alta).
- Catálogo público, creación de cursos y gestión de categorías, que consumen la API con `fetch`.

## Requisitos

XAMPP con Apache (módulo `mod_rewrite` activo y `AllowOverride All`), PHP 8.1 o superior y MySQL o MariaDB.

## Instalación

1. Clona el repositorio dentro de la carpeta `htdocs` de XAMPP e inicia Apache y MySQL.
2. En phpMyAdmin importa `proyecto/database/schema.sql`. Ojo: ese archivo borra y vuelve a crear la base `walteredu`.
3. Copia `proyecto/.env.example` como `proyecto/.env` y ajusta los datos de conexión si hace falta.
4. Abre `http://localhost/<carpeta-del-repositorio>/proyecto/`.

## Cuentas

El formulario de registro crea cuentas de estudiante o instructor. Para tener un administrador, registra una cuenta y cambia su rol en la base de datos:

```sql
UPDATE usuarios SET rol = 'administrador' WHERE email = 'tu@correo.com';
```

## API

Todas las respuestas usan la misma envoltura:

```json
{ "exito": true, "mensaje": "Consulta realizada correctamente", "datos": [], "errores": [] }
```

| Método | Ruta | Acceso |
|---|---|---|
| GET | `/api/cursos` (filtros: `categoria`, `titulo`, `nivel`, `publicador`, `fecha_inicio`, `fecha_fin`, `orden`, `pagina`, `por_pagina`) | Público |
| GET | `/api/cursos/{id}` | Público |
| POST | `/api/cursos` | Instructor o administrador |
| PUT, PATCH, DELETE | `/api/cursos/{id}` | Dueño del curso o administrador |
| GET | `/api/categorias` | Público |
| POST | `/api/categorias` | Administrador |

Códigos de estado: 200, 201, 400, 401, 403, 404, 405, 415 y 500.

## Estructura

```
proyecto/
  index.php        punto de entrada y enrutador de las pantallas
  api/             puntos de entrada de la API
  config/          conexión a MySQL y autocarga de clases
  controladores/   controladores de pantallas y de la API
  modelos/         acceso a datos (solo aquí hay consultas)
  middleware/      control de acceso y CORS
  vistas/          pantallas por perfil
  publico/         css, js e imágenes
  database/        esquema de la base de datos
```
