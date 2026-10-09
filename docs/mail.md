# Correo local y Brevo

M02 usa SMTP de Laravel/Symfony y una cola `database` en PostgreSQL. La solicitud de recuperación siempre encola un trabajo cifrado, exista o no la cuenta; la búsqueda y el broker se ejecutan en el worker. Las tres notificaciones de seguridad también son trabajos cifrados. No se incluyen contraseñas en correos ni se usa un fallback a logs. Los enlaces se generan desde `APP_URL`, nunca desde el Host recibido.

## Recorrido local en la Mac

```bash
./scripts/setup.sh
./scripts/start.sh
```

Abrir [EstuFlow](http://localhost:8000) y [Mailpit](http://localhost:8025). Mailpit captura realmente SMTP y sólo publica su interfaz en `127.0.0.1`; su puerto SMTP permanece en la red Docker. No reenvía a destinatarios reales. Sus mensajes son efímeros y contienen enlaces privados: no publicar capturas de la bandeja ni copiar enlaces en tickets.

`start.sh` aplica migraciones pendientes, comprueba PostgreSQL/Redis, inicia web, Mailpit y worker. La cola de seguridad se fuerza a `database` en Compose, incluso para `.env` de M01 que decía `sync`. El antiguo `MAIL_MAILER=array` se interpreta como SMTP **sólo en local** para activar Mailpit sin sobrescribir ese archivo. PHPUnit sigue usando `array`/fakes; E2E fuerza SMTP a `test-mailpit`, con worker y PostgreSQL de testing. No comparten cola, bandeja ni datos con desarrollo.

Variables locales nuevas (sin secretos):

```dotenv
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_TIMEOUT=10
MAIL_REQUIRE_TLS=false
MAIL_FROM_ADDRESS=hola@estuflow.test
MAIL_FROM_NAME=EstuFlow
MAILPIT_PORT=8025
```

Mailpit interno no ofrece TLS; `MAIL_REQUIRE_TLS=false` es exclusivo del capturador local. La verificación de certificados TLS conserva los valores seguros de Symfony. No se agrega `verify_peer=false`.

## Activar Brevo real

1. En Brevo, activar el servicio transaccional de la cuenta; si está pendiente de aprobación, completar ese proceso. Verificar el remitente o autenticar el dominio siguiendo los registros DNS que muestra Brevo (incluido DKIM y la política DMARC apropiada). Esperar la verificación DNS.
2. En SMTP & API → SMTP, obtener el **login SMTP** y generar una **clave SMTP**. La clave SMTP es distinta de la API key y de la contraseña de Brevo. Guardar ambos localmente o en el gestor de secretos del ambiente; nunca enviarlos por chat o Git.
3. Editar el `.env` local con este ejemplo, completando sus valores vacíos:

```dotenv
APP_URL=http://localhost:8000
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_TIMEOUT=10
MAIL_REQUIRE_TLS=true
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=EstuFlow
```

`MAIL_FROM_ADDRESS` debe ser un remitente verificado; no es el usuario técnico SMTP. `MAIL_SCHEME=smtp` en 587 negocia STARTTLS; `MAIL_REQUIRE_TLS=true` exige que la conexión quede cifrada. Esta versión lee `MAIL_SCHEME`; `MAIL_ENCRYPTION` no se usa. Dejar `MAIL_URL` sin configurar para evitar que sobrescriba host/esquema/credenciales.

4. Aplicar configuración y reiniciar los procesos largos:

```bash
docker compose run --rm app php artisan config:clear
docker compose restart app worker
./scripts/start.sh
```

5. Con un destinatario propio autorizado, solicitar verificación o recuperación y confirmar entrega, botón/enlace, caducidad y acceso. Revisar el registro transaccional de Brevo: aceptado por SMTP no equivale a recibido en bandeja. La prueba real no se ejecutó en esta entrega por falta de configuración externa.

El plan Gratis consultado el 9/10/2026 permite **300 emails diarios**, compartidos con otros envíos de la cuenta; no se garantiza entrega inmediata al alcanzar la cuota. Consultar el panel antes de enviar pruebas.

## Worker y diagnóstico

Cada trabajo tiene hasta 3 intentos, backoff de 15 segundos también en E2E y timeout de proceso de 30 segundos; SMTP usa timeout de 10 segundos. Se conservan trabajos fallidos para diagnóstico, con payload cifrado. No copiar payloads, excepciones SMTP ni mensajes completos a evidencia pública. No cambiar a `log`/`failover` para sortear fallos.

```bash
docker compose ps app worker mailpit
# Sólo nombres/estado de trabajos; no muestra contraseñas ni cuerpo del correo.
docker compose exec worker php artisan queue:failed
# Tras resolver configuración/red/proveedor, reintentar un UUID concreto:
docker compose exec worker php artisan queue:retry UUID
```

Si no hay correo: comprobar que worker esté activo; revisar cola fallida y conexión saliente 587; confirmar login/clave SMTP, remitente/dominio, activación transaccional, cuota, rebotes/supresiones y spam en Brevo. Los links vencen 60 minutos después de su generación: una cola demorada puede requerir un enlace nuevo. Un cambio de clave de la app exige una estrategia de rotación para sesiones y trabajos cifrados, fuera de este entorno local.

Para un hosting futuro: `APP_URL=https://...` confiable por ambiente, debug desactivado, cookies seguras, worker supervisado, certificados válidos y remitente autenticado. M02 no despliega hosting.

Referencias verificadas: [SMTP Brevo](https://help.brevo.com/hc/en-us/articles/7924908994450-Send-transactional-emails-using-Brevo-SMTP), [diagnóstico](https://help.brevo.com/hc/en-us/articles/115000188150-Troubleshooting-Issues-with-Brevo-SMTP), [cuota Gratis](https://help.brevo.com/hc/en-us/articles/208580669-FAQs-What-are-the-limits-of-the-Free-plan), [broker Laravel](https://laravel.com/framework/docs/passwords), [notificaciones](https://laravel.com/framework/docs/13.x/notifications).
