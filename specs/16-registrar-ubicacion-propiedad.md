# SPEC 16 — Registrar la ubicación de una propiedad

> **Estado:** Implementada
> **Depende de:** SPEC 01, SPEC 03, SPEC 04, SPEC 14
> **Fecha:** 2026-11-10
> **Objetivo:** Registrar y mostrar la ubicación de las propiedades por departamento, provincia y distrito del Perú.
> **Historia de origen:** H-03 en backlogs/inmobiliaria-v4.md

## Alcance

**Incluye:**

- Reemplazar ciudad y zona por departamento, provincia y distrito obligatorios al publicar y editar propiedades.
- Usar un catálogo local de ubicaciones peruanas contrastado con una fuente oficial vigente. Validar en el servidor la existencia y la relación entre los tres niveles.
- Mostrar la nueva ubicación en el catálogo, la ficha pública y los listados y detalles del panel, incluidas las propiedades archivadas.
- Adaptar la búsqueda y los filtros de ubicación del panel a los tres niveles, conservando los permisos y el comportamiento de la SPEC 14.
- Sustituir el filtro público de zona por uno de distrito. Distinguir los distritos homónimos mediante su provincia y departamento.
- Adaptar las propiedades de muestra y las pruebas afectadas. Permitir el borrado de propiedades existentes solo en desarrollo y pruebas, conservando las consultas históricas.

**Fuera de alcance:**

- Filtros públicos jerárquicos por departamento, provincia y distrito, previstos en H-04.
- Zona o urbanización adicional, dirección exacta y mapas.
- Migración automática de ubicaciones antiguas o borrado automático de propiedades en producción. Si existen datos reales, detener la implementación y solicitar un plan de migración.
- Administración del catálogo de ubicaciones desde el panel y actualización automática desde servicios externos.
- Compatibilidad de enlaces antiguos que usan los parámetros de ciudad o zona.

## Plan de implementación

### Bloque 1: catálogo y persistencia

