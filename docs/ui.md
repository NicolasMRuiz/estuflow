# Identidad, responsive y estados de autenticación

Español rioplatense, tipografía del sistema y recursos locales. Alpine se carga únicamente mediante Livewire. Se conservan la marca y la paleta:

| Token | Valor | Uso |
| --- | --- | --- |
| Navy | `#09263D` | Sidebar, texto y marca |
| Teal | `#17A88B` | Marca y acentos |
| Teal oscuro | `#087963` | Botones, enlaces y foco sobre fondo claro |
| Menta | `#98E6D4` | Marca, foco y navegación sobre navy |
| Fondo | `#F7F5EF` | Fondo de páginas |
| Coral | `#F27864` | Acentos; variantes oscuras para errores |

## Composición desde 320 px CSS hasta 4K

El dashboard reserva el espacio de la sidebar mediante una variable compartida (`--sidebar-width`), y centra su contenedor de hasta 1800 px dentro del área restante. La sidebar es fija y puede desplazarse verticalmente en pantallas de poca altura. El contenido usa grids `minmax(0, …)`, tarjetas que pueden contraerse, márgenes adaptables y tipografía algo mayor desde 1920 px. No se escala la página con `transform`.

Bajo 801 px, la sidebar pasa a un menú en el flujo del documento. Se abre con el botón de navegación, admite Enter/Espacio, Escape lo cierra y devuelve el foco al botón, e Inicio permite navegar y cerrar el menú. Hay un enlace de salto al contenido. Inicio, Mi carrera, Calendario y Métodos de acceso son destinos operativos. Sólo Planificador conserva «Próximo» y `aria-disabled`. M03 usa exclusivamente datos del catálogo y progreso declarado.

Las pantallas de autenticación mantienen dos columnas desde 801 px, con la historia de marca limitada a 620 px y centrada en su columna; el formulario nunca supera 440 px. En móvil queda una sola columna con la marca arriba. Las páginas pueden desplazarse verticalmente; no se recortan formularios para forzarlos a entrar en pantallas de poca altura. Los inputs tienen texto de 16 px y controles principales de al menos 48 px de alto.

| Viewport CSS | Revisión E2E ejecutada |
| --- | --- |
| 320×740 | Reflow, errores y navegación móvil |
| 390×844 y 430×932 | Teléfonos |
| 768×1024 | Tablet |
| 1280×720 | Notebook y poca altura |
| 1440×900 | Escritorio de referencia |
| 1920×1080 | Full HD |
| 2560×1440 | QHD, caso reportado como 2K |
| 3840×2160 | 4K |

Se prueba también 640×360 CSS como el espacio de composición equivalente a una pantalla 1280×720 con zoom al 200 %. Ese ensayo de reflow no acredita haber automatizado el zoom del navegador ni el escalado del sistema operativo. La resolución física del monitor no determina por sí sola el viewport CSS.

La inspección preliminar de M02 reprodujo el login real a 2560×1440 antes de cambiar CSS: columna de marca de aproximadamente 1126 px, formulario de 400 px e historia alineada al margen izquierdo de su columna. La captura permitió revisar esa composición. La hipótesis de desalineación del dashboard surgió del `margin-left` fijo junto al `max-width` sin centrado; esa lectura del código, por sí sola, **no acredita una reproducción visual del dashboard anterior**. La evidencia final debe distinguir la comparación anterior de la revisión de la interfaz ya corregida.

El E2E del 13 de octubre reprodujo las reglas anteriores de ancho/posición sobre el markup M02: a 2560 px dejó 0 px libres junto a la sidebar y 610 px a la derecha. Las reglas actuales dejan 240 px a cada lado. `docs/evidence/m02/layout-qhd.json` registra medidas y alcance; la captura con reglas previas no representa la aplicación anterior completa ni el monitor físico de Nicolás.

La revisión de contraste corrigió placeholders y pie del panel a `#5b6b75`, y bordes de campos a `#7b8b84`. Cálculos de luminancia: placeholder sobre blanco 5,52:1, borde sobre canvas 3,28:1, botón blanco/teal 5,35:1, navy/canvas 14,20:1 y error/fondo coral claro 5,85:1. Son comprobaciones de esos pares, no una certificación completa de accesibilidad.

