# Arquitectura

Monolito Laravel: controladores y Form Requests para registro/login/logout, modelo `User` y componente Livewire de página para el dashboard. Sin repositorios artificiales ni módulos vacíos. El dominio académico se incorporará cuando tenga requisitos aprobados.

## Compatibilidad y stack

Laravel 13 admite PHP 8.4; Livewire 4 admite Illuminate 13. Las dependencias exactas quedan en `composer.lock`. Blade, Tailwind 4 y Vite se instalan con Node 22 y `package-lock.json`. Alpine se carga solo con `@livewireScripts`, sin otra importación ni CDN.

Las imágenes oficiales se fijan por digest de índice multiplataforma. No se fuerza AMD64 en Compose. La ejecución real en la nube es Linux AMD64; una prueba en macOS Apple Silicon queda pendiente. Las extensiones PHP se compilan con dos trabajos. Composer corre en la misma imagen PHP que la aplicación y comprueba sus requisitos reales.

El servidor de desarrollo es `php artisan serve`, dentro de `app`. No es una configuración de producción. Solo la web publica puerto en loopback; PostgreSQL y Redis no exponen puertos del host. PostgreSQL 18 monta `/var/lib/postgresql`. Los volúmenes son `estuflow_postgres_data` y `estuflow_test_postgres_data`.

Cuentas y sesiones cifradas residen en PostgreSQL. Redis almacena caché y contadores, sin ser el almacenamiento de datos personales. `environment:check` escribe/lee/borra solo una clave UUID temporal; no ejecuta flush.

## Autenticación

Email: trim y minúsculas antes de validar; el modelo aplica la misma normalización al guardar. Unicidad en validación e índice PostgreSQL. Contraseñas de 10–128 caracteres con el cast `hashed` del framework y Argon2id. Sin criptografía propia.

Login admite cinco fallos por combinación de email normalizado e IP durante 60 segundos; también hay un límite por IP para evitar rotación de emails. Los mensajes no revelan existencia de cuentas. Registro/login regeneran el ID de sesión; logout POST usa la protección CSRF del framework, invalida la sesión y regenera el token.

El dashboard exige `auth` y lee la identidad autenticada en cada render. Ninguna propiedad pública o parámetro de URL selecciona la cuenta mostrada. Blade escapa su nombre. No se exige email verificado. No existen rutas/enlaces de recuperación, verificación o perfil.

## Pruebas y mantenimiento

Testing usa otro servidor PostgreSQL, usuario y volumen. `TestingDatabase` rechaza operaciones fuera de `testing`, `pgsql`, host `test-postgres` y base `estuflow_test`, tanto al arrancar esa instancia como antes de reset y migraciones de tests.

Los paquetes de sistema/Chromium provienen de repositorios Debian firmados. Las imágenes base y dependencias Composer/npm están fijadas; los paquetes de sistema pueden cambiar entre reconstrucciones. No se desactivan firmas ni TLS.

El helper de nube configura DNS/proxy/CA localmente y almacena en la caché de Composer los archivos del destino oficial de GitHub (`codeload.github.com`) en el commit exacto del lockfile. Comprueba integridad ZIP, referencia y cualquier checksum declarado sin cambiar el lockfile. Evita clones completos de todas las dependencias y conserva la verificación TLS. Ningún secreto se escribe en ese helper u override.

## Referencias oficiales consultadas

- [Laravel 13: releases](https://github.com/laravel/docs/blob/13.x/releases.md).
- [Skeleton oficial Laravel 13](https://github.com/laravel/laravel/tree/13.x).
- [Livewire: instalación y Alpine incluido](https://github.com/livewire/livewire/blob/main/docs/installation.md).
- Metadatos de `laravel/framework` y `livewire/livewire` publicados en Packagist y de Tailwind/Vite en npm.
- [Imagen oficial PostgreSQL](https://hub.docker.com/_/postgres).
- [Caché de archivos de Composer](https://github.com/composer/composer/blob/main/src/Composer/Downloader/FileDownloader.php).
