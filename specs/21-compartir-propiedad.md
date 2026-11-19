# SPEC 21 — Compartir propiedad

> **Estado:** Aprobada
> **Depende de:** SPEC 01
> **Fecha:** 2026-11-18
> **Objetivo:** Permitir que cualquier visitante comparta la ficha pública de una propiedad por correo, WhatsApp, Facebook, X o mediante un enlace.
> **Historia de origen:** H-08 en backlogs/inmobiliaria-v4.md

## Alcance

**Incluye:**

- Mostrar junto al título de la ficha pública un botón «Compartir» que ofrece correo, WhatsApp, Facebook, X y copiar enlace. El control funciona sin iniciar sesión, en móvil y escritorio, y admite teclado y lector de pantalla.
- Compartir la URL absoluta de la ficha de la propiedad, sin parámetros ni fragmentos de la navegación actual. Todos los destinos usan la misma URL pública existente.
- Abrir la aplicación de correo del visitante con el título y el enlace preparados. El visitante elige el destinatario y confirma el envío. Preparar también el título y el enlace para WhatsApp y X. Abrir WhatsApp, Facebook y X en otra pestaña; la plataforma externa controla el resto del recorrido.
- Copiar el enlace y confirmar visualmente que se copió. Si la copia falla o no está disponible, informar del fallo y mostrar el enlace seleccionable para copiarlo manualmente.
- Proporcionar metadatos de vista previa con el título, un fragmento de la descripción, la URL pública y la foto de portada. Los metadatos deben estar en el HTML inicial para que los lectores de enlaces puedan obtenerlos sin ejecutar JavaScript. Si no hay portada disponible en los datos, omitir la imagen sin impedir el acceso ni la opción de compartir.
- Mantener las reglas existentes de visibilidad. Una propiedad vendida, alquilada, archivada o eliminada deja de estar disponible mediante el enlace público.

**Fuera de alcance:**

- Añadir acciones de compartir a las tarjetas del catálogo o al panel.
- Enviar correos desde el servidor, gestionar destinatarios o registrar los envíos y las veces que se comparte.
- Exigir una cuenta, añadir favoritos o modificar los permisos y estados de las propiedades.
- Personalizar el contenido final, garantizar la vista previa que presenta cada plataforma o invalidar sus cachés.
- Incorporar nuevas redes, enlaces cortos, códigos QR o cambios en la URL pública de las fichas.

## Plan de implementación

### Bloque 1: metadatos de la ficha

1. Aprovechar los datos de `app/Http/Resources/PropertyResource.php` y la ruta pública existente para preparar título, descripción, enlace absoluto y portada. Evitar que los parámetros o fragmentos recibidos alteren el enlace compartido.
2. Adaptar la respuesta de `app/Http/Controllers/PropertyController.php` y la cabecera de `resources/views/app.blade.php` según corresponda para entregar los metadatos en el HTML inicial, sin depender de la hidratación de React. Mantener el comportamiento de navegación de Inertia y la visibilidad actual de la ficha.
3. Añadir pruebas de respuesta HTTP para los metadatos, los caracteres especiales, el enlace absoluto, la ausencia de portada y las propiedades que no son públicas.

### Bloque 2: opciones para compartir

1. Añadir el botón y las opciones accesibles a `resources/js/pages/properties/show.tsx`, reutilizando los componentes existentes cuando corresponda. Mantener el recorrido de consulta y la presentación de la ficha en móvil y escritorio.
2. Preparar los enlaces de correo, WhatsApp, Facebook y X con los datos acordados y codificarlos correctamente. Abrir las plataformas externas en otra pestaña de forma segura. No afirmar que el mensaje se envió al abrir una aplicación externa.
3. Implementar la copia del enlace, su confirmación y el enlace seleccionable de respaldo cuando el portapapeles falle o no esté disponible.
4. Añadir pruebas de navegador para los destinos, la copia y su fallo, la navegación por teclado y el acceso como visitante. Ejecutar las pruebas afectadas y comprobar la interfaz en móvil y escritorio.

## Criterios de aceptación

- [x] Un visitante sin sesión puede abrir «Compartir» junto al título de una ficha pública y encontrar correo, WhatsApp, Facebook, X y copiar enlace en móvil y escritorio.
- [x] El botón y sus opciones tienen nombres accesibles y se pueden usar con teclado y lector de pantalla.
- [x] Todas las opciones usan la URL absoluta de la ficha, sin los parámetros ni el fragmento presentes en la navegación actual. El enlace abre esa propiedad sin exigir inicio de sesión mientras esté disponible.
- [x] La opción de correo abre el cliente configurado con el título y el enlace preparados. No envía el mensaje desde la aplicación ni indica que ya fue enviado.
- [ ] WhatsApp y X reciben el título y el enlace preparados. Facebook recibe el enlace de la propiedad. Los caracteres especiales del título y de la URL se transmiten correctamente.
- [ ] WhatsApp, Facebook y X se abren en otra pestaña. La ficha original permanece abierta.
- [x] Si la copia tiene éxito, la interfaz lo confirma. Si falla o no está disponible, informa del fallo y ofrece el enlace seleccionable para copiarlo manualmente.
- [x] Una petición HTTP inicial a la ficha devuelve metadatos de vista previa con título, fragmento de descripción, URL absoluta y URL de la portada, sin necesitar JavaScript. Los valores con caracteres especiales no alteran el HTML.
- [x] Si no hay portada en los datos, la ficha y las opciones para compartir siguen funcionando y no se anuncia una imagen inexistente en los metadatos.
- [x] La URL pública de una propiedad vendida, alquilada, archivada o eliminada mantiene la respuesta 404 existente.
- [x] Pasan las pruebas afectadas de servidor y navegador, incluidos los recorridos de compartir y el fallo de copia.

## Decisiones

- **Sí:** colocar la acción en la ficha, no en el catálogo. La historia se centra en compartir una propiedad concreta.
- **Sí:** preparar título y enlace para correo, WhatsApp y X. Se identifica la propiedad sin incluir un precio que puede cambiar. Facebook recibe el enlace y decide cómo presentarlo.
- **Sí:** abrir el cliente de correo del visitante y dejarle elegir destinatario y enviar. No se necesita un servicio de envío desde la web.
- **Sí:** abrir las redes en otra pestaña y conservar la ficha original.
- **Sí:** incluir metadatos con título, fragmento de descripción y portada en el HTML inicial. La vista previa final depende de cada plataforma y de su caché.
- **Sí:** ofrecer el enlace seleccionable si falla la copia. El visitante conserva una alternativa cuando no puede usar el portapapeles automático.
- **No:** mantener accesible una propiedad retirada mediante un enlace compartido. Se conservan las reglas actuales de visibilidad.

## Riesgos

| Riesgo | Mitigación |
| --- | --- |
| Una plataforma no ejecuta JavaScript o usa una vista previa almacenada en caché. | Entregar los metadatos en la respuesta HTML inicial y no prometer que la plataforma actualice su caché de inmediato. |
| La portada no tiene una URL accesible públicamente desde la plataforma externa. | Usar la URL de la portada existente y comprobar en el entorno de despliegue que la plataforma puede acceder a ella. La vista previa depende también de esa configuración. |
| El navegador bloquea el portapapeles o no tiene cliente de correo configurado. | Ofrecer copia manual del enlace. La aplicación prepara el correo, pero el envío depende del cliente del visitante. |
