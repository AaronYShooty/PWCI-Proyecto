# API de WalterEdu

URL base local `http://localhost/WalterEdu/proyecto/`. Ajusta `baseUrl` en la [colección de Postman](WalterEdu.postman_collection.json) para otra carpeta o puerto.

Las rutas privadas usan la cookie `PHPSESSID` que devuelve `POST /api/sesiones`. Postman conserva la cookie dentro de su almacén. Si usas cURL, puedes guardarla con `-c cookies.txt` y enviarla con `-b cookies.txt`. Envía `Content-Type: application/json` en las operaciones con cuerpo JSON. Las fechas de filtro usan `AAAA-MM-DD`. Los IDs de los ejemplos son ilustrativos.

Para correr la colección, configura `baseUrl` sin diagonal final y las credenciales de cuentas de prueba para los tres roles. Para el grupo de video y avance usa un curso publicado de dos niveles, el ID de un PDF del primer nivel y una cuenta estudiantil inscrita que aún no haya publicado su reseña.

Las respuestas JSON tienen `exito`, `mensaje`, `datos` y `errores`. Cada error de campo indica `campo` y `descripcion`. Según la ruta y el método, también se puede devolver `405` por método no permitido, `415` por formato de cuerpo incorrecto, `500` por error interno o `503` si no se puede validar la sesión. Un origen distinto del configurado recibe `403`. Las solicitudes `OPTIONS` de un origen permitido reciben `204`, sin cuerpo.

## Sesión

| Petición | Respuesta de ejemplo | Acceso y códigos |
|---|---|---|
| `POST /api/sesiones` con `{"correo":"alumna@example.test","password":"Ejemplo2026!"}` | `200` con `{"exito":true,"mensaje":"Inicio de sesión exitoso","datos":{"id":7,"nombre":"Ana Pérez","rol":"estudiante","foto":null},"errores":[]}` y la cookie de sesión. | Público. `400` por campos inválidos, `401` por credenciales incorrectas o cuenta bloqueada. |
| `DELETE /api/sesiones` con cookie y sin cuerpo | `200` con `{"exito":true,"mensaje":"Sesión cerrada correctamente","datos":[],"errores":[]}` | Cualquier cuenta activa. `401` sin sesión vigente. |

## Cursos

Los filtros opcionales de la colección son `titulo` (búsqueda parcial), `categoria` e `publicador` por ID, `nivel` de 1 a 5, `fecha_inicio` y `fecha_fin` inclusivas, `orden` (`recientes`, `calificacion` o `ventas`), `pagina` (1 por omisión) y `por_pagina` (10 por omisión, máximo 50). El catálogo incluye cursos activos.

En el alta y en la sustitución completa se requieren `titulo`, `descripcion`, `nivel`, `precio` y `categorias` (arreglo con uno o más IDs). `imagen` es opcional y admite una ruta o URL. El precio es `0` para un curso gratuito, o un monto en MXN entre 10.00 y 999999.99 con hasta dos decimales. En el alta el curso se guarda como borrador. Para publicarlo, el instructor agrega sus videos desde **Editar niveles**. En una modificación, `activo` es opcional y omitirlo conserva el estado.

Los ejemplos de modificación muestran los campos de `datos` relevantes. La API devuelve el objeto completo del curso, como en el ejemplo de detalle. El borrador tiene `fecha_publicacion:null` hasta su primera publicación.

El editor del instructor también acepta `POST /api/cursos` y `POST /api/cursos/{id}` con `multipart/form-data` para subir un banner en JPG, PNG o WebP de hasta 5 MB. Envía el archivo en `portada`, un objeto JSON serializado en `datos` y el campo `csrf` del formulario de edición. El propietario y el administrador pueden editar el curso por API. El banner subido establece el campo `imagen`, que sigue aceptando rutas o enlaces en las solicitudes JSON.

Por ejemplo, `POST /api/cursos/12` con `datos={}`, `csrf` del editor y `portada=@banner.png` devuelve `200` con `{"exito":true,"mensaje":"Curso actualizado correctamente","datos":{"id_curso":12,"imagen":"publico/uploads/portadas/a123456789abcdef123456789abcdef0.png"},"errores":[]}`. En el alta con los campos obligatorios devuelve `201`. Un archivo inválido devuelve `400`. El token o los permisos inválidos devuelven `403`.

