# Evidencia M05 · 23 de octubre de 2026

Base: M04 `2cd2827b4396ab17681a1de5464ac3c16205e68a`, PR #3 aún en borrador al comenzar. Rama M05 `codex/m05-perfil`; entrega apilada sobre M04, sin merge ni despliegue. [Especificación](../../m05-spec.md), [perfil](../../profile.md), [instalación](../../installation.md), [demo CRUD](../../demo.md).

## Comprobaciones locales

Anfitrión macOS/Darwin ARM64; Docker Desktop con motor Linux ARM64. PHP 8.4/PostgreSQL 18 dentro de imágenes fijadas. PHPUnit y E2E secuenciales, exclusivamente `estuflow_test` en `test-postgres`; worker/test-mailpit aislados. Ninguna solicitud OAuth real ni envío Brevo se usa como prueba automática.

| Comando/método | Resultado |
| --- | --- |
| `./scripts/setup.sh` sobre instalación existente | PASS: lockfiles, build, migraciones pendientes/importación idempotente y PostgreSQL/Redis. |
| `./scripts/start.sh`, repetir, `stop.sh` y `start.sh` | PASS: login con CSRF; app/worker/scheduler saludables. |
| Preservación al repetir start/stop/start | PASS: hashes privados de tablas users/enrollments/course_progress/events/notes/oauth idénticos; archivos `.env` y `.env.testing` idénticos byte por byte. No se publican hashes/contenido de datos privados. |
| `bash -n scripts/*.sh` | PASS. |
| `docker compose config --quiet` | PASS, sin imprimir configuración/secrets. |
| `./scripts/check.sh` | PASS: Composer strict/Pint/Larastan/build. |
| `./scripts/test.sh` | PASS: 174 pruebas / 1266 aserciones tras el ajuste final de renovación de sesión. |
| `./scripts/e2e.sh` | PASS: 15 recorridos, en cinco especificaciones secuenciales (5 académicos + 3 auth + 3 M04 + 3 perfil + 1 responsive). |
| `npm audit --json` | PASS: 0 vulnerabilidades reportadas después de Vite 8.3.5. |
| Instalación limpia, proyecto/puertos/volúmenes aislados | En verificación. |
| GitHub Actions Linux AMD64 | Pendiente del push de esta rama. |

Las primeras ejecuciones identificaron expectativas de la antigua página de accesos y claves hasheadas de throttle al avanzar el reloj de fixture; se corrigieron los tests conservando el middleware real. La regresión mantiene las verificaciones M02–M04. No se modificaron `.env`/`.env.testing`, credenciales, `APP_KEY` ni el volumen de desarrollo; no se ejecutó migrate:fresh/reset sobre desarrollo ni borrado de volúmenes.

## Seguridad y comportamiento probado

- Nombre propio, acentos/espacios, límite/no escalares, campos ajenos/sensibles rechazados, persistencia en saludo/inicial y POST sin JavaScript.
- Email inválido/repetido/mismo, reautenticación ligada a cuenta y límites; activo conservado mientras pendiente. Confirmaciones en ambos órdenes, GET sin mutación, token incorrecto/alterado/vencido/reusado o de otra cuenta/propósito denegado. Reenvío/reemplazo/cancelación invalidan enlaces y trabajos anteriores.
- Dos procesos PHP con conexiones PostgreSQL reales compiten por el mismo email: exactamente un cambio completo y un conflicto controlado, sin 500 ni email parcial. Restricción única revalidada al confirmar.
- Fallo/supresión SMTP mantiene email activo y estado de envío fallido. Payload de trabajo cifrado, sin token legible en tabla de cola. Un envío encolado o aceptado no se presenta como cambio completado.
- Contraseña actual/reglas/confirmación, nueva credencial válida y anterior rechazada; sólo otras sesiones de esa cuenta se eliminan. OAuth-only requiere reautenticación de identidad vinculada y conserva el vínculo; otra identidad no confirma. OAuth conocido no sobrescribe nombre/email.
- Cambio de email conserva usuario/progreso/eventos/notas/identidades/lectura de avisos/programación; recordatorio posterior usa el nuevo email activo verificado. Se eliminan solicitudes antiguas de recuperación de esa cuenta y rota remember token. La suite M02 conserva broker/verificación y sus límites.
- E2E con SMTP capturado por test-mailpit confirma ambas direcciones, comprueba que otra sesión real pide login tras email y contraseña, mantiene la actual y persiste tras logout/login. Otro recorrido sin JS cancela/confirma cambio académico desde perfil y vuelve al destino correcto.

