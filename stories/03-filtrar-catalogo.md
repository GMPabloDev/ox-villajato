# Historia 3 — Filtrar el catálogo

- **Estado:** Aprobada
- **Fecha:** 2026-10-27
- **Origen:** `backlog.md`, historia 3
- **Depende de:** #1
- **Siguiente paso:** `/pair`

## Story

Como visitante, quiero filtrar el catálogo por operación, tipo y zona, para encontrar las propiedades que me interesan.

## Acceptance criteria

- [ ] Dado que hay propiedades publicadas y estoy en el catálogo, cuando uso el filtro de zona, entonces veo cada zona publicada una sola vez, en orden alfabético.
- [ ] Dado que estoy en el catálogo, cuando elijo uno o más filtros y pulso Filtrar, entonces veo únicamente las propiedades que cumplen todos los filtros elegidos, con los mismos datos de tarjeta que el catálogo sin filtros, y al recargar o compartir esa URL obtengo el mismo resultado.
- [ ] Dado que la combinación aplicada no coincide con ninguna propiedad, cuando se muestra el catálogo, entonces veo un mensaje de que no hay propiedades para esos filtros y una acción para limpiarlos que devuelve el catálogo completo.

## Plan

- Leer los filtros de la URL y validar operación y tipo contra sus valores permitidos antes de consultar; la zona se compara por coincidencia exacta.
- Aplicar los tres filtros en conjunto sobre la consulta del catálogo, conservando el orden actual y las tarjetas existentes.
- Poblar el filtro de zona con las zonas distintas de las propiedades publicadas, ordenadas alfabéticamente, y ofrecer "Todas"/"Todos" en cada filtro.
- Añadir la barra de filtros al catálogo con botón Filtrar y limpiar, conservar las selecciones en la URL y mostrar un estado vacío propio cuando no haya coincidencias.
- Cubrir con pruebas la combinación y los filtros parciales, el estado sin coincidencias, la lista de zonas y que el catálogo sin filtros no cambia.

## Out of scope

- Filtrar por ciudad, precio, dormitorios, baños, área, texto libre u ordenamiento.
- Zonas dependientes de la operación o el tipo elegidos.
- Paginación, contador de resultados y búsqueda.
- Normalizar o unificar variantes de escritura de las zonas (mayúsculas, tildes); se muestran tal como se publicaron.
- Recordar filtros entre visitas o aplicar filtros por defecto.
- Filtros en el panel o en la ficha de la propiedad.
- Propiedades vendidas o alquiladas (historia 5).
- Rutas amigables o páginas propias por filtro.
- Convertir la zona en un catálogo administrable al publicar.
