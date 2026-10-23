# M04 · Evidencia del 23/10/2026

Base: M03 corregida/aprobada, `39c22b2`, PR #2 aún abierta en borrador. Rama de entrega `codex/m04-calendario`, [PR #3 en borrador](https://github.com/NicolasMRuiz/estuflow/pull/3) dependiente de M03, sin merge ni despliegue. [Modelo y operación](../../m04.md) · [Especificación](../../m04-spec.md).

## Checks locales

Mac ARM64, Docker Desktop; PHP 8.4 / Laravel 13, PostgreSQL 18 y Chromium aislado. Sin modificar dependencias/lockfiles ni `.env`/`.env.testing` (hashes comparados antes/después; siguen ignorados). Migraciones aditivas de desarrollo, catálogo idempotente; sin reset ni borrado de volúmenes.

| Comprobación | Resultado real |
| --- | --- |
| `setup.sh` | PASS: dependencias de lockfiles, migraciones pendientes, catálogo y readiness. |
| `start.sh` / Compose config quiet | PASS: app, worker, scheduler, PostgreSQL y Redis operativos; scheduler sin puertos. |
| `check.sh` | PASS: Composer strict, Pint, Larastan nivel 6 y Vite build. |
| `test.sh` | PASS: **143 pruebas / 921 aserciones**, PostgreSQL estuflow_test/test-postgres. |
| `e2e.sh` | PASS: **12 recorridos** (5 académicos + 3 autenticación + 3 M04 + 1 matriz responsive), posteriores a PHPUnit. |
| GitHub Actions | PASS implementación `da92fb5`: push y pull_request; logs confirman 143/921 y 12 E2E. |

PHPUnit incluye dos procesos reales concurrentes de `m04:tick --local-only`: una única notificación persistida. También comprueba versión, trabajo antiguo, eliminación, corte exacto de utilidad, recuperación, presupuesto, fallo/resultado SMTP incierto, propiedad/fechas/contexto, fuentes y notas. No consulta proveedores reales.

La primera suite E2E completa falló en el registro inicial del último archivo: más de diez cuentas ficticias desde una misma IP en menos de un minuto alcanzaron el límite real M02. Se corrigió el aislamiento por prefijo Redis de cada especificación, sin cambiar límites de aplicación, borrar verificaciones ni hacer flush. Los prefijos de desarrollo no se tocan. La suite completa mantiene las cuatro especificaciones y sus 12 recorridos, secuenciales.

`npm ci` informó una vulnerabilidad de severidad alta en dependencias fijadas ya existentes. No se ejecutó audit fix ni se actualizaron dependencias fuera del alcance. Esto se informa por separado del resultado de los scripts, que no incluyen una auditoría npm como gate.

## SMTP, fixtures y diagnóstico real

- **SMTP local comprobado:** el navegador crea y reprograma un examen; el controlador CLI exclusivo de testing adelanta un aviso persistido. El worker database real lo envía a test-mailpit, Playwright recupera el enlace y verifica el estado sent. No se publicó mensaje/bandeja ni URL de seguridad. Los offsets se verifican con reloj controlado en PHPUnit; este recorrido no acredita espera natural del reloj ni entrega a una casilla externa.
- **HTTP simulado en suites:** ArgentinaDatos/cartelera mediante fixtures mínimos ficticios. Baseline sin avisos históricos, nuevo aviso, títulos repetidos, edición/reordenamiento, validación completa, colisión, respuesta antigua, timeout, tamaño, backoff y deduplicación/fan-out recuperable. Cero llamadas externas al navegar.
- **Diagnóstico externo de sólo lectura:** `docker compose exec -T app php artisan m04:diagnose` respondió correctamente para ambos orígenes: ArgentinaDatos 2026, **2204 bytes / 20 entradas**; raíz institucional, **131976 bytes / 38 tarjetas**. Validación TLS activa. El comando no persistió ni notificó. El HTML se inspeccionó antes del adaptador; no anunciaba feed/permalinks de publicación. No se versionó la página real.
- **Scheduler local automático observado:** inició la carga compartida sin ejecutar tick manualmente: 20 feriados de 2026 y 16 de 2027 ya publicados, 38 publicaciones como baseline, próxima cartelera 24/10 a las 08:00 Buenos Aires y feriados a 15 días del éxito. Son datos compartidos de desarrollo; no pruebas de destinatarios ni emails reales. La copia anterior se conserva ante fallos posteriores.
- **Brevo real: NO EJECUTADO.** No se enviaron emails de prueba a usuarios reales; el transporte/configuración M02 se conserva. Microsoft/Google no forman parte de M04 ni se acreditan como probados por estas suites.

