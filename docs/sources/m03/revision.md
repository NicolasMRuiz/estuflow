# EstuFlow M03 — revisión de los planes de estudio

Fecha de revisión: 13 de octubre de 2026. Fuente: los tres PDFs adjuntos del Instituto Tecnológico Beltrán / ISFT N.º 197. Cada archivo tiene una página. Se verificaron las tablas con extracción independiente de Poppler y pdfplumber y revisión visual de las tres páginas.

## Alcance y decisiones

M03 incorpora estas tres carreras, su configuración inicial, Mi carrera y un dashboard con progreso real. El catálogo admite nuevos planes y carreras; no se inventan los nueve planes restantes del objetivo de doce carreras. La carga académica declarada por el estudiante sirve para organizarse y no acredita inscripciones ni títulos ante el instituto.

El año del nombre del PDF es una referencia del archivo, no la resolución ni el año de ingreso. El plan de Sistemas aplica desde el ingreso 2020; Enfermería desde 2019; Higiene, Seguridad y Ambiente Laboral desde 2026. No hay fecha final de vigencia en estos documentos.

| Carrera | Resolución visible | Ingreso desde | Materias por año | Total materias | Horas sumadas |
| --- | --- | --- | --- | --- | --- |
| Tecnicatura Superior en Análisis de Sistemas | 6790/19 | 2020 | 8/8/7 | 23 | 1856 |
| Tecnicatura Superior en Enfermería | 854/16 | 2019 | 8/9/7 | 24 | 2148 |
| Tecnicatura Superior en Higiene, Seguridad y Ambiente Laboral | No consta | 2026 | 8/9/7 | 24 | 1920 |

Las horas totales de esta tabla son cálculos sobre las filas de los PDFs; no son totales oficiales impresos. Enfermería conserva las 356 horas publicadas para la materia 342.

## Cómo leer las correlativas

- **Final:** finales aprobados que el PDF pide para rendir el final de esa materia.
- **Cursada:** cursadas aprobadas que el PDF pide para inscribirse a cursarla.
- **Inscripción final:** finales aprobados que el PDF pide para inscribirse a cursarla.
- **—:** celda vacía en esa columna del PDF; también aplican las condiciones generales del año.
- **TODAS:** celda especial de la práctica de tercer año, conservada junto con la nota al pie; no se convierte en una dependencia de sí misma.

**G** = formación general; el PDF permite rendir en modalidad libre. **E** = formación específica; el PDF no permite modalidad libre. **R** = acredita mediante examen final. **P** = puede acreditar mediante promoción, sin final. No se infieren horas semanales, cuatrimestre ni modalidad de asistencia.

## Condiciones generales presentes en los tres planes

1. Primer año: documentación de matriculación en plazo, curso de ingreso realizado y trámite de libreta de estudiante.
2. Segundo año: al menos dos materias de primero aprobadas en examen final; al menos una de formación específica E.
3. Tercer año: al menos dos materias de segundo aprobadas en examen final; al menos una de formación específica E.
4. La condición regular debe alcanzarse a la fecha de inscripción a cursadas del calendario académico. Los PDFs no traen ese calendario ni sus fechas.

Estas condiciones se presentan como orientación. EstuFlow permite guardar estados actuales e históricos aunque la declaración inicial esté incompleta; no debe bloquear la carga personal por correlativas pendientes.

## Datos que requieren confirmación académica

| Tema | Evidencia y tratamiento en M03 |
| --- | --- |
| Promoción frente a requisitos de final | Los PDFs admiten P sin final y a la vez piden finales aprobados en varias reglas. No explican si la promoción satisface esas reglas. Guardar el método de aprobación y mostrar «Por confirmar» cuando esa equivalencia cambie el resultado; no asumir equivalencia ni exigir al estudiante un final de una materia ya promocionada. |
| Práctica de tercer año | La columna dice TODAS; la nota exige finales de toda la carrera para aprobar la práctica. Excluir la propia práctica del conjunto para evitar una referencia circular, conservar texto y contexto y señalar pendiente el momento de aplicación y la equivalencia de promociones. |
| Enfermería 342 | La página original imprime 356 Hs. Se conserva ese valor; confirmar con el instituto si corresponde, sin reemplazarlo por 352 o por otro número. |
| Higiene, Seguridad y Ambiente Laboral: resolución | El documento de ingreso 2026 no indica resolución. Guardar null; no adivinarla. |
| Higiene, Seguridad y Ambiente Laboral 604 | La fuente escribe «Seguirdad». Se guarda source_name exacto y display_name con la corrección editorial «Seguridad»; el nombre oficial queda por confirmar. |
| Sistemas 305 y 314 | La fuente escribe «Ingenieria». Se mantiene el nombre fuente y se incorpora la tilde en el nombre visible, como corrección editorial documentada. |
| Información ausente | No constan vencimiento de regularidad, notas mínimas, horarios, calendario, equivalencias, régimen cuatrimestral/anual ni reglas de recursada. No implementarlas a partir de supuestos. |

