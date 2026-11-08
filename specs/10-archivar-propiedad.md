# Historia 10 — Archivar propiedad

- **Estado:** Aprobada
- **Origen:** `backlogs/inmobiliaria-v3.md`, historia 1
- **Siguiente paso:** `/skill:ox-pair`

## Historia

Como agente o administrador, quiero archivar una propiedad disponible, para retirarla de la vista pública sin marcarla como vendida ni alquilada.

## Criterios de aceptación

- [ ] Dada una propiedad disponible publicada por un agente, cuando ese agente o el administrador la archiva, entonces el panel confirma la acción y la propiedad deja de aparecer en el listado habitual.
- [ ] Dada una propiedad archivada, cuando una persona consulta el catálogo, intenta abrir su ficha o enviar una consulta, entonces la propiedad no aparece en el catálogo y su ficha y formulario de consulta no están disponibles.
- [ ] Dada una propiedad disponible de otro agente, cuando un agente intenta archivarla, entonces la acción se rechaza y la propiedad permanece sin cambios.
- [ ] Dada una propiedad vendida o alquilada, cuando se intenta archivarla, entonces la acción se rechaza y conserva su estado.
- [ ] Dada una propiedad archivada, cuando se revisan sus consultas recibidas, entonces estas siguen disponibles.

## Plan

- Añadir la acción de archivar para propiedades disponibles, respetando los permisos acordados.
- Retirar las propiedades archivadas del listado habitual y de los recorridos públicos.
- Confirmar la acción al usuario y conservar los datos y consultas de la propiedad.
- Verificar los casos autorizados, los intentos rechazados y la conservación de consultas.

## Fuera de alcance

- Consultar el listado de propiedades archivadas y republicarlas. Corresponde a la historia 2.
- Eliminar propiedades archivadas. Corresponde a la historia 3.
- Mostrar el agente de cada consulta. Corresponde a la historia 4.
