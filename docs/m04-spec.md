# EstuFlow — M04: calendario, recordatorios, cartelera y notas

Implementá M04 completa en el proyecto existente. Nico aprobó la corrección visual de M03 y acordó este alcance. Tu responsabilidad es implementar, verificar con recorridos reales y dejar una PR en borrador revisable. Avanzá en esta sesión con las decisiones definidas aquí; resolvé las elecciones rutinarias según el repositorio y consultá sólo ante un bloqueo real. No termines después de presentar un plan o un prototipo.

## 1. Base de trabajo y alcance

Repo: `NicolasMRuiz/estuflow`. Stack existente: Laravel + Livewire + Blade + Tailwind + Alpine + PostgreSQL + Redis + Docker. Leé `AGENTS.md`, README, `docs/ui.md`, `docs/testing.md` y progreso antes de editar. Preservá versiones, componentes y trabajo local; no crees otro proyecto ni worktree.

Referencia verificada el 23/10/2026: M03 está en `codex/m03-mi-carrera`, PR #2 abierta en borrador, HEAD `39c22b287a1e9bb80b49b67a2628e21ef837ee11`, base `codex/m02-auth-responsive`. M02 también seguía sin integrar. Comprobá el estado real antes de elegir base. Si M03 sigue pendiente, creá `codex/m04-calendario` desde su HEAD actualizado y una PR en borrador con base en M03; si ya se integró, partí de main actualizado. No mezcles PRs automáticamente ni implementes sobre el main antiguo. Si la rama M04 ya existe, comprobá y retomá su trabajo sin sobrescribirlo.

La UI aprobada es **M03 corregida**: `docs/evidence/m03-correction/`, sus componentes y el dashboard actual. Conservá saludo, cuenta, franja de carrera y la grilla de cuatro tarjetas. Esta entrega activa el calendario y agrega notificaciones/notas con ese mismo lenguaje visual; no habilita un rediseño general.

| Tarea | Resultado requerido |
| --- | --- |
| M04-01 | Calendario personal con CRUD completo y asociación opcional a materia. |
| M04-02 | Feriados de ArgentinaDatos compartidos y sincronizados cada 15 días. |
| M04-03 | Centro de notificaciones y recordatorios personales persistentes. |
| M04-04 | Cartelera institucional sincronizada una vez al día; avisos de publicaciones nuevas. |
| M04-05 | Notas personales tipo post-it en el dashboard, con CRUD completo. |
| M04-06 | Integración visual, tareas programadas, pruebas, documentación y entrega. |

Perfil editable, traslado de Cambiar carrera y Planificador quedan para otra entrega. Conservá la ubicación y comportamiento actuales de los ajustes de M03. La creación de eventos será mediante un formulario claro. No agregues recurrencias, sincronización con calendarios externos, chat de IA ni canales de mensajería adicionales. Microsoft y sus credenciales tampoco forman parte de M04.

## 2. Contrato de UI

Reutilizá botones, campos, badges, avisos y paneles de M03. Español rioplatense, textos breves, estados claros de guardado/error/carga, labels asociados y errores inline. Una acción principal por formulario y acciones secundarias reconocibles. No expongas claves técnicas, payloads externos o textos de extracción como contenido principal.

Activá «Calendario» en la navegación, con estado activo correcto. Dentro del calendario: mes/año visible; anterior, siguiente y Hoy; selector Mes/Agenda; botón **Nuevo evento**. En escritorio predomina la vista mensual; en móvil se abre una agenda legible, conservando la posibilidad de cambiar de vista. Semana de lunes a domingo, fechas en español y distinción por texto además de color entre tipos de evento y feriados.

El calendario mensual permite elegir una fecha y crear con esa fecha precargada. Cada evento abre su detalle y ofrece Editar/Eliminar visibles. Si un día tiene muchos elementos, mostrar algunos y un «Ver N más» que abra ese día; no recortar contenido ni crear celdas gigantes. Los formularios son compactos y coherentes con el producto. La agenda, el CRUD básico y la navegación deben tener alternativa sin JavaScript.

En el dashboard, conectá **Próximas actividades** con los próximos eventos personales, ordenados y acotados, con horario/fecha y acceso al calendario o evento. Mostrá un estado vacío con Crear evento si no hay ninguno. Los feriados se consultan en el calendario; no deben desplazar los compromisos personales de esa tarjeta.

