# Servicios web REST — Valle de San Remo

Base local: `/api`. Todos los servicios, salvo `POST /login`, requieren `Authorization: Bearer <token>`. Los tokens expiran a las 12 horas. Los JSON de paquetes excluyen siempre la firma digital; los datos de residente devueltos son solo ID, nombre y casas activas (el contrato conserva las claves técnicas existentes). Esta API no altera las rutas web existentes.

Las historias/casos de uso relacionados se identifican por el flujo funcional existente en la aplicación: autenticación y control de acceso; registro de paquetes por portería; consulta de paquetes e historial; consulta de residentes y casas para registrar paquetes; confirmación y trazabilidad de entrega. El repositorio no contiene identificadores de historias/casos de uso en texto verificable, por lo que se describen por su objetivo y no se atribuyen códigos no confirmados.

## A. Autenticación API

### Iniciar sesión

- **Objetivo / caso relacionado:** autenticar al usuario autorizado del sistema para operar según su rol actual.
- **Método y ruta:** `POST /api/login`.
- **Rol y autenticación:** público; requiere credenciales válidas de una cuenta activa.
- **Entrada:** `email` (correo válido) y `password` (texto, máximo 255).
- **Validaciones:** campos requeridos; credenciales verificadas contra el hash guardado; cuenta y residente asociado activos.
- **Postman:** `POST http://localhost:8000/api/login`, Body → raw → JSON:

```json
{"email":"vigilante@example.test","password":"clave-de-prueba"}
```

- **Respuesta 200:**

```json
{"data":{"token":"<token-bearer>","tipo":"Bearer","expira_en_segundos":43200},"user":{"id":7,"name":"Vigilante","email":"vigilante@example.test","rol":"vigilante"}}
```

- **Errores:** `401` credenciales incorrectas; `403` cuenta inactiva; `422` entrada inválida; `429` demasiados intentos.
- **Prueba:** `API authentication issues a bearer token, rejects bad credentials and inactive accounts` en `tests/Feature/ApiServicesTest.php`.

### Cerrar sesión

- **Objetivo / caso relacionado:** revocar el token actual.
- **Método y ruta:** `POST /api/logout`.
- **Rol y autenticación:** cualquier usuario autenticado con token Bearer vigente.
- **Entrada:** encabezado `Authorization: Bearer <token>`; no requiere cuerpo.
- **Respuesta 200:** `{"message":"Sesión API cerrada."}`.
- **Errores:** `401` token ausente, inválido o revocado.
- **Prueba:** `API requires a token, enforces roles and revokes tokens on logout`.

## B. Consulta de paquetes

### Listar paquetes

- **Objetivo / caso relacionado:** consultar paquetes e historial con filtros; los residentes solo ven los suyos.
- **Método y ruta:** `GET /api/paquetes`.
- **Rol y autenticación:** admin, vigilante o residente con token Bearer. Residentes sin asociación reciben `403`.
- **Parámetros:** `estado` opcional (`Pendiente` o `Entregado`); `fecha_desde` y `fecha_hasta` opcionales; `per_page` opcional entre 1 y 100 (predeterminado 15); `page` estándar de paginación Laravel.
- **Validaciones:** estado enumerado; fechas válidas y fecha hasta no anterior a fecha desde; límite de paginación.
- **Postman:** `GET http://localhost:8000/api/paquetes?estado=Pendiente&fecha_desde=2026-10-01&per_page=10`, encabezado Bearer.
- **Respuesta 200:** objeto paginado Laravel con `data`, `links` y `meta`. Cada paquete expone ID, estado, empresa, guía, detalles, ruta de foto, nombre/ID del residente y casa (`id`, `codigo`), y trazabilidad de entrega si aplica. No expone firma.
- **Errores:** `401` sin token; `403` rol no autorizado o residente sin asociación; `422` filtros inválidos.
- **Pruebas:** `package list filters and pagination are validated and bounded`; `residents only see their packages and cannot read another residents package`.

### Consultar detalle

- **Objetivo / caso relacionado:** consultar el estado y trazabilidad de un paquete.
- **Método y ruta:** `GET /api/paquetes/{paquete}`.
- **Rol y autenticación:** admin, vigilante o residente autenticado. El residente solo puede consultar el paquete asociado a su propio residente.
- **Parámetros:** `paquete` es el ID existente.
- **Postman:** `GET http://localhost:8000/api/paquetes/42`, encabezado Bearer.
- **Respuesta 200:** `{"data":{"id":42,"estado":"Entregado","empresa":"Mensajería","guia":"ABC123","detalles":null,"foto":null,"residente":{"id":4,"nombre":"Residente API"},"casa":{"id":2,"codigo":"A1"},"entrega":{"fecha":"2026-10-16T12:00:00.000000Z","recibido_por":"Residente API","vigilante":"Vigilante"},"autorizacion_activa":null,"creado_en":"2026-10-16T10:00:00.000000Z"}}`.
- **Errores:** `401` no autenticado; `403` paquete ajeno para residente; `404` ID inexistente.
- **Prueba:** `residents only see their packages and cannot read another residents package`.

