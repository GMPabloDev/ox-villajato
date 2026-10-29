# Historia 8 — Consultas de propiedades del agente

- **Estado:** Aprobada
- **Fecha:** 2026-10-28
- **Origen:** `backlog.md`, Inmobiliaria v2, historia 3
- **Depende de:** historia 2 de la v2
- **Siguiente paso:** `/pair`

## Story

Como agente, quiero ver los contactos recibidos para mis propiedades, para atender las consultas que me corresponden.

## Acceptance criteria

- [ ] Dado que un visitante envía una consulta válida desde la ficha de una propiedad, cuando se confirma el envío, entonces la consulta queda disponible para el agente responsable y la inmobiliaria recibe el correo.
- [ ] Dado que un visitante envía una consulta válida y falla el envío del correo, cuando termina el envío de la consulta, entonces el visitante ve la confirmación y la consulta queda disponible para el agente responsable.
- [ ] Dado que inicié sesión como agente, cuando abro mis consultas, entonces veo solo las de mis propiedades, con la propiedad, nombre, correo, teléfono si se proporcionó, mensaje y fecha de recepción.
- [ ] Dado que una de mis propiedades se marcó como vendida o alquilada, cuando abro mis consultas, entonces sigo viendo las consultas que recibió antes del cierre.
- [ ] Dado que no tengo consultas, cuando abro el listado, entonces veo un estado vacío.
- [ ] Dado que soy agente, cuando intento acceder a una consulta de una propiedad de otro agente, entonces no puedo ver sus datos.
- [ ] Dado que no inicié sesión, cuando intento acceder a las consultas del panel, entonces se me redirige al inicio de sesión.

## Plan

- Guardar las consultas nuevas junto con sus datos y la propiedad correspondiente.
- Conservar el envío de correo sin impedir la confirmación si el correo falla.
- Añadir al panel un listado con los datos necesarios para atender cada consulta.
- Limitar el acceso de cada agente a las consultas de sus propiedades, también cuando estén cerradas.
- Probar la recepción, el fallo de correo, el listado y las restricciones de acceso.

## Out of scope

- Recuperar en el panel consultas anteriores que solo se enviaron por correo.
- Listado de consultas de todas las propiedades para el administrador, previsto en la historia 5 de la v2.
- Responder, marcar como atendidas o gestionar el seguimiento desde el panel.
- Recibir nuevas consultas sobre propiedades vendidas o alquiladas.
- Reintentar automáticamente los correos fallidos.