Agregá una campana con contador de no leídas al encabezado compartido. Agregá **Mis notas** debajo de la grilla actual, sin alterar el orden ni sustituir las cuatro tarjetas. Hasta tres notas visibles y acceso al resto; reutilizá los tonos del producto. Los formularios, modales o paneles nuevos deben tener foco, salida/cancelación y retorno al control que los abrió.

Desde 320 px CSS hasta 4K: contenedor centrado junto a la sidebar, nombres legibles, acciones alcanzables, sin overflow horizontal accidental y sin escalar artificialmente toda la página. Conservá teclado, menú móvil y zoom 200 %. Los pendientes de configuración técnica se documentan; la UI explica sólo lo útil para el estudiante.

## 3. M04-01 — Eventos personales y fechas

CRUD real persistido en PostgreSQL, siempre del usuario autenticado:

- Título obligatorio, tipo Examen/Entrega/Clase/Otro, descripción opcional y lugar opcional.
- Fecha y hora de inicio, fin opcional y opción Día completo. Validar límites de longitud razonables y formato de fechas. Con hora, el fin debe ser posterior al inicio; en día completo, la fecha final inclusiva puede ser igual o posterior a la inicial. Permitir eventos históricos sin programar avisos vencidos.
- Materia opcional, seleccionable de la carrera activa. Un evento existente conserva la asociación a una materia de un plan anterior del mismo usuario; no debe perderse por cambiar de carrera. Validar pertenencia académica en servidor, no sólo existencia de un código del catálogo compartido.
- Recordatorios según la sección siguiente.

Guardar, consultar detalle/listado, editar y eliminar deben funcionar desde la interfaz. La eliminación pide confirmación, cancela avisos pendientes y actualiza calendario/dashboard. Si se abre una notificación de un evento eliminado, explicar que ya no está disponible sin exponer información de otra cuenta. Cancelar una edición no persiste cambios. Conservar vista/mes y filtros al volver.

**Zona de producto:** `America/Argentina/Buenos_Aires`. Para eventos con hora, convertir entre hora local de UI y UTC de persistencia. Para día completo, conservar fechas civiles sin convertirlas a medianoches UTC que cambien de día; la fecha final que ingresa el usuario es inclusiva. La capa de presentación debe adaptar esa convención si usa un componente que requiera fin exclusivo. Probar cruces de medianoche, mes y año.

Las consultas del calendario usan rangos acotados y solapamiento correcto, incluyendo eventos que empiezan antes del mes visible y terminan dentro de él. No cargar todo el historial para dibujar cada mes. Los eventos pertenecen a la cuenta: cambiar carrera no los elimina ni los oculta automáticamente. La vista predeterminada contiene sus eventos personales; la materia aporta contexto.

## 4. M04-02 — Feriados compartidos

Fuente aprobada:

- Documentación: https://argentinadatos.com/docs/operations/get-feriados
- Endpoint: `https://api.argentinadatos.com/v1/feriados/{año}`; ejemplo verificado: `/2026`.
- Contrato documentado: lista con `fecha`, `tipo` y `nombre`.

Consultá por año explícito. Guardá una copia compartida y persistente por año con metadatos de sincronización. Validá respuesta, fechas, año consultado y campos antes de aplicar una actualización completa. Admití varias entradas válidas en una fecha. Conservá el tipo de la fuente y su representación humana; un tipo nuevo no debe romper todo el calendario.

Cadencia normal: **carga inicial y actualización cada 15 días por año**, independiente del número de usuarios. Cubrir el año actual y preparar el siguiente cuando el proveedor lo publique. Un año aún no publicado, un error HTTP, timeout, HTML inesperado o una lista vacía no validada no significa «ese año no tiene feriados»: conservar lo anterior y distinguir falta de datos de ausencia de feriados. Los eventos personales pueden crearse en años cuya información externa aún no esté disponible.

Los feriados son de consulta, diferenciados de los eventos personales y atribuibles a ArgentinaDatos. No se editan/eliminan desde el CRUD del estudiante. No garantizan suspensión de clases ni impiden crear eventos; no muevas un examen automáticamente por coincidir con un feriado. Fuente y última actualización quedan accesibles de forma discreta.

