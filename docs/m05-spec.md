# EstuFlow — M05: perfil, ajustes de cuenta y arranque multiplataforma

Implementá M05 completa sobre el proyecto existente. Nico considera que M04 funciona y quiere avanzar con el cierre funcional del alcance actual. Hay ajustes visuales generales pendientes que se harán después: no rediseñes dashboard, calendario, notificaciones ni notas. Esta entrega agrega perfil, reúne ajustes y deja instalación/documentación utilizables en macOS, Windows y Linux.

Tenés autorización para implementar, verificar, hacer commits, push y crear una PR en borrador. Resolvé las decisiones rutinarias con esta especificación y el contexto del repo. Completá la entrega en esta sesión, sin detenerte después de proponer un plan. Consultá sólo ante un bloqueo real; preservá trabajo ajeno y continuá con lo independiente.

## 1. Base, tareas y límites

Leé `AGENTS.md`, README y documentación vigente. Stack existente: Laravel / Livewire / Blade / Tailwind / Alpine / PostgreSQL / Redis / Docker. Reutilizá convenciones, componentes y reglas de negocio.

Estado verificado el 23/10/2026: PR #3 abierta en borrador, rama `codex/m04-calendario`, HEAD `2cd2827b4396ab17681a1de5464ac3c16205e68a`, base `codex/m03-mi-carrera`. Las entregas anteriores todavía no estaban integradas. Verificá el estado actual: si M04 sigue pendiente, creá `codex/m05-perfil` desde su HEAD actualizado y PR en borrador con base en M04. Si ya está integrada, partí de main actualizado. No mezcles las PRs ni implementes desde un main antiguo. No crees otro worktree; si ya existe trabajo de M05, comprobalo y retomalo con cuidado.

| Tarea | Resultado |
| --- | --- |
| M05-01 | Mi perfil con datos personales editables y persistentes. |
| M05-02 | Cambio seguro de email y gestión de contraseña, compatible con cuentas locales y OAuth. |
| M05-03 | Carrera y métodos de acceso reunidos en el perfil, reutilizando los flujos existentes. |
| M05-04 | Recorridos de instalación/operación reales para macOS, Windows con WSL2 y Linux. |
| M05-05 | README reescrito y demostración clara del CRUD ya implementado. |
| M05-06 | Regresión, documentación y entrega verificable. |

No agregues campos personales sin uso, subida de fotos, redes sociales, eliminación de cuentas, un panel administrativo ni otro CRUD artificial. El avatar de iniciales es suficiente. Planificador, Microsoft real, otras carreras y mejoras visuales generales siguen para después. Las preferencias de recordatorio por evento de M04 conservan su comportamiento; no crees otro sistema global de notificaciones para esta entrega.

## 2. M05-01 — Mi perfil y presentación

Agregá **Mi perfil**, accesible desde el encabezado/cuenta mediante un enlace o menú claro. Reutilizá el shell y componentes aprobados, con tres grupos comprensibles: **Datos personales**, **Seguridad y accesos**, **Mi carrera**. Evitá convertirlo en otro dashboard de estadísticas.

Perfil requiere autenticación y email verificado; debe poder consultarse aunque la configuración académica esté incompleta, sin bucles entre perfil, verificación y onboarding. Los destinos académicos siguen conservando sus guardas.

- Editar el nombre completo actual, conservando el modelo de nombre de M02; no dividirlo en campos que requieran migrar nombres existentes. Aceptar nombres con acentos y espacios; validar longitud y entradas malformadas según las convenciones del proyecto.
- Guardar y Cancelar con efectos claros. El nombre persiste al recargar y volver a ingresar, y actualiza saludo, cuenta e iniciales sin conservar un valor anterior en otra pantalla.
- Mostrar email actual, estado de verificación y operación explícita para cambiarlo según la sección siguiente. No mezclar el guardado del nombre con un cambio silencioso de credenciales.
- Estados de carga/éxito/error, foco y validación inline accesibles; no restaurar contraseñas en el HTML ni mostrar dos veces el mismo error.

Nota del código actual: `academic-header.blade.php` excluye determinados campos del resumen global de errores. Incorporá los formularios de perfil al patrón compartido sin duplicar errores inline de nombre/email/contraseña en el encabezado. Extraé una solución coherente si hace falta; no sigas agregando reglas ad hoc por cada pantalla.

Toda escritura se aplica a la cuenta de sesión. No aceptar `user_id`, verificaciones, roles, carrera, contraseña u otros atributos mediante mass assignment del formulario de nombre.

## 3. M05-02 — Email, contraseña e identidad

