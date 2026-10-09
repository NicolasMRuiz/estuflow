# Google y Microsoft en M02

Estado: **implementado; prueba real pendiente de configuración**. Las pruebas de aplicación usan dobles y no consultan Google/Microsoft. Los dos proveedores permanecen deshabilitados si faltan credenciales, la bandera de activación o el callback exacto. No se crearon aplicaciones externas ficticias ni se verificó una cuenta institucional real.

## Activación local

Con la aplicación preparada mediante `./scripts/setup.sh`, agregar estas variables a la configuración local sin subir sus valores secretos a Git. No reemplazar el `.env` existente. Los callbacks siguientes corresponden exactamente a `APP_URL=http://localhost:8000`; abrir el navegador con ese mismo host.

```dotenv
GOOGLE_ENABLED=false
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

MICROSOFT_ENABLED=false
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_REDIRECT_URI=http://localhost:8000/auth/microsoft/callback
MICROSOFT_TENANT_ID=common
```

Completar las credenciales desde la consola del proveedor y cambiar solamente la bandera del proveedor listo a `true`. Después ejecutar `docker compose run --rm --no-deps app php artisan config:clear` y `./scripts/start.sh`. Si cambia `APP_URL` o el puerto, actualizar el callback registrado externamente y su variable local. El código rechaza discrepancias de esquema, host, puerto o ruta; nunca toma el callback de un parámetro HTTP ni del encabezado Host.

Para un hosting futuro usar HTTPS, una aplicación/credenciales por ambiente cuando corresponda, `APP_URL` del ambiente y los callbacks exactos de ese host. Registrar esas URLs en ambas consolas antes de habilitar botones. Los secretos pertenecen a variables protegidas del servidor, no al frontend, documentación, capturas o chat.

### Google Cloud

1. Crear o seleccionar el proyecto de Google Cloud. Configurar Google Auth Platform: nombre EstuFlow, contacto, marca y audiencia. Si la app está en Testing con audiencia External, agregar las cuentas autorizadas como usuarios de prueba. Publicación, dominios, privacidad y eventual verificación de marca deben completarse para el hosting real según las exigencias de Google.
2. Crear un cliente OAuth de tipo **Aplicación web**. Registrar `http://localhost:8000/auth/google/callback` como URI de redirección autorizada. Este flujo utiliza redirección del servidor; no necesita un SDK JavaScript ni APIs de Gmail, Drive o Calendar.
3. Copiar el ID de cliente y el secreto a `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET`. Solicitar exclusivamente `openid profile email`, que son los scopes configurados por el código. Activar `GOOGLE_ENABLED=true` una vez registrado el callback.
4. Probar desde login/registro, consentir con una cuenta de prueba autorizada y completar la verificación propia de EstuFlow en el capturador de correo. Probar también cancelación y una identidad cuyo email ya pertenezca a una cuenta local.

### Microsoft Entra

1. En App registrations, registrar EstuFlow con **cuentas de cualquier directorio organizativo y cuentas Microsoft personales** (`AzureADandPersonalMicrosoftAccount`). Esa audiencia, junto con `MICROSOFT_TENANT_ID=common`, permite cuentas personales, laborales y educativas.
2. Agregar plataforma **Web**, con URI `http://localhost:8000/auth/microsoft/callback`. No habilitar implicit grant, acceso público ni redirect a una SPA para este flujo Authorization Code del servidor.
3. Crear un secreto de cliente. Guardar su **Value**, no su Secret ID, en `MICROSOFT_CLIENT_SECRET`; guardar Application (client) ID en `MICROSOFT_CLIENT_ID`. Registrar su vencimiento y rotarlo antes de expirar.
4. Los scopes son `openid profile email User.Read`. `User.Read` delegado permite al adaptador leer el perfil propio mediante Microsoft Graph `/v1.0/me`; no se pide correo, archivos, calendario, grupos, directorio completo ni `offline_access`. No activar claims de grupos ni permisos adicionales para esta entrega.
5. Activar `MICROSOFT_ENABLED=true` y probar una cuenta personal y una laboral/educativa autorizada. Una institución puede restringir el consentimiento del usuario aunque los scopes sean mínimos; su administrador debe aprobar la app de acuerdo con su política. La aplicación presenta un mensaje de cancelación/restricción y ofrece el método local.

