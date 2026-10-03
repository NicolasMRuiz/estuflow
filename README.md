# EstuFlow

Primera entrega de un organizador académico: registro, login/logout y dashboard privado en español, con identidad propia y estados honestos sin datos académicos. Las secciones futuras no son enlaces operativos.

## Requisitos y arranque desde una carpeta nueva

Docker Desktop con Docker Compose v2, funcionando. Compatible con el stack de Docker para macOS Apple Silicon, sin PHP, Composer ni Node globales. La ejecución real en Apple Silicon aún no fue realizada; esta entrega se verificó en Linux AMD64.

```bash
git clone --branch main https://github.com/NicolasMRuiz/estuflow.git
cd estuflow
./scripts/setup.sh
./scripts/start.sh
```

La URL local es `http://localhost:8000`; login en `/login`, registro en `/registro` y panel en `/dashboard`. La primera entrega se conserva en la rama `main` del repositorio; estos comandos preparan una instalación local nueva.

`setup.sh` genera `.env` y `.env.testing` únicamente si no existen, con claves/contraseñas aleatorias locales. Nunca imprime esos valores. Conserva los archivos existentes; si ya hay una base, aplica solo migraciones pendientes. Las variables `DB_PASSWORD`, `TEST_DB_PASSWORD` y las claves de app deben estar configuradas localmente; no compartir su contenido. `.env.example` y `.env.testing.example` no contienen secretos reales.

Si el puerto 8000 está ocupado, ajustar `APP_PORT` y `APP_URL` en el `.env` local. Compose publica únicamente la web en `127.0.0.1`; las bases y Redis quedan en la red interna. Para equipos compartidos/producción se requiere una configuración adicional con HTTPS, cookies seguras, debug desactivado y servidor web de producción.

## Comandos

```bash
# Preparar/refrescar dependencias usando lockfiles y comprobar servicios
./scripts/setup.sh

# Iniciar o detener procesos, preservando volúmenes
./scripts/start.sh
docker compose stop app

# Formato, análisis estático y build
./scripts/check.sh

# Pruebas Laravel en PostgreSQL aislado
./scripts/test.sh

# Smoke E2E y capturas, solo contra datos ficticios de prueba
./scripts/e2e.sh

# Readiness: PostgreSQL, sesiones y operación temporal de Redis
docker compose run --rm app php artisan environment:check

# Aplicar formato de PHP
docker compose run --rm --no-deps app composer format
```

Los scripts comprueban el daemon; si Docker falta o está detenido, iniciarlo manualmente. No instalan Docker ni cambian ajustes globales del host. No borrar volúmenes para solucionar un problema.

En la nube administrada, los scripts detectan el override ignorado `docker/compose.cloud.yaml`, que configura proxy, DNS y CA. Para comandos Docker manuales en esa instancia, ejecutar primero `source scripts/common.sh`. El helper `python3 scripts/cloud-setup.py` prepara ese override y caché de archivos oficiales para evitar el endpoint GitHub bloqueado; se ejecuta únicamente en la nube. No es requisito de macOS.

## Recorrido manual

1. Abrir `/registro`, completar nombre, email y contraseña confirmada de 10–128 caracteres. El email se normaliza a minúsculas.
2. Tras crear la cuenta, comprobar el saludo personal y los estados vacíos del dashboard.
3. Usar **Cerrar sesión**; volver a `/dashboard` debe llevar a `/login`.
4. Ingresar con el mismo email/contraseña para recuperar el panel. Un email duplicado o credenciales incorrectas producen errores en español.

No se requiere email verificado ni se envían correos. No se implementaron recuperación, edición de perfil, modelo académico o CRUD.

## Documentación y evidencia

- `docs/architecture.md`: stack, seguridad y decisiones.
- `docs/ui.md`: identidad, tokens, estados y responsive.
- `docs/testing.md`: comandos, aislamiento y límites de las pruebas.
- `docs/progress.md`: entrega actual y siguientes pasos.
- `docs/evidence/checks.md`: resultados ejecutados y versiones.
- `docs/evidence/dashboard-1440x900.png` y `dashboard-390x844.png`: cuenta ficticia; no implican aceptación visual de Nicolás.
- `AGENTS.md`: reglas permanentes y comandos para trabajar en este checkout.

Las versiones exactas PHP/Composer/Node/PostgreSQL/Redis están asociadas a los digests en Docker; Laravel/Livewire y las dependencias frontend quedan fijadas por sus lockfiles. `docs/evidence/checks.md` registra las versiones efectivamente ejecutadas.
