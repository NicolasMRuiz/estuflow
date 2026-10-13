# Evidencia M02 — cierre local del 13 de octubre de 2026

Implementación iniciada el 9 de octubre y verificación final retomada el 13. Entorno local macOS Apple Silicon, Docker Linux ARM64 (`aarch64`); CI usa Linux AMD64. Las pruebas locales, los dobles OAuth y los proveedores reales se informan por separado. Las capturas no implican aceptación visual final de Nicolás.

## Entorno comprobado

PHP 8.4.26, Laravel 13.34.0, PostgreSQL 18.6, Redis 7.4.11, Node 22 y Vite 8.3.2. Versiones exactas de Livewire, Socialite, Microsoft, Tailwind y Playwright en los lockfiles; imágenes fijadas por digest multiplataforma.

## Resultados locales

| Comando o recorrido | Resultado real |
| --- | --- |
| `./scripts/setup.sh` | **PASS**: dependencias desde lockfiles, migraciones pendientes y readiness; `.env` existentes preservados. |
| `./scripts/start.sh` | **PASS**: PostgreSQL/sesiones, clave Redis temporal, web con CSRF, worker y Mailpit activos. |
| `./scripts/check.sh` | **PASS**: Composer strict, Pint sobre 58 archivos, Larastan nivel 6 sin errores y build Vite. |
| `docker compose run --rm --no-deps app composer audit --locked` | **PASS**: sin advisories conocidos al ejecutar. |
| `./scripts/test.sh` | **PASS**: 75 pruebas, 455 aserciones; incluye 36 pruebas OAuth con dobles. |
| `./scripts/e2e.sh` | **PASS**: 4 recorridos Chromium; SMTP real capturado, worker aislado, sesiones, CSRF, POST sin JavaScript, errores/foco/teclado y matriz responsive. |
| Brevo SMTP real | **NO EJECUTADO**: sin configuración externa ni destinatario autorizado para envío real. |
| Google real | **NO EJECUTADO**: pendiente aplicación, credenciales y cuenta de prueba autorizada. |
| Microsoft real | **NO EJECUTADO**: pendiente Entra, credenciales y cuentas personales/institucionales autorizadas. |
| Zoom real del navegador al 200 % | **NO EJECUTADO**: control UI de Chrome no disponible/sin permisos; reflow equivalente de 640×360 sí comprobado por E2E. No se equiparan ambos ensayos. |

PHPUnit y E2E se ejecutaron secuencialmente y únicamente sobre PostgreSQL `testing` / `test-postgres` / `estuflow_test`. PHPUnit usa caché array por proceso; E2E utiliza un prefijo Redis nuevo por corrida, sin flush. No se resetearon datos de desarrollo.

Los cuatro recorridos E2E comprueban registro → aviso pendiente → verificación desde correo SMTP capturado → dashboard → logout/login; recuperación para cuenta existente/inexistente con igual respuesta → enlace capturado → contraseña nueva → rechazo de la anterior e invalidación de otra sesión; errores asociados, corrección y foco a 320 px; POST sin JavaScript; y nueve viewports sobre pantallas representativas. El cambio genera además un email informativo sin contraseñas.

### Fallos intermedios corregidos

- `artisan serve` descartaba variables de Compose al crear el servidor PHP: se usa `--no-reload` en desarrollo/testing para conservar cola, SMTP y guardas de aislamiento.
- `MAIL_URL` vacío anulaba el transporte en Laravel: ahora se trata como ausente. Se agregó una regresión que construye SMTP sin enviar a la red.
- El error general contenía texto oculto de un resumen vacío: sólo se renderiza su contenido cuando hay varios campos inválidos.
- Se corrigieron expectativas de tests, aislamiento de contadores, un doble de sesión Socialite y el formato de la nueva regresión. Los resultados de la tabla son los obtenidos después de las correcciones.

## GitHub Actions