## Tablas verificadas

### Tecnicatura Superior en Análisis de Sistemas

Fuente: `Estructura Curricular y Correlatividades - TSAS - 2024.pdf`, página 1. Plan `TSAS-6790-19-INGRESO-2020`.

SHA-256: `ff244f7b9eadc34c11fc3a4f8b763ff6974a09948b9c11a54b65b2662679d475`.

#### Año 1 — 8 materias, 576 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 294 | Inglés I | G | P | 64 | — | — | — |
| 295 | Ciencia, Tecnología y Sociedad | G | P | 64 | — | — | — |
| 296 | Análisis Matemático I | G | R | 64 | — | — | — |
| 297 | Álgebra | G | R | 64 | — | — | — |
| 298 | Algoritmos y Estructuras de Datos I | E | R | 128 | — | — | — |
| 299 | Sistemas y Organizaciones | E | R | 64 | — | — | — |
| 300 | Arquitectura de Computadores | E | R | 64 | — | — | — |
| 301 | Prácticas Profesionalizantes I | E | R | 64 | — | — | — |

#### Año 2 — 8 materias, 640 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 302 | Inglés II | G | P | 64 | 294 | 294 | — |
| 303 | Análisis Matemático II | G | R | 64 | 296,297 | 296 | — |
| 304 | Estadística | G | P | 64 | 296,297 | 296,297 | — |
| 305 | Ingenieria de Software I | E | R | 64 | 299 | 299 | — |
| 306 | Algoritmos y Estructuras de Datos II | E | R | 128 | 298 | 298 | — |
| 307 | Sistemas Operativos | E | R | 64 | 300 | 300 | — |
| 308 | Base de Datos | E | R | 64 | 298 | 298 | — |
| 309 | Prácticas Profesionalizantes II | E | R | 128 | 299,301 | 299,301 | — |

#### Año 3 — 7 materias, 640 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 310 | Inglés III | G | P | 64 | 302 | 302 | 294 |
| 311 | Aspectos legales de la profesión | G | P | 64 | 309 | 309 | — |
| 312 | Seminario de actualización | E | P | 64 | 309 | 309 | — |
| 313 | Redes y Comunicaciones | E | R | 64 | 307 | 307 | 300 |
| 314 | Ingenieria de Software II | E | R | 64 | 305 | 305 | 299 |
| 315 | Algoritmos y Estructuras de Datos III | E | R | 128 | 306 | 306 | 296, 298 |
| 316 | Prácticas Profesionalizantes III | E | R | 192 | TODAS*** | 305,306,308, 309 | 298, 299 |

Nota de la práctica 316 transcrita: *** Es requisito tener aprobados los finales de todas las materias de la carrera para aprobarla Práctica del 3° año de la carrera.

### Tecnicatura Superior en Enfermería

Fuente: `Estructura Curricular y Correlatividades - TECEN - 2024.pdf`, página 1. Plan `TECEN-854-16-INGRESO-2019`.

SHA-256: `052f96bf15efc5eea6649e81db6edd4057ba0bbb0ced43bf4e24da011634bf16`.

#### Año 1 — 8 materias, 736 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 215 | Psicología | G | P | 32 | — | — | — |
| 216 | Teorías Socio Culturales de la Salud | G | P | 32 | — | — | — |
| 217 | Condiciones y Medio Ambiente del Trabajo | E | R | 64 | — | — | — |
| 218 | Salud Pública I | E | R | 64 | — | — | — |
| 219 | Biología Humana | E | R | 128 | — | — | — |
| 220 | Fundamentos del Cuidado | E | R | 64 | — | — | — |
| 221 | Cuidados de la Salud centrados en la comunidad y la familia | E | R | 96 | — | — | — |
| 222 | Práctica Profesionalizante I | E | R | 256 | — | — | — |