## 5. M04-03 — Recordatorios y centro de notificaciones

### Recordatorios de eventos

Para eventos con hora: elegir Ninguno, 24 horas antes, 1 hora antes o ambas opciones, con canales claros. La notificación dentro de EstuFlow es el canal base; **el email por Brevo requiere activación explícita en ese evento** y un email verificado. Mostrá la elección al revisar/editar el evento.

Para Día completo: ofrecer «El día anterior a las 09:00» y «Ese día a las 09:00», en Buenos Aires; la UI explica estos horarios. No inventes una hora de examen para poder programar el aviso. Omití las opciones cuyo instante ya pasó y explicalo; no dispares una acumulación de avisos retroactivos al crear un evento.

Persistí los avisos y sus estados en PostgreSQL; no dependas sólo de memoria, Redis o trabajos demorados que quedan desactualizados al editar. Cada minuto, un proceso busca pendientes vencidos para despachar. Usá identificación estable y restricciones durables para evitar doble creación/despacho ante reintentos o ejecuciones solapadas.

Reglas de actualización:

- Editar título, descripción o lugar no reenvía recordatorios ya enviados.
- Cambiar fecha/hora, opciones de aviso o canales cancela los pendientes anteriores y programa los nuevos que aún correspondan; versioná la programación para invalidar trabajos viejos en cola.
- Eliminar el evento invalida sus pendientes. Antes de enviar, volver a comprobar existencia, versión, propietario y vigencia del aviso.
- Si el servicio estuvo detenido, recuperar pendientes todavía útiles y no enviar recordatorios de eventos que ya finalizaron. Para eventos sin fin explícito, el inicio es el corte de utilidad de sus avisos; para día completo, el corte es el final del último día local.
- Las escrituras del evento y programación son coherentes; despachar trabajos después del commit. Guardar el evento no espera a SMTP.

Evitar duplicados debe estar probado para las notificaciones internas. En SMTP existe incertidumbre si un envío se acepta pero se pierde la respuesta: registrar estados/resultados y aplicar una política acotada documentada, sin afirmar entrega exactamente una vez. No marcar como entregado un correo que falló ni reintentarlo indefinidamente.

Reutilizá el transporte Brevo y la cola existentes. Incorporá un presupuesto configurable y conservador para los emails de recordatorios —por defecto, hasta 250 diarios para M04—, preservando el correo de autenticación y la notificación interna si el cupo se agota. No envíes al día siguiente un recordatorio que perdió utilidad. El plan Gratis de Brevo informa 300 envíos diarios; el presupuesto propio no garantiza disponibilidad de toda la cuota del proveedor. No cambies las credenciales ni el comportamiento de recuperación/verificación M02.

Referencia de límites: https://help.brevo.com/hc/es/articles/208580669-FAQ-Cu%C3%A1les-son-los-l%C3%ADmites-del-plan-Gratis

### Bandeja dentro de EstuFlow

Lista paginada de notificaciones propias, distinción leída/no leída, filtros por estado y tipo —Recordatorios/Cartelera—, marcar una o todas las propias como leídas y navegación al destino correcto. Las mutaciones se hacen con acciones protegidas, no GET que alteren estado. El contador se actualiza tras acciones/navegación y mediante una consulta local ligera mientras la app esté abierta; no requiere consultar fuentes externas.

Abrir/cargar notificaciones usa registros ya guardados. Evitá duplicar notificaciones por recargar la página, reanudar sesión o repetir un job. Usuarios nuevos no reciben como no leído todo el historial institucional previo a su incorporación. Nada de los eventos, notas, estados de lectura o bandejas de otra cuenta debe ser accesible manipulando IDs.

## 6. M04-04 — Cartelera institucional

URL confirmada por Nico: **https://alumnos.ibeltran.com.ar/**. Usá esa raíz como referencia; la variante `/index.php` no pudo abrirse en la revisión. Es una cartelera pública con publicaciones fechadas, imágenes y adjuntos. Inspeccioná su HTML real antes de escribir el adaptador; si ofrece un feed o una interfaz estructurada suficiente, preferila. No supongas endpoints que no existan.

