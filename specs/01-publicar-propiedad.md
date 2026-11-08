# Historia 1 — Publicar propiedad

- **Estado:** Aprobada
- **Fecha:** 2026-10-25
- **Origen:** `backlogs/inmobiliaria-v1.md`, historia 1
- **Depende de:** ninguna
- **Siguiente paso:** `/pair`

## Story

Como miembro de la inmobiliaria, quiero publicar una casa o departamento con sus datos y fotos, para que aparezca en el catálogo público con su propia ficha.

## Acceptance criteria

- [ ] Dado que inicié sesión con la cuenta de la inmobiliaria, cuando completo operación, tipo, título, descripción, precio con moneda, ciudad, zona, dormitorios, baños y área, y adjunto entre 1 y 8 fotos válidas, entonces la propiedad se publica y veo su ficha pública con todos esos datos y fotos.
- [ ] Dado que una propiedad fue publicada, cuando cualquier visitante abre el catálogo en `/`, entonces la ve listada con su foto principal, título, operación, tipo, precio con moneda, ciudad y zona, y puede abrir su ficha sin iniciar sesión.
- [ ] Dado que envío el formulario con datos obligatorios faltantes o inválidos, o con fotos fuera del rango 1–8, de más de 5 MB o que no sean JPG, PNG o WebP, entonces permanezco en el formulario con los errores señalados y no se publica nada.
- [ ] Dado que no inicié sesión, cuando intento abrir el formulario de publicación, entonces se me redirige al inicio de sesión.
- [ ] Dado que soy un visitante, cuando intento registrarme, entonces la opción de registro no existe y no puedo crear una cuenta para acceder al panel.

## Plan

- Modelar la propiedad con operación, tipo, precio y moneda, ubicación y datos; galería ordenada donde la primera foto es la portada.
- Construir el formulario de publicación en el panel con validación en servidor y guardado de fotos en S3 (adaptador oficial; local en desarrollo y pruebas).
- Exponer el catálogo público en `/` y una ficha con URL propia por propiedad, sin autenticación y con estado vacío.
- Dar acceso al panel solo a la cuenta compartida: cerrar el registro público, retirar los enlaces de registro y redirigir a inicio de sesión a los invitados.
- Cubrir con pruebas el alta válida, las validaciones, la visibilidad pública, el bloqueo a invitados y el registro cerrado.

## Out of scope

- Filtros por operación, tipo y zona (historia 3).
- Consulta desde la ficha (historia 2).
- Edición, borrado y reordenamiento de propiedades o fotos (historia 4).
- Marcar vendida o alquilada (historia 5).
- Borradores o publicación programada.
- Dirección exacta, mapas y conversión de moneda.
- Home con hero y catálogo debajo, y alias `/propiedades`.
- Listado de propiedades en el panel (llega con la edición).
- Paginación y búsqueda.
- Roles, permisos y gestión de más de una cuenta.
- Configuración del bucket/credenciales de AWS y creación de la cuenta compartida en producción.

## Notas de implementación

- La dependencia `league/flysystem-aws-s3-v3` está aprobada para el disco S3.
- En desarrollo y pruebas se usa disco local; en producción, S3.
