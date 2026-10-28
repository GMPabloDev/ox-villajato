# Backlog — Inmobiliaria v1

Inmobiliaria única en Perú. Casas y departamentos en venta y alquiler. Recorrido: publicar → catálogo público → consulta.

## Historias

1. Como miembro de la inmobiliaria, quiero publicar una casa o departamento con sus datos y fotos, para que aparezca en el catálogo público con su propia ficha.
2. Como visitante, quiero enviar una consulta desde la ficha de una propiedad, para que la inmobiliaria reciba mis datos y pueda contactarme. Requires: #1
3. Como visitante, quiero filtrar el catálogo por operación, tipo y zona, para encontrar las propiedades que me interesan. Requires: #1
4. Como miembro de la inmobiliaria, quiero editar una propiedad publicada, para corregir su precio, descripción, fotos o datos. Requires: #1
5. Como miembro de la inmobiliaria, quiero marcar una propiedad como vendida o alquilada, para que deje de aparecer como disponible. Requires: #1, #4

## Historias refinadas

- [Historia 1 — Publicar propiedad](stories/01-publicar-propiedad.md)
- [Historia 2 — Consulta desde la ficha](stories/02-consulta-desde-la-ficha.md)
- [Historia 3 — Filtrar el catálogo](stories/03-filtrar-catalogo.md)
- [Historia 4 — Editar propiedad](stories/04-editar-propiedad.md)
- [Historia 5 — Marcar vendida o alquilada](stories/05-marcar-vendida-alquilada.md)

## Deuda técnica

- **Listado del panel:** paginar y reducir el payload de `/panel/properties` para enviar solo portada, título, precio y enlace de edición; hoy trae todas las propiedades con descripción y todas sus fotos. Queda fuera de la historia 4 y de la historia 1, que excluye “Paginación y búsqueda”.
- **Controller del panel:** separar `panelIndex`, `edit` y `update` de `PropertyController` en un controller propio del panel, para no mezclar el catálogo público con la gestión interna. Es un refactor sin cambio de comportamiento.

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
