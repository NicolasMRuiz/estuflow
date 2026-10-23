# Corrección M03 · 23/10/2026

Base revisada: `60d0fb6` de `codex/m03-mi-carrera`, PR #2 en borrador sobre M02 `9f31538`. M02 y M03 seguían sin integrar al iniciar. Referencia visual: dashboard, Blade y CSS de M02; la presentación M03 del 13/10 no estaba aceptada.

El adjunto de esta corrección disponible en la sesión contiene sólo texto. Las capturas del usuario del 23/10 que menciona no estaban adjuntas como imágenes accesibles; no se afirma haberlas inspeccionado. Se revisaron las capturas existentes del repositorio y se preservan doce vistas anteriores de M03 en `before/`, copiadas de `60d0fb6`. No se presentan como diseño aprobado.

## Hallazgo → cambio → verificación

| Hallazgo confirmado | Cambio | Verificación |
| --- | --- | --- |
| Dashboard con fila dominante de contadores, sin bloque de cuenta y tarjetas reordenadas | Composición M02, avatar, banner breve, misma grilla/orden e iconos; conteos compactos en avance | Capturas 390/1440/2560/3840; orden de tarjetas y avatar en E2E |
| Anillo neutro fijo | SVG proporcional a aprobadas/total y texto accesible | Recorrido real que edita materias: 0 %, 4 % y 100 %, incluidas promociones y métodos sin especificar; tres capturas |
| Claves/siglas/celdas del PDF como contenido principal | Nombres comprensibles, información útil y fuente en Datos del plan | Inspección de listado/detalle y conservación exacta del catálogo en PHPUnit |
| Dos selects y ayuda repetidos en todas las materias | Años plegables, método condicional, acción por año con deshacer, barra de acciones compartida | E2E: aplicar/deshacer sin guardar, guardar todos los años, recuperar tras login y volver desde resumen |
| Botones con márgenes contradictorios y controles desproporcionados | Componentes compartidos, márgenes de barras, selector de detalle acotado y filtros móviles sin texto cortado | Capturas abiertas e inspeccionadas; reflow y controles alcanzables |
| Edición poco reconocible y redirect innecesario | Ver detalle / Editar estado; editor inline con Guardar cambios / Cancelar y alternativa HTTP | E2E conserva filtro, actualiza conteos, anuncia salida del filtro y devuelve foco; sin JS conserva contexto |
| Selección de otra carrera activada demasiado pronto | Candidato persistente separado de la confirmada; activación sólo al confirmar revisión vigente | PHPUnit/E2E: cancelar en los tres pasos, logout intermedio, reanudar, confirmar, retornar y mismo plan |
| Riesgo de estado anterior al cancelar/reabrir editor | Binding de campos sin entanglement persistente y acciones inhabilitadas durante requests | Recorrido de navegador reproduce cancelar, reabrir, editar y guardar con filtro; verifica resultado persistido |
| Errores técnicos/duplicados | Mensajes humanos junto al campo, año con error abierto y foco al control | Selección vacía y método inválido en E2E; sin `validation.*`, validación P/R del servidor |

## Datos y seguridad

La migración aditiva agrega `is_candidate` y un índice parcial único por usuario. No borra progreso ni reinicia una finalización al seleccionar. Repara el estado antiguo de cambio activo sin terminar recuperando la selección confirmada más reciente y conservando el reemplazo como candidato. Ese caso se reproduce explícitamente sobre PostgreSQL de test.

Confirmar comprueba cuenta, candidato, materias y huella del borrador revisado en sesión bajo bloqueo del usuario. Un resumen ajeno/desactualizado se rechaza. Elegir el mismo plan confirmado no cambia el registro. Cancelar conserva la selección confirmada y los datos de ambos planes. El primer onboarding sigue bloqueando el dashboard hasta confirmar.

HTTP y Livewire resuelven materias dentro de la cuenta y selección pertinentes. Los tests cubren IDs manipulados, propiedad locked, cuenta sin verificar y aislamiento entre dos usuarios. No se cambia importador, JSON, PDFs, hechos académicos ni autenticación. Se conservan tres relaciones de correlativas, reglas del año anterior, promoción incierta, práctica sin autorreferencia, 356 horas de TECEN 342 y resolución nullable.

