# SPEC 18 — Registrar garaje y amueblado

> **Estado:** Aprobada
> **Depende de:** SPEC 01, SPEC 04, SPEC 07
> **Fecha:** 2026-11-14
> **Objetivo:** Registrar si una propiedad tiene garaje y si está amueblada, y mostrar ambos datos en su ficha y en el panel.
> **Historia de origen:** H-05 en backlogs/inmobiliaria-v4.md

## Alcance

**Incluye:**

- Solicitar una elección explícita de sí o no para garaje y amueblado al publicar o editar una propiedad.
- Considerar que una propiedad tiene garaje cuando incluye un espacio de estacionamiento, techado o no.
- Mostrar ambos datos y sus valores en la ficha pública y el detalle de la propiedad en el panel.
- Eliminar las propiedades existentes de desarrollo y adaptar las propiedades de muestra. Conservar las consultas históricas.
- Detener la migración y consultar antes de continuar si existen propiedades reales en producción sin estos datos.

**Fuera de alcance:**

- Mostrar estos datos en las tarjetas del catálogo o añadir filtros por garaje o amueblado.
- Registrar la cantidad de estacionamientos, sus dimensiones o el grado de amueblado.
- Borrar propiedades reales o asignarles valores sin comprobar. Su migración requiere un acuerdo posterior.
- Cambiar los permisos, los estados de propiedad o las reglas existentes para fotografías y consultas.

## Plan de implementación

### Bloque 1: persistencia y datos existentes

1. Añadir los dos datos a la persistencia y al modelo de propiedad. Conservar la distinción entre sí y no en las lecturas y escrituras.
2. Antes de modificar datos existentes, proteger las propiedades reales: si existen en producción sin los nuevos datos, detener la migración y solicitar una decisión. No borrar ni inventar valores para ellas.
3. Permitir la eliminación de propiedades existentes en desarrollo y pruebas, conservando las consultas históricas. Adaptar la factory y las propiedades de muestra para incluir valores explícitos coherentes con sus descripciones.
4. Comprobar la persistencia de ambos valores, la conservación de consultas y la protección de datos fuera de desarrollo y pruebas.

### Bloque 2: registro y presentación

1. Añadir al formulario compartido de publicación y edición dos campos con elección explícita entre sí y no. Al editar, precargar los valores guardados.
2. Validar ambos campos en el servidor tanto al publicar como al editar. Ante valores ausentes o inválidos, señalar los errores y no guardar cambios.
3. Incorporar ambos datos a la representación de la propiedad. Mostrar garaje y amueblado con su valor sí o no en la ficha pública y en el detalle del panel.
4. Conservar las restricciones de acceso existentes: el administrador gestiona todas las propiedades y cada agente solo edita las suyas.

### Bloque 3: verificación

1. Actualizar las pruebas afectadas por los campos obligatorios. Cubrir el alta y la edición con valores afirmativos y negativos, la precarga y el rechazo de datos ausentes o inválidos.
2. Comprobar la presentación de ambos valores en la ficha y el detalle del panel, los permisos existentes y la protección de las propiedades reales durante la migración.
3. Ejecutar las pruebas afectadas y comprobar en navegador la publicación, la edición y las dos vistas.

## Criterios de aceptación

- [ ] Al publicar o editar, el formulario exige seleccionar sí o no para garaje y para amueblado. No se presenta una respuesta como seleccionada por defecto al publicar.
- [ ] Al editar, el formulario muestra los valores guardados y permite cambiarlos de sí a no y de no a sí.
- [ ] El servidor rechaza cada campo ausente o inválido en alta y edición. Muestra los errores y no guarda cambios.
- [ ] La ficha pública y el detalle del panel muestran ambos datos, tanto cuando su valor es sí como cuando es no.
- [ ] Garaje significa que la propiedad incluye un espacio de estacionamiento, techado o no.
- [ ] El administrador conserva el acceso a la edición de todas las propiedades y el agente solo puede editar las suyas, sujeto a las restricciones de estado existentes.
- [ ] Las propiedades existentes pueden eliminarse en desarrollo y pruebas sin borrar las consultas históricas. Las propiedades de muestra tienen ambos datos explícitos y coherentes con sus descripciones.
- [ ] Si existen propiedades reales en producción sin estos datos, la migración se detiene sin borrarlas ni asignarles valores. Antes de continuar se consulta cómo migrarlas.
- [ ] Las pruebas afectadas pasan y se comprueban en navegador la publicación, la edición, la ficha pública y el detalle del panel.

## Decisiones

- **Sí:** exigir una elección explícita para ambos datos. Evita interpretar una selección predeterminada como información confirmada.
- **Sí:** incluir espacios de estacionamiento techados o no en la definición de garaje.
- **Sí:** mostrar siempre ambos valores en la ficha pública y el detalle del panel, incluidos los negativos.
- **No:** añadir los datos a tarjetas o filtros. La historia se limita al registro y a su presentación en las vistas de detalle.
- **Sí:** eliminar propiedades de desarrollo y adaptar las muestras. Las consultas históricas se conservan.
- **No:** borrar propiedades reales ni asignarles valores desconocidos. Si existen en producción, se detiene la migración hasta acordar su tratamiento.

## Riesgos

| Riesgo | Mitigación |
| --- | --- |
| Una propiedad real existente no tiene valores verificados para los nuevos campos. | Detener la migración en producción y acordar el tratamiento antes de continuar. |
| El borrado de propiedades de desarrollo puede afectar consultas históricas. | Conservar las consultas y verificar su persistencia tras el borrado. |
