# EstuFlow — M03: catálogo académico, configuración inicial y Mi carrera

Implementá M03 completo sobre la entrega M02 existente. Nico supervisa y revisa; tu responsabilidad es implementar, verificar y dejar una PR revisable. Los adjuntos son la especificación de esta entrega. Procedé con las decisiones definidas aquí; consultá sólo ante un bloqueo real que no se pueda resolver sin inventar datos o perder información.

## 1. Contexto y base de trabajo

- Proyecto: `NicolasMRuiz/estuflow`. Stack existente: Laravel + Livewire + Blade + Tailwind + Alpine + PostgreSQL + Redis + Docker. Conservá versiones, convenciones, diseño y herramientas del repositorio; no hace falta actualizar dependencias para esta entrega.
- Leé `AGENTS.md`, README y documentación vigente antes de editar. Verificá rama, estado del checkout y base real; no sobrescribas trabajo local del usuario.
- Referencia de entrega anterior al preparar este prompt: PR #1 en borrador, rama `codex/m02-auth-responsive`, último commit conocido `9f31538ecc3e7d7ca27851e295abb04db5f8c984`. El estado remoto puede cambiar: comprobalo antes de decidir la base.
- Si M02 ya está integrada, creá `codex/m03-mi-carrera` desde main actualizado. Si sigue sin integrar, creá la rama desde la versión más reciente de M02 y abrí una PR en borrador con base en esa rama; documentá que deberá apuntar a main después de integrar M02. No implementes sobre el main antiguo sin M02 y no mezcles automáticamente la PR #1. Usá el checkout actual; no crees un worktree salvo indicación del usuario.
- Microsoft y otras mejoras identificadas quedaron para después. No requieren cambios ni bloquean M03. Conservá los flujos existentes de login, Google, verificación y recuperación de contraseña.
- No modifiques `.env`, `.env.testing`, credenciales ni valores reales de Brevo/OAuth. PostgreSQL de desarrollo sigue accesible para DataGrip por `127.0.0.1:5433`; Laravel usa `postgres:5432`. Preservá volúmenes; test-postgres y Redis siguen internos.

## 2. Archivos y fuente de verdad

Se adjuntan:

1. `EstuFlow_M03_catalogo.json`: catálogo estructurado revisado, tres planes y **71 materias**.
2. `EstuFlow_M03_revision.md`: tablas completas, semántica, anomalías y siete tareas para el tablero.
3. Este prompt.
4. Los tres PDFs originales: TSAS 2024, TECEN 2024 y TSHSA 2026, cuyos nombres y hashes están en el JSON.

Usá el JSON para la importación y los PDFs para contrastar dudas. Copiá los adjuntos necesarios a ubicaciones estables del repo, siguiendo sus convenciones; documentá la procedencia y el comando de importación. La transcripción ya fue revisada; si encontrás una discrepancia, registrá exactamente página, campo y diferencia antes de cambiarla. No reemplaces silenciosamente datos llamativos.

| Carrera | Plan según la fuente | Materias | Año 1 / 2 / 3 |
| --- | --- | --- | --- |
| Tecnicatura Superior en Análisis de Sistemas | Resolución 6790/19; ingreso desde 2020 | 23 | 8 / 8 / 7 |
| Tecnicatura Superior en Enfermería | Resolución 854/16; ingreso desde 2019 | 24 | 8 / 9 / 7 |
| Tecnicatura Superior en Higiene, Seguridad y Ambiente Laboral | Ingreso desde 2026; resolución no indicada | 24 | 8 / 9 / 7 |

El año del nombre del PDF no es el año de inicio del plan. El producto apunta a doce carreras; esta entrega carga las tres con fuente recibida y permite sumar otras sin rediseñar el modelo. No inventes las otras nueve ni afirmes vigencia institucional que los documentos no acreditan.

## 3. M03-01 — Catálogo e importación

