# Pruebas y aislamiento

Desde la raíz del proyecto, con Docker funcionando:

```bash
./scripts/setup.sh
./scripts/start.sh
./scripts/check.sh
./scripts/test.sh
./scripts/e2e.sh
```

Ejecutar PHPUnit y E2E **secuencialmente**: ambos usan la misma base aislada de pruebas. `setup.sh` prepara dependencias y migraciones pendientes de desarrollo, y `start.sh` inicia los servicios y comprueba readiness. No reemplazan la ejecución de los checks.

`check.sh` valida Composer, Pint sin modificar archivos, Larastan nivel 6 sobre `app/` sin ignores generales y el build frontend. Para aplicar formato: `docker compose run --rm --no-deps app composer format`.

## Bases, colas y resets

`test.sh` detiene `test-worker`/`test-app`, arranca `test-postgres` y Redis y ejecuta Laravel con `--env=testing`. La persistencia usa PostgreSQL 18 con `RefreshDatabase`, nunca SQLite. Antes de que el trait migre, las guardas del TestCase y del proveedor comprueban entorno `testing`, driver `pgsql`, host `test-postgres` y base `estuflow_test`.

`e2e.sh` detiene la instancia y el worker de pruebas antes de llamar a `testing:prepare --env=testing`, que valida las mismas guardas antes de `migrate:fresh`. **Solo recrea `estuflow_test`**, sobre otro servidor y volumen. Luego inicia `test-app` y `test-worker` y ejecuta un navegador en la red interna de Compose. El worker tiene readiness de PostgreSQL/sesiones/Redis; una trampa del script detiene ambos servicios al finalizar, incluso si falla el arranque. No se publica un puerto del servidor de pruebas.

El worker E2E usa exclusivamente el entorno `testing`, la cola `database` de `estuflow_test` y SMTP hacia `test-mailpit:1025`; no usa el worker ni el capturador de desarrollo. Compose fuerza MAIL_URL vacío, credenciales SMTP vacías y proveedores OAuth deshabilitados, incluso si `.env.testing` heredó otra configuración. `test-mailpit` tampoco publica puertos al host. Playwright consulta su API interna para recuperar emails realmente capturados por SMTP. No se emplean transportes de correo que escriban enlaces de seguridad en logs.

El servidor usa `artisan serve --no-reload` para conservar las variables forzadas por Compose en su proceso PHP hijo. Sin esa opción, Laravel descarta variables al recargar `.env.testing` y puede sustituir SMTP/cola por sus valores antiguos. `config/mail.php` trata `MAIL_URL` vacío como ausente para que Laravel conserve el transporte SMTP; una regresión comprueba su construcción sin acceder a la red.

Playwright rechaza cualquier `baseURL` diferente de `http://test-app:8000` y exige `E2E_ISOLATED=1`. El TestCase de Laravel verifica las guardas antes de cualquier reset. PostgreSQL de desarrollo puede publicar loopback mediante `DB_FORWARD_PORT`; test-postgres y Redis siguen sin puertos publicados. Ningún script ejecuta `down -v`, resetea desarrollo ni debe eliminar sus sesiones o usuarios. Las cuentas de prueba son ficticias.

## Cobertura de aplicación

Los tests de aplicación usan dobles para notificaciones y proveedores externos. Comprueban:

- Registro, normalización y duplicación de email, reglas de contraseña, confirmación, hashing y ausencia de contraseñas en flash.
- Sesión limitada antes de verificar, protección HTTP/Livewire del dashboard, verificación firmada para la cuenta y el email correctos, expiración, alteración, otra cuenta, reenvío limitado e idempotencia.
- Recuperación con respuesta pública equivalente para email existente/inexistente, límites por IP/email, token hasheado, enlace correcto/alterado/vencido/reutilizado, cambio de contraseña e invalidación exclusiva de las sesiones de su titular. La recuperación no verifica el email.
- Login, contraseña nullable de cuentas OAuth, regeneración e invalidación de sesión, límites y logout POST.
- OAuth con proveedores simulados: identidad nueva/existente, state incorrecto, proveedor desconocido/deshabilitado, cancelación, falta de email, colisión con una cuenta local, vínculo con reautenticación, restricciones únicas y separación entre cuentas.
- Entradas malformadas, salida escapada, confirmación atribuida al campo correcto y guardas de reset.

La presencia de esa cobertura no equivale a un resultado PASS: consultar los resultados de la ejecución final en `docs/evidence/m02/checks.md`.

## Navegador, correo y responsive

E2E recorre registro → aviso pendiente → email capturado por SMTP → verificación → dashboard → logout/login. También solicita recuperación para una cuenta conocida y otra inexistente, abre el enlace capturado, cambia contraseña, comprueba el email informativo y el rechazo de la contraseña anterior, y vuelve a ingresar. Un segundo contexto permite comprobar la invalidación de una sesión previa. Los enlaces firmados y tokens permanecen en memoria; no se publican bandejas de correo ni mensajes completos.

