# SPEC 17 — Filtrar el catálogo por ubicación

> **Estado:** Aprobada
> **Depende de:** SPEC 03, SPEC 16
> **Fecha:** 2026-11-11
> **Objetivo:** Permitir que el visitante filtre las propiedades disponibles por departamento, provincia y distrito.
> **Historia de origen:** H-04 en backlogs/inmobiliaria-v4.md

## Alcance

**Incluye:**

- Añadir al catálogo público filtros jerárquicos de departamento, provincia y distrito. Se puede filtrar solo por departamento o por departamento y provincia, sin elegir distrito.
- Ofrecer ubicaciones de propiedades disponibles. Las provincias dependen del departamento elegido y los distritos de la provincia elegida. Las opciones no dependen de operación ni tipo.
- Aplicar cada selección inmediatamente y combinarla con operación y tipo. Conservar la URL compartible, el estado sin coincidencias y la acción para limpiar filtros.
- Mantener los enlaces existentes con `district_code`, completar sus niveles superiores y conservar una ubicación seleccionada aunque ya no tenga propiedades disponibles.
- Validar los códigos recibidos y descartar los niveles incompatibles con un nivel superior válido.

**Fuera de alcance:**

- Modificar el registro de ubicaciones o los filtros del panel.
- Mostrar ubicaciones sin propiedades disponibles como opciones generales, actualizar el catálogo de ubigeos o comprobar de nuevo su fuente y vigencia.
- Añadir mapas, búsqueda por texto, filtros de precio u otros filtros de propiedades.
- Recuperar enlaces anteriores que usaban ciudad o zona.

## Plan de implementación

### Bloque 1: consulta y contrato del catálogo

1. Extender la validación y la consulta pública de `app/Http/Controllers/PropertyController.php` para aceptar departamento, provincia y distrito, manteniendo operación, tipo y las propiedades disponibles.
2. Resolver la jerarquía de un enlace existente con `district_code` y conservar los niveles superiores válidos cuando una URL incluya niveles incompatibles. Mantener el manejo de errores de validación para códigos inexistentes.
3. Proporcionar a la página las ubicaciones con propiedades disponibles y los datos necesarios para mostrar una selección válida que ya no tenga oferta. No filtrar estas opciones por operación ni tipo.
4. Cubrir con pruebas de servidor los filtros parciales y combinados, las URL existentes, los niveles incompatibles y las selecciones sin oferta.

### Bloque 2: controles y recorrido público

1. Adaptar `resources/js/pages/properties/index.tsx` para mostrar los tres filtros dependientes y aplicar las selecciones de inmediato. Al cambiar o limpiar un nivel superior, limpiar los niveles inferiores que ya no correspondan.
2. Mantener las selecciones al cambiar operación o tipo, recargar o compartir la URL. Distinguir las ubicaciones homónimas con sus niveles superiores.
3. Conservar el mensaje sin coincidencias y la acción de limpiar todos los filtros. Mantener visible la ubicación seleccionada cuando ya no tenga propiedades disponibles.
4. Actualizar las pruebas existentes y comprobar en navegador la selección en cascada, la combinación con operación y tipo, la URL compartida y la limpieza.

## Criterios de aceptación

- [ ] El catálogo sin filtros sigue mostrando las propiedades disponibles con sus tarjetas y orden existentes.
- [ ] Elegir un departamento muestra únicamente sus propiedades disponibles. Elegir además una provincia limita los resultados a esa provincia; elegir también un distrito los limita a ese distrito.
- [ ] Las opciones de departamento, provincia y distrito proceden de propiedades disponibles. Las provincias corresponden al departamento elegido y los distritos a la provincia elegida. Operación y tipo no modifican estas opciones.
- [ ] Al cambiar o limpiar un nivel superior se eliminan las selecciones inferiores incompatibles. Cada cambio se aplica sin pulsar un botón adicional.
- [ ] Los filtros de ubicación se combinan con operación y tipo. Cambiar cualquiera de estos conserva los demás filtros compatibles.
- [ ] Recargar o compartir la URL reproduce las selecciones y los resultados. Un enlace existente con `district_code` sigue filtrando ese distrito y muestra su provincia y departamento.
- [ ] Una URL con niveles existentes pero incompatibles conserva el nivel superior válido y descarta los descendientes incompatibles. Los códigos inexistentes siguen el manejo de errores de validación del catálogo.
- [ ] Una ubicación válida seleccionada permanece visible aunque ya no tenga propiedades disponibles. El catálogo muestra el estado sin coincidencias.
- [ ] Las ubicaciones homónimas se distinguen mediante sus niveles superiores. El filtro de distrito no confunde distritos con el mismo nombre.
- [ ] Si ningún resultado coincide, se muestra el mensaje correspondiente. La acción de limpiar elimina todos los filtros y devuelve el catálogo completo.
- [ ] Pasan las pruebas afectadas de servidor y navegador para los recorridos y fallos anteriores.

## Decisiones

- **Sí:** selectores jerárquicos con filtrado parcial. El visitante puede buscar por departamento, provincia o distrito sin completar niveles inferiores.
- **Sí:** opciones de ubicaciones con propiedades disponibles, independientes de operación y tipo. Se conserva el criterio del filtro público existente.
- **Sí:** aplicación inmediata. La interfaz actual no tiene botón de aplicación y se mantiene ese recorrido.
- **Sí:** conservar enlaces por `district_code` y reconstruir sus niveles superiores. Los enlaces de distrito compartidos siguen siendo útiles.
- **Sí:** conservar ubicaciones seleccionadas sin oferta y mostrar cero resultados. Así no se pierde el contexto de una URL válida.
- **Sí:** descartar niveles incompatibles y conservar el nivel superior válido. El panel ya utiliza ese criterio para su jerarquía.
- **No:** mostrar todo el catálogo de ubicaciones como opciones generales. Se evita ofrecer ubicaciones sin propiedades disponibles.
- **No:** añadir un botón para aplicar filtros ni condicionar las opciones a operación y tipo. Se conserva el comportamiento actual.

## Riesgos

| Riesgo | Mitigación |
| --- | --- |
| La SPEC 16 figura implementada, pero su criterio sobre la vigencia de la fuente del catálogo permanece sin marcar. | Esta spec usa el catálogo existente y no afirma que su vigencia esté verificada. La comprobación pendiente corresponde a la SPEC 16. |
| Un enlace por distrito puede dejar de funcionar al introducir niveles superiores obligatorios en la interfaz. | Resolver provincia y departamento a partir del distrito y cubrir el enlace existente con pruebas. |