## Capturas y revisión visual

Se abrieron capturas individuales y composiciones comparativas de las seis pantallas a 320/390/1440/2560/3840: calendario, formulario, bandeja, notas, Inicio y aviso institucional. Se revisaron jerarquía, espaciado, orden de tarjetas, nombres, acciones y foco, además de las aserciones de reflow. Se corrigieron controles de mes pequeños y artefactos de captura por scroll/zoom antes de generar la evidencia final.

Capturas normales desde scroll cero; las de zoom real 200 % usan chrome.tabs.setZoom/getZoom en Chromium efímero y captura CDP del viewport superior/final, evitando el recorte de fullPage con zoom. [Medición](zoom.json). No acredita escalado físico macOS, otros navegadores ni lector de pantalla. La aceptación visual M04 corresponde a Nico.

| Pantalla | Móvil 390 | Escritorio 1440 | QHD / 4K |
| --- | --- | --- | --- |
| Calendario | [Agenda](calendario-390.png) | [Mes](calendario-1440.png) | [2560](calendario-2560.png) / [3840](calendario-3840.png) |
| Formulario | [390](evento-formulario-390.png) | [1440](evento-formulario-1440.png) | [2560](evento-formulario-2560.png) / [3840](evento-formulario-3840.png) |
| Notificaciones | [390](notificaciones-390.png) | [1440](notificaciones-1440.png) | [2560](notificaciones-2560.png) / [3840](notificaciones-3840.png) |
| Mis notas | [390](notas-390.png) | [1440](notas-1440.png) | [2560](notas-2560.png) / [3840](notas-3840.png) |
| Inicio | [390](dashboard-390.png) | [1440](dashboard-1440.png) | [2560](dashboard-2560.png) / [3840](dashboard-3840.png) |
| Cartelera ficticia | [390](cartelera-390.png) | [1440](cartelera-1440.png) | [2560](cartelera-2560.png) / [3840](cartelera-3840.png) |

46 capturas M04: 30 de la matriz, diez de zoom (superior/final) y seis del CRUD. Las capturas M02/M03 que regeneran sus pruebas se conservan en su versión aprobada; las comparaciones nuevas quedan sólo aquí.

## Demostración de CRUD en el producto

**Evento:** elegir fecha desde Calendario, crear «Examen de Redes» con 24h/1h y email explícito → [consultar guardado](crud-evento-creado.png), visible en Próximas actividades → cambiar fecha manteniendo vista/filtro → [consultar nueva fecha](crud-evento-editado.png), programación versión 2 y dos pendientes anteriores cancelados. Cancelar una edición conserva el título. El correo capturado y la bandeja abren el evento; eliminar exige confirmación y deja cero pendientes. [El aviso anterior explica el destino eliminado](crud-evento-eliminado.png).

**Nota:** Nueva nota → escribir dos líneas → [consultar](crud-nota-creada.png) → editar y guardar → cerrar/abrir sesión → [la nota editada persiste en Inicio](crud-nota-editada-persistida.png). Eliminar/cancelar conserva; confirmar elimina → [listado vacío](crud-nota-eliminada.png). Las pruebas comprueban además que cambiar carrera conserva eventos/notas y rechazan IDs de otra cuenta.

Las imágenes usan exclusivamente cuentas/contenido ficticios. No hay menús técnicos de CRUD ni acciones simuladas.


## GitHub Actions comprobado

Implementación `da92fb5b3073af56e9ae1c1e571732aaaeff8a00`: [push PASS](https://github.com/NicolasMRuiz/estuflow/actions/runs/40002894860) y [PR PASS](https://github.com/NicolasMRuiz/estuflow/actions/runs/40002920905), Linux AMD64. Se consultaron jobs/logs: setup, Composer/Pint/Larastan/build, **143 pruebas / 921 aserciones**, E2E **5 + 3 + 3 + 1 = 12**, y carga de capturas aprobados. [Artifact de esa corrida](https://github.com/NicolasMRuiz/estuflow/actions/runs/40002920905/artifacts/12523256875), retención 14 días; las 46 capturas versionadas M04 no dependen de esa caducidad.

Este registro de resultados se agrega en un commit exclusivamente documental posterior. El resultado de Actions del HEAD final se mantiene en [la PR #3](https://github.com/NicolasMRuiz/estuflow/pull/3/checks), con su SHA exacto, separado de estas corridas de implementación. Los archivos locales de entorno se verificaron sin cambios y los procesos de testing quedaron detenidos. Worker/scheduler de desarrollo quedaron activos y saludables. Ninguna entrega real Brevo se acredita por estas pruebas.