Reutilizá la reautenticación M02, los límites de intentos, correo en cola y validaciones. Si la confirmación de identidad compartida necesita salir de un controlador a un servicio cohesionado, hacelo preservando los contratos OAuth. Los redirects de retorno a perfil/operación deben ser destinos internos permitidos por el servidor.

### Cambio de email

Implementá una solicitud pendiente, separada del email activo:

1. Reautenticación reciente de la misma cuenta: contraseña actual o proveedor ya vinculado. Normalizar el nuevo email, validar disponibilidad y rechazar el mismo email como cambio inexistente.
2. Conservar email activo/verificado mientras se confirma. Exigir confirmación del correo actual y del propuesto; no asumir MFA por usar OAuth. Ofrecer estado pendiente, reenvío limitado y cancelación.
3. Enlaces por cuenta, dirección, propósito y versión: tokens aleatorios guardados como hash, vencimiento de 60 minutos y sin reutilización efectiva. Una nueva solicitud invalida la anterior. Abrir un enlace sólo muestra confirmación; la mutación requiere acción protegida y cuenta correcta.
4. Al completar ambas confirmaciones, aplicar el cambio atómicamente, revalidando unicidad y marcando el nuevo email como verificado en ese momento. Una colisión concurrente no produce 500 ni modifica otra cuenta. Limpiar el pendiente, renovar la sesión actual, invalidar sesiones/remember tokens anteriores y solicitudes de reset obsoletas de esa cuenta. Avisar del cambio al email anterior.
5. Expiración, cancelación, fallo de correo o confirmación incompleta preservan el email activo. La UI explica qué falta sin filtrar tokens ni identificar otras cuentas.

Para OAuth-only, confirmar identidad mediante un proveedor vinculado sin exigir una contraseña inexistente. Conservar identificadores estables issuer/subject y las identidades vinculadas al cambiar email; no vincular por coincidencia de dirección ni sobrescribir el nuevo nombre/email con el perfil del proveedor al ingresar después.

La cuenta permanece utilizable durante el pendiente. Reminders y correos ordinarios usan el email activo verificado; los trabajos de confirmación se ligan a la versión/destino pendiente para que una solicitud reemplazada no se reactive desde una cola vieja. No guardar enlaces/tokens completos en logs, evidencias o notificaciones públicas.

### Contraseña y accesos

- Cuentas con contraseña: formulario de contraseña actual, nueva y confirmación, con las mismas reglas que M02. Validar la actual en servidor, guardar hash y permitir cancelar sin efectos.
- Cuentas OAuth-only: explicar que ingresan con su proveedor. Permitir establecer una contraseña local tras confirmar identidad con un proveedor ya vinculado y manteniendo email verificado; no inventar una contraseña previa ni quitar el acceso OAuth.
- Una modificación válida notifica por el mecanismo existente, rota remember tokens y cierra las otras sesiones de la propia cuenta. La sesión actual se renueva y continúa utilizable; no afectar otras cuentas. Preservar el comportamiento de recuperación de contraseña M02.
- Reutilizar el estado/vinculación de Google/Microsoft existente. Proveedores deshabilitados siguen así; no modificar credenciales ni agregar desvinculación o habilitación automática. Confirmar identidad vuelve a la operación de perfil iniciada, sin obligar a buscarla de nuevo.

Referencia para contrastar las decisiones de cambio de identidad: https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html . Es una referencia técnica; no copiar sus textos a la UI ni agregar MFA completo fuera del alcance.

## 4. M05-03 — Ajustes académicos y navegación

En **Mi carrera** dentro del perfil, mostrar carrera y plan confirmados con nombres humanos, enlace para consultar materias y **Cambiar carrera**. Si no hay configuración completa, ofrecer completarla. El candidato en preparación se distingue de la selección confirmada.

Mover el acceso principal a cambiar carrera desde la página Mi carrera al perfil; la edición de materias y correlativas permanece en Mi carrera. Mover Métodos de acceso al grupo Seguridad y accesos, y reemplazar el enlace independiente del encabezado por Mi perfil. Reutilizá las vistas/lógica existentes o integrá sus componentes; no mantengas dos implementaciones de vinculación.

Preservá URLs antiguas relevantes mediante rutas compatibles o redirects internos claros para enlaces y callbacks existentes. Cambiar la ubicación no debe romper OAuth, reautenticación, cancelación ni formularios POST.

El flujo de cambio sigue siendo M03: candidato separado, progreso por plan, cancelación/interrupción seguras y activación sólo al confirmar. Cuando se inicia desde perfil, cancelar/volver devuelve al perfil y conserva la cuenta configurada; confirmar muestra su carrera efectiva. El onboarding inicial mantiene su destino y prioridad actuales. No trasladar todos los pasos académicos a una nueva implementación de perfil.

