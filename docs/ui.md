# Identidad y estados

Español rioplatense, tipografía del sistema y recursos locales. Sin fuentes, iconos ni servicios remotos.

| Token | Valor | Uso |
| --- | --- | --- |
| Navy | `#09263D` | Sidebar, texto y marca |
| Teal | `#17A88B` | Marca y acentos |
| Teal oscuro | `#087963` | Botones y enlaces legibles |
| Menta | `#98E6D4` | Marca sobre azul, navegación activa |
| Fondo | `#F7F5EF` | Fondo de páginas |
| Coral | `#F27864` | Acentos; variantes oscuras para texto |

Sidebar fija en escritorio, menú desplegable en flujo en móvil; tarjetas blancas de bordes suaves y sombras discretas, apiladas bajo 800 px. El progreso sin carrera es **Sin datos**, sin porcentajes/fechas inventadas. Inicio es el único enlace operativo. Las demás secciones indican «Próximo» y `aria-disabled`. No hay búsqueda, notificaciones ni botones engañosos.

Login/registro comparten identidad, labels visibles, autocomplete, errores asociados con `aria-describedby`/`aria-invalid`, resumen `role=alert`, foco visible y estado de envío. Escape cierra el menú móvil y devuelve el foco al botón. Hay salto al contenido. El saludo proviene de la cuenta autenticada.

Capturas en `docs/evidence/`: cuenta ficticia E2E a 1440×900, 390×844 y reflow a 320 px. No implican aceptación visual de Nicolás. Resultados en `docs/evidence/checks.md`.