#### Año 2 — 9 materias, 736 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 327 | Comunicación en Ciencias de la Salud | G | P | 32 | — | — | — |
| 328 | Inglés | G | P | 32 | — | — | — |
| 329 | Introducción a la Metodología de la Investigación | G | P | 64 | — | — | — |
| 330 | Nutrición y Dietoterapia | E | P | 32 | — | — | — |
| 331 | Salud Pública II | E | R | 96 | 218 | 218 | — |
| 332 | Farmacología en Enfermería | E | R | 64 | — | — | — |
| 333 | Enfermería Materno Infantil | E | R | 64 | 215,219,220, 221 | 215,219,220, 221 | — |
| 334 | Enfermería del Adulto y del Adulto Mayor I | E | R | 96 | 215,219,220 | 215,219,220 | — |
| 335 | Práctica Profesionalizante II | E | R | 256 | 222 | 222 | — |

#### Año 3 — 7 materias, 676 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 336 | Organización y Gestión de servicios de Enfermería | E | R | 64 | — | — | — |
| 337 | Aspectos Bioéticos y legales de la profesión | E | P | 32 | — | — | — |
| 338 | Enfermería en Salud Mental | E | R | 64 | — | — | — |
| 339 | Enfermería del Adulto y del Adulto Mayor II | E | R | 64 | 330,332,334 | 330,332,334 | — |
| 340 | Enfermería comunitaria y práctica educativa en Salud | E | R | 64 | — | — | — |
| 341 | Enfermería en Emergencias y Catástrofes | E | P | 32 | — | — | — |
| 342 | Módulo Práctica Profesionalizante III | E | R | 356 | TODAS **** | 335 | — |

Nota de la práctica 342 transcrita: *** Es requisito tener aprobados los finales de todas las materias de la carrera para aprobarla Práctica del 3° año de la carrera.

### Tecnicatura Superior en Higiene, Seguridad y Ambiente Laboral

Fuente: `Estructura Curricular y Correlatividades - TSHSA - 2026.pdf`, página 1. Plan `TSHSA-INGRESO-2026`.

SHA-256: `dd052ececee23e0c9044b2b864851c929ae607dec2d21e67f2b800a630a7d347`.

#### Año 1 — 8 materias, 576 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 604 | Administración de las Organizaciones en Seguirdad | G | P | 96 | — | — | — |
| 605 | Derecho de Trabajo | E | P | 64 | — | — | — |
| 606 | Física para Seguridad 1 | E | P | 64 | — | — | — |
| 607 | Tecnologías de Representación para Seguridad | E | P | 64 | — | — | — |
| 608 | Fundamentos de Matemática para Seguridad | G | P | 64 | — | — | — |
| 609 | Química para Seguridad 1 | E | P | 64 | — | — | — |
| 610 | Seguridad 1 | E | R | 96 | — | — | — |
| 611 | Práctica Profesionalizante 1: Aproximación al Campo Laboral | E | R | 64 | — | — | — |

#### Año 2 — 9 materias, 640 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 612 | Ergonomía | E | R | 64 | 610, 611 | 604, 605, 610, 611 | — |
| 613 | Física para Seguridad 2 | E | P | 64 | 606 | 606, 608 | — |
| 614 | Higiene y Ambiente Laboral 1 | E | R | 64 | 610, 611 | 608, 610, 611 | — |
| 615 | Medicina Laboral 1 | E | R | 64 | 605 | 605, 610 | — |
| 616 | Psicología Laboral | G | P | 64 | — | 604 | — |
| 617 | Química para Seguridad 2 | E | P | 64 | 609 | 608, 609 | — |
| 618 | Seguridad 2 | E | R | 96 | 605, 610 | 605, 610, 611 | — |
| 619 | Capacitación Laboral | E | R | 64 | 610, 611 | 610, 611 | — |
| 620 | Práctica Profesionalizante 2: Intervenciones en el Campo Profesional | E | R | 96 | 610, 611 | 607, 608, 610, 611 | — |

#### Año 3 — 7 materias, 704 horas sumadas

