# Historia 6 — Crear cuentas para agentes

- **Estado:** Aprobada
- **Fecha:** 2026-10-28
- **Origen:** `backlog.md`, Inmobiliaria v2, historia 1
- **Depende de:** ninguna
- **Siguiente paso:** `/pair`

## Story

Como administrador, quiero crear cuentas individuales para los agentes, para controlar quién accede al panel.

## Acceptance criteria

- [ ] Dado que inicié sesión como administrador, cuando creo una cuenta de agente con nombre y correo válidos, entonces se crea la cuenta y el agente recibe un enlace por correo para establecer su contraseña.
- [ ] Dado que recibí un enlace válido, cuando establezco mi contraseña, entonces puedo iniciar sesión con mi cuenta individual.
- [ ] Dado que soy administrador, cuando intento crear una cuenta con datos obligatorios faltantes o inválidos, o con un correo ya registrado, entonces veo los errores y no se crea la cuenta.
- [ ] Dado que soy agente o no inicié sesión, cuando intento crear una cuenta de agente, entonces no puedo hacerlo.
- [ ] Dado que soy agente, cuando intento publicar o gestionar propiedades, entonces no puedo acceder a esas funciones hasta que se implemente la historia 2 de la v2.
- [ ] Dado que soy visitante, cuando intento registrarme por mi cuenta, entonces no puedo crear una cuenta.
- [ ] Dado que existe la cuenta actual de la inmobiliaria, cuando se habilita la v2, entonces esa cuenta conserva acceso como administrador.

## Plan

- Distinguir la cuenta de administrador de las cuentas de agente y convertir la cuenta actual.
- Añadir al panel del administrador el alta de agentes con validación.
- Enviar al agente un enlace para establecer su contraseña y usar el inicio de sesión existente.
- Restringir el alta de agentes al administrador y bloquear temporalmente la gestión de propiedades a los agentes.
- Cubrir el alta, el acceso, las validaciones y las restricciones con pruebas.

## Out of scope

- Publicación y gestión de propiedades propias por agentes, que corresponde a la historia 2 de la v2.
- Listado de contactos por agente o administrador, que corresponde a las historias 3 y 5 de la v2.
- Edición, desactivación o eliminación de cuentas de agentes.
- Creación de más cuentas de administrador.
- Historial de modificaciones.