## Formularios y errores

Login, registro, recuperación, nueva contraseña y el paso de email OAuth utilizan `novalidate`: se evitan los globos nativos del navegador y se conserva `type="email"`, `required`, `maxlength`, `minlength`, labels y autocomplete. El POST normal funciona sin JavaScript y la validación autoritativa permanece en Form Requests y controladores del servidor.

`x-field` separa instrucciones permanentes (`*-hint`) de errores (`*-error`), asocia ambos mediante `aria-describedby` y establece `aria-invalid`. Los errores se expresan con texto además del color. Los valores anteriores solo se restauran en campos no sensibles; las contraseñas permanecen vacías y las entradas no escalares no se imprimen como valores de inputs.

`x-form-errors` muestra un único mensaje general para credenciales incorrectas: «El email o la contraseña no son correctos». El fallo no marca el email como inválido ni informa si la cuenta existe. Los errores de servicio, enlaces y límites también se muestran como avisos persistentes. Cuando hay más de un campo inválido, un resumen enlaza los campos; un único error de campo se explica junto a ese campo.

El JavaScript local valida al enviar, mueve el foco al primer campo inválido y conserva el botón disponible si falla la validación. Tras recibir un error general del servidor, el foco llega al aviso. Editar un campo elimina el error obsoleto correspondiente y los avisos generales del intento anterior; salir de un campo editado vuelve a validarlo si ya tuvo un error o se intentó enviar. No hay solicitudes por cada tecla. Al enviar un formulario válido, el botón indica el trabajo en curso y evita envíos duplicados. Los avisos usan `role="alert"`, `role="status"` o regiones anunciables según su función.

## Pantallas M02

- **Recuperación:** solicitar enlace desde el login, respuesta pública idéntica para cuentas existentes e inexistentes y formulario de nueva contraseña con confirmación. Un enlace inválido permite solicitar otro. El cambio vuelve al login con confirmación.
- **Verificación:** dirección propia visible, instrucciones para revisar spam, reenvío con espera visible y cierre de sesión mediante POST. Sin JavaScript, el servidor conserva el límite y se indica actualizar la pantalla cuando termine la espera. El enlace inválido ofrece uno nuevo y permite cambiar de cuenta.
- **OAuth:** Google y Microsoft aparecen en login/registro únicamente cuando el backend informa que están habilitados y configurados. Los botones conservan las marcas de los proveedores. Si falta email, se pide una dirección antes de continuar con la verificación de EstuFlow.
- **Métodos de acceso:** página accesible desde el dashboard con el estado de contraseña/proveedores y confirmación de identidad antes de vincular. Las tarjetas se apilan para conservar una lectura cómoda incluso a 320 px.

## Evidencia y límites

Las capturas M02 se guardan en `docs/evidence/m02/`, con login y dashboard a 390×844, 1440×900, 2560×1440 y 3840×2160, además de estados nuevos útiles para revisar. Deben inspeccionarse visualmente: ausencia de overflow no garantiza por sí sola una buena composición. Se usan cuentas ficticias, contraseñas vacías y capturas sin barra de URL que exponga firmas o tokens.

El resultado real de los comandos, la revisión visual final y las limitaciones están en `docs/evidence/m02/checks.md`; este documento describe la implementación y el alcance de revisión, no reemplaza esos resultados ni implica aceptación visual de Nicolás.

## Corrección M03 · 23 de octubre de 2026

La referencia visual es M02 en `9f31538`, especialmente `docs/evidence/m02/dashboard-1440x900.png`, su Blade y CSS. Las capturas M03 del 13/10 registran una implementación que el usuario rechazó; no constituyen aprobación visual. La aprobación de esta corrección queda a cargo de Nicolás.

Se conserva el saludo con avatar y bloque de cuenta, banner breve y grilla M02: Avance de carrera / Próximas actividades, luego Materias en curso / Correlativas. Las mismas cuatro tarjetas se apilan en ese orden. El indicador SVG representa aprobadas/total con un arco proporcional, texto accesible y numerador/denominador. Los conteos compactos viven en su pie, con singular/plural natural. Las listas se limitan a tres materias y llevan a sus detalles/filtros reales. Calendario sigue próximo, sin eventos inventados.