## C. Consulta de residentes

### Buscar residentes activos

- **Objetivo / caso relacionado:** encontrar residentes y casas activas que el vigilante necesita al registrar un paquete.
- **Método y ruta:** `GET /api/residentes`.
- **Rol y autenticación:** admin o vigilante autenticado.
- **Parámetros:** `busqueda` opcional por nombre (máximo 100 caracteres); `per_page` opcional entre 1 y 100.
- **Validaciones:** tipo, longitud y límite de resultados. Solo residentes y casas activas.
- **Postman:** `GET http://localhost:8000/api/residentes?busqueda=Ana&per_page=10`, encabezado Bearer.
- **Respuesta 200:** lista paginada con ID, nombre y casas (`id`, `codigo`). No incluye correo, teléfono ni datos de cuenta.
- **Errores:** `401` sin token; `403` rol residente; `422` parámetros inválidos.
- **Prueba:** `resident lookup is role protected and exposes only necessary fields`.

## D. Registro de paquetes

### Registrar paquete

- **Objetivo / caso relacionado:** registrar en portería un paquete recibido y enviar la notificación por correo existente cuando el residente tenga una cuenta con correo. No se afirma integración con WhatsApp ni otro canal externo.
- **Método y ruta:** `POST /api/paquetes`.
- **Rol y autenticación:** vigilante autenticado.
- **Entrada JSON:** `residente_id`, `casa_id`, `empresa`, `destinatario`, `direccion` y `guia`; opcionales `detalles` y `foto`. `casa_id` contiene el ID de la casa del catálogo. Para foto se envía `multipart/form-data`.
- **Validaciones:** residente y número de casa existentes; empresa requerida hasta 255; guía requerida hasta 100; fotografía opcional JPEG/JPG/PNG/WebP hasta 5 MB; el residente y la casa deben estar activos y relacionados.
- **Postman JSON:** `POST http://localhost:8000/api/paquetes`, Bearer y Body raw JSON:

```json
{"residente_id":4,"casa_id":2,"empresa":"Mensajería","destinatario":"Ana","direccion":"A1","guia":"ABC123","detalles":"Caja mediana"}
```

- **Respuesta 201:** `{"data":{"id":43,"estado":"Pendiente","empresa":"Mensajería","guia":"ABC123","detalles":"Caja mediana","foto":null,"residente":{"id":4,"nombre":"Residente API"},"casa":{"id":2,"codigo":"A1"},"entrega":null,"autorizacion_activa":null,"creado_en":"2026-10-16T10:00:00.000000Z"},"notificacion_enviada":true}`. No se devuelve la firma.
- **Errores:** `401` sin token; `403` rol no autorizado; `404` registro inexistente; `422` datos inválidos o casa no relacionada; `201` aunque el correo no se pueda enviar, en cuyo caso `notificacion_enviada` es `false`.
- **Prueba:** `vigilante can register a package and invalid resident apartment relation is rejected`.

## E. Confirmación de entrega

### Confirmar entrega

- **Objetivo / caso relacionado:** registrar la entrega de un paquete pendiente con trazabilidad y aplicación de una autorización de retiro vigente.
- **Método y ruta:** `POST /api/paquetes/{paquete}/entrega`.
- **Rol y autenticación:** vigilante autenticado.
- **Entrada:** `tipo_receptor` (`residente` o `autorizado`) y `firma` como PNG codificado en Data URL. El servidor establece fecha, vigilante y receptor; no acepta esos campos desde el cliente.
- **Validaciones:** paquete pendiente; firma PNG válida con trazo y tamaño máximo de 2 MB; autorización activa requerida al seleccionar `autorizado`. Dentro de transacción se vuelve a comprobar que siga pendiente. La autorización queda utilizada si retira un autorizado o cancelada si recibe el residente.
- **Postman:** `POST http://localhost:8000/api/paquetes/42/entrega`, Bearer, Body raw JSON con `tipo_receptor` y `firma` en formato `data:image/png;base64,...`.
- **Respuesta 200:** mensaje de éxito y paquete con estado `Entregado`, fecha, receptor y vigilante. La respuesta no contiene el valor de la firma.
- **Errores:** `401` token no válido; `403` rol no autorizado; `404` paquete inexistente; `409` paquete ya entregado; `422` firma o tipo de receptor inválidos, o autorización activa ausente.
- **Prueba:** `vigilante delivery records traceability and duplicate delivery returns conflict without exposing signature`.

## Verificación automatizada

Los casos documentados se comprueban en `tests/Feature/ApiServicesTest.php`. La suite usa `RefreshDatabase` y la configuración de PHPUnit `DB_DATABASE=:memory:` para que las pruebas no operen sobre `database/database.sqlite`. Ejecutar con `php artisan test`. La suite comprueba autenticación, cuentas inactivas, roles, token requerido/revocado, aislamiento por residente, filtros, límite de paginación, privacidad de respuesta, registro, validaciones, trazabilidad, rechazo de duplicados y códigos JSON HTTP.
