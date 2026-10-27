# Historia 4 — Editar propiedad

- **Estado:** Aprobada
- **Fecha:** 2026-10-27
- **Origen:** `backlog.md`, historia 4
- **Depende de:** #1
- **Siguiente paso:** `/pair`

## Story

Como miembro de la inmobiliaria, quiero editar una propiedad publicada, para corregir su precio, descripción, fotos o datos.

## Acceptance criteria

- [ ] Dado que inicié sesión, cuando abro el listado de propiedades del panel, entonces veo todas las propiedades con su portada, título, precio y un acceso para editar cada una; si no hay ninguna, veo un estado vacío con la acción de publicar.
- [ ] Dado que abro la edición de una propiedad, cuando se muestra el formulario, entonces veo precargados todos sus datos y sus fotos en el orden actual, con la primera indicada como portada.
- [ ] Dado que estoy editando una propiedad, cuando cambio uno o más datos y guardo valores válidos, entonces vuelvo a su ficha pública con una confirmación y tanto la ficha como el catálogo muestran los datos actualizados.
- [ ] Dado que estoy editando una propiedad, cuando agrego fotos, quito una o más o cambio su orden y guardo, entonces la ficha pública muestra solo las fotos conservadas y las nuevas en el nuevo orden, y el catálogo usa como portada la primera foto.
- [ ] Dado que intento guardar con datos obligatorios faltantes o inválidos, sin fotos, con más de 8 fotos o con fotos fuera del formato o peso permitidos, entonces permanezco en la edición con los errores señalados y no cambia ningún dato, foto ni orden.
- [ ] Dado que no inicié sesión, cuando intento abrir el listado o la edición de una propiedad, o guardar cambios, entonces se me redirige al inicio de sesión.

## Plan

- Añadir al panel un listado de propiedades con portada, título, precio y acceso a editar, disponible desde la navegación y con estado vacío.
- Construir el formulario de edición precargado con los mismos campos y validaciones del alta.
- Gestionar la galería en el formulario: agregar, quitar y reordenar con controles de subir y bajar, usando la primera foto como portada y manteniendo entre 1 y 8.
- Guardar datos, altas, bajas y orden en una sola operación; si la validación falla no tocar nada, y al guardar eliminar del almacenamiento las fotos quitadas.
- Cubrir con pruebas el listado, la precarga, la edición de datos y fotos, el cambio de portada, las validaciones y el acceso restringido.

## Out of scope

- Eliminar la propiedad.
- Duplicar, archivar o cambiar la fecha de publicación.
- Marcar vendida o alquilada (historia 5).
- Editar desde el catálogo o la ficha pública.
- Edición masiva, historial, auditoría o deshacer cambios.
- Reordenar propiedades en el catálogo.
- Recortar, optimizar o editar la imagen en sí.
- Reemplazar una foto conservando su posición; se resuelve quitando y agregando.
- Guardado automático o borradores de cambios.
- Roles, permisos o más de una cuenta.

## Suggestions (your call)

- Extraer los campos comunes de alta y edición a un componente de formulario, porque el alta ya concentra toda esa estructura y la edición la repetiría.
- Compartir las reglas de validación entre alta y edición para que no diverjan.
