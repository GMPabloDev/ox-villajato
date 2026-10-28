# Historia 5 — Marcar vendida o alquilada

- **Estado:** Aprobada
- **Fecha:** 2026-10-27
- **Origen:** `backlog.md`, historia 5
- **Depende de:** #1 (listado del panel de #4)
- **Siguiente paso:** `/pair`

## Story

Como miembro de la inmobiliaria, quiero marcar una propiedad como vendida o alquilada, para que deje de aparecer en el catálogo y su ficha pública ya no esté disponible.

## Acceptance criteria

- [ ] Dado que inicié sesión, cuando abro el listado de propiedades del panel, entonces veo todas las propiedades con su estado —Disponible, Vendida o Alquilada— y, en cada una, la acción que corresponde: marcar como vendida si es de venta, marcar como alquilada si es de alquiler, o volver a disponible si está cerrada.
- [ ] Dado que estoy en el listado del panel, cuando marco una propiedad disponible como vendida o alquilada y confirmo, entonces veo una confirmación, el listado muestra su nuevo estado y la propiedad deja de aparecer en el catálogo y en las opciones de zona.
- [ ] Dado que una propiedad está vendida o alquilada, cuando elijo volver a disponible y confirmo, entonces veo una confirmación, el listado muestra Disponible, la propiedad reaparece en el catálogo y en las opciones de zona, y su ficha pública vuelve a abrirse.
- [ ] Dado que una propiedad está vendida o alquilada, cuando un visitante abre su ficha o intenta enviar una consulta, entonces la ficha y el envío de consulta responden como no encontrados y no se envía ningún correo.
- [ ] Dado que una propiedad está vendida o alquilada, cuando edito sus datos sin cambiar la operación y guardo, entonces sigue cerrada y su ficha sigue sin estar disponible.
- [ ] Dado que una propiedad está vendida o alquilada, cuando cambio su operación en la edición y guardo, entonces vuelve a estar disponible y reaparece en el catálogo.
- [ ] Dado que no inicié sesión, cuando intento abrir el listado del panel, marcar una propiedad o volverla a disponible, entonces se me redirige al inicio de sesión.

## Plan

- Modelar el estado de la propiedad como disponible, vendida o alquilada, con el estado cerrado correspondiente a su operación y el reinicio a disponible al cambiarla.
- Mostrar el estado y la acción correspondiente en cada tarjeta del listado del panel, con confirmación y aviso de éxito.
- Excluir las propiedades cerradas del catálogo y de las opciones de zona, y hacer que su ficha y el envío de consulta respondan como no encontrados.
- Mantener las propiedades cerradas visibles y editables en el panel, y permitir volver a disponible desde el listado.
- Cubrir con pruebas el cierre y la reapertura, la salida del catálogo y las zonas, los 404 de ficha y consulta, la edición y el acceso restringido.

## Out of scope

- Fecha, motivo, historial o auditoría del cierre.
- Estados adicionales como reservada o pausada, o cierre programado.
- Marcar o revertir varias propiedades a la vez.
- Eliminar, archivar o duplicar propiedades cerradas.
- Recibir, guardar o responder consultas de propiedades cerradas.
- Añadir un campo de estado al formulario de edición; el estado se marca y se revierte desde el listado.
- Mostrar u operar el estado desde el catálogo o la ficha pública.
- Roles, permisos o más de una cuenta.

## Suggestions (your call)

- Modelar el estado como enum con `label()`, siguiendo `PropertyOperation` y `PropertyType`, para no repetir las etiquetas entre panel y pruebas.
- Reutilizar la tarjeta existente del listado del panel añadiendo el distintivo de estado y la acción, sin crear una vista nueva.
- Ajustar el texto del estado vacío del catálogo a «No hay propiedades disponibles» para que siga siendo cierto cuando todas estén cerradas.