Proceso compartido **una vez al día**, por defecto 08:00 de Buenos Aires, con recuperación de una ejecución omitida al reiniciar. Esa recuperación respeta la cadencia y un bloqueo compartido: no genera una consulta por usuario. Guardá publicaciones y estado de sincronización de forma persistente.

Reglas:

1. La primera sincronización válida establece una línea de base: guarda lo existente sin enviar una avalancha de avisos históricos. Sólo después de completar esa sincronización se habilita la detección de nuevas publicaciones.
2. Definí la identidad a partir del identificador/permalink estable de la fuente, si existe. El sitio repite títulos como «IMPORTANTE»: el título solo, el orden de las tarjetas o el hash de todo el HTML no sirven como identidad. Si hace falta una clave compuesta, justificá sus campos estables, posibles colisiones y límites con el HTML observado. Separá identidad de huella de contenido para que una edición no se interprete automáticamente como otra publicación.
3. Nuevas publicaciones generan una notificación por destinatario elegible, con deduplicación durable incluso ante reintentos o fan-out parcial. Considerar cuentas verificadas con configuración académica completa; no retroceder al historial para cuentas que se incorporan después. Las publicaciones se importan una vez, no una copia por usuario.
4. Editar/reordenar avisos existentes actualiza sus datos y no dispara otro aviso de publicación nueva. Un fallo de parseo o una página inesperada no se informa como sincronización exitosa con cero novedades ni borra publicaciones guardadas. Registrar el problema y conservar la última lectura válida.
5. Mostrar título, fecha de publicación cuando existe, un extracto breve seguro y origen. No confundir fecha publicada con fecha de detección ni convertir automáticamente las fechas del cuerpo en eventos personales. No inferir destinatarios por carrera a partir de palabras sueltas.
6. Ofrecer enlace real a la publicación original si existe; si sólo hay página general, etiquetar **Ver cartelera original**. Los adjuntos públicos pueden enlazarse con su URL válida, sin descargarlos y reinterpretarlos automáticamente. No renderizar HTML externo sin sanitizar ni ejecutar contenido de la fuente.

La cartelera genera avisos **dentro de EstuFlow**, sin envío masivo por email en M04. No agrega un módulo de publicación institucional ni un editor para que los estudiantes modifiquen avisos externos. Si hace falta una vista local breve para consultar un aviso notificado, reutilizá estos datos y la identidad visual existente.

## 7. M04-05 — Mis notas

CRUD personal sencillo: contenido obligatorio de texto plano, título opcional, límites razonables, creación, lectura, edición y eliminación confirmada. Guardado persistente por cuenta y recuperación tras logout/login. La nota no depende de la carrera activa, no dispara notificaciones ni tiene fechas límite.

En dashboard, hasta tres notas recientes con acciones visibles y Nueva nota; acceso a todas mediante una lista/expansión sencilla, con paginación si hace falta. Tono de post-it dentro de la paleta aprobada, sin transformar la sección en un tablero de planificación. Conservar saltos de línea y escapar contenido. Cancelar la edición no persiste cambios. No hace falta arrastrar, asignar usuarios, adjuntar archivos o gestionar estados de tareas.

## 8. Ejecución periódica, persistencia y fallos

El Compose actual ya tiene un worker que usa **cola database**; `routes/console.php` no tiene tareas programadas. Incorporá el scheduler real con el mismo entorno e imagen y sin puertos públicos; integralo en los scripts de arranque/readiness y parada existentes. Al levantar el proyecto deben quedar activos app, worker y scheduler, sin pedirle a Nico que ejecute un comando manual cada minuto.

| Proceso | Cadencia normal | Datos consultados |
| --- | --- | --- |
| Feriados | Inicial + cada 15 días por año | Proveedor externo, una lectura compartida por año debido. |
| Cartelera | Una vez al día, 08:00 Buenos Aires | Fuente institucional compartida. |
| Avisos pendientes | Cada minuto | PostgreSQL local; luego despacho a los canales elegidos. |
| Login/dashboard/calendario | Al navegar | Datos locales y caché; sin sincronizaciones externas. |

