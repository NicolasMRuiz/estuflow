# Instalación y operación local

## macOS

Instalar Git (las herramientas de línea de comandos de Apple lo incluyen), Bash y [Docker Desktop para Mac](https://docs.docker.com/desktop/setup/install/mac-install/), seleccionando la descarga que corresponda a Apple Silicon o Intel. Abrir Docker Desktop y esperar a que el motor esté listo. Desde Terminal, comprobar `git --version`, `bash --version`, `docker info` y `docker compose version`. Continuar con el arranque común. No se usa Homebrew ni herramientas PHP/Node globales.

## Windows con Git Bash

Instalar [Git for Windows](https://git-scm.com/download/win) y [Docker Desktop para Windows](https://docs.docker.com/desktop/setup/install/windows-install/). Iniciar Docker Desktop y seleccionar **Linux containers**. Su backend debe cumplir los [requisitos vigentes de Docker Desktop](https://docs.docker.com/desktop/setup/install/windows-install/); el backend puede usar virtualización/WSL2 internamente, pero **no hace falta instalar Ubuntu, crear una distro ni ejecutar comandos en una terminal WSL**.

Abrir **Git Bash** en la carpeta del checkout. No ejecutar los scripts `.sh` desde PowerShell o CMD. Comprobar:

```bash
git --version
bash --version
docker info
docker compose version
```

Si `docker info` falla, abrir Docker Desktop y esperar a que indique que el motor está en ejecución. No instalar PHP, Composer, Node ni otro Docker Engine en Windows; los servicios y las herramientas se ejecutan dentro de contenedores. El setup convierte automáticamente la ruta del checkout para los bind mounts de Git Bash.

## Linux

Instalar Git, Bash y certificados con el gestor de la distribución; por ejemplo en Ubuntu/Debian: `sudo apt-get update` y `sudo apt-get install -y git bash ca-certificates`. Instalar [Docker Engine según la distribución](https://docs.docker.com/engine/install/) con el plugin **Docker Compose v2**, o Docker Desktop. Iniciar el daemon y dar acceso al usuario siguiendo las instrucciones oficiales de permisos de esa instalación; no ejecutar los scripts del proyecto con `sudo` ni desactivar TLS para reparar descargas.

En Bash, comprobar `git --version`, `bash --version`, `docker info` y `docker compose version`. Si Docker sólo responde como root, resolver el acceso al daemon antes de continuar, según la instalación elegida. Continuar con el arranque común.

## Arranque común

Mientras M05 esté en revisión:

```bash
git clone --branch codex/m05-perfil https://github.com/NicolasMRuiz/estuflow.git
cd estuflow
./scripts/setup.sh
./scripts/start.sh
```

`setup.sh` usa un contenedor PHP fijado para crear `.env` y `.env.testing` sólo si no existen. Genera claves y passwords locales nuevos; conserva los archivos existentes y no copia configuración privada. Instala Composer/npm desde lockfiles, compila recursos y aplica migraciones pendientes e importación idempotente del catálogo. No usa resets de desarrollo. En Windows se ejecuta desde Git Bash; Bash resuelve el checkout incluso si la ruta contiene espacios.

`start.sh` comprueba PostgreSQL/Redis, detiene app/worker/scheduler, limpia la caché de configuración, aplica pendientes y catálogo y comprueba el ambiente. Inicia Mailpit y recrea esos tres procesos con espera de salud; comprueba el login con CSRF. La recreación recarga código y entorno, sin cambiar volúmenes. Puede haber unos segundos sin web durante el arranque. Si un paso falla, corregir su causa y repetir; no borrar la base para resolverlo.

Entradas predeterminadas: [web](http://localhost:8000), [capturador SMTP](http://localhost:8025). En Windows se abren desde el navegador de Windows. Registrar cuenta, abrir su verificación en Mailpit y elegir carrera. El catálogo es real; los datos personales/actividades se cargan desde la UI.

### Puertos y configuración

| Servicio | Anfitrión (loopback) | Dentro de Docker |
| --- | --- | --- |
| Web | `APP_PORT=8000` | `app:8000` |
| Mailpit UI | `MAILPIT_PORT=8025` | `mailpit:8025` |
| PostgreSQL desarrollo | `DB_FORWARD_PORT=5433` | `postgres:5432` |
| PostgreSQL testing / Redis / SMTP | Sin publicar | Red interna Docker |

Si un puerto está ocupado, cambiar sólo su variable en el `.env` de esa instalación. Con un `APP_PORT` diferente ajustar también `APP_URL` y los callbacks OAuth de esa instalación si se activan. Laravel conserva `DB_HOST=postgres` y `DB_PORT=5432`. Ver [DataGrip](datagrip.md). `docker compose config --quiet` valida sin imprimir secretos; `docker compose port app 8000` muestra la publicación efectiva. No compartir la salida completa de `compose config`.

Mailpit es el default sin credenciales externas. Brevo/Google/Microsoft se activan explícitamente según [correo](mail.md) y [OAuth](oauth.md); nunca copiar los secretos de un equipo automáticamente. Después de cambios locales, ejecutar `./scripts/start.sh` para limpiar config y recargar los procesos. No cambiar una `APP_KEY` existente: protege sesiones y trabajos cifrados.

## Actualizar una instalación existente

Desde Git Bash en Windows o Bash en macOS/Linux; guardar primero cualquier trabajo propio y revisar `git status`:

```bash
./scripts/stop.sh
git fetch origin
git switch codex/m05-perfil
git pull --ff-only origin codex/m05-perfil
./scripts/setup.sh
./scripts/start.sh
```

Si aún no existe la rama local, `git switch --track origin/codex/m05-perfil`. Una vez integrada, usar la rama integrada correspondiente. Detener antes de traer código evita que procesos largos sigan ejecutando la versión anterior mientras se instalan dependencias. Los scripts conservan cuentas, materias, eventos, notas, claves y volúmenes. No regeneran claves ni cambian secretos existentes. Al repetirlos sólo se aplican migraciones pendientes/importación idempotente.

## Uso diario y diagnóstico

```bash
./scripts/start.sh
docker compose ps
./scripts/stop.sh
```

Parar detiene sólo app, worker, scheduler y Mailpit. PostgreSQL/Redis quedan activos y la base persiste. Mailpit guarda correo efímero: no es un respaldo. Recordatorios/fuentes requieren Docker, equipo, scheduler y worker activos; no se garantizan envíos con el equipo apagado. El inicio recupera sólo avisos todavía útiles según M04.

- **Docker no disponible:** iniciar Docker Desktop/daemon, elegir Linux containers y repetir `docker info` desde la misma terminal.
- **Compose desconocido:** instalar/activar el plugin v2, no `docker-compose` v1.
- **Puerto ocupado:** usar las variables indicadas, sin tocar las credenciales ni el volumen.
- **Permission denied:** revisar permisos del checkout y que el usuario tenga acceso a Docker. Mantener LF; un checkout recibido fuera de Git puede necesitar `chmod +x scripts/*.sh`. No aplicar permisos globales 777.
- **Descarga/build fallido:** revisar conectividad y certificados del motor, conservar digests, TLS y lockfiles. Repetir setup cuando se resuelva.
- **Ambiente incompleto:** `docker compose run --rm app php artisan environment:check` informa acceso a PostgreSQL/Redis sin mostrar credenciales. No usar reset como reparación.

Otra computadora tiene una instalación/base nueva: Git no transporta volúmenes, sesiones o `.env`. Esta guía no migra datos entre equipos. Cualquier traslado futuro requiere respaldo/restauración explícitos y preservar por separado la configuración de cifrado original; no copiar datos/secretos automáticamente.

## Validación en Windows

Se verificó el arranque de este checkout desde Git Bash en Windows con Docker Desktop y el motor Linux: `setup.sh`, `start.sh`, PostgreSQL/Redis, login con CSRF y salud de app/worker/scheduler. No se ejecutaron `check.sh`, `test.sh` ni `e2e.sh` en Windows; los resultados de otras plataformas no sustituyen esas pruebas. Ver la [matriz de evidencia](evidence/m05/checks.md).

La [matriz de evidencia](evidence/m05/checks.md) distingue macOS ARM64, CI Linux AMD64 y plataformas sin ejecución real.