1. Obtener una versión descargable del catálogo oficial de ubigeos. El [servicio de códigos estandarizados del INEI](https://www.gob.pe/15028-consultar-codigos-estandarizados-del-inei) ofrece consulta y descarga. Comprobar la vigencia de los datos y registrar su fuente y fecha de consulta junto con la copia local. Si se usa el repositorio propuesto en el backlog, contrastar sus ubicaciones y códigos con la fuente oficial.
2. Incorporar el catálogo local y una forma de identificar cada distrito y su jerarquía sin confundir homónimos. No depender de un servicio externo durante el registro o la consulta de propiedades.
3. Sustituir la ubicación anterior en la persistencia y adaptar las factories y el seed de propiedades de muestra. Limitar cualquier borrado de propiedades existentes a desarrollo y pruebas. Conservar las consultas históricas.
4. Comprobar la carga del catálogo, las relaciones entre niveles, el seed y la protección de los datos fuera de desarrollo y pruebas.

### Bloque 2: registro y presentación

1. Añadir al formulario de alta y edición selectores dependientes para departamento, provincia y distrito. Precargar los valores al editar y limpiar las selecciones dependientes que ya no correspondan al cambiar un nivel superior.
2. Validar en el servidor los tres niveles y su relación, tanto al crear como al editar. Ante errores, conservar el formulario y señalar los campos afectados sin guardar cambios.
3. Si no se puede cargar el catálogo, informar del problema, ofrecer reintento y evitar guardar una ubicación incompleta o no validada.
4. Adaptar los datos enviados a la interfaz y mostrar departamento, provincia y distrito en las vistas públicas y del panel.
5. Cubrir el alta, la edición, la precarga, las relaciones inválidas, la indisponibilidad del catálogo y la presentación.

### Bloque 3: filtros y regresiones

1. Sustituir ciudad y zona en los filtros del panel por departamento, provincia y distrito. Mantener la dependencia entre niveles y limpiar las selecciones incompatibles. Buscar por título o cualquiera de los tres nombres, sin distinguir mayúsculas ni tildes.
2. Mantener las opciones de ubicación y los resultados limitados a las propiedades accesibles para cada usuario. Conservar los demás filtros, contadores, paginación y parámetros de URL de la SPEC 14.
3. Reemplazar el filtro público de zona por distrito. Obtener sus opciones de las propiedades disponibles y mostrar distrito, provincia y departamento para identificar cada opción. Conservar la combinación con los demás filtros y la URL compartible.
4. Ejecutar las pruebas afectadas de propiedades, filtros y seeds, y comprobar en navegador los recorridos de alta, edición y filtrado.

## Criterios de aceptación

- [x] Al publicar o editar, departamento, provincia y distrito son obligatorios y se eligen del catálogo local. Las provincias corresponden al departamento elegido y los distritos a la provincia elegida.
- [x] Al cambiar un nivel superior, se limpian las selecciones dependientes que no pertenecen a la nueva ubicación. La edición precarga los tres valores guardados.
- [x] El servidor rechaza valores inexistentes o combinaciones de niveles incompatibles, tanto en alta como en edición, sin guardar cambios.
- [x] Si el catálogo no está disponible para el formulario, se muestra un error y una opción de reintento. No se guarda una ubicación incompleta o no validada.
- [ ] El catálogo utilizado tiene una fuente oficial y una fecha de consulta registradas. Su vigencia se comprueba frente a la fuente oficial antes de incorporarlo.
- [x] La ficha, las tarjetas públicas y las vistas de propiedades del panel muestran departamento, provincia y distrito de la propiedad.
- [x] El panel filtra por cada nivel y combina estos filtros con los existentes. La búsqueda encuentra coincidencias por título, departamento, provincia o distrito sin distinguir mayúsculas ni tildes.
- [x] Los resultados, contadores y opciones del panel respetan los permisos del administrador y del agente. Sus filtros y paginación siguen funcionando al recargar la URL.
- [x] El filtro público muestra una opción por distrito con propiedades disponibles, identificada con su provincia y departamento. Una selección devuelve solo propiedades de ese distrito, incluso si existen distritos homónimos.
- [x] El filtro público se combina con operación y tipo, conserva la selección en la URL y mantiene el estado sin coincidencias y la acción de limpiar filtros.
- [x] El seed genera propiedades con ubicaciones válidas. Las consultas históricas se conservan al eliminar las propiedades de desarrollo autorizadas.
- [x] Ninguna migración elimina automáticamente propiedades existentes en producción. Si existen datos reales, se detiene la implementación y se solicita un plan de migración.
- [x] Las pruebas afectadas pasan y se comprueban en navegador el alta, la edición y los filtros.

## Decisiones

- **Sí:** tres selectores obligatorios y dependientes. Evitan combinaciones de ubicación incompatibles.
- **No:** texto libre y zona adicional. La ubicación se limita a la jerarquía acordada.
- **Sí:** catálogo local contrastado con una fuente oficial vigente. El registro no depende de la disponibilidad de un servicio externo.
- **Sí:** error y reintento si no se puede cargar el catálogo. No se acepta una ubicación que el sistema no pueda validar.
- **Sí:** reemplazar el filtro público de zona por distrito, con provincia y departamento para distinguir homónimos. H-04 incorporará los tres filtros públicos.
- **Sí:** adaptar el panel a los tres niveles y buscar por sus nombres. Se conservan sus restricciones de acceso.
- **Sí:** borrar propiedades existentes de desarrollo y pruebas y regenerar las muestras. Las consultas históricas se conservan.
- **No:** borrar propiedades automáticamente en producción. Los datos reales necesitan un plan de migración aprobado.

## Riesgos

| Riesgo                                                            | Mitigación                                                                                                                      |
| ----------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| El catálogo propuesto en el backlog puede estar desactualizado.   | Contrastar códigos y ubicaciones con una fuente oficial vigente y registrar la procedencia. La vigencia aún no está verificada. |
| Los nombres de distritos pueden repetirse.                        | Identificar cada distrito por su jerarquía y mostrar provincia y departamento en el filtro público.                             |
| El reemplazo de ciudad y zona afecta filtros y vistas existentes. | Adaptar las superficies afectadas y ejecutar sus pruebas de regresión.                                                          |
