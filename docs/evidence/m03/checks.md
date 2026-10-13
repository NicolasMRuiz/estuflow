# Evidencia M03 · 13 de octubre de 2026

Implementación en Mac ARM64 con Docker Desktop, sobre M02 `9f31538`. Rama `codex/m03-mi-carrera`; la PR usa `codex/m02-auth-responsive` como base mientras #1 siga abierta/sin integrar. No se hizo merge ni despliegue. Fuentes y alcance en [M03](../../m03.md).

## Checks locales ejecutados

| Comando / revisión | Resultado real |
| --- | --- |
| `./scripts/setup.sh` | PASS: lockfiles existentes, migración aditiva, importación de 3 planes y readiness PostgreSQL/Redis |
| `./scripts/start.sh` | PASS: migraciones pendientes/importación idempotente, servicios healthy y login con CSRF |
| `./scripts/check.sh` | PASS: Composer validate strict, Pint, Larastan nivel 6 sin errores, build Vite |
| `./scripts/test.sh` | PASS: 103 pruebas, 634 aserciones, PostgreSQL estuflow_test/test-postgres (15,50 s) |
| `./scripts/e2e.sh` | PASS: 7 recorridos Chromium, 27,8 s, instancia/worker/test-mailpit aislados; posterior a PHPUnit |
| `git diff --check` | PASS |
| `.env` y `.env.testing` | PASS: comparación SHA-256 privada, bytes conservados; ambos ignorados |
| Proveedores externos desde automatización | No consultados: mocks/fakes PHPUnit y SMTP test-mailpit E2E |
| Login Google / entrega Brevo reales | NO EJECUTADO por el agente; no se acredita con mocks, configuración ni capturador local |
| Microsoft real | NO EJECUTADO; sigue deshabilitado |

PHPUnit prueba catálogo reconstruible, SHA de los tres PDFs, 71 materias, horas de TECEN 342, correcciones editoriales, reglas generales/especiales, importación repetida conservando progreso y nuevas versiones conservando el plan anterior. Rechaza duplicados, autorreferencia, ciclo, referencias ajenas, tipos/códigos/orden/resúmenes inválidos sin persistencia parcial. FK PostgreSQL rechaza dependencia entre planes.

También cubre cuenta existente, registro/verificación primero, callback Google con mock que llega a elección académica, borrador/login, todas pendientes, métodos P/R, IDs manipulados HTTP/Livewire, propiedad locked, revalidación de plan/email, cambio/retorno, progreso 0/intermedio/100, filtros, consultas acotadas y condiciones por año anterior G/E. Correlativas 303/310/621 y TODAS tienen casos explícitos.

E2E mantiene recuperación/verificación SMTP y seguridad M02; añade elección → borrador recuperado → resumen → dashboard → filtros/detalle → edición Livewire → cambio y retorno → login. Otro recorrido completo funciona sin JavaScript. El tercero verifica zoom real en las seis pantallas académicas. Las cuentas y estados de evidencia son ficticios y viven sólo en testing. Las capturas antiguas de M02 se conservan en Git; no se presentan como evidencia visual M03.

## Correcciones verificadas durante el trabajo

- La primera suite encontró que la validación de confirmación de cambio también exigía confirmar al elegir por primera vez. Se corrigió la condición y el recorrido pasa por HTTP y E2E.
- Se asociaron errores de borrador a sus selects de materia y se agregaron mensajes españoles para elecciones/confirmaciones.
- Larastan detectó condiciones de autorización no booleanas y una inferencia ambigua en la validación de resúmenes; se corrigieron sin ignorar reglas.
- Se eliminó un error duplicado del editor Livewire; la prueba adicional rechaza promoción en una R, conserva el estado previo y permite luego aprobar sin especificar método.
- La inspección de capturas detectó que TODAS se describía como celda vacía al no tener correlativas numéricas explícitas. Ahora esa columna enlaza textualmente a la regla especial; se conserva la distinción con columnas realmente vacías.

## Capturas y revisión visual

Se generaron y revisaron las 24 capturas a 390×844, 1440×900, 2560×1440 y 3840×2160 de elección, situación inicial, resumen, Inicio, Mi carrera y detalle de práctica. Se inspeccionó su composición conjunta y en detalle los nombres largos/detalle/móvil. Son capturas del viewport; los formularios y listas siguen mediante scroll vertical. No hay recorte forzado ni scroll horizontal en los recorridos comprobados. También se verificaron 320×740 y reflow 640×360; la matriz M02 mantiene nueve viewports.

