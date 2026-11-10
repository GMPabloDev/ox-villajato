# SPEC 14 — Filtrar propiedades del panel

> **Estado:** AProbada
> **Depende de:** SPEC 05, SPEC 07, SPEC 10, SPEC 11
> **Fecha:** 2026-11-09
> **Objetivo:** Hacer funcionales los filtros, la búsqueda y el orden del panel de propiedades, con resultados paginados.
> **Historia de origen:** H-01 en backlogs/inmobiliaria-v4.md

## Alcance

**Incluye:**

- Filtrar por estado, operación, tipo, ciudad, zona, moneda, rango de precio y cantidad mínima de dormitorios. Los filtros seleccionados se combinan y cada propiedad debe cumplirlos todos.
- Buscar por título, ciudad o zona, sin distinguir mayúsculas ni tildes. El campo no debe anunciar búsqueda por código.
- Ordenar por más recientes, título de A a Z o precio de menor a mayor o de mayor a menor.
- Conservar filtros, búsqueda, orden y página en la URL. Al abrirla, cada usuario ve solo las propiedades que le corresponden.
- Paginar los resultados, con 15 propiedades por página.
- Mantener el acceso existente: el administrador ve todas las propiedades no archivadas y el agente solo las suyas. Las propiedades archivadas permanecen en su listado separado.
- Mostrar un estado sin coincidencias con una acción para limpiar la búsqueda y los filtros.

**Fuera de alcance:**

- Filtros del panel de consultas, previstos en H-02.
- Sustituir ciudad y zona por departamento, provincia y distrito. Esa adaptación corresponde a H-03.
- Crear códigos de propiedad, convertir monedas o filtrar propiedades archivadas.
- Añadir filtros de garaje o amueblado, previstos como datos de propiedad en H-05.

## Plan de implementación

### Bloque 1: consulta y permisos

1. Aplicar la búsqueda, los filtros y el orden a las propiedades no archivadas accesibles al usuario. Validar los valores recibidos y respetar la moneda elegida al filtrar u ordenar precios.
2. Obtener las opciones de ubicación y los contadores sin exponer datos de propiedades ajenas. Paginar las coincidencias y conservar los parámetros de la URL.
3. Cubrir con pruebas las combinaciones de filtros, búsqueda, orden, paginación, rangos inválidos y límites de acceso por rol.

### Bloque 2: interacción del panel

1. Conectar los controles existentes a los resultados, sincronizar los controles repetidos y actualizar el texto de búsqueda.
2. Aplicar los cambios del panel lateral al pulsar «Mostrar resultados». Permitir cerrarlo sin aplicar los cambios y mostrar los errores de rango.
3. Mostrar contadores, paginación, estado sin coincidencias y detalle de la propiedad seleccionada según las decisiones acordadas.
4. Comprobar el recorrido en el panel y ejecutar las pruebas afectadas.

## Criterios de aceptación

- [x] Cada control existente de estado, operación, tipo, ciudad, zona, moneda, precio y dormitorios modifica los resultados según el valor seleccionado. Los dormitorios utilizan las opciones existentes de cantidad mínima.
- [x] Al combinar filtros, cada resultado cumple todas las condiciones seleccionadas.
- [x] La búsqueda encuentra coincidencias en título, ciudad o zona sin distinguir mayúsculas ni tildes. El texto del campo no ofrece buscar por código.
- [x] La selección de una ciudad limita las zonas disponibles a esa ciudad. Si se cambia de ciudad y la zona elegida no pertenece a la nueva ciudad, se limpia la zona.
- [x] Para filtrar por rango de precio u ordenar por precio se debe elegir PEN o USD. No se comparan ni convierten importes de distintas monedas.
- [x] Si se quita la moneda, se limpia el rango de precio y se restablece el orden por más recientes.
- [x] Si el precio mínimo es mayor que el máximo, el panel lateral muestra un error, permanece abierto y no aplica el rango inválido.
- [x] Los cambios del panel lateral se aplican al pulsar «Mostrar resultados». Al cerrarlo sin aplicar, los cambios pendientes se descartan.
- [x] Las pestañas de estado muestran los totales que cumplen los demás filtros, sin limitarse a la página actual. Cada pestaña cuenta su propio estado.
- [x] Cada página muestra como máximo 15 propiedades. Al cambiar un filtro se vuelve a la primera página.
- [x] Al recargar o abrir una URL filtrada se conservan los filtros, la búsqueda, el orden y la página, sin ampliar los permisos del usuario.
- [x] Si una propiedad seleccionada deja de aparecer en la página visible, el detalle muestra la primera propiedad visible. Si no hay resultados, no muestra ningún detalle.
- [x] Si existen propiedades accesibles pero ninguna coincide, el panel informa de que no hay coincidencias y permite limpiar la búsqueda y los filtros.
- [x] El administrador solo recibe propiedades no archivadas del inventario completo. El agente solo recibe sus propias propiedades no archivadas. Los contadores y las opciones de ubicación tampoco revelan datos de otros agentes.
- [x] El listado sigue ofreciendo las acciones de gestión existentes para las propiedades visibles.

## Decisiones

- **Sí:** funcionalidad para todos los controles actuales, incluidos ciudad y zona. H-03 los adaptará a la nueva clasificación de ubicaciones.
- **No:** búsqueda por código. La propiedad no tiene un código propio y el campo dejará de anunciarlo.
- **Sí:** moneda obligatoria para rangos y orden por precio. PEN y USD no se convierten ni se comparan entre sí.
- **Sí:** parámetros en la URL y 15 resultados por página. La paginación también forma parte de esta historia.
- **Sí:** cambios en borrador dentro del panel lateral hasta pulsar «Mostrar resultados». Cerrar sin aplicar descarta esos cambios.
- **Sí:** contadores por estado calculados con los demás filtros y sobre todas las páginas. Así muestran cuántas coincidencias ofrece cada pestaña.
- **No:** mostrar el estado vacío del inventario cuando solo faltan coincidencias. El estado filtrado ofrece limpiar la búsqueda y los filtros.