| Petición | Respuesta de ejemplo | Acceso y códigos |
|---|---|---|
| `GET /api/cursos?titulo=foto&categoria=2&publicador=3&fecha_inicio=2026-10-01&fecha_fin=2026-12-31&orden=recientes` | `200` con `{"exito":true,"mensaje":"Consulta realizada correctamente","datos":[],"errores":[]}` cuando no hay coincidencias. Los resultados usan el mismo objeto que el detalle. | Público. `400` por filtros inválidos. |
| `GET /api/cursos/12` | `200` con `{"exito":true,"mensaje":"Consulta realizada correctamente","datos":{"id_curso":12,"titulo":"Fotografía","descripcion":"Fundamentos","nivel":1,"precio":150.0,"imagen":null,"id_instructor":3,"instructor":"Luis Reyes","foto_instructor":null,"fecha_publicacion":"2026-10-01 00:00:00","categorias":["Arte"],"ids_categorias":[2],"promedio_calificacion":4.5,"activo":true},"errores":[]}` | Público. `404` para ID inexistente o curso fuera del catálogo. |
| `POST /api/cursos` con `{"titulo":"Fotografía","descripcion":"Fundamentos","nivel":1,"precio":150,"categorias":[2],"activo":false}` | `201` con `{"exito":true,"mensaje":"Curso creado correctamente","datos":{"id_curso":13,"titulo":"Fotografía","activo":false,"fecha_publicacion":null},"errores":[]}` | Instructor o administrador. `400` por campos inválidos, `401` sin sesión, `403` por rol, `409` al intentar publicar en el alta. |
| `PUT /api/cursos/12` con `{"titulo":"Fotografía digital","descripcion":"Luz y encuadre","nivel":2,"precio":150,"categorias":[2],"imagen":null}` | `200` con `{"exito":true,"mensaje":"Curso actualizado correctamente","datos":{"id_curso":12,"titulo":"Fotografía digital","nivel":2},"errores":[]}` | Dueño o administrador. `400`, `401`, `403`, `404` y `409` si falta cargar el contenido para activar el curso. |
| `PATCH /api/cursos/12` con `{"precio":180}` | `200` con `{"exito":true,"mensaje":"Curso actualizado correctamente","datos":{"id_curso":12,"precio":180.0},"errores":[]}` | Dueño o administrador. Requiere al menos un campo editable. `400`, `401`, `403`, `404`, `409`. |
| `DELETE /api/cursos/12` sin cuerpo | `200` con `{"exito":true,"mensaje":"Curso dado de baja correctamente","datos":{"id_curso":12,"activo":false},"errores":[]}` | Dueño o administrador. Baja lógica. `401`, `403`, `404`. |

## Categorías

El nombre debe tener de 1 a 80 caracteres y ser único. La descripción es opcional y acepta hasta 1,000 caracteres. Los cursos conservan al menos una categoría asignada.

| Petición | Respuesta de ejemplo | Acceso y códigos |
|---|---|---|
| `GET /api/categorias` | `200` con `{"exito":true,"mensaje":"Consulta realizada correctamente","datos":[],"errores":[]}` si la lista está vacía. | Público. |
| `GET /api/categorias/2` | `200` con `{"exito":true,"mensaje":"Consulta realizada correctamente","datos":{"id_categoria":2,"nombre":"Arte","descripcion":null,"creada_por":"Administradora","fecha_creacion":"2026-10-01 00:00:00","total_cursos":4},"errores":[]}` | Público. `404` si la categoría no existe. |
| `POST /api/categorias` con `{"nombre":"Arte","descripcion":"Cursos creativos"}` | `201` con `{"exito":true,"mensaje":"Categoría creada correctamente.","datos":{"id_categoria":2,"nombre":"Arte","descripcion":"Cursos creativos","creada_por":"Administradora","fecha_creacion":"2026-10-01 00:00:00","total_cursos":0},"errores":[]}` | Administrador. `400` por campos inválidos, `401`, `403`, `409` si el nombre se registró paralelamente. |
| `PUT /api/categorias/2` con `{"nombre":"Arte digital","descripcion":"Diseño e imagen"}` | `200` con `{"exito":true,"mensaje":"Categoría actualizada correctamente.","datos":{"id_categoria":2,"nombre":"Arte digital","descripcion":"Diseño e imagen","creada_por":"Administradora","fecha_creacion":"2026-10-01 00:00:00","total_cursos":0},"errores":[]}` | Administrador. `400`, `401`, `403`, `404`, `409`. |
| `DELETE /api/categorias/2` sin cuerpo | `200` con `{"exito":true,"mensaje":"Categoría eliminada correctamente.","datos":{"id_categoria":2},"errores":[]}` | Administrador. `401`, `403`, `404`, `409` si algún curso quedaría sin categorías. |