Separá el catálogo institucional compartido del progreso personal: institución/carrera, versión de plan, materias del plan, requisitos y reglas generales; por otro lado, selección académica del usuario y estado de cada materia. Elegí nombres y cantidad de tablas según las convenciones del proyecto, con claves foráneas, índices y restricciones de unicidad necesarias. Un código de materia pertenece a un plan; nunca lo uses como identificador global.

Conservá en el modelo datos suficientes para reconstruir el JSON: nombre fuente y visible, correcciones editoriales, código, orden, año académico, G/E, R/P, horas, origen y página, tres tipos de correlativas y reglas especiales. No cruces materias homónimas entre carreras ni supongas equivalencias. La resolución de TSHSA es nullable.

Importador/seeder transaccional e idempotente, con claves estables de plan y materia. Validá estructura, tipos, códigos únicos, referencias dentro del mismo plan y ausencia de autorreferencias/ciclos explícitos antes de persistir. Reejecutar con el mismo archivo no duplica carreras, planes, materias, reglas ni relaciones y **no modifica progreso personal**. Ante una importación inválida, no debe quedar un catálogo parcialmente escrito. No borres versiones anteriores para introducir otra; las futuras actualizaciones del catálogo deben conservar referencias personales y quedar documentadas.

Integrá la carga de forma reproducible con el flujo existente de instalación y documentá cómo aplicarla a una instalación M02 con datos. Usá migraciones aditivas; nunca `migrate:fresh`, reset de desarrollo, `down -v` o reemplazo de volúmenes. El catálogo compartido no es editable por estudiantes.

### Reglas académicas que hay que conservar

- `to_take_final_requires_finals`: finales previos para rendir el final.
- `to_enroll_requires_coursework`: cursadas previas aprobadas para inscribirse a cursar.
- `to_enroll_requires_finals`: finales previos para inscribirse a cursar.
- Reglas por año: segundo requiere dos materias de primero aprobadas en final, al menos una E; tercero requiere dos de segundo, al menos una E. No alcanza con un único contador global de toda la carrera. Primer año incluye condiciones administrativas; mostrarlas informativamente, sin pedir documentos o simular una validación institucional.
- Las prácticas de tercer año tienen `TODAS` y una nota al pie. Guardalo como regla especial sobre las demás materias del mismo plan, excluyendo la propia práctica; no lo expandas a una relación circular. La columna refiere al final y la nota a aprobar la práctica: el momento exacto de aplicación queda por confirmar.
- Conservá **356 horas** para Enfermería 342. Conservá el nombre fuente «Seguirdad» para TSHSA 604 y usá la corrección visible documentada «Seguridad». En Sistemas 305 y 314, mantené la transcripción y la tilde del nombre visible documentada en el JSON.
- No inventes vencimientos de regularidad, notas mínimas, fechas, horarios, cuatrimestres, equivalencias ni reglas de recursada.

## 4. M03-02 y M03-03 — Configuración inicial

Recorrido: registro/login → verificación de email según M02 → selección de carrera/plan → carga inicial de materias → resumen y confirmación → dashboard. Debe funcionar para cuentas locales, Google y usuarios ya existentes que no tengan configuración académica. Una cuenta existente no pierde datos de autenticación ni verifica otra vez un email ya verificado.

Primer paso: las tres carreras, sus nombres completos y referencia de plan/ingreso. Selección explícita, sin preseleccionar una carrera. No agregues el año de ingreso como requisito personal: estos PDFs no bastan para resolver cohortes de planes anteriores.

Segundo paso: listado agrupado por año con estados iniciales pendientes. El estudiante puede guardar cambios y retomar el borrador tras recargar o volver a iniciar sesión. Se puede terminar con todas pendientes. Mostrá resumen antes de confirmar y persistí la finalización de forma consistente.

Estados personales mínimos, con valores internos estables y etiquetas en español:

| Estado visible | Significado |
| --- | --- |
| Pendiente | Aún no cursada / no declarada |
| Cursando | La está cursando |
| Regularizada / final pendiente | Cursada aprobada; acreditación final pendiente |
| Aprobada | Materia acreditada por el estudiante |