## Recorridos y política de cuentas

| Ruta | Acceso y función |
| --- | --- |
| `GET /auth/{google\|microsoft}/redirect` | Invitado: inicia acceso con proveedor. |
| `GET /auth/{google\|microsoft}/callback` | Valida sesión, state e intención; procesa el código de autorización. |
| `GET/POST /auth/completar-email` | Invitado: completa una identidad pendiente guardada en el servidor. |
| `GET /metodos-de-acceso` | Cuenta autenticada y verificada: muestra contraseña y proveedores vinculados. |
| `POST /metodos-de-acceso/reautenticar` | Reautentica mediante la contraseña actual. |
| `POST /metodos-de-acceso/{provider}/reautenticar` | Reautentica mediante una identidad previamente vinculada. |
| `POST /metodos-de-acceso/{provider}/vincular` | Inicia un vínculo para el usuario actual después de reautenticación reciente. |

Una identidad conocida ingresa siempre a su propietario local. El email o nombre que cambie en el proveedor no sobrescribe el email local, su verificación ni permisos. Una identidad nueva con email disponible crea una cuenta con `password=null`, la vincula y exige verificación de email de EstuFlow. Nunca se confía en el claim `email_verified` para habilitar el dashboard.

Cuando no hay un email utilizable se pide uno en una pantalla breve. La identidad y su nombre permanecen en la sesión cifrada de PostgreSQL; el navegador únicamente envía el email. El paso vence a los 10 minutos y comenzar otro intento reemplaza el pendiente. Una coincidencia con un email local existente no vincula cuentas: la persona debe usar su método actual o recuperar acceso y luego vincular desde Métodos de acceso.

Las cuentas creadas con OAuth pueden establecer una contraseña mediante el Password Broker y un enlace válido enviado a su email. Métodos de acceso ofrece «Cerrar sesión y recuperar acceso» mediante POST con CSRF y lleva al formulario de recuperación. Ese cambio conserva identidades vinculadas y no sustituye la verificación. Login local rechaza cuentas con password nulo. No hay desvinculación en M02.

Para vincular se exige cuenta verificada y una confirmación de identidad de los últimos 10 minutos. Login reciente cuenta como confirmación; posteriormente se usa la contraseña actual o una redirección nueva con un proveedor ya vinculado. Microsoft solicita `prompt=login` durante reautenticación; Google ofrece selección explícita de cuenta. Una identidad diferente no confirma la cuenta ni cambia su sesión. Se guarda en servidor el usuario iniciador, proveedor, intención y vencimiento. El callback debe conservar ese usuario y la vigencia de su confirmación, y nunca traslada un vínculo desde otra cuenta.

## Protecciones y dependencias

Dependencias agregadas sin una actualización general: `laravel/socialite` 5.31.0 y `socialiteproviders/microsoft`/`manager` 4.10.0, con `firebase/php-jwt` 7.2.1 y sus dependencias fijadas en `composer.lock`. El adaptador está registrado mediante el evento `SocialiteWasCalled` de Laravel 13.

Ambos proveedores usan Authorization Code, sesión y `state` consumible, además de PKCE S256 de Socialite. No se llama a `stateless()`. Un intento vence a los 10 minutos y el callback consume su contexto. `state`, nonce y verifier se eliminan al terminar o fallar. Solo se aceptan destinos internos bajo el `APP_URL` confiable; una verificación firmada pendiente puede reanudarse después del login. Autenticar regenera el ID de sesión.

Google obtiene el identificador estable `sub` mediante el endpoint userinfo y el access token recibido del intercambio de código; no se autentica decodificando un ID token en código propio. Microsoft obtiene claims de `getClaims()` del adaptador, que verifica la firma con JWKS, issuer y vencimiento mediante Firebase JWT. La revisión de su versión 4.10.0 detectó que faltaba comprobar nonce y que la comprobación de audience aceptaba substrings: EstuFlow agrega nonce de solicitud aleatorio, comparación constante, audience exactamente igual al client ID y coherencia exacta de issuer con el tenant validado. No hay implementación criptográfica propia ni JWT meramente decodificado.

