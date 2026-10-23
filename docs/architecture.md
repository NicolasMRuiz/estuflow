# Arquitectura

Monolito Laravel 13 / PHP 8.4 / Livewire 4 / Blade / Tailwind 4. PostgreSQL 18 conserva cuentas, sesiones cifradas, identidades OAuth, tokens hasheados de recuperación y trabajos cifrados de correo. Redis almacena caché/contadores con prefijos separados por ambiente. Alpine proviene exclusivamente de Livewire. Las versiones exactas quedan en Composer/npm lockfiles; M02 agrega únicamente Socialite y el adaptador Microsoft con sus dependencias.

## Autenticación y autorización

Controladores pequeños y Form Requests propios; no starter kit ni Fortify. Email normalizado con trim/minúsculas en formularios y modelo, unicidad PostgreSQL; password 10–128 caracteres, confirmación asociada al campo, hasher Argon2id del framework. Password es nullable para cuentas creadas con OAuth; el login local rechaza esas cuentas hasta establecer contraseña mediante el broker.

Registro despacha `Registered`, inicia sesión limitada y lleva a `/verificar-email`. `User` implementa `MustVerifyEmail`. Dashboard y métodos exigen `auth` + `verified`; el render Livewire vuelve a comprobar ambos, incluyendo updates posteriores. Los usuarios existentes no se marcan verificados por migración. No se acepta `email_verified_at` del navegador. No se agregan dominios institucionales. M03 agrega catálogo y progreso académico separados de autenticación.

Login tiene límite por combinación email normalizado/IP (5 fallos/60s) y por IP (30/min). Credenciales incorrectas muestran un único error general. Registro tiene 10 intentos/min/IP; recuperación 10/min/IP y 1/min/email normalizado, incluso inexistente; actualización de contraseña 10/min/IP; verificación 20/min/IP y reenvío 1/60s/cuenta. OAuth 30/min/IP; reautenticación 5/min/usuario+IP. Los errores de límite conservan sólo nombre/email e indican espera.

Login y registro regeneran sesión; logout es POST con CSRF, invalida sesión y renueva CSRF. Las redirecciones posteriores se restringen al `APP_URL` confiable. Las URLs de correo se generan forzando ese origen. Formularios no flashean passwords, confirmaciones ni tokens. Blade escapa datos, incluyendo nombres del proveedor. Servidor de desarrollo silencioso para no registrar URLs con tokens.

## Recuperación y verificación

Rutas: GET/POST `/olvide-mi-contrasena`; GET `/restablecer-contrasena/{token}` y POST `/restablecer-contrasena`. El POST de solicitud devuelve la misma respuesta para cuentas presentes/ausentes y encola siempre un trabajo cifrado; éste hace la consulta/broker fuera del request público. Correo también encolado con SMTP, 3 intentos, backoff y timeout acotados. Backend `database`, worker Docker y Mailpit local; ver [configuración de correo](mail.md).

El broker almacena token hasheado, expira en 60 minutos, throttle 60 segundos y reemplaza el anterior. El cambio serializa por cuenta en transacción para impedir uso concurrente del mismo token, usa el hasher del modelo, rota remember token y elimina exclusivamente sesiones PostgreSQL de esa cuenta. Despacha `PasswordReset`, notifica cambio y vuelve al login sin autenticar ni verificar email.

Rutas de verificación: GET `/verificar-email`, POST `/verificar-email/reenviar`, GET `/verificar-email/{id}/{hash}`. Laravel genera firma temporal de 60 minutos; el callback verifica firma/expiración, ID autenticado y hash del email, serializa la transición en PostgreSQL, marca sólo esa cuenta y despacha `Verified` una vez. Errores tienen pantalla útil, reenvío y logout. Un invitado conserva el destino firmado y lo reanuda tras login. Es independiente del broker de recuperación.

## OAuth

[Política, protecciones y activación](oauth.md). Socialite para Google, SocialiteProviders/Microsoft para Microsoft; Authorization Code con sesión, state y PKCE. No se persisten access/refresh tokens. Identidad única `(provider, issuer, subject)`, transacciones/constraints para callbacks repetidos o concurrentes. Google usa ID de userinfo; Microsoft usa issuer+sub de claims validados por adaptador y comprobaciones adicionales nonce/audiencia/tenant. No se implementa criptografía JWT propia.

Identidad conocida ingresa en su cuenta. Nueva con email libre crea cuenta no verificada; sin email utiliza identidad pendiente cifrada en sesión con vencimiento 10 minutos. Email coincidente no vincula automáticamente. Vinculación sólo desde cuenta autenticada/verificada, reautenticación reciente de 10 minutos por password o proveedor ya vinculado e intención ligada a cuenta en sesión; no cambia la cuenta ni traslada identidad. No hay desvinculación. Proveedores ocultos/deshabilitados hasta configuración válida.

## Ejecución y mantenimiento

Imágenes oficiales y Mailpit fijados por digest multiplataforma, sin forzar AMD64. Composer corre en imagen PHP de la app. Frontend usa Node 22. La web y la interfaz del capturador publican sólo loopback. PostgreSQL de desarrollo publica `127.0.0.1:${DB_FORWARD_PORT:-5433}:5432` para DataGrip; Laravel conserva `postgres:5432`. Test-postgres, Redis y SMTP del capturador no publican puertos. El volumen PostgreSQL y sus credenciales se conservan al recrear el servicio; ver [DataGrip](datagrip.md). `php artisan serve` es sólo desarrollo. No se borran volúmenes ni se ejecutan resets de desarrollo.

Testing tiene servidor, usuario y volumen PostgreSQL diferentes; `TestingDatabase` valida entorno `testing`, driver `pgsql`, host `test-postgres`, base `estuflow_test` antes de migrar/reset. PHPUnit/E2E secuenciales, instancias web/worker/Mailpit aisladas. Los scripts detienen test-worker antes de reset. `environment:check` comprueba sesiones/PostgreSQL y una clave Redis UUID temporal; nunca flush.

El helper cloud configura DNS/proxy/CA local y descarga ZIP oficiales del commit exacto de los lockfiles, sin desactivar TLS/firmas/checksums. No se necesita en Mac. La ejecución Mac ARM64 y los resultados concretos están en [evidencia M02](evidence/m02/checks.md); CI Linux AMD64 se reporta por separado.

## M03 · Datos académicos

[Modelo y contrato](m03.md). Catálogo normalizado y planes inmutables; progreso aislado por usuario/plan mediante FK compuestas y un único plan activo. Configuración académica después de email verificado; middleware sólo en rutas académicas. Escrituras HTTP y Livewire usan AcademicProgress con bloqueo por usuario. Advisory evalúa datos cargados en memoria con relaciones eager, sin consultas por correlativa; no bloquea la declaración personal.

M03 corregida el 23/10 distingue `is_candidate` de `is_active`: la carrera confirmada no se modifica durante la preparación de un cambio. El resumen queda ligado a la huella del candidato en sesión y se valida bajo bloqueo del usuario al confirmar. CareerBrowser ofrece edición inline con filtros persistentes; comparte AcademicProgress con el editor HTTP/Livewire de detalle. Presentación de nombres, conteos y estados separada de los datos fuente.
