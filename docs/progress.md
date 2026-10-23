# Progreso

## M02 — implementación del 9 y cierre local del 13 de octubre de 2026

Implementados los cinco bloques de la entrega:

1. Responsive de 320 px CSS a 4K, auth con medida máxima y composición adaptable; dashboard centrado dentro del área libre de sidebar, navegación móvil con teclado.
2. Recuperación completa con Password Broker, token hasheado de 60 minutos, throttle de 60 segundos, trabajo asíncrono también para cuentas ausentes, notificaciones cifradas en cola PostgreSQL, worker Docker, Mailpit local y configuración Brevo STARTTLS. Reset rota remember token, invalida sólo sesiones propias, notifica y vuelve al login.
3. Verificación con MustVerifyEmail, Registered/Verified, enlace firmado temporal, sesión limitada, reenvío con cooldown y restricciones auth+verified en HTTP/Livewire. Cuentas existentes no se verifican automáticamente.
4. Google y Microsoft con Socialite, state/PKCE/nonce cuando corresponde, identidad estable única, password nullable, email pendiente en servidor, conflicto sin autovínculo, sección Métodos de acceso y vínculo con reautenticación. Google habilitado en local tras configurar .env; Microsoft permanece deshabilitado.
5. Errores inline accesibles y consistentes, credenciales incorrectas como único aviso general, foco y corrección de errores, POST sin JavaScript y escape de datos.

Verificación local final: setup/start/check PASS, 75 pruebas con 455 aserciones y 4 recorridos E2E aprobados. GitHub Actions también PASS en Linux AMD64 sobre `1d1a714`: setup, check, PHPUnit, E2E y artifact de capturas. Resultados, capturas y estado de GitHub en [evidencia M02](evidence/m02/checks.md). Guías de [correo/Brevo](mail.md) y [Google/Microsoft](oauth.md). Las pruebas dobles y SMTP local no acreditan entrega Brevo ni OAuth real: el login Google y la autenticación/entrega Brevo reales quedan NO EJECUTADOS, pendientes de prueba manual con cuentas autorizadas pese a su configuración local aplicada. Microsoft sigue deshabilitado. No se desplegó hosting ni se hizo merge.

La entrega M01 se conserva en el historial y su evidencia previa en `docs/evidence/`; M02 no reinterpreta sus resultados como pruebas de las funciones nuevas. Las reglas permanentes están en AGENTS.md; commits/push requieren autorización explícita, otorgada para esta entrega.

## Ampliación local — DataGrip y configuración externa

PostgreSQL de desarrollo publicado exclusivamente en `127.0.0.1:5433`, configurable con `DB_FORWARD_PORT`; Laravel sigue en `postgres:5432`. Se conservaron volumen, identidad del clúster, tabla/usuarios y credenciales. Test-postgres y Redis permanecen internos. Configuración aplicada, app/worker reiniciados, Google visible/redirect comprobado sin consultar al proveedor y Brevo efectivo con STARTTLS obligatorio. Sin funcionalidades ni dependencias nuevas. [Guía DataGrip](datagrip.md) y [checks de la ampliación](evidence/m02/datagrip-config.md).

## M03 — Mi carrera (13 de octubre de 2026)

1. **M03-01:** catálogo trazable de 3 planes/71 materias, importador validado/transaccional/idempotente y planes inmutables; fuentes conservadas.
2. **M03-02:** elección explícita después de verificar email, también para cuentas M02 existentes y OAuth.
3. **M03-03:** borrador persistente por plan, cuatro estados, método de aprobación opcional y resumen/confirmación; todas pendientes es válido.
4. **M03-04:** Mi carrera con filtros, detalle y edición propia HTTP/Livewire; cambio de plan confirmado conserva estados anteriores.
5. **M03-05:** orientación por tres columnas, reglas del año anterior G/E y TODAS sin autorreferencia; promoción/sin método y ambigüedades por confirmar.
6. **M03-06:** dashboard con carrera activa, conteos, avance real y acceso a cursando; responsive y POST sin JavaScript.
7. **M03-07:** pruebas PostgreSQL/E2E, documentación y capturas. Resultados reales y estado CI en [evidencia M03](evidence/m03/checks.md).

[Modelo, fuentes, importación y revisión manual](m03.md). M03 se apoya en la rama M02; la PR se mantiene en borrador para revisión, sin merge ni despliegue. Se preservan datos/configuración local.