`.env` y `.env.testing` se comparan con SHA-256 privado y siguen iguales e ignorados. No se publican hashes de configuración, passwords, emails personales, tokens ni URLs de seguridad. No cambian dependencias/lockfiles, Compose, Brevo, Microsoft ni puertos. DataGrip sigue `127.0.0.1:5433`; testing y Redis internos. No hay reset de desarrollo ni borrado de volúmenes.

## Checks ejecutados en Mac ARM64

| Comando | Resultado |
| --- | --- |
| `./scripts/setup.sh` | PASS: lockfiles actuales, migraciones pendientes/importación y readiness |
| `./scripts/start.sh` | PASS: migración aditiva, importación idempotente, servicios healthy y login con CSRF |
| `./scripts/check.sh` | PASS: Composer strict, Pint, Larastan nivel 6 sin errores y build Vite |
| `./scripts/test.sh` | PASS: 109 pruebas, 703 aserciones, 17,81 s; PostgreSQL estuflow_test/test-postgres |
| `./scripts/e2e.sh` | PASS: 9 recorridos, 41,0 s, posterior a PHPUnit, con app/worker/test-mailpit aislados |
| `git diff --check` | PASS |
| Proveedores Google/Brevo/Microsoft reales | NO EJECUTADO: pruebas automatizadas usan mocks/fakes y test-mailpit; no acreditan integraciones externas |

Se usaron recorridos focalizados durante la corrección; las cifras anteriores son de las suites completas del cierre. Los números del 13/10 no acreditan esta corrección. No se ejecutaron PHPUnit y E2E en paralelo. La autenticación conserva sus recorridos de login/logout/verificación/recuperación, con correo SMTP capturado en testing y mocks OAuth.

## Inspección visual y capturas

Se abrieron las capturas generadas: composición conjunta de las seis pantallas en cuatro anchos, más inspección ampliada de dashboard, anillo completo, formulario inicial, Mi carrera móvil/escritorio, resumen y detalle con nombre largo. La inspección corrigió el ancho innecesario del selector y el texto truncado del filtro móvil. El dashboard de 1440 recupera saludo/banner y posiciones de grilla de M02; las cuatro tarjetas se apilan en el mismo orden en móvil.

33 capturas nuevas: seis pantallas a 390×844, 1440×900, 2560×1440 y 3840×2160 (24); seis con zoom real 200 %; tres del anillo. Son capturas del viewport: listas y páginas mantienen scroll vertical. Las doce anteriores están en `before/`.

| Pantalla | Antes 1440 | Después 1440 | Después 390 | Después 2560 | Después 3840 |
| --- | --- | --- | --- | --- | --- |
| Inicio | [PNG](before/dashboard-1440x900.png) | [PNG](dashboard-1440x900.png) | [PNG](dashboard-390x844.png) | [PNG](dashboard-2560x1440.png) | [PNG](dashboard-3840x2160.png) |
| Elegir carrera | [PNG](before/elegir-plan-1440x900.png) | [PNG](elegir-plan-1440x900.png) | [PNG](elegir-plan-390x844.png) | [PNG](elegir-plan-2560x1440.png) | [PNG](elegir-plan-3840x2160.png) |
| Mis materias | [PNG](before/situacion-inicial-1440x900.png) | [PNG](situacion-inicial-1440x900.png) | [PNG](situacion-inicial-390x844.png) | [PNG](situacion-inicial-2560x1440.png) | [PNG](situacion-inicial-3840x2160.png) |
| Resumen | [PNG](before/resumen-1440x900.png) | [PNG](resumen-1440x900.png) | [PNG](resumen-390x844.png) | [PNG](resumen-2560x1440.png) | [PNG](resumen-3840x2160.png) |
| Mi carrera | [PNG](before/mi-carrera-1440x900.png) | [PNG](mi-carrera-1440x900.png) | [PNG](mi-carrera-390x844.png) | [PNG](mi-carrera-2560x1440.png) | [PNG](mi-carrera-3840x2160.png) |
| Detalle | [PNG](before/detalle-practica-1440x900.png) | [PNG](detalle-practica-1440x900.png) | [PNG](detalle-practica-390x844.png) | [PNG](detalle-practica-2560x1440.png) | [PNG](detalle-practica-3840x2160.png) |

