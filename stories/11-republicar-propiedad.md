# Historia 11 — Consultar y republicar propiedades archivadas

- **Estado:** Aprobada
- **Origen:** `backlogs/inmobiliaria-v3.md`, historia 2
- **Siguiente paso:** `/skill:ox-pair`

## Historia

Como agente o administrador, quiero consultar las propiedades archivadas y republicarlas cuando corresponda, para volver a ofrecerlas al público.

## Criterios de aceptación

- [ ] Dado que soy agente, cuando consulto las propiedades archivadas, entonces veo únicamente las que publiqué. Si no tengo ninguna, veo un mensaje que lo indica.
- [ ] Dado que soy administrador, cuando consulto las propiedades archivadas, entonces veo las de todos los agentes y las que publiqué. Si no hay ninguna, veo un mensaje que lo indica.
- [ ] Dada una propiedad archivada que publiqué, cuando la republico como agente o la republica el administrador y confirma la acción, entonces el panel confirma la republicación, la propiedad desaparece de las archivadas, vuelve al listado habitual como disponible y reaparece en el catálogo con su ficha y formulario de consulta disponibles.
- [ ] Dada una propiedad archivada de otro agente, cuando intento republicarla, entonces se rechaza la acción y la propiedad permanece archivada.
- [ ] Dada una propiedad que no está archivada, cuando se intenta republicarla desde esta función, entonces se rechaza la acción y conserva su estado.
- [ ] Dado que no inicié sesión, cuando intento consultar las archivadas o republicar una propiedad, entonces se me redirige al inicio de sesión.

## Plan

- Añadir al panel un acceso al listado de archivadas, con los mismos límites de visibilidad por usuario que el listado habitual.
- Permitir republicar una propiedad archivada tras confirmar la acción y mostrar el resultado.
- Restringir la republicación al propietario o al administrador y rechazar propiedades que no estén archivadas.
- Verificar los permisos, los estados y el regreso de la propiedad al recorrido público.

## Fuera de alcance

- Editar propiedades mientras están archivadas.
- Eliminar propiedades archivadas, previsto en la historia 3.
- Mostrar el agente responsable de cada consulta, previsto en la historia 4.
