# Instalación y operación local

## macOS

Instalar Git (las herramientas de línea de comandos de Apple lo incluyen), Bash y [Docker Desktop para Mac](https://docs.docker.com/desktop/setup/install/mac-install/), seleccionando la descarga que corresponda a Apple Silicon o Intel. Abrir Docker Desktop y esperar a que el motor esté listo. Desde Terminal, comprobar `git --version`, `bash --version`, `docker info` y `docker compose version`. Continuar con el arranque común. No se usa Homebrew ni herramientas PHP/Node globales.

## Windows: preparar WSL2

[Microsoft: instalar WSL](https://learn.microsoft.com/en-us/windows/wsl/install) indica Windows 11 o Windows 10 versión 2004/build 19041 o superior para estos comandos; comprobar además los [requisitos vigentes de Docker Desktop](https://docs.docker.com/desktop/setup/install/windows-install/), virtualización y una versión de Windows admitida. Docker requiere WSL 2.1.5 o posterior; usar la versión vigente.

**PowerShell como administrador**, primera instalación:

```powershell
wsl --install -d Ubuntu
```

Reiniciar Windows si lo solicita. Abrir Ubuntu y crear el usuario/contraseña Linux. **PowerShell** después del reinicio:

```powershell
wsl --update
wsl --set-default-version 2
wsl --list --verbose
```

Ubuntu debe mostrar VERSION 2. Si ya existía con versión 1:

```powershell
wsl --set-version Ubuntu 2
```

Instalar y abrir [Docker Desktop para Windows](https://docs.docker.com/desktop/setup/install/windows-install/). En Settings → General, usar **WSL 2 based engine** (puede venir activado sin mostrar opción). En Settings → Resources → WSL Integration, activar **Ubuntu** y Apply. Usar **Linux containers**; si no aparece la integración, comprobar ese modo. No instalar otro Docker Engine dentro de Ubuntu: Docker Desktop proporciona su motor mediante integración. [Procedimiento oficial](https://docs.docker.com/desktop/features/wsl/).

**PowerShell** para entrar a la terminal correcta:

```powershell
wsl -d Ubuntu
```

A partir de aquí, **Bash dentro de Ubuntu/WSL**:

```bash
sudo apt-get update
sudo apt-get install -y git ca-certificates bash
git --version
bash --version
docker info
docker compose version
mkdir -p ~/proyectos
cd ~/proyectos
```

Clonar y ejecutar los comandos del siguiente apartado allí. Guardar el checkout en el sistema Linux (`~/proyectos`), preferible a `/mnt/c/...`; [Docker recomienda el sistema de archivos Linux para bind mounts](https://docs.docker.com/desktop/features/wsl/best-practices/). Los scripts `.sh` no se ejecutan en PowerShell/CMD nativos. Las credenciales Linux y los secretos de la app son independientes.

## Linux

Instalar Git, Bash y certificados con el gestor de la distribución; por ejemplo en Ubuntu/Debian: `sudo apt-get update` y `sudo apt-get install -y git bash ca-certificates`. Instalar [Docker Engine según la distribución](https://docs.docker.com/engine/install/) con el plugin **Docker Compose v2**, o Docker Desktop. Iniciar el daemon y dar acceso al usuario siguiendo las instrucciones oficiales de permisos de esa instalación; no ejecutar los scripts del proyecto con `sudo` ni desactivar TLS para reparar descargas.

En Bash, comprobar `git --version`, `bash --version`, `docker info` y `docker compose version`. Si Docker sólo responde como root, resolver el acceso al daemon antes de continuar, según la instalación elegida. Continuar con el arranque común.

## Arranque común en Bash

Mientras M05 esté en revisión:

```bash
git clone --branch codex/m05-perfil https://github.com/NicolasMRuiz/estuflow.git
cd estuflow
./scripts/setup.sh
./scripts/start.sh
```

`setup.sh` usa un contenedor PHP fijado para crear `.env` y `.env.testing` sólo si no existen. Genera claves de cifrado y passwords locales nuevos; los archivos existentes se conservan byte por byte. No copia configuración privada de otra computadora. Instala Composer/npm desde lockfiles, compila recursos, detiene procesos PHP antiguos, limpia config y aplica migraciones pendientes/catálogo idempotente. No usa resets de desarrollo. El `.gitattributes` versionado exige LF; Git conserva el bit ejecutable. Bash resuelve el checkout por la ubicación del script, incluso con espacios. Los UID/GID del anfitrión se usan para escribir los bind mounts sin generar archivos root.

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

Desde su checkout en Bash; guardar primero cualquier trabajo propio y revisar `git status`:

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

- **Docker no disponible:** iniciar Docker Desktop/daemon; en Windows revisar Ubuntu integrada y modo Linux. Repetir `docker info` en la misma Bash.
- **Compose desconocido:** instalar/activar el plugin v2, no `docker-compose` v1.
- **Puerto ocupado:** usar las variables indicadas, sin tocar las credenciales ni el volumen.
- **Permission denied:** revisar permisos del checkout y que el usuario tenga acceso a Docker. Mantener LF; un checkout recibido fuera de Git puede necesitar `chmod +x scripts/*.sh`. No aplicar permisos globales 777.
- **Descarga/build fallido:** revisar conectividad y certificados del motor, conservar digests, TLS y lockfiles. Repetir setup cuando se resuelva.
- **Ambiente incompleto:** `docker compose run --rm app php artisan environment:check` informa acceso a PostgreSQL/Redis sin mostrar credenciales. No usar reset como reparación.

Otra computadora tiene una instalación/base nueva: Git no transporta volúmenes, sesiones o `.env`. Esta guía no migra datos entre equipos. Cualquier traslado futuro requiere respaldo/restauración explícitos y preservar por separado la configuración de cifrado original; no copiar datos/secretos automáticamente.

## Checklist pendiente en Windows/WSL2

**NO EJECUTADO** en esta entrega hasta contar con una PC Windows real:

1. Preparar Ubuntu VERSION 2, Docker Desktop Linux e integración; verificar daemon/Compose desde Bash.
2. Clonar la rama en `~/proyectos`, ejecutar setup/start sin PHP/Composer/Node globales.
3. Abrir web/Mailpit desde Windows; registrar/verificar cuenta, configurar carrera, crear evento y nota, guardar perfil.
4. Repetir start, stop/start y logout/login: comprobar persistencia y salud de app/worker/scheduler.
5. Ejecutar check, test y e2e secuencialmente; registrar Windows/WSL/Docker/arquitectura y resultados reales. Comprobar DataGrip si se usa.

La [matriz de evidencia](evidence/m05/checks.md) distingue macOS ARM64, CI Linux AMD64 y plataformas sin ejecución real.