Anillo: [0 %](avance-0-1440x900.png), [4 %](avance-4-1440x900.png), [100 %](avance-100-1440x900.png). La cuenta ficticia aprueba materias por la UI; el trazo y numerador/denominador corresponden al estado guardado.

Reflow comprobado en 320×740 y 640×360, además de la matriz M02 de nueve viewports. Zoom **real** 200 % con la infraestructura existente de Chromium (`setZoom/getZoom=2`): [medidas](zoom-200.json); 1280×720 se compone en 640×360 CSS sin ancho extra en las seis pantallas. Se comprueba llegar a acciones y guardar. No se usa CSS zoom ni se acredita escalado de macOS/monitor, Safari/Firefox o lectores de pantalla. Teclado: navegación móvil Enter/Escape, apertura del editor, Tab al estado, cancelación y retorno de foco; error de método enfocado y guardado anunciado.

## Revisión manual de Nico

1. Ingresar/verificar email si corresponde. Una cuenta configurada debe entrar directamente a Inicio.
2. Revisar saludo/avatar, carrera, anillo y cuatro tarjetas en el orden M02. Abrir una materia cursando y un requisito desde Correlativas.
3. En Mi carrera, filtrar y editar: cancelar sin persistir, luego guardar; comprobar aviso si sale del filtro y progreso del dashboard.
4. Cambiar carrera, guardar borrador, visitar Inicio y cerrar/abrir sesión. La carrera anterior sigue activa. Retomar y cancelar en selección/materias/resumen.
5. Repetir y confirmar; volver al plan anterior y revisar estados/métodos. Elegir el mismo plan no reinicia nada.
6. Con cuenta sin configurar: todas pendientes, aplicar por año/deshacer, métodos P/R, guardar/retomar, volver desde resumen y confirmar. Repetir el flujo básico sin JS.
7. Revisar 390/1440, QHD/4K y 200 % de zoom; consultar promociones/condiciones de año/práctica final sin mensajes de habilitación oficial.

Para aplicar, desde la rama de PR #2 y con cambios propios preservados: `git fetch origin`, `git switch codex/m03-mi-carrera`, `git pull --ff-only`, `./scripts/setup.sh`, `./scripts/start.sh`. Sólo migraciones pendientes e importación idempotente, sin resets. La aprobación visual final corresponde a Nicolás; las ambigüedades académicas y proveedores reales conservan sus pendientes documentados.

## Publicación y CI

Corrección publicada en `e8b56f0aad0b1e044fdd332e0a84cfd76a944700`. GitHub Actions consultado directamente, con todos los pasos aprobados en Linux AMD64:

- [Push](https://github.com/NicolasMRuiz/estuflow/actions/runs/39984375447): PASS.
- [Pull request](https://github.com/NicolasMRuiz/estuflow/actions/runs/39984381459), [job checks](https://github.com/NicolasMRuiz/estuflow/actions/runs/39984381459/job/120519642903): PASS.
- Setup, Composer/Pint/Larastan/build, PHPUnit y E2E completados; los logs confirman **109 pruebas / 703 aserciones y 9 E2E**. Capturas subidas como artifact `estuflow-capturas`, retención 14 días. Las imágenes nuevas y sus comparaciones quedan además versionadas aquí, sin esa caducidad.

PR #2 sigue en borrador, base M02 mientras #1 no esté integrada. No se hizo merge ni despliegue. El commit posterior sólo registra estos resultados; el estado del HEAD actualizado está en [Checks de la PR](https://github.com/NicolasMRuiz/estuflow/pull/2/checks). Los PASS anteriores de M03 no se presentan como prueba de esta corrección.
