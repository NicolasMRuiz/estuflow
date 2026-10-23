# Mi perfil

El enlace Mi perfil del encabezado y la tarjeta de cuenta de Inicio abren `/mi-perfil`. Requiere sesión y email verificado; se puede entrar antes de configurar carrera. Tres grupos: Datos personales, Seguridad y accesos, Mi carrera.

## Datos personales

Editar el nombre y elegir Guardar nombre. Cancelar recarga el dato guardado. Es un único nombre de hasta 100 caracteres, con espacios/acentos; se refleja en el saludo y la inicial de la cuenta. Funciona con POST normal sin JavaScript. La operación sólo acepta `name`: no permite modificar IDs, email, permisos ni campos sensibles por asignación masiva.

## Email

En Cambiar email, confirmar identidad con la contraseña actual o un proveedor ya vinculado (10 minutos). Ingresar otra dirección disponible. La solicitud pendiente **no cambia el email activo ni su verificación**; se sigue usando para login, recuperación y recordatorios.

Se envía una confirmación a cada dirección. Abrir cada correo, ingresar con la cuenta actual si hace falta y presionar Confirmar este correo. Abrir el enlace mediante GET sólo muestra la pantalla; la confirmación es POST con CSRF. El orden es indistinto. Sólo al completar ambas se cambia el email y queda verificado, conservando el mismo usuario y sus identidades/datos. Se notifica al email anterior, se renueva la sesión actual, se cierran las otras sesiones de esa cuenta, rota remember token y se eliminan sus solicitudes anteriores de recuperación.

Los enlaces vencen a los 60 minutos y son de un solo uso. Reenviar renueva la solicitud completa: invalida ambos enlaces y sus confirmaciones anteriores. Solicitar otro email también reemplaza el pendiente. Cancelar invalida los enlaces y conserva el activo. Límite compartido de solicitud/reenvío: 1 por minuto y 5 por hora por cuenta. La dirección se vuelve a comprobar bajo transacción/índice único al completar; una colisión conserva el email activo y permite cancelar/elegir otro, sin error 500.

El estado distingue correo en cola, aceptado por SMTP y envío no confirmado/fallido; la aceptación SMTP no garantiza recepción. Si falla, corregir el transporte y reenviar después de la espera. No se informa «email cambiado» por encolar o enviar un mensaje. Los trabajos cifrados se ligan a versión, propósito y destinatario; una solicitud cancelada/reemplazada no puede reactivarse con correo viejo. No compartir los enlaces ni capturas de la bandeja.

La política sigue la [guía de autenticación de OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#changing-a-users-registered-email-address): reautenticación y confirmación en ambas direcciones para este flujo sin MFA. No agrega MFA ni una dirección secundaria de acceso.

## Contraseña y proveedores

Una cuenta local ingresa contraseña actual, nueva de 10–128 caracteres y confirmación. Cancelar no guarda. Una cuenta sólo OAuth confirma identidad con un proveedor **ya vinculado** y puede establecer una contraseña sin inventar una actual. Se conserva OAuth. Los proveedores deshabilitados siguen sin habilitarse; no hay desvinculación.

Guardar rota el token recordatorio, invalida las otras sesiones propias y solicitudes de recuperación, mantiene la sesión actual con ID renovado y envía el aviso de contraseña cambiada M02. No se flashean passwords. La recuperación M02 sigue disponible desde el login. El login OAuth conocido busca issuer/subject: nunca sobrescribe nombre/email editados ni vincula una cuenta por coincidencia de email.

Seguridad y accesos muestra contraseña/proveedores y reutiliza el flujo de vinculación. `/metodos-de-acceso` redirige a Mi perfil; sus POST y callbacks previos siguen operativos. La reautenticación iniciada en perfil vuelve a la operación iniciada mediante un destino permitido por servidor, sin aceptar URLs arbitrarias.

## Carrera

Mi carrera en el perfil muestra carrera/plan confirmados y el candidato sin confirmar por separado. Ver mis materias abre el editor habitual. Cambiar carrera reutiliza el borrador/resumen M03: cancelar devuelve al perfil con la selección anterior; confirmar vuelve al perfil con la nueva carrera. Interrumpir conserva la carrera confirmada y permite retomar el candidato. Si todavía no hay carrera completa, Completar mi carrera mantiene el recorrido inicial hacia Inicio al terminar.

Nombre, email y carrera conservan progreso de todos los planes, eventos/notas y asociaciones previas, programación de recordatorios, lecturas de bandeja e identidades OAuth. Los recordatorios usan el email activo verificado al despacharse; un cambio pendiente no redirige envíos.

## Comprobación manual

Con Mailpit local: crear/verificar dos cuentas ficticias, probar guardar/cancelar nombre, doble confirmación en ambos órdenes, vencimiento/cancelación/reenvío y cambio de contraseña. Mantener la misma cuenta abierta en otro navegador: tras email/contraseña, esa otra sesión debe pedir login y la actual seguir operativa. Ingresar con el nuevo email/password, comprobar materias/eventos/notas y recuperación. No modificar configuraciones privadas para correr las pruebas automatizadas: siempre usan testing/fakes/test-mailpit.

Una prueba con Brevo real y un login/reauth Google real son pruebas manuales separadas; Mailpit/dobles no acreditan esas integraciones. Microsoft real queda pendiente. Ver [correo](mail.md), [OAuth](oauth.md) y [evidencia](evidence/m05/checks.md).