## Video, materiales y avance

El instructor dueño puede previsualizar borradores. Una cuenta activa puede ver los niveles gratuitos de un curso publicado. Los demás niveles requieren inscripción o acceso de gestión. Los videos y PDF pasan por la API con la cookie, y el reproductor solicita rangos del archivo al adelantar o retroceder.

| Petición | Respuesta de ejemplo | Acceso y códigos |
|---|---|---|
| `GET /api/niveles/31/video` con `Range: bytes=0-1023` | `206` con `Content-Type: video/mp4`, `Content-Range: bytes 0-1023/110240` y 1024 bytes del video. Sin rango devuelve `200` y el video completo. | Cuenta con acceso al nivel. `401`, `403`, `404`, `416` para un rango inválido. |
| `HEAD /api/niveles/31/video` | `200` con `Content-Type: video/mp4`, `Accept-Ranges: bytes`, `Content-Length: 110240` y cuerpo vacío. Con rango aplica el mismo código que en `GET`. | Mismos permisos y errores que `GET`. |
| `GET /api/adjuntos/5` | `200` con `Content-Type: application/pdf`, `Content-Disposition: attachment; filename="material.pdf"` y el archivo binario. | Cuenta con acceso al nivel. `401`, `403`, `404`. |
| `HEAD /api/adjuntos/5` | `200` con los encabezados de descarga y cuerpo vacío. | Mismos permisos y errores que `GET`. |
| `PUT /api/niveles/31/avance` con `{"completado":true}` | `200` con `{"exito":true,"mensaje":"Avance guardado.","datos":{"completado":true,"avance":100,"curso_id":12,"nivel_id":31},"errores":[]}` cuando era el último nivel pendiente. | Estudiante inscrito. `400`, `401`, `403`, `404`. Repetir la petición conserva el avance y el mismo diploma. |

## Reseñas

| Petición | Respuesta de ejemplo | Acceso y códigos |
|---|---|---|
| `POST /api/cursos/12/resenas` con `{"texto":"Las prácticas me ayudaron.","calificacion":5}` | `201` con `{"exito":true,"mensaje":"Reseña publicada correctamente.","datos":{"curso_id":12},"errores":[]}` | Estudiante que completó el curso. Texto de hasta 2,000 caracteres y calificación entera de 1 a 5. `400`, `401`, `403`, `404`, `409` si ya publicó una reseña. |

## Confirmación de pago

| Petición | Respuesta de ejemplo | Acceso y códigos |
|---|---|---|
| `POST /index.php?ruta=pago-confirmar` con `X-CSRF-Token` del formulario y `{"payment_intent":"pi_0123456789ABCDE"}` | `200` con `{"exito":true,"mensaje":"¡Pago confirmado! Ya tienes acceso al curso.","datos":{"redirigir":"index.php?ruta=curso&id=12","pendiente":false,"vale":null},"errores":[]}`. `202` mientras está pendiente, con `pendiente:true` y el enlace al vale cuando aplica. | Estudiante dueño de la orden. `400`, `401`, `403`, `404`, `405`, `415`, `502` si no se pudo verificar el pago. |
| `POST /index.php?ruta=stripe-webhook` con el encabezado `Stripe-Signature` y un evento original como `{"type":"payment_intent.succeeded","data":{"object":{"id":"pi_0123456789ABCDE"}}}` | `200` con `{"exito":true,"mensaje":"Evento recibido.","datos":[],"errores":[]}` | Autenticación por firma de Stripe. `400` si la firma o evento son inválidos, `405`, `500` si falla el registro, `503` cuando no hay secreto configurado. |