Fallo histórico confirmado en [run 37083754713](https://github.com/NicolasMRuiz/estuflow/actions/runs/37083754713): setup, check y PHPUnit PASS; E2E FAIL antes de los recorridos por `chrome_crashpad_handler: --database is required` y salida Chromium SIGTRAP. M02 configura directorios XDG escribibles para el UID del contenedor. Chromium ya inicia y completa E2E localmente; eso no acredita por sí solo un PASS AMD64 remoto.

Las primeras corridas M02 no llegaron a probar la aplicación: [37993590470](https://github.com/NicolasMRuiz/estuflow/actions/runs/37993590470) falló en setup por cuota anónima de Docker Hub; [37993723180](https://github.com/NicolasMRuiz/estuflow/actions/runs/37993723180) aplicó correctamente el mirror, pero falló al descargar PHP por timeout de `auth.docker.io`, exit 125. Check, PHPUnit y E2E quedaron **SKIPPED**, no PASS. El artifact de esa corrida sólo contenía la captura baseline ya existente en Git.

El workflow conserva todos los checks, TLS y digests, configura el caché público soportado por Google en el runner efímero y ejecuta setup → check → PHPUnit → E2E. Publica únicamente PNGs durante 14 días, incluso si falla un paso. El estado remoto del último commit se registra en la [PR en borrador #1](https://github.com/NicolasMRuiz/estuflow/pull/1) y en la entrega final, con enlace a su corrida real. Un PASS local no sustituye ese resultado.

## Capturas inspeccionadas

Las imágenes de esta carpeta usan cuentas ficticias, contraseñas vacías, y no incluyen barra URL, firmas, tokens, cookies, bandejas ni trazas. El helper rechaza passwords poblados y las aserciones de navegación no imprimen URLs de seguridad.

| Viewport CSS | Login | Dashboard |
| --- | --- | --- |
| 390×844 | [Captura](login-390x844.png) | [Captura](dashboard-390x844.png) · [Página completa](dashboard-390-full.png) |
| 1440×900 | [Captura](login-1440x900.png) | [Captura](dashboard-1440x900.png) |
| 2560×1440 | [Captura](login-2560x1440.png) | [Captura](dashboard-2560x1440.png) |
| 3840×2160 | [Captura](login-3840x2160.png) | [Captura](dashboard-3840x2160.png) |

Estados adicionales: registro [móvil](registro-390x844.png) / [escritorio](registro-1440x900.png); verificación [móvil](verificar-email-390x844.png) / [escritorio](verificar-email-1440x900.png); enlace inválido [móvil](verificacion-invalida-390x844.png) / [escritorio](verificacion-invalida-1440x900.png); métodos de acceso [móvil](metodos-de-acceso-390x844.png) / [escritorio](metodos-de-acceso-1440x900.png); [recuperación](recuperacion-320x740.png), [nueva contraseña](restablecer-1440x900.png) y [error de credenciales/foco](login-error-320x740.png).

La inspección visual revisó composición, legibilidad, superposiciones, orden de tarjetas y estados de error. Formularios con medida máxima, panel centrado y desplazamiento vertical normal en móvil; sin scroll horizontal en la matriz de 320×740, 390×844, 430×932, 768×1024, 1280×720, 1440×900, 1920×1080, 2560×1440 y 3840×2160. Se corrigieron contraste de placeholders/bordes/pie y puntuación del aviso de verificación. Los pares de contraste medidos y sus límites están en [UI](../../ui.md).

### Comparación QHD y alcance

[Login anterior real](login-before-2560x1440.png): baseline del 9 de octubre, antes del ajuste. [Dashboard con reglas anteriores](dashboard-qhd-reglas-previas.png): reproducción parcial de ancho/posición sobre markup M02, no captura de toda la versión anterior ni del monitor físico reportado. A 2560 px esas reglas dejaron 0 px junto a sidebar y 610 px a la derecha; las actuales dejan 240 px a ambos lados. [Mediciones](layout-qhd.json).

## Pendientes y activación externa

[Correo/Brevo](../../mail.md) detalla servicio transaccional, remitente/dominio verificado, login y clave SMTP local, 587/STARTTLS obligatorio y diagnóstico/cuota. [Google/Microsoft](../../oauth.md) detalla aplicaciones Web, audiencias, scopes mínimos, secretos locales, callbacks exactos y activación por bandera. No se solicitan secretos por chat.

Pendientes: recorridos reales Brevo/Google/Microsoft, consentimiento institucional, zoom manual al 200 %, Safari/Firefox y auditoría completa con lector de pantalla. M02 no implementa matrícula/modelo académico ni despliega hosting.