Después de cambiar email/nombre/carrera, conservar materias, eventos, notas, recordatorios, lectura de notificaciones e identidades. Verificá especialmente asociaciones a materias de un plan anterior y el envío a la dirección activa de la cuenta.

## 5. M05-04 — Portabilidad real del arranque

El README actual contiene «Iniciar en Mac» y una ruta personal. Los scripts usan Bash: `common.sh` usa `id -u/id -g` y la ubicación del checkout. El bootstrap PHP ya corre dentro de un contenedor y `.gitattributes` ya define LF. Inspeccioná antes de cambiar; no agregues soluciones a problemas inexistentes.

Definí y documentá estos recorridos:

| Sistema anfitrión | Ruta de ejecución |
| --- | --- |
| macOS | Git, Docker Desktop con Compose v2 y scripts Bash desde el checkout. |
| Windows | Docker Desktop con backend WSL2, una distribución como Ubuntu integrada, Git/Bash dentro de ella y los scripts ejecutados en su terminal. |
| Linux | Git, Bash, Docker Engine con plugin Compose v2 —o Docker Desktop— y acceso operativo al daemon. |

Para Windows, distinguir los comandos de PowerShell destinados a instalar/comprobar/abrir WSL de los comandos Bash del proyecto dentro de Ubuntu. Explicar integración de Docker con la distribución y modo Linux containers; clonar preferentemente en el filesystem Linux de WSL para este recorrido. No presentar `./scripts/setup.sh` como un comando nativo de PowerShell/CMD. No hace falta duplicar scripts en PowerShell si este recorrido queda completo y operativo.

Fuentes oficiales:

- https://docs.docker.com/desktop/features/wsl/
- https://learn.microsoft.com/en-us/windows/wsl/install
- https://docs.docker.com/engine/install/

Revisá setup/start/stop/check/test/e2e, Compose, Dockerfiles y bootstrap respecto de rutas, permisos/UID, espacios, comandos específicos del host, finales de línea y arquitectura. Corregí los bloqueos concretos que encuentres conservando un núcleo de scripts común. No requerir Homebrew, PHP, Composer o Node globales para el recorrido documentado; los pasos especiales de un entorno cloud no se convierten en requisito de una máquina personal.

Conservar imágenes reproducibles y compatibilidad de arquitecturas del proyecto; no fijar ARM de la Mac ni quitar digests/TLS para resolver una descarga. Un build en AMD64 no prueba por sí mismo ARM64 o Windows. Si hay una incompatibilidad concreta de imagen/manifest, resolvela de forma acotada y verificable.

La instalación nueva funciona con los archivos versionados y genera su entorno local sin depender del `.env` de Nico, archivos ignorados o configuración personal de OAuth/Brevo. Los proveedores externos son opcionales; el correo local usa Mailpit. Si falta una dependencia del anfitrión, informar el requisito y paso real para resolverlo, sin dejar datos parcialmente reiniciados.

Separar **instalar**, **actualizar**, **iniciar** y **detener**. Repetir setup/start conserva APP_KEY, secretos, usuarios y volúmenes. Las actualizaciones aplican migraciones pendientes e importación idempotente; el arranque actualizado debe recargar con cuidado worker/scheduler cuando sea necesario y comprobar readiness. No dejar procesos ejecutando código anterior mientras la respuesta afirma que ya se actualizó. Documentar exactamente qué hace cada comando; conservar la semántica de stop si sólo detiene aplicación/worker/scheduler/correo.

Mantener loopback y puertos actuales: app 8000, Mailpit 8025 y PostgreSQL 5433 configurables; PostgreSQL interno sigue `postgres:5432`, test-postgres y Redis internos. No tocar las credenciales/puertos efectivos de la instalación existente. Nada de reset de desarrollo, `down -v`, regeneración de APP_KEY existente o borrado de volúmenes para facilitar la prueba.

## 6. M05-05 — README del producto y CRUD demostrable

Reescribí **README.md completo** como entrada al producto para una persona que lo clona por primera vez. Debe ser breve, natural y preciso:

1. Qué es EstuFlow y funcionalidades disponibles, con el stack real.
2. Requisitos y preparación por plataforma, enlazando una guía ampliada de instalación si hace falta.
3. Inicio rápido: clonar, entrar al repo, preparar, iniciar y URLs. Usar rutas relativas, sin `/Users/nicolasruiz`, nombres personales ni requisitos exclusivos de macOS. Mientras M05 no esté integrada, el comando debe seleccionar la rama publicada que contiene esta entrega; clonar main antiguo no puede presentarse como acceso a las funciones nuevas.
4. Actualizar/iniciar/detener sin perder datos, incluidos worker y scheduler.
5. Configuración opcional de Brevo/Google/Microsoft y conexión DataGrip, con enlaces a las guías existentes. No afirmar que las credenciales de Nico ya vienen configuradas en cualquier clon.
6. Comandos reales de comprobación y enlaces a documentación de arquitectura, testing, instalación y uso.

La historia de módulos/commits, números de pruebas y evidencias detalladas va en progreso/evidencia. README no es una transcripción de conversaciones, una bitácora de Codex ni una lista de advertencias genéricas. Mantener sólo las precauciones prácticas que afectan instalación, datos o funcionamiento. Indicar lo pendiente con honestidad —Planificador y proveedor no habilitado, por ejemplo— sin describirlo como implementado.

Aclarar brevemente que otra computadora tiene una instalación/base local nueva: git no traslada volúmenes ni la configuración privada. No asumir que el login/datos de la Mac aparecerán en Windows al clonar. Si se documenta migración de datos, debe ser explícita y preservar configuración de cifrado; no copiar datos o secretos automáticamente.

Revisá también las guías operativas actuales que dicen «la Mac» cuando describen requisitos generales; reemplazar por equipo anfitrión/Docker según corresponda. Preservar los nombres reales de plataforma y resultados en evidencias históricas. No hacer un reemplazo masivo que convierta pruebas realizadas en Mac en supuestas pruebas Windows.

El requisito del profesor ya se cumple con **eventos y notas**. Agregá o consolidá una guía breve de demostración —puede ser `docs/demo.md`— con esta correspondencia:

| Operación | Ejemplo visible | Persistencia |
| --- | --- | --- |
| Crear | Nuevo evento / Nueva nota | `calendar_events` / `personal_notes` |
| Consultar | Calendario/agenda/detalle y listado/detalle de notas | Recupera registros propios guardados. |
| Modificar | Editar y guardar; recargar para comprobar el cambio | Actualiza el registro existente. |
| Eliminar | Confirmar eliminación y comprobar su ausencia | Semántica real existente; eventos mantienen su borrado lógico. |

Recorrido con datos ficticios que se pueda mostrar en pocos minutos, comprobando al menos persistencia tras logout/login. No cambiar el CRUD para ajustarlo a una tabla ni borrar cuentas/materias del catálogo para inventar otro. Mantener las acciones normales de producto; la explicación técnica del CRUD pertenece a docs, no a carteles dentro de la app.

## 7. Calidad, datos y verificación

Monolito modular, clases cohesionadas, reglas fuera de Blade y autorización/validación del servidor. Reutilizar correo y reautenticación; evitar duplicación y abstracciones genéricas. Campos sensibles sólo por operaciones dedicadas; transacciones, índices y restricciones de unicidad donde correspondan. Escapar texto y validar entradas no escalares. Mantener CSRF, límites y permisos HTTP/Livewire.

Migraciones aditivas. `.env` y `.env.testing` existentes conservados e ignorados; no editar secretos de Nico. Nuevas opciones con defaults seguros/documentación y ejemplos sin secretos. Mantener idempotencia, catálogo, estados académicos, cadencias compartidas, baseline de cartelera y guardas de testing. No cambiar horarios/recordatorios M04 para adaptar el perfil.

En la evidencia de M04 se informó una vulnerabilidad alta de npm en dependencias previas. Verificá el estado vigente y su advisory; si hay una corrección compatible y acotada, aplicala con lockfile y regresión. Sin actualizaciones masivas ni `audit fix --force`. Si requiere un cambio mayor, documentar paquete/advisory, alcance y pendiente concreto, sin ocultar ni declarar resuelto lo que no se corrigió.

Pruebas necesarias en PostgreSQL aislado:

- Perfil propio: guardar/cancelar nombre, validación, persistencia y actualización de saludo/iniciales; rechazo de IDs/atributos ajenos y operación sin JavaScript.
- Email: email inválido/no escalar/repetido/mismo; reautenticación y límites; pendiente sin cambiar activo; ambas confirmaciones en distinto orden; token vencido/alterado/reutilizado, propósito/cuenta incorrectos, nueva solicitud que invalida anterior, reenvío/cancelación y colisión concurrente. Fallo SMTP no modifica el email activo ni produce éxito falso.
- Contraseña: actual incorrecta, confirmación/reglas, cambio válido, login con nueva y rechazo de anterior; sólo se invalidan las otras sesiones de esa cuenta. OAuth-only puede establecer contraseña tras reautenticación vinculada y conserva OAuth; otra identidad no puede confirmar la operación.
- Después de cambiar email: identidades/usuario estables, recuperación y verificación correctas, credenciales y destinatarios activos coherentes, sin perder materias/eventos/notas ni resetear sus avisos.
- Navegación: perfil antes de completar carrera sin bucles, destino tras reautenticación, rutas antiguas/callbacks, cambio de carrera desde perfil con cancelar/interrumpir/confirmar/retornar y todos los datos preservados.

