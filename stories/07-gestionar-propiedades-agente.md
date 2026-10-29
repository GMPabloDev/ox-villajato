# Historia 7 — Gestionar propiedades propias

- **Estado:** Aprobada
- **Fecha:** 2026-10-28
- **Origen:** `backlog.md`, Inmobiliaria v2, historia 2
- **Depende de:** historia 1 de la v2
- **Siguiente paso:** `/pair`

## Story

Como agente, quiero publicar y gestionar mis propias propiedades, para mantenerlas actualizadas sin acceder a las de otros agentes.

## Acceptance criteria

- [ ] Dado que inicié sesión como agente, cuando publico una propiedad con datos y fotos válidos, entonces queda asociada a mi cuenta y aparece en el catálogo público con su ficha.
- [ ] Dado que soy agente, cuando abro el listado del panel, entonces veo únicamente mis propiedades, incluidas las cerradas, con sus acciones de gestión. Si no tengo propiedades, veo un estado vacío y la opción de publicar.
- [ ] Dado que soy agente, cuando edito mis propiedades o las marco como vendidas, alquiladas o disponibles, entonces los cambios se reflejan en el panel y en el catálogo según las reglas existentes.
- [ ] Dado que soy agente, cuando intento abrir la edición, modificar o cambiar el estado de una propiedad de otro agente, entonces no puedo hacerlo y la propiedad no cambia.
- [ ] Dado que soy agente, cuando envío datos o fotos inválidos al publicar o editar, entonces veo los errores y no se publica ni se modifica la propiedad.
- [ ] Dado que soy administrador, cuando abro el listado del panel, entonces veo todas las propiedades y puedo publicar y gestionar propiedades, incluidas las de los agentes.
- [ ] Dado que no inicié sesión, cuando intento acceder a la gestión de propiedades, entonces se me redirige al inicio de sesión.
- [ ] Dado que las propiedades existentes son datos de prueba, cuando se habilita esta versión, entonces se empieza sin esas propiedades.

## Plan

- Eliminar las propiedades de prueba existentes para empezar en limpio.
- Asociar cada propiedad nueva con la cuenta que la publica.
- Habilitar para los agentes el listado, la publicación y la gestión de sus propiedades.
- Restringir la gestión de cada agente a sus propiedades y conservar el acceso global del administrador.
- Cubrir permisos, validaciones y recorridos de publicación y gestión con pruebas.

## Out of scope

- Listado de contactos por agente o administrador, previsto para las historias 3 y 5 de la v2.
- Funcionalidades de supervisión adicionales para el administrador.
- Transferencia de propiedades entre agentes.
- Eliminación de propiedades desde el panel e historial de modificaciones.
