# SPEC 15 — Filtrar consultas del panel

> **Estado:** Implementada
> **Depende de:** SPEC 08, SPEC 09, SPEC 12, SPEC 13
> **Fecha:** 2026-11-10
> **Objetivo:** Hacer funcionales la búsqueda, el filtro por propiedad y el orden del panel de consultas.
> **Historia de origen:** H-02 en backlogs/inmobiliaria-v4.md

## Alcance

**Incluye:**

- Buscar en el nombre y el correo de la persona, el título de la propiedad y el mensaje. La búsqueda no distingue mayúsculas ni tildes.
- Filtrar por una propiedad y ordenar las consultas por fecha de recepción, de más recientes o de más antiguas. La búsqueda y el filtro se combinan.
- Incluir en el selector todas las propiedades con consultas accesibles, incluso las eliminadas. Identificar cada propiedad por separado. Cuando varias tengan el mismo título, mostrar también su identificador.
- Aplicar el selector y el orden al cambiarlos, y la búsqueda tras una breve pausa al escribir. Activar el atajo `/` para enfocar la búsqueda cuando no se escribe en otro campo.
- Conservar búsqueda, propiedad, orden y página en la URL. Mantener la paginación existente de 15 consultas por página y los permisos actuales de cada rol.
- Distinguir el total de consultas accesibles del número de coincidencias. Mostrar un estado sin coincidencias con una acción para limpiar la búsqueda y el filtro.

**Fuera de alcance:**

- Filtrar por publicador o por rango de fechas.
- Buscar por teléfono o por nombre del publicador.
- Responder consultas, asignarlas o marcar su seguimiento desde el panel.
- Cambiar los permisos de acceso o eliminar consultas.

## Plan de implementación

### Bloque 1: consulta, opciones y permisos

1. Aplicar búsqueda, filtro de propiedad y orden a las consultas accesibles para el usuario. Identificar las propiedades por su identificador y conservar las consultas de propiedades eliminadas.
2. Obtener el total accesible y las opciones del selector sobre todas las páginas, sin revelar datos de otros agentes. Paginar las coincidencias y conservar los parámetros de la URL.
3. Cubrir con pruebas los campos de búsqueda, la combinación de controles, ambos órdenes, los títulos repetidos, las propiedades eliminadas, la paginación y los límites de acceso por rol.

### Bloque 2: interacción del panel

1. Conectar los controles existentes y el atajo de búsqueda. Retirar el aviso de que los filtros no funcionan.
2. Sincronizar los controles con la URL, volver a la primera página al cambiar búsqueda, propiedad u orden y mantener el detalle coherente con la página visible.
3. Mostrar el total accesible, las coincidencias y el estado sin coincidencias con su acción de limpieza. Conservar el estado existente cuando no hay consultas accesibles.
4. Comprobar el recorrido en el panel y ejecutar las pruebas afectadas, incluidas las de interfaz para los estados y la navegación.

## Criterios de aceptación

- [ ] La búsqueda encuentra coincidencias en nombre, correo, título de propiedad y mensaje sin distinguir mayúsculas ni tildes. No busca por teléfono ni publicador.
- [ ] El selector muestra las propiedades con consultas accesibles de todas las páginas, incluidas las eliminadas. Cada opción filtra una propiedad concreta, aunque otra tenga el mismo título; los títulos repetidos muestran su identificador.
- [ ] Al combinar búsqueda y propiedad, cada consulta mostrada cumple ambas condiciones.
- [ ] El orden permite mostrar primero las consultas más recientes o las más antiguas según su fecha de recepción.
- [ ] El selector y el orden se aplican al cambiar. La búsqueda se aplica tras una breve pausa al escribir. La tecla `/` enfoca la búsqueda cuando no se escribe en otro campo.
- [ ] Al cambiar la búsqueda, la propiedad o el orden, el listado vuelve a la primera página. Cada página muestra como máximo 15 consultas.
- [ ] Al recargar o abrir una URL filtrada se conservan la búsqueda, la propiedad, el orden y la página. Los enlaces de paginación conservan los parámetros.
- [ ] El administrador recibe todas las consultas accesibles; el agente recibe solo las que le corresponden. Los resultados, el total y las opciones del selector no revelan consultas ni propiedades de otros agentes.
- [ ] El encabezado y el menú muestran el total de consultas accesibles sin limitarlo por filtros. El listado muestra el número de coincidencias filtradas.
- [ ] Si hay consultas accesibles pero ninguna coincide, el panel muestra un estado sin coincidencias y permite limpiar la búsqueda y el filtro. Si no hay consultas accesibles, muestra el estado vacío correspondiente al rol.
- [ ] Si la consulta seleccionada deja de aparecer en la página visible, el detalle muestra la primera consulta visible. Si no hay resultados, no muestra ningún detalle.
- [ ] Las consultas de propiedades vendidas, alquiladas, archivadas o eliminadas siguen disponibles según los permisos existentes.

## Decisiones

- **Sí:** activar búsqueda, propiedad y orden existentes. La historia se limita a los controles actuales.
- **No:** filtrar por publicador o fechas. Ambos amplían el alcance de esta entrega.
- **Sí:** buscar nombre, correo, título y mensaje sin distinguir mayúsculas ni tildes. Teléfono y publicador quedan fuera de la búsqueda.
- **Sí:** identificar cada propiedad individualmente y conservar las eliminadas en el selector. Sus consultas permanecen disponibles aunque la propiedad ya no exista.
- **Sí:** aplicar los cambios al interactuar y conservarlos en la URL. La búsqueda espera una breve pausa para no ejecutar una navegación por cada pulsación.
- **Sí:** mantener el total accesible separado del número de coincidencias. Un filtro sin resultados no implica que no existan consultas.
- **Sí:** activar el atajo `/` que ya anuncia el panel, sin interferir al escribir en otro campo.
