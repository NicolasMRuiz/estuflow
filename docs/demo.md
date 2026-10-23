# Demostración CRUD en tres minutos

Usar una cuenta ficticia propia, verificada y con carrera configurada. Entrar desde Inicio. No hace falta tocar DataGrip ni borrar cuentas/catálogo.

| Operación | Ejemplo visible | Persistencia |
| --- | --- | --- |
| Crear | Calendario → Nuevo evento; Notas → Nueva nota | Inserta en `calendar_events` / `personal_notes`. |
| Consultar | Mes/agenda y detalle del evento; lista/detalle de notas | Recupera los registros propios guardados. |
| Modificar | Editar, guardar y recargar detalle | Actualiza la fila existente. |
| Eliminar | Eliminar, confirmar y comprobar ausencia | Evento: borrado lógico; nota: eliminación real existente. |

1. **Crear (45 s).** En Calendario, Nuevo evento: «Entrega de práctica», una fecha futura y hora; guardar. Desde Inicio → Mis notas → Nueva nota: título «Práctica», contenido «Revisar los ejercicios 1 y 2»; guardar. Son datos ficticios para esta demostración, no datos académicos del catálogo.
2. **Consultar y persistencia (45 s).** Ver el evento en mes/agenda y abrir su detalle; abrir la nota desde el listado. Cerrar sesión y volver a ingresar: ambos siguen guardados. No confundir recargar con volver a crear.
3. **Modificar (45 s).** Editar el mismo evento a «Entrega de práctica revisada» y guardar. Editar la nota agregando «Llevar apuntes». Recargar ambos detalles: los cambios persisten en los registros existentes.
4. **Eliminar (45 s).** En cada detalle, Eliminar y confirmar en la pantalla normal. Comprobar ausencia en calendario/agenda y listado de notas. El evento conserva su fila con `deleted_at` y sus recordatorios quedan cancelados; la nota se elimina según su semántica habitual.

Las cuentas sólo consultan/modifican registros propios. La UI presenta acciones de producto; la correspondencia con tablas pertenece a esta guía. La suite E2E M04 acredita ese recorrido con PostgreSQL y SMTP aislados; M05 lo mantiene en la regresión. Para una presentación, usar Mailpit y no activar correo real a destinatarios de prueba.