`oauth_identities` usa `(provider, issuer, subject)` como identidad única: Google tiene issuer fijo; Microsoft guarda el par issuer/sub del ID token validado, conservando el contexto de tenant. No usa email, `preferred_username` ni el ID de Graph como clave. Un segundo índice único `(user_id, provider)` mantiene un método por proveedor en cada cuenta. Transacciones y manejo de colisiones de unicidad evitan usuarios o vínculos duplicados frente a callbacks simultáneos. No se guardan access tokens, refresh tokens, roles o grupos del proveedor. El rollback de esta migración elimina la tabla de identidades, pero conserva password nullable para no inventar contraseñas ni dañar cuentas OAuth ya existentes; no debe usarse como un mecanismo para revertir cuentas reales.

Límites de servidor: OAuth 30 solicitudes por minuto/IP; reautenticación 5 por minuto/usuario+IP; completar email también pasa por registro 10 por minuto/IP. Los POST tienen CSRF. Se muestran mensajes útiles para state vencido/alterado, cancelación, consentimiento institucional y fallos de conexión; no se muestran ni registran payloads de excepciones externas que puedan contener tokens. La conexión usa TLS validado y timeouts de conexión de 5 segundos y total de 10 segundos.

## Pruebas y límites de la evidencia

`tests/Feature/OAuthTest.php` cubre botones condicionales, callback exacto, state y PKCE reales al generar autorización, rechazo de state por Socialite antes de acceder a red, cancelación, cuentas nuevas/existentes, aislamiento entre cuentas, email pendiente y vencimiento, ausencia de autovínculo por email, issuer/sub de Microsoft, nonce/audience/issuer incorrectos, reautenticación, vencimiento, sesión del iniciador, unicidad en PostgreSQL, password nullable y destinos internos seguros.

Comando focal ejecutado en testing: `docker compose run --rm --no-deps test-app php artisan test --env=testing --filter=OAuthTest`: **PASS, 32 pruebas y 222 assertions**. PHPUnit/E2E deben ejecutarse secuencialmente porque comparten `estuflow_test`; el comando focal no sustituye los checks completos documentados en la evidencia de M02.

Los callbacks de éxito y claims de Microsoft usan dobles locales: no acreditan una respuesta real del proveedor, firma JWT real, consentimiento, configuración de tenant, disponibilidad de email o políticas institucionales. Quedan **NO EJECUTADOS** los recorridos con Google y Microsoft reales hasta configurar aplicaciones/credenciales y cuentas de prueba autorizadas. CI no consulta proveedores ni envía correos externos.

## Referencias verificadas el 9 de octubre de 2026

- [Laravel Socialite](https://laravel.com/docs/13.x/socialite).
- [Microsoft para SocialiteProviders: configuración y claims validados](https://socialiteproviders.com/Microsoft/).
- [Fuente del adaptador Microsoft fijada en el lockfile](https://github.com/SocialiteProviders/Microsoft/blob/e86ad86734df16a54d869b21103b9e2ec3842000/Provider.php), versión instalada 4.10.0.
- [Google OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect).
- [Microsoft: registro de aplicaciones](https://learn.microsoft.com/en-us/entra/identity-platform/quickstart-register-app) y [OIDC](https://learn.microsoft.com/en-us/entra/identity-platform/v2-protocols-oidc).
- [Marca Google](https://developers.google.com/identity/branding-guidelines) y [marca Microsoft](https://learn.microsoft.com/en-us/entra/identity-platform/howto-add-branding-in-apps). El logo local `public/images/google-g.png` procede del [asset oficial de Google](https://developers.google.com/static/identity/images/g-logo.png); Microsoft conserva su pictograma de cuatro colores. No se cargan logos ni scripts remotos al abrir EstuFlow.
