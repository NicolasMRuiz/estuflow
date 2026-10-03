Sos el desarrollador de EstuFlow. Implementá la primera entrega en la carpeta de proyecto seleccionada: base técnica reproducible, registro/login/logout y un dashboard privado con diseño propio.

CONTEXTO Y LÍMITES
EstuFlow será un organizador académico con carrera, materias, correlativas, agenda y planificación. Nicolás supervisa y acepta las entregas; ChatGPT define requisitos y prompts; vos implementás y verificás. No asumas acceso al historial de otros chats.
No existe un máximo de tablas ni de funcionalidades impuesto por el profesor. Aun así, este prompt tiene un alcance concreto: no implementes ahora todo el producto.
Primero inspeccioná el directorio, las instrucciones AGENTS.md aplicables y los cambios existentes. Preservá el trabajo ajeno. Si la carpeta no es inequívocamente la del proyecto, preguntá la ruta antes de escribir.
Presentá un plan breve y ejecutalo; no te detengas solamente en el plan. Pedí intervención únicamente ante permisos, dependencias del sistema ausentes o decisiones que cambien materialmente el alcance.

STACK Y ENTORNO
- Laravel 13, PHP 8.4, Livewire 4, Blade, Tailwind 4, Alpine, PostgreSQL y Redis, con Docker Compose.
- Verificá compatibilidad en documentación oficial y fijá versiones compatibles, lockfiles e imágenes versionadas; no uses etiquetas latest. PostgreSQL 18 es la base propuesta.
- Usá el Alpine incluido por Livewire: una sola instancia.
- Podés aprovechar el starter kit oficial Livewire y sus componentes gratuitos. No incorpores React, Vue, Inertia, WorkOS, UI paga ni servicios externos.
- El entorno debe funcionar en macOS Apple Silicon con Docker. La instalación de Composer/Node y los comandos de la app deben poder ejecutarse en contenedores, sin exigir PHP ni Composer globales.
- Prepará app/web, PostgreSQL, Redis y compilación frontend; healthchecks, volúmenes identificados y readiness de servicios. No expongas PostgreSQL ni Redis al host salvo necesidad documentada; la web local se publica en localhost.
- Guardá cuentas y sesiones en PostgreSQL; Redis queda para caché, nunca como único almacenamiento de datos personales. Demostrá una operación temporal de caché y eliminá solo esa clave de prueba.
- Incluí .env.example sin secretos reales. No sobrescribas un .env existente ni publiques su contenido. No cambies ajustes globales de la Mac, instales Docker ni modifiques configuraciones globales sin autorización.
- Si Docker falta, está detenido o un permiso impide la ejecución, indicá exactamente el bloqueo. Podés avanzar con preparación segura del proyecto, pero no reemplaces el stack ni declares verificadas las partes que no corriste.

FUNCIONALIDAD DE ESTA ENTREGA
1. Registro con nombre, email único y contraseña confirmada; validación en servidor, password hasheado y errores claros en español. Normalizá el email de forma consistente y probá duplicados.
2. Login, credenciales incorrectas, limitación de intentos, regeneración de sesión y redirección a /dashboard.
3. Logout por acción protegida con CSRF; invalidación de sesión y regeneración del token.
4. /dashboard protegido: sin sesión no se accede. El nombre mostrado proviene de la cuenta autenticada; no de fixtures ni de la URL.
5. Para esta entrega no se exige email verificado. Recuperación, verificación de email y edición de perfil serán tareas posteriores: no muestres enlaces que aparenten completar flujos no implementados ni uses correo externo.
6. No crees todavía el modelo académico ni CRUD de materias, correlativas, agenda, planificador, búsqueda, alertas o reporte imprimible. Dejá esas features como pendientes documentadas, no como funcionalidad simulada.