## Capturas e inspección

`profile.spec.js`: perfil, email pendiente, contraseña y carrera confirmada en **320/390/1440/2560/3840 px**, error HTTP sin JavaScript a 390, y zoom real **200 %** con `chrome.tabs.setZoom/getZoom`, viewport 1280×720 → 640×360 CSS. Capturas superior/final vía CDP, sin recorte de zoom de Playwright ni escalado del SO. [Medición](zoom.json).

Inspección visual local de perfil 390/1440/3840, email pendiente 390 y final del perfil con zoom: controles legibles y accesibles, email largo envuelto, grupos apilados y carrera separada, sin scroll horizontal ni formularios cortados. La composición mantiene el shell existente. Se conservan las evidencias históricas M02–M04; sus imágenes regeneradas durante regresión no se reinterpretan como aceptación nueva. Los refinamientos visuales generales M04 siguen pendientes.

- [Perfil 390](perfil-390.png), [perfil 1440](perfil-1440.png), [perfil 2560](perfil-2560.png), [perfil 3840](perfil-3840.png).
- [Email pendiente 390](email-pendiente-390.png), [contraseña 1440](contrasena-1440.png), [carrera confirmada 390](carrera-confirmada-390.png).
- [Error HTTP](error-nombre-http-390.png), [zoom superior](perfil-zoom200-superior.png), [zoom final](perfil-zoom200-final.png).

Son cuentas/direcciones ficticias `.test`, passwords vacías y capturas de contenido sin barra URL; no se publica bandeja de correo, tokens ni enlaces de seguridad.

## Dependencia corregida

El audit anterior informó Vite 8.3.2 (directa dev) con seis advisories, incluido [GHSA-587h-7wmj-h5pm / CVE-2026-112942](https://github.com/advisories/GHSA-587h-7wmj-h5pm): bypass de la comprobación de archivo de launch-editor en Windows, que puede combinarse con expansión `%` para ejecutar comandos cuando un atacante controla la entrada al editor. Afecta al servidor/herramientas de desarrollo, no implica que el build estático por sí mismo ejecute ese endpoint. El advisory incluye Vite 8.3.0–8.3.2 y corrección desde 8.3.3. Se aplicó **8.3.5** mediante instalación exacta compatible, con lockfile actualizado; no se usó audit fix --force ni actualización masiva. Audit posterior: info/low/moderate/high/critical/total = 0. Build y regresión se ejecutan con esa versión.

## Matriz de plataformas e integraciones

| Plataforma/alcance | Método | Estado |
| --- | --- | --- |
| macOS ARM64 + Docker Linux ARM64, existente | setup/start/repetición/stop-start, checks/PostgreSQL/E2E, capturas | PASS: regresión final 174/1266 y 15 E2E, más checks/operación y capturas. |
| macOS ARM64 + Docker Linux ARM64, limpio | Clone temporal sólo de versionados, proyecto/puertos/volúmenes separados | En verificación. |
| Linux AMD64, GitHub Actions | Checkout limpio, setup/start/stop-start/check/test/e2e | Pendiente de ejecución de esta rama. |
| Windows/WSL2 (PC de Nico) | Guía oficial y revisión de scripts, sin PC disponible | **NO EJECUTADO**. Checklist en installation.md; Linux CI no lo acredita. |
| macOS Intel / Linux ARM64 nativo / otras arquitecturas | Sin anfitrión disponible | **NO EJECUTADO**; contenedores ARM64 sobre Mac no equivalen a Linux nativo. |
| Brevo real y Google login/reauth real | Se preservó la configuración privada, sin enviar/consultar para tests | **NO EJECUTADO**, pruebas manuales separadas. |
| Microsoft real | Permanece deshabilitado/configuración pendiente del usuario | **NO EJECUTADO**. |
| DataGrip GUI en cada plataforma | Guía y publicación loopback | **NO EJECUTADO** en M05; evidencia histórica M02 no equivale a operar la GUI ahora. |
