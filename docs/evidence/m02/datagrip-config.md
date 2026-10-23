# Ampliación M02 — DataGrip, Google y Brevo

Verificación local del 13 de octubre de 2026 sobre la rama `codex/m02-auth-responsive`, PR #1. Alcance limitado a publicación loopback de PostgreSQL, aplicación/verificación de configuración y documentación. Sin funcionalidades ni actualizaciones de dependencias.

## Cambio y conservación

- `postgres` publica `127.0.0.1:${DB_FORWARD_PORT:-5433}:5432`.
- Puerto efectivo **5433**, disponible antes de aplicarlo; no hizo falta elegir otro. Añadido `DB_FORWARD_PORT=5433` a `.env.example` y al `.env` local, conservando byte por byte todos sus valores previos. `.env` y `.env.testing` siguen ignorados.
- Laravel mantiene `DB_HOST=postgres`, `DB_PORT=5432`, base/usuario `estuflow` y sus credenciales actuales.
- Se recreó sólo el contenedor PostgreSQL y se mantuvo `estuflow_postgres_data`; identidad del clúster, OID de users y cantidad de usuarios coincidieron antes/después y tras los tests. No se ejecutaron resets de desarrollo ni se borraron volúmenes.
- Test-postgres y Redis siguen sin host ports; comprobado tanto en Compose como en los contenedores activos.
- Se limpió config cache, reiniciaron app/worker y ejecutó `./scripts/start.sh` con readiness aprobado; no había migraciones pendientes. Cola de desarrollo vacía antes y después, sin solicitudes de correo real generadas por estas comprobaciones.

## Checks locales reales

| Comprobación | Resultado |
| --- | --- |
| `docker compose config --quiet` y `--profile test config --quiet` | **PASS**, sin volcar secretos. |
| `docker compose port postgres 5432` | **PASS**, `127.0.0.1:5433`. |
| Socket TCP desde la Mac a `127.0.0.1:5433` | **PASS**, conectividad; no valida credenciales PostgreSQL. |
| Consulta PDO autenticada desde Laravel | **PASS**, base/rol `estuflow` por `postgres:5432`. |
| `./scripts/start.sh` | **PASS**, PostgreSQL/sesiones, Redis temporal, app con CSRF y worker activos. |
| `./scripts/check.sh` | **PASS**, Composer strict, Pint, Larastan nivel 6 y Vite. |
| `./scripts/test.sh` | **PASS**, 75 pruebas / 455 aserciones, PostgreSQL aislado. |
| `./scripts/e2e.sh` | **PASS**, 4 recorridos Chromium, 15,5 segundos; SMTP real capturado en test-mailpit. |
| Google en login/registro | **PASS**, botón presente; Microsoft ausente. |
| Inicio local Google | **PASS**, HTTP 302 hacia `https://accounts.google.com/o/oauth2/auth`, callback exacto `http://localhost:8000/auth/google/callback`, Authorization Code, scopes sólo `openid profile email`, state y PKCE S256. No se siguió el redirect ni se consultó al proveedor. |
| Configuración efectiva Brevo | **PASS**, SMTP `smtp-relay.brevo.com:587`, esquema smtp, STARTTLS automático y obligatorio, validación de certificados, timeout 10 s, login/clave presentes, remitente configurado, MAIL_URL ausente y cola database. No se imprimieron sus valores privados. |
| Test Connection en DataGrip / autenticación desde cliente Mac | **NO EJECUTADO**, pendiente en la interfaz; no hay psql local instalado. |
| Login Google real | **NO EJECUTADO**, prueba manual pendiente. |
| Autenticación SMTP y entrega Brevo reales | **NO EJECUTADO**, configuración comprobada sin conexión/envío al proveedor. Verificación del remitente/dominio, cuota y entrega pendientes en Brevo. |
| Microsoft | **NO EJECUTADO**, comentado/deshabilitado a pedido del usuario. |

PHPUnit y E2E se ejecutaron secuencialmente en `testing`, PostgreSQL `estuflow_test` del servicio `test-postgres`. Las notificaciones y OAuth de aplicación usan dobles; E2E fuerza test-mailpit y proveedores deshabilitados, pese al .env local configurado. No se enviaron correos reales ni se consultaron proveedores OAuth desde las pruebas. Las capturas anteriores se conservaron; no hubo cambios de UI.

## Conexión y prueba manual

DataGrip: **host 127.0.0.1, puerto 5433, base estuflow, usuario estuflow, contraseña DB_PASSWORD de .env local**. [Guía DataGrip](../../datagrip.md) y [pruebas manuales Brevo/Google](../../mail.md#prueba-manual-desde-la-ui-con-brevo-configurado). Estas pruebas reales deben usar un buzón/cuenta propios autorizados; configurar variables no acredita autenticación o entrega.

## Publicación y CI

Cambios versionables en la misma [PR #1 en borrador](https://github.com/NicolasMRuiz/estuflow/pull/1), hacia main; sin merge ni despliegue. El estado de Actions para el commit de esta ampliación se registra en esa PR y en la entrega final. El PASS remoto anterior de M02 no sustituye los checks de un commit nuevo.