## Pendiente de producto

M03 corregida fue aprobada por Nicolás según el alcance acordado de M04. Sigue pendiente la confirmación académica de las ambigüedades documentadas. Faltan las otras nueve carreras sin fuente y Planificador; el perfil se incorpora en M05. Las integraciones Google/Brevo reales y Microsoft siguen con el estado informado en M02; los checks no las acreditan.

## Corrección integral M03 · 23 de octubre de 2026

La presentación inicial de M03 no estaba aceptada. Se recupera la composición M02 con datos reales y anillo proporcional; controles coherentes, lenguaje de estudiante, tres pasos compactos y edición cotidiana con filtros conservados. El cambio de carrera ahora mantiene la selección confirmada hasta confirmar el candidato; cancelación, interrupción y retorno conservan estados. Catálogo/reglas/autenticación/configuración externa permanecen. [Evidencia nueva](evidence/m03-correction/checks.md); Nicolás aprobó esta corrección como base de M04.


## M04 · Calendario y organización personal · 23 de octubre de 2026

1. **M04-01:** eventos propios con CRUD HTTP, mes/agenda, fechas UTC/civiles, materia opcional y preservación tras cambiar carrera.
2. **M04-02:** feriados compartidos por año, copia/validación atómica y cadencia de 15 días transcurridos; fallos conservan lo anterior.
3. **M04-03:** programación persistente/versionada, avisos internos deduplicados, email opt-in con presupuesto y política SMTP acotada; bandeja propia paginada/filtrable.
4. **M04-04:** adaptador del HTML real, baseline sin avisos históricos, identidad independiente del título, audiencia persistente y fan-out recuperable sin emails masivos.
5. **M04-05:** notas personales persistentes con CRUD confirmado y hasta tres en Inicio, debajo de las cuatro tarjetas aprobadas.
6. **M04-06:** scheduler Compose/arranque/parada, pruebas aisladas, SMTP test-mailpit, capturas responsive/zoom y documentación.

[Modelo, operación y recorrido manual](m04.md), [especificación](m04-spec.md) y [resultados reales](evidence/m04/checks.md). M04 requiere revisión visual de Nico; no modifica la aceptación M03 ni acredita entrega real de Brevo. Sin merge ni despliegue. Perfil, Planificador y reubicación de ajustes quedan para otra entrega.

## M05 · Perfil y arranque multiplataforma · 23 de octubre de 2026

M04 se considera funcional según Nicolás; sus refinamientos visuales generales siguen pendientes y esta entrega no rediseña esas pantallas.

1. **M05-01:** Mi perfil con nombre único editable, guardar/cancelar, persistencia, saludo e inicial actualizados; sólo atributos autorizados de la cuenta actual.
2. **M05-02:** email pendiente separado, reautenticación y confirmación POST de ambas direcciones, expiración/reenvío/cancelación, jobs cifrados versionados y colisión transaccional; contraseña local/OAuth-only, revocación de otras sesiones propias y tokens anteriores, avisos de seguridad.
3. **M05-03:** accesos y carrera centralizados en perfil; rutas previas compatibles, retorno de OAuth a la operación iniciada y del cambio académico al perfil. Catálogo/progreso/eventos/notas/avisos/identidades conservados.
4. **M05-04:** Bash común con requisitos claros, config limpia y procesos PHP recreados con readiness; instalación nueva/existente y guías macOS, Windows/WSL2 y Linux. Ejecución real de Windows pendiente, sin atribuirle resultados de Docker/CI.
5. **M05-05:** README reescrito, [instalación](installation.md), [uso del perfil](profile.md) y [demostración CRUD](demo.md) con eventos/notas existentes.
6. **M05-06:** regresión PostgreSQL/E2E, SMTP aislado, prueba concurrente real, capturas responsive/zoom, actualización acotada Vite 8.3.2 → 8.3.5 con lockfile y audit sin vulnerabilidades reportadas. [Resultados y límites](evidence/m05/checks.md).

Rama `codex/m05-perfil` basada en M04 mientras su PR siga pendiente; entrega para revisión, sin merge ni despliegue. Google/Brevo reales y Microsoft no se acreditan con dobles/Mailpit. Windows/WSL2 y arquitecturas no ejecutadas se mantienen como NO EJECUTADO. Perfil funcional no equivale a aprobación visual general.
