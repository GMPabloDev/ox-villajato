# ox-villajato

Portal inmobiliario peruano para casas y departamentos en venta y alquiler. Catálogo público filtrable, ficha de propiedad con consulta, y panel para agentes y administrador. Stack: PHP, Laravel, Eloquent, React, TypeScript, Tailwind CSS, AWS S3.

## Funcionalidades

- **Catálogo público.** Listado de propiedades disponibles con filtros por operación (venta o alquiler), tipo (casa o departamento) y ubicación por departamento, provincia y distrito.
- **Ficha de propiedad.** Galería de fotos, datos de la propiedad y formulario de consulta. Cada consulta llega por correo al destinatario configurado, con un límite de envíos por IP.
- **Panel.** Crear, editar, archivar, cerrar, republicar y eliminar propiedades; revisar consultas con filtros; crear cuentas de agente (solo el administrador).
- **Cuentas.** Autenticación con Laravel Fortify, incluidos passkeys y gestión de perfil, contraseña y seguridad en `settings`.
- **Precios en PEN o USD**, sin conversión entre monedas.
- **Ubicación según el catálogo INEI.** El seeder de ubigeo carga departamentos, provincias y distritos del Perú.

## Stack

- Laravel 13 y PHP 8.3 o superior
- Inertia v3 con React 19 y TypeScript
- Tailwind CSS 4
- Fortify (autenticación y passkeys)
- Wayfinder (funciones de rutas para el frontend)
- Flysystem AWS S3 (fotos de propiedades)
- PHPUnit, Laravel Dusk, PHPStan (Larastan) y Pint

## Requisitos

- PHP 8.3 o superior con Composer
- Node.js

## Instalación

`composer setup` copia `.env.example` a `.env`, genera la clave de la aplicación, ejecuta las migraciones, instala las dependencias de PHP y npm, y compila los assets:

```
composer setup
```

Después carga los datos semilla. `db:seed` carga el catálogo de ubigeo y un usuario de prueba:

```
php artisan db:seed
```

Para añadir propiedades de ejemplo con fotos locales:

```
php artisan db:seed --class=SamplePropertiesSeeder
```

`ClearSamplePropertiesSeeder` elimina las propiedades de ejemplo cuando ya no las necesitas.

## Desarrollo

`composer dev` arranca los cuatro procesos en paralelo: el servidor (`artisan serve`), la cola (`queue:listen`), los logs en vivo (`pail`) y Vite (`npm run dev`):

```
composer dev
```

## Pruebas y calidad

```
composer test         # Pint, PHPStan y PHPUnit
composer dusk         # pruebas de navegador en un proyecto aislado
composer lint:check   # solo revisión de estilo
composer types:check  # solo análisis estático
```

## Variables de entorno

Las variables de dominio viven en `config/properties.php`. Las principales:

- `DB_CONNECTION`: base de datos. Por defecto `sqlite`.
- `PROPERTY_PHOTOS_DISK`: disco donde se guardan las fotos de las propiedades. Por defecto `public` (disco local). Usa `s3` y completa `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION` y `AWS_BUCKET` para almacenarlas en S3.
- `PROPERTY_INQUIRY_RECIPIENT`: correo que recibe las consultas de la ficha.
- `PROPERTY_INQUIRY_RATE_LIMIT`: consultas por minuto y por IP admitidas en la ficha. Por defecto 5.

## Especificaciones

El directorio `specs/` guarda las especificaciones de producto numeradas de la 01 a la 17. Cada una describe una historia con su criterio de aceptación y guía la implementación de esa funcionalidad.
