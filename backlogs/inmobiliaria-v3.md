# Backlog — Inmobiliaria v3

Inmobiliaria en Perú con propiedades publicadas por agentes y consultas de posibles clientes. Se busca retirar propiedades de la vista pública sin marcarlas como vendidas y conocer el agente responsable de cada consulta.

Stack existente: Laravel, PHP, Inertia y React.

Decisiones importantes: los agentes pueden archivar; el agente que publicó la propiedad y el administrador pueden republicarla. Solo el administrador puede eliminar propiedades archivadas, confirmando con su nombre. Las consultas y la identificación de la propiedad y su agente se conservan tras el borrado.

## Historias

1. Como agente, quiero archivar una propiedad que publiqué, para retirarla de la vista pública sin marcarla como vendida.
2. Como agente o administrador, quiero consultar las propiedades archivadas y republicarlas cuando corresponda, para volver a ofrecerlas al público. Requires: #1
3. Como administrador, quiero eliminar definitivamente una propiedad archivada confirmando su nombre, para retirar sus datos sin perder las consultas recibidas. Requires: #1
4. Como administrador, quiero ver qué agente publicó la propiedad de cada consulta, para saber a quién corresponde, incluso si la propiedad fue eliminada.
