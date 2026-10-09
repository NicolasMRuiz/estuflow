# EstuFlow

Organizador académico en español rioplatense. M02 implementa registro/login/logout, recuperación y verificación por email, acceso/vinculación Google y Microsoft, validación accesible y responsive hasta 4K. El dashboard conserva estados académicos vacíos; las secciones futuras no son enlaces operativos.

## Iniciar en Mac

Requisito: Docker Desktop con Compose v2 activo. No requiere PHP/Composer/Node globales.

```bash
cd /Users/nicolasruiz/estuflow
./scripts/setup.sh
./scripts/start.sh
```

Abrir [EstuFlow](http://localhost:8000) y [correo local Mailpit](http://localhost:8025). `setup.sh` crea `.env`/`.env.testing` sólo si no existen, con secretos aleatorios, instala lockfiles y aplica sólo migraciones pendientes. Preserva archivos/datos. `start.sh` verifica readiness e inicia también worker/capturador. Bases y Redis quedan internos; web y bandeja sólo loopback. Nunca borrar volúmenes para solucionar un problema.

Si el puerto cambia, configurar `APP_PORT` y `APP_URL` en `.env`; el origen confiable también determina URLs de seguridad/callbacks. Para detener: `docker compose stop app worker mailpit` (preserva datos).

## Revisar los cinco bloques de M02

1. **Responsive:** revisar login, registro y panel en 320, 390, 430, 768, 1280, 1440, 1920, 2560 y 3840 px CSS; menú móvil con teclado/Escape, 200% de zoom y altura 720 px. Capturas y límites en [evidencia](docs/evidence/m02/checks.md).
2. **Recuperación:** desde `/login`, «¿Olvidaste la contraseña?». Pedir enlace, abrir el correo en Mailpit, elegir contraseña confirmada y volver a ingresar. Se cierran sesiones anteriores sólo de esa cuenta; el enlace no verifica email.
3. **Verificación:** crear cuenta en `/registro`; queda en `/verificar-email`. Abrir el enlace de Mailpit con esa cuenta para acceder al dashboard. Reenvío tiene espera visible de 60 segundos. Cuentas previas sin verificar también pasan por este aviso.
4. **Google/Microsoft:** implementación completa con pruebas dobles; botones ocultos hasta configurar credenciales. [Activación OAuth](docs/oauth.md) detalla aplicaciones Web, audiencias, permisos y callbacks. Después de verificar, abrir `/metodos-de-acceso` para vincular tras confirmar identidad. No hay vinculación automática por email coincidente ni contraseña inventada.
5. **Errores:** probar vacío/email inválido/confirmación diferente; mensajes inline y foco. Credenciales incorrectas tienen un solo aviso general. Funciona con POST normal sin JavaScript.

Correo externo: [activar Brevo, STARTTLS y diagnóstico](docs/mail.md). **Brevo, Google y Microsoft reales: NO EJECUTADOS por falta de configuración externa.** Las pruebas locales usan fakes y SMTP capturado aislado, nunca proveedores reales.

## Checks

Ejecutar PHPUnit y E2E **secuencialmente**, comparten exclusivamente PostgreSQL `estuflow_test` del servicio `test-postgres`:

```bash
./scripts/check.sh   # Composer, Pint, Larastan nivel 6, build
./scripts/test.sh    # PHPUnit PostgreSQL aislado
./scripts/e2e.sh     # Chromium + worker + test-mailpit aislados
```

Para formato: `docker compose run --rm --no-deps app composer format`. Para readiness: `docker compose run --rm app php artisan environment:check`. No usar SQLite ni resetear desarrollo. Los scripts detienen los procesos de testing antes de resets; E2E genera capturas con cuentas ficticias y campos password vacíos.

Documentación: [arquitectura](docs/architecture.md), [interfaz](docs/ui.md), [testing](docs/testing.md), [progreso](docs/progress.md), [correo](docs/mail.md), [OAuth](docs/oauth.md), [resultados y capturas M02](docs/evidence/m02/checks.md), [reglas de trabajo](AGENTS.md). Los lockfiles/digests fijan versiones. M02 no despliega hosting ni implementa modelo académico.