**Zoom real PASS:** Chromium headless aislado, pestaña 1280×720, `chrome.tabs.setZoom(2)` y `getZoom() === 2`; composición medida de 640×360 CSS y documentWidth 640 en las seis pantallas. [Medidas](zoom-200.json). Es zoom de navegador real mediante la [API oficial de Chromium/Chrome](https://developer.chrome.com/docs/extensions/reference/api/tabs#method-setZoom), distinto de cambiar sólo el viewport o aplicar CSS zoom. La extensión efímera se crea y elimina dentro del navegador de testing, sin instalar nada en la Mac del usuario. No acredita escalado del monitor/macOS, Safari ni Firefox. Se comprueba también poder guardar en ese estado.

| Pantalla | Móvil 390 | Escritorio 1440 | QHD 2560 | 4K 3840 | Zoom 200 % |
| --- | --- | --- | --- | --- | --- |
| Elegir plan | [PNG](elegir-plan-390x844.png) | [PNG](elegir-plan-1440x900.png) | [PNG](elegir-plan-2560x1440.png) | [PNG](elegir-plan-3840x2160.png) | [PNG](elegir-plan-zoom-200.png) |
| Situación inicial | [PNG](situacion-inicial-390x844.png) | [PNG](situacion-inicial-1440x900.png) | [PNG](situacion-inicial-2560x1440.png) | [PNG](situacion-inicial-3840x2160.png) | [PNG](situacion-inicial-zoom-200.png) |
| Resumen | [PNG](resumen-390x844.png) | [PNG](resumen-1440x900.png) | [PNG](resumen-2560x1440.png) | [PNG](resumen-3840x2160.png) | [PNG](resumen-zoom-200.png) |
| Inicio | [PNG](dashboard-390x844.png) | [PNG](dashboard-1440x900.png) | [PNG](dashboard-2560x1440.png) | [PNG](dashboard-3840x2160.png) | [PNG](dashboard-zoom-200.png) |
| Mi carrera | [PNG](mi-carrera-390x844.png) | [PNG](mi-carrera-1440x900.png) | [PNG](mi-carrera-2560x1440.png) | [PNG](mi-carrera-3840x2160.png) | [PNG](mi-carrera-zoom-200.png) |
| Detalle práctica | [PNG](detalle-practica-390x844.png) | [PNG](detalle-practica-1440x900.png) | [PNG](detalle-practica-2560x1440.png) | [PNG](detalle-practica-3840x2160.png) | [PNG](detalle-practica-zoom-200.png) |

Teclado móvil (Tab/Enter/Escape y retorno de foco) se conserva en E2E M02. Labels, estados en texto, mensajes de guardado anunciados y controles alcanzables sin JavaScript se probaron en los recorridos nuevos. La revisión no constituye una certificación completa WCAG ni una prueba con lector de pantalla.

## Datos locales y pendientes reales

Se aplicó únicamente la nueva migración académica e importación; ninguna operación M03 resetea o elimina datos de desarrollo. No cambian Compose, credenciales, dependencias ni lockfiles. Se comprobó que `.env`/`.env.testing` siguen idénticos. La comparación privada de cuentas/identidades detectó actividad mientras la app local estaba abierta, con timestamps entre 15:18 y 15:20; no se revirtió ni modificó esa actividad. Su origen queda pendiente de confirmación del usuario; no se afirma una igualdad de hashes de esas tablas. Los procesos automatizados apuntan a test-postgres y sus guardas siguen activas.

Pendiente de aceptación: revisión visual de Nicolás, vigencia/plan aplicable y ambigüedades académicas detalladas en [guía M03](../../m03.md). No hay datos de las otras nueve carreras ni calendario institucional inferido. Los proveedores externos requieren las pruebas manuales de [correo](../../mail.md)/[OAuth](../../oauth.md); no se publican contraseñas, correos personales, tokens ni enlaces firmados.

## GitHub Actions

La primera implementación `de80c83` pasó el [workflow de la PR](https://github.com/NicolasMRuiz/estuflow/actions/runs/38450889529), job [checks](https://github.com/NicolasMRuiz/estuflow/actions/runs/38450889529/job/115481179005), en Linux AMD64: setup, check, PHPUnit (102 pruebas/625 aserciones), E2E (7 recorridos, incluido zoom real) y artifact de capturas. Los logs/steps y el artifact se consultaron directamente en GitHub; no se infiere el resultado a partir del PASS local.

El commit posterior incorpora una corrección de presentación de errores Livewire, su prueba adicional y esta evidencia. Los resultados locales de la tabla corresponden a esa versión (103/634 y 7 E2E). El estado de CI del último commit se consulta en [Checks de PR #2](https://github.com/NicolasMRuiz/estuflow/pull/2/checks); se informará tras terminar su ejecución. No se confunden los resultados del primer commit ni los de M02 con los del último HEAD.
