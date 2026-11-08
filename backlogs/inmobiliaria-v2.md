# Backlog — Inmobiliaria v2

Panel para una inmobiliaria en Perú, con un administrador y agentes con cuentas individuales.

El administrador crea las cuentas y puede ver todas las propiedades y contactos. Cada agente solo ve sus propiedades y los contactos recibidos para ellas. Se puede eliminar la propiedad de desarrollo existente para empezar en limpio.

## Historias

1. Como administrador, quiero crear cuentas individuales para los agentes, para controlar quién accede al panel.
2. Como agente, quiero publicar y gestionar mis propias propiedades, para mantenerlas actualizadas sin acceder a las de otros agentes. Requires: #1
3. Como agente, quiero ver los contactos recibidos para mis propiedades, para atender las consultas que me corresponden. Requires: #2
4. Como administrador, quiero ver y gestionar las propiedades de todos los agentes, para supervisar la oferta de la inmobiliaria. Requires: #2
5. Como administrador, quiero ver los contactos recibidos para todas las propiedades, para supervisar las consultas. Requires: #3

El historial de modificaciones queda para más adelante.

## Historias refinadas

- [SPEC 06 — Crear cuentas para agentes](../specs/06-crear-cuentas-agentes.md) (historia 1)
- [SPEC 07 — Gestionar propiedades propias](../specs/07-gestionar-propiedades-agente.md) (historia 2)
- [SPEC 08 — Consultas de propiedades del agente](../specs/08-consultas-propiedades-agente.md) (historia 3)
- Historia 4 — absorbida por la SPEC 07: el acceso global del administrador quedó como criterio de aceptación de esa spec, sin spec propia.
- [SPEC 09 — Consultas de todas las propiedades](../specs/09-consultas-todas-propiedades.md) (historia 5)

Todas las historias están implementadas y cubiertas por pruebas en `tests/`.
