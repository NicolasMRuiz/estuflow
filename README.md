# EstuFlow

Un espacio personal para organizar el recorrido académico: elegir carrera y plan, registrar el estado de las materias, consultar correlativas, planificar eventos con recordatorios, guardar notas y leer novedades. Mi perfil reúne datos personales, seguridad, accesos y configuración académica. El Planificador está pendiente.

Laravel 13, PHP 8.4, Livewire 4, Blade y Tailwind 4; PostgreSQL 18 y Redis. Docker ejecuta PHP, Composer, Node, correo local y procesos en segundo plano.

## Preparar el equipo

- **macOS:** Git, Bash y Docker Desktop en ejecución con Compose v2.
- **Windows:** Docker Desktop con backend WSL2 y contenedores Linux; Ubuntu integrada. Los comandos del proyecto se ejecutan en **Bash dentro de Ubuntu/WSL**, desde su sistema de archivos Linux.
- **Linux:** Git, Bash y Docker Engine con plugin Compose v2 —o Docker Desktop— y acceso al daemon.

La [guía de instalación](docs/installation.md) incluye los pasos de PowerShell para preparar WSL2, requisitos y soluciones a fallos de arranque. No hace falta instalar PHP, Composer o Node en el anfitrión.

## Primera instalación

Mientras esta entrega esté en revisión, seleccionar su rama publicada:

```bash
git clone --branch codex/m05-perfil https://github.com/NicolasMRuiz/estuflow.git
cd estuflow
./scripts/setup.sh
./scripts/start.sh
```

Abrir [EstuFlow](http://localhost:8000) y [Mailpit](http://localhost:8025). Crear una cuenta y verificarla desde el correo capturado en Mailpit. Después, configurar la carrera. Los puertos se pueden ajustar en el `.env` generado; [detalles](docs/installation.md#puertos-y-configuración).

Cada equipo crea su propia base y configuración privada: Git no traslada usuarios, contraseñas, volúmenes ni `.env` de otro equipo.

## Actualizar, iniciar y parar

Con el checkout limpio y desde Bash:

```bash
./scripts/stop.sh
git pull --ff-only
./scripts/setup.sh
./scripts/start.sh
```

`setup.sh` instala las dependencias fijadas, compila recursos y aplica migraciones pendientes e importación idempotente del catálogo. Conserva los archivos de entorno y sus claves. `start.sh` limpia la caché de configuración, aplica pendientes y recrea app, worker y scheduler para cargar el código/configuración actualizados; comprueba readiness y el login.

Para el uso diario: `./scripts/start.sh`. Para parar: `./scripts/stop.sh`; detiene web, worker, scheduler y Mailpit, conservando PostgreSQL y Redis. Los avisos requieren el equipo y esos procesos activos.

## Uso y configuración

- [Mi perfil y seguridad](docs/profile.md): nombre, doble confirmación de email, contraseña y carrera.
- [Demostración CRUD en tres minutos](docs/demo.md): eventos y notas, con persistencia entre sesiones.
- [Correo local y Brevo opcional](docs/mail.md), [Google/Microsoft opcionales](docs/oauth.md). Una instalación nueva usa Mailpit y proveedores deshabilitados; las credenciales personales no se distribuyen con Git. Microsoft sigue pendiente de prueba real.
- [PostgreSQL desde DataGrip](docs/datagrip.md): conexión sólo en loopback, separada de las pruebas.
- [Calendario, avisos y notas](docs/m04.md), [carrera y materias](docs/m03.md).

## Verificación y documentación

```bash
./scripts/check.sh
./scripts/test.sh
./scripts/e2e.sh
```

Ejecutar PHPUnit y E2E secuencialmente; usan exclusivamente PostgreSQL de pruebas y correo/proveedores aislados. [Pruebas y aislamiento](docs/testing.md), [arquitectura](docs/architecture.md), [interfaz](docs/ui.md), [alcance y progreso](docs/progress.md) y [evidencia M05](docs/evidence/m05/checks.md).