Cuando esté aprobada, permití indicar método **Final**, **Promoción** o **No lo especifico**. Promoción está disponible para materias P; para R, final o no especificado. No hagas obligatoria una nota o fecha que las fuentes no definen. Aprobada satisface el requisito de cursada previa; regularizada también. Una aprobación por promoción cuenta como materia aprobada para progreso personal, pero su equivalencia frente a una regla que exige final queda pendiente de confirmación institucional.

Guardá sólo materias del plan elegido y del usuario autenticado, con validación del servidor. Protegé el acceso HTTP y las acciones Livewire; una cuenta sin configuración completa llega al paso pendiente al acceder al dashboard académico. Evitá bucles con las rutas de onboarding, logout, OAuth, verificación y recuperación. La verificación de email conserva prioridad. No marques onboarding completo sólo por haber elegido carrera.

## 5. M03-04 y M03-05 — Mi carrera y orientación

Activá el enlace real «Mi carrera». Mostrar carrera, referencia del plan, resumen y materias por año, con filtros por año/estado y acceso a un detalle de cada materia: horas, tipo, instancia, estado personal y correlativas separadas para cursada/final. Los códigos relacionados deben resolverse a nombres de ese mismo plan.

Permití editar el propio estado después del onboarding y cambiar el método de aprobación cuando corresponda. Si deja de estar aprobada, no conserves un método como si siguiera acreditada. Al cambiar carrera/plan, explicá y confirmá el cambio, preservá el progreso del plan anterior, cargá el borrador correspondiente al nuevo y completá su configuración. Volver al anterior recupera sus datos. Una carrera activa por cuenta en esta entrega; no agregues una interfaz de múltiples carreras simultáneas.

Las correlativas y condiciones del año son **orientación para organizarse**. No bloquean guardar materias actuales o históricas: el usuario puede estar terminando de declarar su información o tener situaciones no descritas por el PDF. No uses mensajes que acrediten una inscripción oficial o garanticen que puede rendir.

Implementá una evaluación pequeña y comprobable con resultado **Completo**, **Faltan requisitos declarados** o **Por confirmar**, explicando los requisitos que producen el resultado:

- Cursada requerida: regularizada o aprobada satisface; pendiente/cursando aún no.
- Final requerido: aprobada por final satisface; pendiente/cursando/regularizada aún no. Aprobada por promoción o sin método es incierta porque estas fuentes no establecen equivalencia.
- En umbrales del año, si los finales declarados ya alcanzan dos y al menos uno E, el requisito conocido se satisface. Si sólo se alcanzaría contando promociones/aprobaciones sin método, queda por confirmar. Si ni incluyéndolas alcanza, faltan requisitos declarados.
- Las condiciones se combinan por contexto: inscripción a cursada y correlativas de final se muestran separadas. No afirmes habilitación completa para rendir por haber completado sólo las correlativas.
- En TODAS, mostrá el texto y los requisitos personales observables, con la aclaración breve sobre el momento de aplicación y las promociones. No resuelvas automáticamente la ambigüedad de la fuente.

La UI necesita una nota breve en el detalle de las reglas afectadas; la explicación técnica y las dudas documentales van en docs, no en un bloque de advertencias repetido en cada pantalla.

## 6. M03-06 — Dashboard y UI

Conectá el dashboard existente con datos reales: carrera activa, total de materias, aprobadas, cursando, regularizadas/final pendiente y pendientes; progreso `aprobadas / total del plan`. Mostrá numerador y denominador y una regla de redondeo consistente. No mezcles materias de un plan anterior ni uses horas para ese porcentaje. Las aprobadas por promoción también suman al progreso.

La tarjeta de materias cursando abre Mi carrera filtrada. Los accesos implementados deben funcionar; las funcionalidades futuras mantienen el patrón existente de próximamente. Conservá el diseño EstuFlow de M02 y documentá cualquier ajuste necesario con capturas, sin inventar estadísticas, calendarios o tareas.

