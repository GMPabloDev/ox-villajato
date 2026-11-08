# Historia 12 — Eliminar propiedad archivada

- **Estado:** Aprobada
- **Origen:** `backlogs/inmobiliaria-v3.md`, historia 3
- **Siguiente paso:** `/skill:ox-pair`

## Historia

Como administrador, quiero eliminar definitivamente una propiedad archivada confirmando su nombre, para retirar sus datos sin perder las consultas recibidas.

## Criterios de aceptación

- [ ] Dada una propiedad archivada, cuando el administrador solicita eliminarla y escribe su título con coincidencia exacta, incluidas mayúsculas y espacios, entonces la propiedad desaparece del panel, sus fotos se eliminan del almacenamiento y el administrador recibe una confirmación.
- [ ] Dada una solicitud de eliminación, cuando el título está vacío o no coincide exactamente, entonces se muestra un error y la propiedad y sus fotos permanecen intactas.
- [ ] Dada una propiedad eliminada, cuando el administrador o el agente que la publicó consulta las consultas recibidas, entonces puede seguir viéndolas con sus datos y la identificación de la propiedad. También se conserva la identificación del agente que la publicó.
- [ ] Dada una propiedad que no está archivada, cuando alguien intenta eliminarla definitivamente, entonces la eliminación se rechaza y la propiedad permanece intacta.
- [ ] Dada una propiedad archivada, cuando un agente o una persona sin sesión intenta eliminarla, entonces la eliminación se rechaza y la propiedad permanece intacta.
- [ ] Dada una solicitud de eliminación, cuando ocurre un error, entonces se informa al administrador y no se muestra una confirmación de éxito.

## Plan

- Añadir la opción de eliminación en las propiedades archivadas para el administrador, con confirmación por título.
- Restringir la operación a administradores y propiedades archivadas, y validar el título.
- Conservar las consultas y la identificación de propiedad y agente tras el borrado.
- Eliminar la propiedad y sus fotos, y comunicar el resultado.
- Comprobar el recorrido, las restricciones y los casos de error.

## Fuera de alcance

- Mostrar al administrador qué agente publicó la propiedad de cada consulta. Corresponde a la historia 4.
- Restaurar propiedades eliminadas.