La suite comprueba POST sin JavaScript, errores asociados a campos, ausencia de duplicación del error de credenciales, limpieza al editar, foco, teclado, menú móvil, CSRF real y ausencia de desbordamientos. Laravel omite CSRF en los tests HTTP internos; por eso se verifica también en navegador real.

La matriz responsive usa 320×740, 390×844, 430×932, 768×1024, 1280×720, 1440×900, 1920×1080, 2560×1440 y 3840×2160 CSS px en login, registro, recuperación, nueva contraseña, verificación, dashboard y métodos de acceso. En QHD/4K se comparan los márgenes del contenido dentro del área restante de la sidebar. El ensayo adicional de 640×360 verifica reflow equivalente a 200 % sobre 1280×720; no automatiza el zoom del navegador ni el escalado del sistema operativo. La revisión visual de las capturas complementa estas aserciones.

## Evidencia, CI y límites

Playwright genera `test-results/e2e-results.json` (ignorado) y capturas seleccionadas en `docs/evidence/m02/`. No se guardan trazas, vídeo ni capturas automáticas tras fallos. El helper de capturas rechaza formularios que contengan contraseñas; tampoco se publica la barra de URL con tokens o firmas. Los resultados y versiones efectivamente ejecutados quedan en `docs/evidence/m02/checks.md`.

GitHub Actions configura el caché público `mirror.gcr.io` en el daemon del runner efímero, preservando sus otras opciones y todos los digests, y ejecuta setup, checks, PHPUnit y E2E en ese orden. El caché reduce la dependencia de la cuota anónima de Docker Hub; si falta una imagen, Docker consulta su origen canónico. No modifica Docker Desktop de la Mac. [Configuración documentada por Google](https://docs.cloud.google.com/artifact-registry/docs/pull-cached-dockerhub-images). Se debe revisar el resultado real y los logs de la corrida correspondiente al commit de la entrega. Un check local aprobado no acredita el resultado remoto, y una corrida todavía activa se informa como pendiente.

CI y pruebas locales no consultan Google/Microsoft ni envían emails mediante Brevo. Los mocks OAuth prueban comportamiento de la aplicación; el SMTP capturado prueba el transporte y recorrido local. Las pruebas reales de Google, Microsoft y Brevo requieren configuración externa y un destinatario autorizado y se informan por separado como **NO EJECUTADO** cuando falten esas condiciones. Los pasos de activación están en la documentación de integración correspondiente.

Chromium se instala desde Debian en Docker y Playwright queda fijado en npm. WebKit/Safari y Firefox permanecen como comprobaciones futuras. La revisión de accesibilidad cubre estructura, labels, avisos, foco y teclado; no constituye una auditoría completa con lectores de pantalla.

PHPUnit fuerza `CACHE_STORE=array` por proceso para no heredar límites de ejecuciones previas; sesiones y datos siguen en PostgreSQL aislado. E2E conserva Redis real con un `E2E_CACHE_PREFIX` nuevo por ejecución. No se hace flush de Redis ni se toca su prefijo de desarrollo.

## M03

PHPUnit cubre fidelidad/reconstrucción del catálogo, hashes PDF, idempotencia, entradas inválidas sin persistencia parcial, versiones inmutables y FK entre planes; elección/borrador/confirmación, aislamiento HTTP/Livewire, cambio y retorno, orientación incierta y dashboard 0/intermedio/100. Las pruebas M02 mantienen autenticación y ahora configuran una carrera cuando necesitan un dashboard completo. OAuth usa mocks; notificaciones fakes en PHPUnit y test-mailpit SMTP en E2E.

`testing:prepare` importa el catálogo después de recrear exclusivamente estuflow_test. E2E añade el recorrido académico, persistencia y edición HTTP sin JavaScript. Capturas nuevas usan cuentas ficticias y quedan en docs/evidence/m03; no se publican correos, passwords, URLs firmadas ni storageState. Ejecutar PHPUnit antes de E2E, nunca en paralelo. Resultados y límites en [evidencia M03](evidence/m03/checks.md).

La corrección M03 añade casos de cancelación/interrupción de cambio, selección del mismo plan, revisión desactualizada/ajena, migración desde un cambio antiguo incompleto y edición con filtros conservados. E2E verifica carga por año/deshacer, visibilidad de métodos, cancelación real y guardado contextual, y anillos 0/intermedio/100. La infraestructura existente se reutiliza; `./scripts/e2e.sh tests/e2e/academic.spec.js --grep ...` permite recorridos focalizados durante ajustes, antes de las suites completas del cierre. Las capturas nuevas viven en docs/evidence/m03-correction.