Mantener layout usable de 320 px CSS hasta 4K: panel centrado, ancho de lectura controlado, tarjetas y tablas adaptadas, nombres largos completos en detalle, sin overflow horizontal accidental de la página. Estados por texto además de color; teclado, foco visible, labels, errores inline, `aria-describedby` y anuncios de guardado accesibles. Mantener un recorrido básico utilizable sin JavaScript mediante los patrones existentes y mejora progresiva, sin duplicar reglas de negocio.

## 7. Implementación y verificación

Monolito modular y clases cohesionadas. Reglas y cálculo de progreso fuera de Blade y de handlers Livewire extensos; autorización y validación del servidor, enums/value objects sólo cuando aclaren el dominio. Aplicá SOLID de forma práctica, evitando capas genéricas, repositorios o una arquitectura de servicios que no resuelvan una necesidad real. Cuidá consultas y carga de relaciones; no introduzcas N+1 en el listado de correlativas.

Pruebas pertinentes sobre PostgreSQL de test, siguiendo la separación y los guards de M02:

1. Importación: tres planes/71 materias, tipos/horas/metadatos, idempotencia, transacción ante referencias inválidas y preservación del progreso personal.
2. Ejemplos que comprueban columnas distintas: TSAS 303 requiere 296 para cursada, pero 296 y 297 para final; TSAS 310 requiere 294 como final para inscripción; TSHSA 621 tiene finales previos para inscripción y columna de correlativas de final vacía. TODAS sin autorreferencia. Enfermería 342 mantiene 356 horas.
3. Configuración inicial de cuenta nueva, existente y flujo Google; prioridades de verificación, borrador recuperable, todas pendientes, finalización, validación de estados/métodos y rechazo de materias ajenas al plan.
4. Aislamiento entre dos cuentas, manipulación de IDs en requests y acciones Livewire; el estudiante no altera catálogo ni progreso de otros. Cambio de carrera y retorno sin perder estados.
5. Evaluación de correlativas y umbrales: final vs promoción vs método desconocido, G/E, año anterior correcto y resultado incierto sin bloquear la carga personal.
6. Dashboard y Mi carrera con datos reales; progreso 0 %, intermedio y 100 %, filtros y persistencia tras logout/login. E2E de onboarding → edición → dashboard y una configuración existente; no depende de proveedores OAuth o Brevo reales.

Ejecutá `check.sh`, `test.sh` y `e2e.sh` según sus contratos; PHPUnit y E2E secuenciales sobre la base de test. Corré los checks requeridos de CI, sin repetir una suite que ya pasó salvo nuevos cambios o dudas concretas. No reportes pruebas simuladas de Google/Brevo como integraciones reales.

Capturas de las pantallas nuevas con datos reproducibles al menos a 390, 1440, 2560 y 3840 px; comprobación de reflow a 320 px y zoom 200 %. Señalá qué se revisó realmente y qué no. Documentá defectos y correcciones si aparecen. No agregues pruebas que sólo reproduzcan la implementación ni infraestructura ajena al alcance.

## 8. M03-07 — Documentación y entrega

Actualizá README y progreso; agregá documentación de M03 con modelo, contrato de catálogo, reglas pendientes de confirmar, método de importación y guía de revisión manual. Incluí evidencia de comandos/checks y capturas siguiendo la estructura existente. Las siete tareas del archivo de revisión son el mapa de esta entrega, no un límite de alcance del producto.

Tenés autorización para los commits de esta entrega, push de su rama y creación de **PR en borrador** para revisión de Nico. Antes, revisá el diff y asegurá que no contenga secretos, `.env`, dependencias, datos personales ni evidencia con credenciales. No hagas merge ni despliegue. No publiques a main directamente.

La PR debe explicar el comportamiento final, fuentes, casos de revisión, pruebas ejecutadas y pendientes reales. Al terminar, informá rama, commits, enlace de la PR, comandos para que Nico actualice y levante su checkout sin perder datos, cómo importar el catálogo en su base existente y el recorrido manual completo. Verificá que los comandos propuestos correspondan al estado real de su base/rama y a los scripts del repo. Indicá claramente cualquier parte incompleta; no declares la entrega terminada si el catálogo, onboarding, Mi carrera o dashboard aún tienen datos ficticios o acciones sin implementar.
