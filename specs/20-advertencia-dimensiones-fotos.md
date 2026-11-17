# SPEC 20 — Advertencia por dimensiones de fotos

> **Estado:** Aprobada
> **Depende de:** SPEC 01, SPEC 04
> **Fecha:** 2026-11-15
> **Objetivo:** Advertir al seleccionar fotos que no cumplen las recomendaciones de dimensiones o proporción, sin impedir su publicación.
> **Historia de origen:** H-07 en backlogs/inmobiliaria-v4.md

## Alcance

**Incluye:**

- Revisar las dimensiones de cada foto nueva seleccionada o arrastrada al formulario compartido de publicación y edición. No revisar las fotos guardadas con anterioridad.
- Recomendar fotos horizontales de proporción 3:2, de al menos 1600 × 1067 píxeles y con un tamaño ideal de 2000 × 1333 píxeles. Mostrar el tamaño ideal como orientación, sin advertir solo por quedar por debajo de él.
- Advertir por tamaño cuando el ancho sea menor de 1600 píxeles o el alto menor de 1067 píxeles. Advertir por proporción cuando el cociente ancho dividido entre alto sea menor de 1,425 o mayor de 1,575. Los extremos están incluidos en el intervalo recomendado.
- Mostrar junto a la miniatura de cada foto afectada si el problema es el tamaño, la proporción o ambos. Explicar que se puede quitar la foto o continuar y guardar. La advertencia desaparece al quitar la foto y permanece asociada a ella al reordenarla.
- Mostrar el estado de revisión mientras se leen las dimensiones. No permitir el envío hasta que termine la revisión de todas las fotos nuevas que siguen seleccionadas. Al terminar, permitir guardar sin confirmación adicional, incluso cuando haya advertencias.
- Si no se pueden leer las dimensiones de una foto, indicar junto a ella que no se pudieron comprobar y permitir continuar. Evitar que una lectura que no termine mantenga el formulario bloqueado indefinidamente; tratarla como una lectura fallida.
- Conservar el límite de 1 a 8 fotos en total, los formatos JPG, PNG y WebP y el máximo de 5 MB por foto. Mantener las validaciones existentes del servidor y distinguir sus errores de las advertencias no bloqueantes.

**Fuera de alcance:**

- Revisar retrospectivamente fotos ya guardadas o almacenar sus dimensiones.
- Recortar, redimensionar, comprimir o editar imágenes, o cambiar el encuadre de las vistas públicas y del panel.
- Cambiar los formatos, el peso máximo, el número de fotos o los permisos existentes.
- Añadir confirmaciones obligatorias, cambios al almacenamiento o nuevas dependencias.

## Plan de implementación

### Bloque 1: revisión y avisos en el formulario

1. Adaptar `resources/js/components/property-photo-fields.tsx` para revisar las dimensiones de cada foto nueva aceptada, tanto al seleccionarla como al arrastrarla. Aplicar de forma independiente las reglas de tamaño y proporción.
2. Mostrar las recomendaciones y los avisos asociados a cada miniatura. Mantener la asociación al reordenar, quitar o añadir fotos y evitar que el resultado tardío de una foto retirada altere la selección actual.
3. Conectar el estado de revisión con `resources/js/components/property-form-sheet.tsx` para impedir el envío mientras haya fotos seleccionadas pendientes. Gestionar fallos y lecturas que no terminen como comprobaciones fallidas, sin impedir que el usuario continúe.
4. Comprobar que los avisos se distinguen de los errores de validación mediante texto y no solo por color, y que se perciben en móvil, escritorio y con lector de pantalla.

### Bloque 2: verificación de comportamiento

1. Añadir pruebas de navegador para la selección y el arrastre, los límites de dimensiones y proporción, los avisos simultáneos, el reordenamiento, la retirada y la espera de la revisión. Cubrir la lectura fallida y el envío con advertencias.
2. Verificar el alta y la edición, incluidas fotos ya guardadas y nuevas. Comprobar que una foto con advertencia se guarda cuando cumple las validaciones existentes.
3. Ejecutar las pruebas afectadas y comprobar que se mantienen el rechazo de formatos y pesos no admitidos, el límite de fotos y los controles de portada y orden.

