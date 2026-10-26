# Valle de San Remo

Sistema web para gestionar los paquetes que llegan al conjunto residencial Valle de San Remo.

## Descripción del proyecto

El proyecto permite registrar la correspondencia recibida en portería, consultar su estado y conservar la trazabilidad de las entregas. Se presenta como parte de la evidencia académica **GA7-220501096-AA4-EV03: Componente front-end del proyecto formativo y proyectos de clase**.

La aplicación principal utiliza **Laravel Blade**. Incluye una demostración independiente de conceptos básicos de React; no todo el sistema está desarrollado en React.

## Funciones principales

- Inicio y cierre de sesión con control de acceso por rol y estado de la cuenta.
- Registro de paquetes, guía, empresa de mensajería y fotografía opcional.
- Consulta e historial con filtros.
- Entrega de paquetes con firma y registro de quien recibe y del vigilante responsable.
- Autorizaciones de retiro por terceros.
- Gestión de residentes, vigilantes y casas.
- Reportes y exportación CSV.
- Notificación de nuevos paquetes al usuario asociado al residente.

En la configuración de demostración, el correo usa el transporte `log`: los mensajes quedan registrados en `storage/logs/laravel.log`, sin enviarse a una bandeja real. No hay integración de envío por WhatsApp.

## Roles actuales

- **Administrador:** gestiona residentes, vigilantes y casas; consulta historial, reportes y configuración.
- **Vigilante:** registra paquetes, consulta pendientes y confirma entregas.
- **Residente:** consulta sus paquetes e historial y autoriza a terceros para retirarlos.

## Tecnologías utilizadas

Laravel, PHP, SQLite, HTML, CSS y JavaScript. React se utiliza para la demostración front-end, con Vite para compilar los recursos.

## Base de datos

Actualmente se utiliza **SQLite**, con el archivo local `database/database.sqlite`. Las migraciones describen su estructura. La base de trabajo y sus datos no se incluyen en el repositorio.

## Instalación básica

Requisitos: PHP compatible con `composer.json`, Composer y Node.js con npm compatible con las dependencias del proyecto. PHP debe disponer de SQLite y GD para la base de datos y las firmas.

Para una **instalación nueva**, clona el repositorio e instala las dependencias:

```sh
git clone https://github.com/alvarezjessica711-debug/paqueterias.git
cd paqueterias
composer install
npm ci
```

Copia `.env.example` como `.env` únicamente si todavía no existe. En una instalación nueva, genera la clave:

```sh
php artisan key:generate
```

Crea un archivo vacío `database/database.sqlite` solo si no existe y ejecuta:

```sh
php artisan migrate
php artisan storage:link
npm run build
```

Si ya tienes una instalación, conserva su `.env`, su clave y su base de datos. No reemplaces esos archivos para seguir estas instrucciones.

Las cuentas y los datos de demostración se preparan por separado. El seeder general no reproduce por sí solo todos los datos de la demostración; el seeder específico de María requiere que su cuenta, casa y vigilante ya existan. No es necesario ejecutar seeders para compilar los recursos.

## Cómo ejecutar Laravel

```sh
php artisan serve
```

Abre la dirección indicada en la terminal y entra en `/login`. Utiliza una cuenta proporcionada por el responsable del proyecto.

## Cómo ejecutar Vite

En otra terminal, durante el desarrollo:

```sh
npm run dev
```

Para generar los archivos compilados:

```sh
npm run build
```

## Cómo ejecutar pruebas

```sh
php artisan test
```

La configuración de pruebas usa SQLite en memoria y no necesita borrar la base de trabajo.

## Demostración React

Con Laravel en ejecución y los recursos compilados o Vite activo, visita `/demo-react`.

El componente muestra un contador mediante `useState`. Los botones usan `onClick` para aumentar el contador o reiniciarlo y `createRoot` monta el componente. Es una actividad académica: no crea paquetes ni modifica la base de datos.

Archivos principales:

- `resources/js/react-demo.jsx`
- `resources/js/react-demo.css`
- `resources/views/demo-react.blade.php`

## Repositorio

[GitHub — paqueterias](https://github.com/alvarezjessica711-debug/paqueterias)

## Autor / integrantes

- Yesica Alvarez
