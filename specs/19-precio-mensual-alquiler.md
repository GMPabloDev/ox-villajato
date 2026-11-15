# SPEC 19 — Precio mensual de alquiler

> **Estado:** Aprobada
> **Depende de:** SPEC 01, SPEC 04, SPEC 14
> **Fecha:** 2026-11-15
> **Objetivo:** Registrar y mostrar el precio de alquiler como un importe mensual, conservando un único precio por propiedad.
> **Historia de origen:** H-06 en backlogs/inmobiliaria-v4.md

## Alcance

**Incluye:**

- Usar el precio existente como importe mensual para alquiler y como precio total para venta. Mantener una moneda por propiedad, PEN o USD, sin conversión.
- Identificar el campo como «Precio mensual» para alquiler y «Precio» para venta en el formulario de publicación y edición. Actualizar la etiqueta al cambiar la operación sin borrar el importe introducido.
- Mostrar «/ mes» junto al precio de alquiler en las tarjetas del catálogo, la ficha pública y su barra móvil, el listado y detalle del panel, y las tarjetas de propiedades archivadas. No añadir el sufijo a los precios de venta.
- Considerar mensuales los importes de alquiler ya registrados, sin modificar sus valores ni eliminar propiedades.
- Mantener las reglas existentes de validación, permisos, estados, filtros y orden por precio.

**Fuera de alcance:**

- Añadir un segundo precio, conversión de moneda u otras periodicidades de alquiler.
- Cambiar el filtro o el orden por precio del panel, o añadir filtros de precio al catálogo público.
- Verificar individualmente los importes de alquiler existentes o cambiarlos de forma automática.

## Plan de implementación

### Bloque 1: registro del precio

1. Adaptar el formulario compartido de publicación y edición para que la etiqueta del precio corresponda a la operación seleccionada. Precargar la operación, el importe y la moneda existentes al editar.
2. Conservar el importe y la moneda al cambiar entre venta y alquiler. Mantener la validación actual y la persistencia en el campo de precio existente.
3. Comprobar el alta y la edición de alquileres y ventas, los cambios de operación en ambos sentidos y el rechazo de precios ausentes o inválidos.

### Bloque 2: presentación del precio

1. Mostrar «/ mes» junto al precio de alquiler en las tarjetas del catálogo y en las dos presentaciones del precio de la ficha pública, incluida la barra móvil.
2. Mantener o añadir el mismo indicador en el listado y el detalle del panel y en las tarjetas de propiedades archivadas. No mostrar el indicador para venta.
3. Comprobar la presentación para ambas operaciones y monedas. Verificar que los alquileres registrados antes del cambio conservan su importe y lo muestran como mensual.

### Bloque 3: verificación

1. Añadir o actualizar las pruebas que cubran la etiqueta reactiva, la conservación del importe al cambiar de operación y la presentación del sufijo en las vistas afectadas.
2. Ejecutar las pruebas afectadas. Comprobar en navegador el alta, la edición y las vistas públicas y del panel, incluida la ficha en tamaño móvil.
3. Verificar que los filtros y el orden del panel conservan su comportamiento actual.

## Criterios de aceptación

- [ ] Al publicar o editar un alquiler, el formulario muestra «Precio mensual»; para una venta muestra «Precio».
- [ ] Al cambiar la operación en cualquier sentido, la etiqueta cambia y el importe y la moneda introducidos se conservan. Al editar se precargan los valores guardados.
- [ ] El servidor sigue exigiendo un precio numérico mayor que cero y una moneda válida. Rechaza los datos inválidos sin guardar cambios.
- [ ] Un alquiler se guarda con un único precio en PEN o USD, sin conversión. Una venta conserva su precio total.
- [ ] Un alquiler existente conserva su importe y moneda y se presenta como mensual, sin migración ni eliminación de propiedades.
- [ ] Las tarjetas del catálogo, la ficha pública y su barra móvil muestran «/ mes» junto al precio de alquiler. Ninguna muestra ese sufijo para venta.
- [ ] El listado y el detalle del panel y las tarjetas de propiedades archivadas muestran «/ mes» junto al precio de alquiler y no lo muestran para venta.
- [ ] Los filtros y el orden por precio del panel mantienen su comportamiento actual, incluida la comparación de importes de venta y alquiler cuando no se filtra por operación.
- [ ] Pasan las pruebas afectadas y se comprueban en navegador el alta, la edición y las vistas indicadas, incluida la ficha móvil.

## Decisiones

- **Sí:** usar el precio existente para el importe mensual del alquiler. Se conserva el contrato de un precio por propiedad y no hace falta otra columna.
- **Sí:** tratar los precios de alquiler existentes como mensuales y conservarlos sin cambios. No se borran propiedades ni se transforman importes.
- **Sí:** mostrar «/ mes» en todas las vistas que presentan el precio. El visitante y el personal pueden identificar la periodicidad sin depender de otra pantalla.
- **Sí:** conservar el importe y la moneda al cambiar la operación y actualizar la etiqueta. La persona que edita puede corregir el importe antes de guardar.
- **No:** exigir una operación para filtrar u ordenar por precio en el panel. Se conserva el comportamiento actual y se puede usar el filtro de operación existente.
- **No:** agregar conversión, nuevas periodicidades ni reglas especiales de validación para alquiler. La historia define el significado y la presentación del precio actual.

## Riesgos

| Riesgo | Mitigación |
| --- | --- |
| Un precio de alquiler existente no corresponde a un importe mensual. | Se conserva y se considera mensual según la decisión acordada; la revisión individual queda fuera de esta historia. |
| Al cambiar la operación, el importe conservado puede no ser adecuado para la nueva operación. | La etiqueta cambia de inmediato y permite corregir el importe antes de guardar. |
| El filtro y el orden del panel pueden comparar alquileres mensuales con precios totales de venta. | Se conserva el filtro por operación existente para limitar los resultados cuando sea necesario. |
