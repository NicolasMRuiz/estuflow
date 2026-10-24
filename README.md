# EstuFlow

Espacio personal para organizar el recorrido académico: carrera y plan, progreso de materias, correlativas, eventos, recordatorios, notas y novedades. Mi perfil reúne datos personales, seguridad y accesos. El Planificador está pendiente.

Laravel 13, PHP 8.4, Livewire 4, Blade y Tailwind 4; PostgreSQL 18 y Redis. Docker ejecuta PHP, Composer, Node, correo y procesos en segundo plano.

## Requisitos

- Git, Bash y Docker Desktop/Engine con Docker Compose v2, iniciado y usando contenedores Linux.
- En Windows: Git for Windows y su terminal **Git Bash**. No hace falta instalar Ubuntu, abrir una terminal WSL ni instalar PHP, Composer o Node en el equipo.
- En macOS y Linux: usar Terminal con Bash y Docker disponible para el usuario.

Docker Desktop administra su propio backend de contenedores. En Windows, sus requisitos pueden incluir una característica de virtualización de Windows; no se necesita una distro Ubuntu para ejecutar estos comandos. La [guía de instalación](docs/installation.md) explica la preparación y los problemas habituales.

## Arranque

En Windows, abrir Git Bash en la carpeta del proyecto. En macOS/Linux, abrir Bash allí. Para un checkout nuevo:

```bash
git clone --branch codex/m05-perfil https://github.com/NicolasMRuiz/estuflow.git
cd estuflow
./scripts/setup.sh
./scripts/start.sh
```

Si ya tenés el checkout abierto, omití `git clone` y `cd` y ejecutá sólo los dos scripts. `setup.sh` crea la configuración local si falta, instala dependencias dentro de Docker, compila los recursos y aplica migraciones pendientes; no borra ni reinicia la base. `start.sh` levanta y comprueba todos los servicios.

Abrir [EstuFlow](http://localhost:8000) y [Mailpit](http://localhost:8025). Crear una cuenta, verificarla desde Mailpit y configurar la carrera. Cada equipo tiene su propio `.env` y base; Git no transporta cuentas, claves ni volúmenes.

Para el uso diario, ejecutar `./scripts/start.sh`. Para apagar web, worker, scheduler y Mailpit: `./scripts/stop.sh`. PostgreSQL y Redis conservan sus datos. Los puertos predeterminados y cómo cambiarlos están en [instalación](docs/installation.md#puertos-y-configuración).

## Proveedores opcionales

Una instalación nueva usa Mailpit: los correos quedan capturados localmente y no se envían a destinatarios externos. Para activar proveedores, editá las líneas existentes de `.env` (no agregues claves duplicadas) y nunca pegues secretos en Git, capturas o chat.

**Google:** crear credenciales OAuth para aplicación Web y autorizar este callback en Google Cloud:

```dotenv
GOOGLE_ENABLED=true
GOOGLE_CLIENT_ID=ID_DE_CLIENTE
GOOGLE_CLIENT_SECRET=SECRETO_DE_CLIENTE
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

**Microsoft:** registrar una aplicación Web en Entra ID y autorizar este callback:

```dotenv
MICROSOFT_ENABLED=true
MICROSOFT_CLIENT_ID=ID_DE_APLICACION
MICROSOFT_CLIENT_SECRET=VALOR_DEL_SECRETO
MICROSOFT_REDIRECT_URI=http://localhost:8000/auth/microsoft/callback
MICROSOFT_TENANT_ID=common
```

`common` permite cuentas personales y organizacionales, sujeto a las políticas de la organización.

**Brevo:** activar email transaccional, verificar el remitente y usar el login SMTP más una clave SMTP (no la API key):

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_REQUIRE_TLS=true
MAIL_USERNAME=LOGIN_SMTP
MAIL_PASSWORD=CLAVE_SMTP
MAIL_FROM_ADDRESS=REMITENTE_VERIFICADO
```

Después de editar `.env`, ejecutar `./scripts/start.sh` para recargar configuración y procesos. El botón de OAuth inicia un acceso real al proveedor; Brevo puede enviar correos reales. Los ejemplos no prueban credenciales, consentimientos ni entrega. Ver pasos completos, scopes, verificación y pruebas manuales: [Google/Microsoft](docs/oauth.md) y [correo/Brevo](docs/mail.md).

## Actualizar y comprobar

Para actualizar un checkout existente, primero guardá tus cambios locales y luego:

```bash
./scripts/stop.sh
git pull --ff-only
./scripts/setup.sh
./scripts/start.sh
```

Para validar el código, ejecutar desde Bash:

```bash
./scripts/check.sh
./scripts/test.sh
./scripts/e2e.sh
```

Ejecutar `test.sh` y `e2e.sh` secuencialmente; usan una base PostgreSQL de pruebas aislada. Más información: [DataGrip](docs/datagrip.md), [perfil y seguridad](docs/profile.md), [calendario y notas](docs/m04.md), [pruebas](docs/testing.md), [arquitectura](docs/architecture.md), [interfaz](docs/ui.md), [alcance](docs/progress.md) y [evidencia M05](docs/evidence/m05/checks.md).
