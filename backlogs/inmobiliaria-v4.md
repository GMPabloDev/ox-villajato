# Backlog — Inmobiliaria v4

Mejoras para la gestión de propiedades y consultas de una inmobiliaria en Perú y para las personas que buscan vivienda en el catálogo público.

Stack: el existente, indicado en [Inmobiliaria v3](inmobiliaria-v3.md).

Decisiones importantes: una sola inmobiliaria; propiedades en venta o alquiler con un precio en PEN o USD; ubicación por departamento, provincia y distrito; cuentas de clientes con correo y contraseña o Google. Compartir propiedades no requiere iniciar sesión; guardar favoritos sí. El mapa queda para otro backlog.

## Historias

- **H-01** Como administrador o agente, quiero filtrar las propiedades desde el panel, para encontrar las que necesito gestionar.
- **H-02** Como administrador o agente, quiero filtrar las consultas desde el panel, para encontrar las que necesito atender.
- **H-03** Como administrador o agente, quiero registrar la ubicación de una propiedad por departamento, provincia y distrito, para indicar dónde se encuentra.
- **H-04** Como visitante, quiero filtrar las propiedades por departamento, provincia y distrito, para encontrar viviendas en la ubicación que busco. Requires: H-03
- **H-05** Como administrador o agente, quiero indicar si una propiedad tiene garaje y si está amueblada, para que esos datos aparezcan en su ficha.
- **H-06** Como administrador o agente, quiero registrar el precio mensual de una propiedad en alquiler, para que el visitante sepa cuánto pagaría al mes.
- **H-07** Como administrador o agente, quiero recibir una advertencia visible cuando las fotos seleccionadas no tengan las dimensiones o proporciones recomendadas, para poder reemplazarlas o decidir subirlas de todos modos.
- **H-08** Como visitante, quiero compartir una propiedad por correo, WhatsApp, Facebook, X o mediante un enlace, para enviársela a otra persona sin iniciar sesión.
- **H-09** Como visitante, quiero registrarme e iniciar sesión con correo y contraseña o con Google, para poder guardar propiedades en favoritos.
- **H-10** Como usuario registrado, quiero guardar y consultar mis propiedades favoritas, para volver a encontrarlas después. Requires: H-09
- **H-11** Como usuario que ha iniciado sesión, quiero que el formulario de contacto complete mi nombre y correo, para consultar por una propiedad sin escribirlos de nuevo. Requires: H-09

## Notas para el refinamiento

- Para H-03, se pueden borrar los datos actuales de desarrollo y adaptar el seed existente a las nuevas ubicaciones. El [repositorio de ubigeos propuesto](https://github.com/RitchieRD/ubigeos-peru-data) es una opción; queda pendiente contrastar su vigencia con los datos oficiales.
- Para H-07, las dimensiones de referencia propuestas son horizontal 3:2, mínimo 1600 × 1067 e ideal 2000 × 1333. La advertencia no bloquea la subida. El límite de peso y los formatos admitidos quedan por definir en la spec.
