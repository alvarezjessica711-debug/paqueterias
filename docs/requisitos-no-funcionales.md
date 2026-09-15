# Requisitos no funcionales

## RNF-01: Usabilidad

El flujo del vigilante se mantiene en una sola pantalla y dos bloques conceptuales: seleccionar residente y apartamento, y registrar la información del paquete. El botón final indica claramente "Registrar y notificar al residente".

Las vistas operativas incluyen viewport, etiquetas asociadas a controles, campos requeridos, mensajes de validación y botones con texto. La hoja de estilos adapta la navegación, formularios y acciones a pantallas menores a 768 px; las tablas se desplazan horizontalmente dentro de su contenedor cuando es necesario para no recortar datos.

Prueba manual: iniciar sesión como vigilante, abrir Registrar Paquete en computador o móvil, seleccionar residente, completar guía y empresa, y registrar el paquete. Verificar también Consultar Entregas y Confirmar Entrega.

## RNF-02: Seguridad y RBAC

Las rutas privadas usan `auth` y `active`; cada módulo operativo o administrativo aplica además `role`. Los controladores validan las entradas y resuelven el residente autenticado en las consultas y autorizaciones, sin confiar en identificadores de residente enviados por el navegador.

Las contraseñas usan el cast `hashed` de Laravel y los formularios de cambio las validan antes de persistirlas. Las cuentas inactivas no inician sesión y el middleware cierra sesiones que hayan quedado abiertas. Los formularios mutables usan CSRF y las cargas de fotografías solo aceptan JPEG, JPG, PNG o WEBP de hasta 5 MB, almacenadas mediante el disco `public` de Laravel con nombre seguro.

Evidencia: pruebas de control por roles, residentes aislados, cuentas inactivas y archivos inválidos. Las relaciones y valores de entrega se calculan en servidor.

## RNF-03: Disponibilidad

El proyecto incluye `GET /health`, un endpoint público y rápido que responde `{"status":"ok"}` sin exponer credenciales, variables de entorno ni información interna. Es apto para un monitor externo básico.

El objetivo de disponibilidad real del 99.5% no puede garantizarse desde un entorno local ni solo con código Laravel. Para cumplirlo en producción se requiere servidor disponible 24/7 con HTTPS, base de datos y almacenamiento de fotos persistentes, backups comprobados, monitoreo del endpoint, alertas, logs centralizados, reinicio automático de servicios, health checks, mantenimiento programado y un procedimiento probado de recuperación ante fallos.

## RNF-04: Rendimiento

El registro valida y almacena la información localmente, carga relaciones necesarias mediante eager loading en las pantallas principales y usa una notificación simulada durante la prueba de rendimiento. La prueba automatizada mide el request de registro y verifica que termine antes de tres segundos en el entorno de pruebas.

Esta evidencia es local y no garantiza el mismo tiempo en producción. Un proveedor de correo externo puede aumentar el tiempo cuando la notificación se envía de manera síncrona; en producción se debe monitorear y, si la carga lo requiere, evaluar una cola ya soportada por la infraestructura.

## RNF-05: Integridad y trazabilidad

La confirmación de entrega solo acepta paquetes Pendientes y vuelve a comprobarlo dentro de una transacción con bloqueo de fila. La firma es validada en servidor, la fecha se genera con `now()`, el vigilante se obtiene de la sesión autenticada y quien recibe se toma del residente o de la autorización activa. No existe una ruta normal de edición de entregas cerradas.

Una segunda solicitud para un paquete Entregado no altera estado, firma, fecha, guarda ni receptor. Si se entrega a un tercero, la autorización se marca Utilizada; si recibe el residente, la autorización activa se Cancela. El historial y reportes cargan el guarda responsable aunque su cuenta esté inactiva actualmente.

Prueba manual: confirmar una entrega con firma y volver a enviar la misma URL. La segunda operación debe ser rechazada y la trazabilidad original debe permanecer intacta.
