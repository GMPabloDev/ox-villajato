# Historia 2 — Consulta desde la ficha

- **Estado:** Aprobada
- **Fecha:** 2026-10-26
- **Origen:** `backlogs/inmobiliaria-v1.md`, historia 2
- **Depende de:** #1
- **Siguiente paso:** `/pair`

## Story

Como visitante, quiero enviar una consulta desde la ficha de una propiedad, para que la inmobiliaria reciba mis datos y pueda contactarme.

## Acceptance criteria

- [ ] Dado que soy un visitante sin iniciar sesión y estoy en la ficha pública de una propiedad, cuando completo mi nombre, email, teléfono opcional y mensaje, y envío la consulta, entonces veo una confirmación en la ficha y la inmobiliaria recibe un correo con mis datos y el título y enlace de esa propiedad.
- [ ] Dado que envío la consulta con nombre, email o mensaje faltantes, o con un email o teléfono con formato inválido, entonces permanezco en la ficha con los errores señalados y no se envía ningún correo.
- [ ] Dado que envío varias consultas seguidas desde la misma conexión, cuando supero el límite permitido, entonces veo un aviso para esperar y no se envía la consulta.

## Plan

- Recibir la consulta en el servidor con nombre, email, teléfono opcional y mensaje, validando los datos y mostrando los errores en el propio formulario.
- Enviar un correo a la dirección configurada de la inmobiliaria con los datos del visitante y el título y enlace de la propiedad, de modo que pueda responder directamente al email del visitante.
- Incrustar el formulario en la ficha pública, disponible para cualquier visitante, con confirmación tras el envío y el formulario limpio.
- Aplicar un límite de envíos por IP y mostrar un aviso claro al superarlo.
- Probar el envío válido y el contenido del correo, las validaciones y el límite, simulando el correo.

## Out of scope

- Guardar las consultas en la base de datos, una bandeja o un historial en el panel.
- Enviar un correo de confirmación al visitante.
- Captcha, moderación o bloqueo permanente de visitantes.
- Consultas desde el catálogo o desde una página fuera de la ficha.
- Adjuntos o archivos en la consulta.
- Responder o dar seguimiento a la consulta desde el panel.
- Notificación por WhatsApp u otro canal.
- Configurar el proveedor de correo real en producción y verificar la entrega externa.
- Reintentos o aviso al visitante si el correo falla.
- Consultas sobre propiedades vendidas o alquiladas (historia 5).

## Notas de implementación

- La dirección de la inmobiliaria que recibe las consultas se configura por entorno.
- En desarrollo y pruebas el correo se registra en el log o se simula; en producción se usa el proveedor configurado.
