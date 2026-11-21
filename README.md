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
- Niveles con video y material PDF, avance al llegar al final del video, diploma y reseñas de cursos completados.
- Inscripciones gratuitas, pagos con tarjeta y efectivo en OXXO mediante Stripe, y reportes desde los tres paneles.

## Requisitos

XAMPP con Apache (módulo `mod_rewrite` activo y `AllowOverride All`), PHP 8.1 o superior y MySQL o MariaDB.

## Instalación

1. Clona el repositorio dentro de la carpeta `htdocs` de XAMPP e inicia Apache y MySQL.
2. En phpMyAdmin importa `proyecto/database/schema.sql`. Ojo: ese archivo borra y vuelve a crear la base `walteredu`.
3. Copia `proyecto/.env.example` como `proyecto/.env` y ajusta los datos de conexión si hace falta.
4. Abre `http://localhost/<carpeta-del-repositorio>/proyecto/`.

Si ya tienes la base de la rama `feature/pago-propio-y-paneles`, aplica `proyecto/database/migracion_revision_final.sql` para conservar tus datos. Si tu base es anterior al cambio a PaymentIntents y conserva el esquema de Stripe Checkout, aplica primero `migracion_pagos_intent.sql`. El archivo `schema.sql` es para instalaciones nuevas.

Apache debe poder escribir en `proyecto/almacen/`. La aplicación admite MP4 y WebM de hasta 100 MB, y PDF de hasta 15 MB. PHP también aplica sus propios límites de carga. Si usas archivos grandes en XAMPP, ajusta `upload_max_filesize` y `post_max_size` en `php.ini` y reinicia Apache. El límite de la petición debe admitir ambos archivos y los campos del formulario.

Para demostrar pagos, usa `STRIPE_SECRET_KEY` y `STRIPE_PUBLISHABLE_KEY` de prueba del mismo entorno. Habilita tarjeta y OXXO en los [métodos de pago del Dashboard de Stripe](https://dashboard.stripe.com/settings/payment_methods) de ese entorno. WalterEdu ofrece los métodos habilitados que admite para ese importe. OXXO está disponible de $10.00 a $10,000.00 MXN. Para recibir la confirmación sin que el alumno vuelva al portal, reenvía el evento `payment_intent.succeeded` al webhook con Stripe CLI y configura su secreto:

```sh
stripe listen --events payment_intent.succeeded --forward-to http://localhost/WalterEdu/proyecto/index.php?ruta=stripe-webhook
```

Guarda el secreto que muestra Stripe CLI como `STRIPE_WEBHOOK_SECRET` en el `.env`. La pantalla de pago también permite consultar un cobro pendiente después de pagar la referencia.

## Cuentas

El formulario de registro crea cuentas de estudiante o instructor. Para tener un administrador, registra una cuenta y cambia su rol en la base de datos:

```sql
UPDATE usuarios SET rol = 'administrador' WHERE email = 'tu@correo.com';
```

## API

[Documentación de la API](docs/api.md) · [Colección de Postman](docs/WalterEdu.postman_collection.json)

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
