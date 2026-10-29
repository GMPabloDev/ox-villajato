# Historia 9 — Consultas de todas las propiedades

- **Estado:** Aprobada
- **Fecha:** 2026-10-28
- **Origen:** `backlog.md`, Inmobiliaria v2, historia 5
- **Depende de:** historia 3 de la v2
- **Siguiente paso:** `/pair`

## Story

Como administrador, quiero ver los contactos recibidos para todas las propiedades, para supervisar las consultas.

## Acceptance criteria

- [ ] Dado que inicié sesión como administrador, cuando abro las consultas, entonces veo las recibidas para todas las propiedades, incluidas las de distintos agentes y las mías. Cada consulta muestra la propiedad, el nombre, el correo, el teléfono si se proporcionó, el mensaje y la fecha de recepción.
- [ ] Dado que una propiedad se marcó como vendida o alquilada, cuando abro las consultas como administrador, entonces sigo viendo las consultas que recibió antes del cierre.
- [ ] Dado que no hay consultas, cuando abro el listado como administrador, entonces veo un estado vacío.
- [ ] Dado que hay consultas en varias páginas, cuando navego por el listado como administrador, entonces puedo verlas todas.
- [ ] Dado que inicié sesión como agente, cuando abro las consultas, entonces sigo viendo únicamente las de mis propiedades.
- [ ] Dado que no inicié sesión, cuando intento acceder a las consultas del panel, entonces se me redirige al inicio de sesión.

## Plan

- Ampliar el listado existente para que el administrador vea las consultas de todas las propiedades.
- Mantener la restricción de acceso de los agentes y la paginación.
- Ajustar el texto del listado y del estado vacío para el administrador.
- Probar el acceso global, los datos mostrados, las propiedades cerradas y las restricciones existentes.

## Out of scope

- Recuperar consultas anteriores que solo se enviaron por correo.
- Responder, marcar como atendidas o gestionar el seguimiento desde el panel.
- Recibir nuevas consultas sobre propiedades vendidas o alquiladas.
- Filtros y funciones adicionales de supervisión.