Un chequeo periódico ligero puede decidir si hay sincronización pendiente usando timestamps durables. No confundir 15 días transcurridos con dos días fijos al mes. Registrar último intento, último éxito y próxima posibilidad de reintento cuando corresponda; aplicar timeouts, tamaño de respuesta limitado y reintentos acotados con espera. Los fallos no habilitan una tormenta de requests cada minuto. Un año aún no publicado se vuelve a intentar según la cadencia, sin bloquear el calendario.

Redis puede servir para caché y locks; la copia válida, la línea de base, las identidades, el estado de envío y los intervalos no pueden desaparecer al reiniciarlo. Usá índices/restricciones y transacciones donde correspondan; un lock temporal no reemplaza la idempotencia de negocio. Se mantienen datos útiles ante fallos externos. La UI puede indicar una actualización pendiente discretamente, sin mostrar trazas técnicas.

Separá adaptadores externos, reglas de eventos/recordatorios y presentación en clases cohesionadas dentro del monolito actual. Policies/autorización y validación del servidor en HTTP y Livewire; propietario obtenido de la sesión, no de un campo del formulario. URLs externas controladas, fechas y rangos validados, salida escapada, sin SSRF por una URL enviada por el usuario. Evitá N+1, consultas de todo el historial y una solicitud externa por destinatario.

No cambies `.env`, `.env.testing`, credenciales reales ni puertos existentes. Las opciones nuevas tienen defaults seguros en configuración y se documentan en `.env.example` sin secretos. PostgreSQL de desarrollo conserva `127.0.0.1:5433`; test-postgres/Redis internos. Migraciones aditivas, catálogo y progreso M03 preservados, sin reset de desarrollo ni `down -v`.

Documentá que, en ejecución local, los avisos automáticos requieren Docker, scheduler y worker activos. El servicio debe recuperarse al reiniciar sin enviar una avalancha de avisos vencidos. Esta entrega no incluye despliegue ni garantiza envíos mientras la Mac está apagada.

## 9. Pruebas y revisión visual

Seguí el aislamiento de `docs/testing.md`: PostgreSQL `estuflow_test`/`test-postgres`, guardas antes de resets, PHPUnit y E2E **secuenciales**. Si incorporás un scheduler de pruebas, aislalo por completo y detenelo durante resets y al terminar; nunca dejes un proceso de desarrollo ejecutando trabajos sobre testing, ni a la inversa.

Pruebas significativas requeridas:

- Eventos: CRUD, campos inválidos, materia ajena al usuario, cancelación de edición/eliminación, día completo, fin, cruce de mes/año y zona horaria; aislamiento de dos cuentas y rango de consulta.
- Feriados: contrato, varias entradas en una fecha, idempotencia, datos modificados, caché/reinicio, año no publicado, timeout/respuesta inválida y conservación de la última copia válida. Navegar y hacer login no generan llamadas al proveedor.
- Recordatorios: reloj controlado, instante límite, offsets, día completo a las 09:00, doble ejecución/reintento, edición de texto frente a cambio de horario, trabajo antiguo en cola, eliminación, recuperación tras parada, email desactivado y presupuesto agotado. Probar cancelación y reprogramación con avisos ya creados; no sólo creación inicial.
- Cartelera: HTML/contrato real representado por fixtures mínimos sanitizados, primera línea de base sin avisos, nueva publicación, títulos repetidos, edición/reordenamiento, fallo de parseo, sincronización repetida y fan-out reintentado sin duplicar. No versionar páginas enteras con datos personales, credenciales o enlaces privados.
- Notificaciones: contador, filtros, paginación, marcar una/todas las propias, destino eliminado y rechazo de IDs de otra cuenta. Notas: CRUD, contenido escapado, persistencia y aislamiento.
- Integración: edición con filtros/contexto conservados, carrera cambiada sin perder eventos/notas, controles coherentes y regresión de login/recuperación/verificación y del dashboard/Mi carrera aprobados.

Las suites no llaman a fuentes externas ni a Brevo real: HTTP simulado/fixtures, notificaciones controladas y SMTP `test-mailpit`. Para acreditar el recorrido de correo, incluí al menos un envío de recordatorio realmente capturado por SMTP en testing; un fake de notificación no acredita el transporte. Evitá esperas largas: usá el reloj y comandos controlados de pruebas, sin rutas públicas de depuración ni backdoors de producción.

