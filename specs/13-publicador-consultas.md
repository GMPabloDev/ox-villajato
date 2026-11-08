# Historia 13 — Publicador de cada consulta

- **Estado:** Aprobada
- **Origen:** `backlogs/inmobiliaria-v3.md`, historia 4
- **Siguiente paso:** `/skill:ox-pair`

## Historia

Como administrador, quiero ver quién publicó la propiedad de cada consulta, para saber a quién corresponde, incluso si la propiedad fue eliminada.

## Criterios de aceptación

- [ ] Dadas consultas de propiedades publicadas por distintos agentes, cuando el administrador abre el listado de consultas, ve el nombre del agente que publicó cada propiedad junto a la consulta correspondiente.
- [ ] Dada una consulta de una propiedad publicada por el administrador, cuando abre el listado de consultas, ve su nombre como publicador.
- [ ] Dada una consulta cuya propiedad fue eliminada, cuando el administrador abre el listado de consultas, sigue viendo el título de la propiedad y el nombre de quien la publicó, junto con los datos de la consulta.
- [ ] Dado un agente que abre sus consultas, cuando consulta el listado, sigue viendo únicamente las consultas de sus propiedades.

## Plan

- Recuperar el publicador asociado a cada consulta.
- Mostrar su nombre en el listado del administrador sin alterar la vista de los agentes.
- Comprobar las consultas existentes y las de propiedades eliminadas.

## Fuera de alcance

- Cambiar quién puede consultar o gestionar propiedades y consultas.
- Filtrar o asignar consultas a otros agentes.
- Eliminar cuentas de agentes.
