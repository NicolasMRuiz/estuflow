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

Aceptación visual de Nicolás y confirmación académica de las ambigüedades documentadas. Faltan las otras nueve carreras sin fuente, agenda, calendario y planificador. Las integraciones Google/Brevo reales y Microsoft siguen con el estado informado en M02; los checks no las acreditan.

## Corrección integral M03 · 23 de octubre de 2026

La presentación inicial de M03 no estaba aceptada. Se recupera la composición M02 con datos reales y anillo proporcional; controles coherentes, lenguaje de estudiante, tres pasos compactos y edición cotidiana con filtros conservados. El cambio de carrera ahora mantiene la selección confirmada hasta confirmar el candidato; cancelación, interrupción y retorno conservan estados. Catálogo/reglas/autenticación/configuración externa permanecen. [Evidencia nueva](evidence/m03-correction/checks.md); la aprobación visual final corresponde a Nicolás.