Reutilizar mocks de proveedores y `test-mailpit`. E2E debe acreditar al menos el cambio de perfil, el flujo de email mediante correos realmente capturados por SMTP y la centralización académica. Pruebas OAuth con dobles no equivalen a login real; Mailpit no equivale a entrega Brevo. No mandar correos de prueba a usuarios reales ni publicar tokens, contraseñas o URLs firmadas.

Ejecutar `check.sh`, `test.sh`, `e2e.sh` conforme a `docs/testing.md`, PHPUnit/E2E secuenciales sobre `estuflow_test`/`test-postgres`. La referencia M04 reportó 144 pruebas/930 aserciones y 12 E2E; usar resultados propios sobre el HEAD final, sin desactivar checks ni eliminar verificaciones para hacer pasar el cambio.

Verificar scripts con sintaxis/operación y arranque en instalación existente y limpia cuando el entorno lo permita. Una prueba limpia debe usar checkout temporal y nombre de proyecto Compose, puertos, cachés y volúmenes aislados; no crear otro worktree ni interferir con desarrollo. Alternativamente, justificar el recorrido limpio efectivamente ejecutado en CI. Probar que repetir el arranque y parar/iniciar preserva datos, y que app/worker/scheduler quedan saludables.

Registrar plataforma, arquitectura, método y resultado de cada comprobación. Linux CI no acredita Windows/WSL2; revisión de scripts o sintaxis tampoco. Si no hay Windows disponible, entregar guía completa y checklist corto para la prueba posterior de Nico, marcado **NO EJECUTADO**. No bloquear el trabajo independiente ni afirmar compatibilidad probada sólo por usar Docker. Igual criterio para arquitecturas sin ejecución real.

Inspeccionar perfil y controles movidos a 390/1440 px, composición a 2560/3840, reflow 320 y zoom 200 % usando lo existente. Asegurar teclado, foco, avisos y formulario utilizable; revisar que el resto conserve su composición. Corregir defectos introducidos por M05. Las mejoras visuales previas de M04 quedan pendientes y no forman parte de un rediseño en esta PR.

## 8. Entrega y aceptación

Actualizar progreso, arquitectura/UI/testing según cambios, documentación de perfil/instalación y evidencia M05 con comandos ejecutados, capturas y límites reales. El README será la entrada; los detalles deben estar enlazados y ser coherentes con los scripts.

- [ ] Mi perfil funciona y los datos se reflejan en toda la cuenta.
- [ ] Cambios de email/contraseña tienen confirmación, autorización y efectos de sesión comprobados.
- [ ] Métodos de acceso y cambio de carrera se encuentran en perfil; flujos previos y datos preservados.
- [ ] README reescrito, sin rutas personales ni configuración privada asumida; rama de instalación correcta.
- [ ] macOS, Windows con WSL2 y Linux tienen requisitos/comandos completos y terminal bien identificada.
- [ ] Arranque, actualización y parada coinciden con los scripts y preservan datos/cifrado/procesos.
- [ ] CRUD de eventos/notas documentado y demostrable con la interfaz existente.
- [ ] Checks, E2E, capturas y matriz de plataformas distinguen ejecutado de pendiente.
- [ ] Scope M05 preservado; ajustes visuales generales y funcionalidades futuras no se dan por terminados.

Revisar diff y publicar commits coherentes en la rama M05, push sin force y PR en borrador con la base correcta. No hacer merge, despliegue ni publicación directa a main. No versionar `.env`, dependencias generadas, dumps, datos privados o evidencia con credenciales.

La PR debe describir comportamiento final, cambios de seguridad, requisitos por plataforma, pruebas ejecutadas y pendientes. Respuesta final: PR/HEAD, comandos reales para actualizar la instalación existente, pasos exactos para el primer arranque en Windows/WSL2, ubicación de la demostración CRUD, resultados locales/CI y lo pendiente de probar en la PC de Nico. No declarar la aplicación ejecutada en Windows si no ocurrió, ni completo un formulario con acciones simuladas o rutas sin funcionamiento.