`x-action`, `x-status-badge`, `x-progress-counts`, `x-dashboard-panel`, `x-progress-fields` y `x-plan-source` comparten controles y estilos. Botones primarios/secundarios no agregan márgenes de contexto: los controlan las barras de acciones. La autenticación conserva sus separaciones mediante reglas de sus propios formularios. Controles principales de 48 px, borde/foco visibles y texto de estado además de color.

Etiquetas: Pendiente, Cursando, Final pendiente (`regularized`, sin cambiar datos), Aprobada. El método opcional ofrece Final / Promoción cuando corresponde / No lo especifico. La UI usa nombres comprensibles de carrera; el título oficial y los datos de trazabilidad viven en Datos del plan, desplegable secundario. No hay claves de plan, siglas ni frases de extracción en el contenido principal.

Configuración en tres pasos con indicador compartido. Años plegables nativos, primero abierto al empezar; el año con error se abre y su control recibe foco. El método sólo se muestra al aprobar. Aplicar un estado a un año cambia el borrador visible; informa alcance/cantidad, deja métodos sin especificar y permite deshacer antes de guardar. Guardar borrador y Continuar son POST explícitos, conservando todos los años plegados. La barra permanece accesible y pasa a posición normal en poca altura/zoom. El resumen muestra cantidades y detalles plegables, sin checkbox redundante.

Mi carrera tiene cabecera y conteos compactos, filtros alineados y acciones Ver detalle / Editar estado. La edición inline reutiliza campos y validación; Guardar cambios actualiza la lista/conteos sin redirigir, Cancelar no persiste. Si deja de coincidir con un filtro, se avisa y se conserva el filtro. El detalle ofrece formulario normal como alternativa sin JavaScript y retorno al contexto filtrado. La información se organiza en Mi estado, Para cursar y Para rendir el final; las correlativas de cursada y de final para inscripción permanecen diferenciadas. Las dudas sobre promociones y práctica final siguen visibles donde afectan la orientación.

Un cambio de carrera se prepara como candidato persistente. La selección confirmada, su finalización y dashboard siguen disponibles hasta confirmar el resumen. Cancelar en cualquier paso conserva la selección previa; interrumpir y volver a ingresar permite retomar el candidato. Elegir el mismo plan no reinicia la configuración. Ver contrato y migración en [M03](m03.md).

El flujo sin JavaScript usa detalles nativos, formularios POST y enlaces al editor por página. El método es opcional y permanece disponible en esa alternativa. La carga por año y edición inline son mejoras progresivas; ninguna regla académica se delega al navegador. [Evidencia de corrección](evidence/m03-correction/checks.md) con recorridos, capturas comparables y límites reales de la inspección.


## M04 · Organización personal

La composición M03 corregida fue aprobada por Nico. M04 mantiene saludo/cuenta/franja y orden de las cuatro tarjetas; conecta Próximas actividades y añade Mis notas debajo, máximo tres. Campana local con contador, navegación Calendario activa y bandeja con filtros/acciones POST. Las novedades institucionales se consultan desde la bandeja; no se inventan avisos ni eventos.

Calendario ofrece Mes en escritorio y Agenda en móvil, con cambio explícito disponible sin JS. Semana lunes–domingo, tipos textuales, selector/filtro/contexto, máximo tres elementos por día y Ver N más. Formulario de página con título/foco, labels, mensajes inline y Cancelar que retorna al control; detalle y confirmación de eliminación. Buenos Aires se explica junto a horarios; Día completo usa fechas inclusivas y dos opciones reales de 09:00. Email es opt-in, nunca implícito.

Notas tipo post-it con texto escapado/saltos de línea, sin arrastre, tareas, vencimientos ni nueva planificación. Listado y CRUD HTTP paginados. Nuevas capturas y revisión en `docs/evidence/m04`, 320/390/1440/2560/3840 y zoom real Chromium 200 %. No constituyen auditoría de lector de pantalla ni escalado físico del monitor.
