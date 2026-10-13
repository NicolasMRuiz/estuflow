# Progreso

## M02 — implementación del 9 y cierre local del 13 de octubre de 2026

Implementados los cinco bloques de la entrega:

1. Responsive de 320 px CSS a 4K, auth con medida máxima y composición adaptable; dashboard centrado dentro del área libre de sidebar, navegación móvil con teclado.
2. Recuperación completa con Password Broker, token hasheado de 60 minutos, throttle de 60 segundos, trabajo asíncrono también para cuentas ausentes, notificaciones cifradas en cola PostgreSQL, worker Docker, Mailpit local y configuración Brevo STARTTLS. Reset rota remember token, invalida sólo sesiones propias, notifica y vuelve al login.
3. Verificación con MustVerifyEmail, Registered/Verified, enlace firmado temporal, sesión limitada, reenvío con cooldown y restricciones auth+verified en HTTP/Livewire. Cuentas existentes no se verifican automáticamente.
4. Google y Microsoft con Socialite, state/PKCE/nonce cuando corresponde, identidad estable única, password nullable, email pendiente en servidor, conflicto sin autovínculo, sección Métodos de acceso y vínculo con reautenticación. Deshabilitados hasta configurar aplicaciones reales.
5. Errores inline accesibles y consistentes, credenciales incorrectas como único aviso general, foco y corrección de errores, POST sin JavaScript y escape de datos.

Verificación local final: setup/start/check PASS, 75 pruebas con 455 aserciones y 4 recorridos E2E aprobados. Resultados, capturas y estado de GitHub en [evidencia M02](evidence/m02/checks.md). Guías de [correo/Brevo](mail.md) y [Google/Microsoft](oauth.md). Las pruebas dobles y SMTP local no acreditan entrega Brevo ni OAuth real: esos recorridos quedan NO EJECUTADOS hasta configurar proveedores y cuentas autorizadas. No se desplegó hosting ni se hizo merge.

La entrega M01 se conserva en el historial y su evidencia previa en `docs/evidence/`; M02 no reinterpreta sus resultados como pruebas de las funciones nuevas. Las reglas permanentes están en AGENTS.md; commits/push requieren autorización explícita, otorgada para esta entrega.

## Pendiente de producto

La aceptación visual final corresponde a Nicolás. No se implementó modelo académico, carrera, plan de estudios, materias, cursadas, correlativas, agenda ni CRUD. El dashboard mantiene estados vacíos y secciones futuras deshabilitadas. Esas funciones requieren requisitos y tarea aparte.