Hacé además una consulta de diagnóstico real de sólo lectura a ArgentinaDatos y a la cartelera, sin fan-out ni email; registrá resultado, estructura y alcance por separado. Si la red lo impide, informá ese bloqueo y no lo reemplaces por una afirmación de integración real. No envíes correos de pruebas a usuarios reales ni publiques contenido de sus bandejas.

Recorrido E2E principal: crear examen en una fecha → verlo en calendario/dashboard → consultar/editar → comprobar reprogramación → abrir notificación → eliminar y verificar cancelación. Añadí notas y lectura de cartelera con fixture al recorrido. Probá las acciones de teclado y la alternativa básica sin JavaScript.

Abrí y revisá capturas comparables a 390/1440 px de calendario, formulario, notificaciones, notas y dashboard; revisá composición a 2560/3840, reflow 320 y zoom 200 % con la infraestructura existente. Corregí cortes, foco oculto, controles desalineados, densidad y textos confusos. No acredites calidad visual sólo con ausencia de overflow. Usá cuentas ficticias y capturas sin tokens/datos privados.

Ejecutá `check.sh`, `test.sh` y `e2e.sh` según sus contratos y revisá CI sobre el HEAD entregado. Partís de una referencia M03 reportada en 109 pruebas/703 aserciones y 9 E2E; esos resultados no acreditan M04. No desactives checks ni elimines verificaciones para adaptar la UI. Durante ajustes, usá pruebas focalizadas y cerrá con las suites completas; repetilas si nuevos cambios lo justifican.

## 10. Documentación, aceptación y publicación

Guardá esta especificación según las convenciones del repo y actualizá README/progreso/UI/testing. `docs/m04.md` debe explicar modelo y propiedad de datos, fuentes/identidad de publicaciones, cadencias/reintentos, zona horaria/día completo, límites de envío, recuperación tras parada y comandos de diagnóstico/operación. Evidencia en `docs/evidence/m04/`, con resultados reales, capturas y pendientes concretos.

Incluí una demostración corta y comprensible del **CRUD completo** de eventos y notas: crear → consultar → editar → eliminar, mostrando persistencia y efecto en la interfaz. Es funcionalidad del producto; no agregues menús técnicos o una pantalla artificial de CRUD para el profesor. El perfil editable y la reubicación de ajustes siguen pendientes para la entrega siguiente.

Antes de cerrar, comprobá:

- [ ] Calendario y agenda con CRUD completo, fechas correctas y permisos propios.
- [ ] Datos reales en Próximas actividades y composición aprobada del dashboard preservada.
- [ ] Feriados compartidos y cartelera diaria; cero sincronizaciones externas al iniciar sesión/navegar.
- [ ] Recordatorios persistentes, reprogramables y cancelables; notificación interna y SMTP de prueba comprobados.
- [ ] Presupuesto de email, reintentos y recuperación sin avisos obsoletos ni duplicados de aplicación.
- [ ] Primera cartelera sin avisos históricos; publicaciones nuevas y lectura individual coherentes.
- [ ] Notas reales persistentes, sin perderlas por logout o cambio de carrera.
- [ ] Scheduler/worker integrados al arranque; aislamiento de procesos y bases de pruebas preservado.
- [ ] Revisión funcional/visual, suites y resultados del HEAD documentados honestamente.
- [ ] Diff limitado a M04 y sus integraciones necesarias; sin rediseño, perfil ni cambios de credenciales.

Tenés autorización para commits coherentes, push de la rama M04 sin force y creación de **PR en borrador** con la base correcta. Revisá el diff antes de publicar: sin secretos, `.env`, dependencias generadas, datos personales ni URLs firmadas. No hagas merge, despliegue ni publicación directa a main.

La PR debe describir el comportamiento final, tareas cubiertas, decisiones operativas, evidencia y limitaciones reales; enlazar M03 como dependencia si sigue abierta. En la respuesta final, incluí enlace y HEAD, cambios implementados, comandos correctos para que Nico actualice/levante sin perder datos, estado de scheduler/worker, checks/CI y recorrido manual breve. Separá mocks/SMTP local de consultas externas y de entrega real de Brevo. Indicá cualquier parte incompleta y no declares terminada una pantalla con acciones simuladas o un proceso periódico que sólo funciona al ejecutarlo manualmente.