## Criterios de aceptación

- [ ] Al publicar o editar, seleccionar o arrastrar una foto nueva inicia la revisión y muestra las recomendaciones de 3:2, mínimo 1600 × 1067 e ideal 2000 × 1333 píxeles.
- [ ] Una foto de 1600 × 1067 no recibe aviso de tamaño. Una de 1599 × 1067 o 1600 × 1066 sí lo recibe. Una foto que cumple el mínimo no recibe aviso solo por estar debajo del ideal.
- [ ] Una foto cuya relación ancho/alto esté entre 1,425 y 1,575, incluidos ambos extremos, no recibe aviso de proporción. Una foto fuera de ese intervalo sí lo recibe, incluidas las cuadradas y verticales.
- [ ] Cada foto muestra junto a su miniatura los motivos que le corresponden. Si incumple tamaño y proporción, muestra ambos. Los avisos permiten identificar la foto sin depender solo del color.
- [ ] Al reordenar fotos, cada aviso sigue asociado a su foto. Al quitar una foto, desaparece su aviso y una lectura pendiente o tardía de esa foto no mantiene bloqueado el envío ni modifica otra foto.
- [ ] Mientras alguna foto nueva seleccionada se revisa, el formulario indica que está revisando y no envía la propiedad. Al terminar todas las revisiones, permite guardar sin confirmación adicional, aunque existan advertencias.
- [ ] Si falla o no termina la lectura de una foto, el formulario indica que no pudo comprobar sus dimensiones y permite continuar. El servidor mantiene la validación de imagen existente.
- [ ] Una foto con advertencia que cumple las validaciones existentes puede guardarse al publicar o editar. Las fotos guardadas previamente no reciben una revisión retroactiva al abrir la edición.
- [ ] Se mantienen las restricciones de 1 a 8 fotos, JPG, PNG o WebP y un máximo de 5 MB por foto, así como las reglas y los errores actuales del servidor.
- [ ] Pasan las pruebas afectadas y se comprueban los avisos, la revisión y el guardado en navegador, tanto en móvil como en escritorio.

## Decisiones

- **Sí:** advertir por debajo del mínimo y presentar el tamaño ideal como orientación. Las fotos que alcanzan el mínimo no generan una advertencia adicional por ser menores que el ideal.
- **Sí:** aceptar una desviación de hasta el 5 % respecto de la proporción 3:2. El intervalo evita avisos por diferencias pequeñas y por el redondeo de píxeles.
- **Sí:** revisar solo fotos nuevas en publicación y edición. Las fotos existentes no almacenan sus dimensiones y no necesitan una revisión retroactiva para esta historia.
- **Sí:** mostrar la causa junto a cada foto y permitir guardar sin confirmación adicional. El usuario puede retirar la foto o conservarla.
- **Sí:** esperar a que termine la revisión antes de permitir el envío. Así el usuario puede ver los avisos antes de decidir. Un fallo de lectura permite continuar.
- **Sí:** conservar los límites de formato y peso existentes. Aunque la nota del backlog deja esos límites por definir, el formulario y el servidor ya los aplican.
- **No:** rechazar una foto por sus dimensiones o proporción, ni modificarla automáticamente. Son recomendaciones, no condiciones para publicar.

## Riesgos

| Riesgo | Mitigación |
| --- | --- |
| Una lectura asíncrona termina después de quitar la foto o queda pendiente sin resolver. | Asociar el resultado a la foto seleccionada, ignorar resultados tardíos y tratar la lectura que no termina como fallida. |
| La persona confunde una advertencia con un error que impide guardar. | Explicar que puede continuar y distinguir los avisos de los errores existentes con texto y presentación. |
| Una foto recomendada se recorta en alguna vista por usar encuadres diferentes. | Mantener los encuadres actuales fuera de esta historia y no prometer que la foto se verá completa en todas las vistas. |