CONTRATO VISUAL
Marca: EstuFlow. Interfaz en español. No entregues el starter kit sin personalizar.
Si adjunto una captura del dashboard, usala como referencia de composición, no como fuente de números, fechas ni historial real. Si no hay imagen disponible, avanzá con este contrato:
- Sidebar azul profundo; fondo claro; tarjetas amplias con bordes suaves, sombras discretas y espaciado consistente.
- Paleta inicial: azul #09263D, verde agua #17A88B, menta #98E6D4, fondo #F7F5EF y coral #F27864. Ajustá combinaciones para legibilidad y contraste.
- Navegación: Inicio, Mi carrera, Calendario, Materias y Planificador. Inicio funciona; las secciones pendientes se identifican como próximas y no se presentan como enlaces operativos.
- Cabecera con saludo usando el nombre autenticado y cierre de sesión funcional. No simules una búsqueda ni notificaciones.
- Dashboard compuesto por tarjetas de avance, próximas actividades, materias en curso y correlativas. Mostrá estados vacíos claros: todavía no hay carrera ni datos académicos. El progreso sin denominador se muestra como “Sin datos”, no un porcentaje inventado.
- Registro y login comparten la identidad visual.
- Responsive real: sidebar accesible en escritorio, menú móvil operable, tarjetas apiladas y sin scroll horizontal a 390 px; revisá también reflow a 320 px.
- Formularios etiquetados, navegación con teclado, foco visible, errores asociados al campo y estados de carga. El color no es la única señal.

ARQUITECTURA Y SEGURIDAD
Monolito modular, siguiendo convenciones Laravel. Aplicá SOLID, KISS y YAGNI de manera pragmática: componentes pequeños, tipado cuando corresponda y validación/autorización en servidor. No crees una interfaz, repositorio o servicio por cada modelo ni carpetas vacías para todos los módulos futuros.
Tratá propiedades y parámetros Livewire como entradas no confiables. Usá la autenticación del framework, CSRF y rate limiting; no implementes criptografía propia. Ninguna contraseña, token o dato privado debe aparecer en logs, capturas o respuestas de entrega.

CALIDAD Y DOCUMENTACIÓN
- Configurá Pint, Larastan, pruebas Laravel y build frontend, con comandos reproducibles. Usá análisis gradual razonable, sin ocultar errores del código propio con ignores generales.
- Las pruebas de persistencia se ejecutan con PostgreSQL, no SQLite como sustituto.
- Creá una base dedicada de pruebas, separada de desarrollo. Antes de cualquier preparación/reset automatizado, comprobá entorno testing y nombre explícito de la base de pruebas; fallá ante otra configuración.
- Cubrí registro válido/inválido, email duplicado, password hasheado, login correcto/incorrecto, rate limit, acceso anónimo, logout y dos cuentas cuyo dashboard muestra únicamente su propia identidad.
- Agregá un smoke E2E en navegador para registro → dashboard → logout → login. Corrélo contra una instancia conectada a datos de prueba aislados, nunca contra datos personales.
- Verificá visualmente escritorio y móvil; guardá capturas de 1440x900 y 390x844 de una cuenta ficticia de prueba. Una captura no implica aceptación visual de Nicolás.
- Documentá README con setup desde una carpeta nueva, comandos y URL; docs/architecture.md con decisiones tomadas; docs/ui.md con tokens y estados; docs/testing.md con aislamiento y checks; docs/progress.md con esta entrega y siguientes pasos.
- Creá o ajustá AGENTS.md con reglas permanentes breves y comandos reales del proyecto. Referenciá documentación según la tarea, no obligues a leer toda la documentación para cualquier edición.
- Si recibís el plan anterior de 40 tareas, usalo como inventario: no es un límite ni habilita a implementar todo. Sus roles y restricciones pendientes se actualizan con el contexto de este prompt.
- No publiques, compres servicios, crees repositorios remotos, hagas push ni commits automáticos. Si ya hay Git, respetá su estado. No borres volúmenes ni ejecutes migraciones destructivas sobre una base existente.

DEFINICIÓN DE TERMINADO
La app arranca mediante los comandos documentados; PostgreSQL y Redis se comprueban; una persona puede registrarse, salir, ingresar y ver su panel privado. El layout es propio, responsive y honesto respecto de las funciones pendientes. Formato, análisis, tests, E2E y build tienen resultados ejecutados o bloqueos explícitos.
Continuá hasta implementar, ejecutar los checks y corregir fallos causados por tus cambios. No hagas otras features para compensar un bloqueo.

ENTREGA FINAL
Respondé en español con:
- Resumen de lo implementado y archivos principales.
- Versiones elegidas, URL y comandos exactos para iniciar y revisar.
- Checks ejecutados con comando y resultado; separá PASS, FAIL y NO EJECUTADO.
- Rutas de capturas y evidencia disponibles, sin inventarlas.
- Decisiones, limitaciones y bloqueos.
- Cómo reproducir registro/login/logout y cuál sería la siguiente tarea.
No afirmes “listo”, “seguro” o “probado” sin evidencia correspondiente.