| Código | Nombre de la fuente | Tipo | Instancia | Horas | Final | Cursada | Inscripción final |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 621 | Inglés para Seguridad | E | P | 64 | — | 614, 620 | 610, 611 |
| 622 | Comunicación, Tecnología y Sociedad | G | R | 64 | 619 | 618, 619, 620 | 604, 616 |
| 623 | Medicina Laboral 2 | E | R | 64 | 615 | 610, 614, 615 | 605, 610 |
| 624 | Control de la Contaminación | E | R | 96 | 614, 617, 618 | 614, 617 | 606, 609 |
| 625 | Higiene y Ambiente Laboral 2 | E | R | 96 | 614 | 612, 614, 620 | 606, 609 |
| 626 | Seguridad 3 | E | R | 128 | 618 | 618, 619, 620 | 604, 606, 609, 610 |
| 627 | Práctica Profesionalizante 3: Diseño de Proyectos en Higiene, Seguridad y Ambiente Laboral | E | R | 192 | TODAS*** | 612, 614, 618, 619, 620 | 606, 607, 608, 609, 610, 611 |

Nota de la práctica 627 transcrita: *** Es requisito tener aprobados los finales de todas las materias de la carrera para aprobarla Práctica del 3° año de la carrera.

## Contrato del JSON

El archivo `EstuFlow_M03_catalogo.json` contiene tres planes y 71 materias. `code` es texto y pertenece al plan; no es un identificador global. `key` combina plan y código. `academic_year` y `catalog_order` mantienen la organización de la fuente. `source_name` conserva la transcripción; `display_name` incorpora sólo las correcciones editoriales listadas en `editorial_corrections`.

`requirements` mantiene tres listas de códigos, todas dentro del mismo plan. `requirements_source_cells` conserva las celdas originales. `special_rules` representa TODAS y su nota; no se expande en una lista que incluya la propia materia. `academic_year_entry_rules` mantiene las condiciones generales, con sus textos fuente. Cada materia referencia página y fila; cada plan incluye nombre del PDF y SHA-256.

Los campos explicativos y los valores de incertidumbre son parte de la documentación de importación; no se deben completar automáticamente con datos inventados. Las horas se conservan como unidades de la fuente, sin convertirlas a horas semanales o cronológicas.

## Verificación de la extracción

- Tres fuentes de una página; hashes coincidentes con los archivos recibidos.
- Revisión visual de las tres páginas y concordancia entre dos extractores para códigos, tipos, instancias y horas de las 71 filas.
- Códigos, distribución por año, nombres y celdas de correlativas cotejados con las tablas originales.
- Referencias numéricas válidas dentro del mismo plan; sin duplicados, autorreferencias ni ciclos en las correlativas explícitas.
- Tres reglas TODAS conservadas como reglas especiales; metadatos ausentes y anomalías documentados.

Esta verificación acredita la fidelidad de la extracción de estos archivos. No confirma la vigencia institucional de los planes ni resuelve las ambigüedades académicas señaladas.

## Historias para el tablero

| ID | Historia / tarea | Aceptación principal |
| --- | --- | --- |
| M03-01 | Importar el catálogo académico | Tres planes, 71 materias y reglas trazables; importación idempotente sin alterar progreso personal. |
| M03-02 | Elegir carrera y plan | Usuario verificado, nuevo o existente, selecciona una de las tres carreras antes del dashboard académico. |
| M03-03 | Registrar mi situación inicial | Materias por año, cuatro estados y método de aprobación opcional; borrador recuperable y configuración válida aunque todas estén pendientes. |
| M03-04 | Consultar y editar Mi carrera | Listado y filtros reales, detalle de correlativas separado por contexto; sólo se edita el progreso propio. |
| M03-05 | Mostrar orientación académica | Condiciones del año y correlativas con estados claros; «Por confirmar» cuando una ambigüedad de la fuente afecte el resultado. |
| M03-06 | Conectar el dashboard | Carrera, materias por estado y progreso calculado con datos de la cuenta; enlaces funcionales. |
| M03-07 | Verificar y publicar la entrega | Pruebas de datos, autorización, onboarding, persistencia y responsive; documentación y PR en borrador sin merge. |

El prompt adjunto define el comportamiento y los criterios completos. Estas siete tareas organizan la entrega; no fijan un límite de funcionalidades para el producto.
