# CLAUDE.md — Entry point del repositorio BlackStones

> **Si estás leyendo esto por primera vez (humano o IA): este es el primer doc que tenés que leer. Te orienta en 60 segundos. No avances sin entenderlo.**

---

## 1. Qué es este repositorio

Este NO es solo el código de la web de BlackStones Marmolería. Es el **centro de operaciones estratégicas** del negocio: marca, copywriting, ads, creativos, prompts de IA, flow de WhatsApp, investigación de avatares, decisiones, libros y benchmarks.

Lo único que **no** vive acá es la contabilidad.

El sitio web (landing + calculadora) es **una carpeta entre nueve**. Vive en `site/public_html/`.

---

## 2. Filosofía operativa (no negociable)

> **"Decidimos rápido, pero solo después de haber investigado lento — cruzando datos propios, mercado, benchmarks de ganadores y validación científica — para operar siempre desde la probabilidad más alta posible, sabiendo que no hay certezas, solo sistemas que se retroalimentan."**

### Regla 1 — Brújula, no mapa

Los documentos sintetizados en este repo son **dirección, no verdad absoluta**. Una brújula excelentemente calibrada sigue siendo una brújula: orienta, no reemplaza el caminar.

Operacionalmente:
- Cuando un doc dice algo, asumimos **alta probabilidad de cierto, no certeza**.
- Antes de una decisión grande basada en un doc, **validamos contra realidad actual** (lenguaje crudo del avatar, métricas vivas, mercado en tiempo real).
- Si la realidad contradice el doc, **gana la realidad** y el doc se actualiza con fecha y razón.

### Regla 2 — Las 4 capas del conocimiento

Todo lo que sabemos vive en una de 4 capas (en `06-knowledge/`):

| Capa | Qué es | Quién la edita |
|---|---|---|
| **layer-0-raw** | Data virgen tal cual llegó (transcripts, dumps, briefings originales). **Nunca se edita.** | Solo se agrega, nunca se modifica. |
| **layer-1-synthesis** | Procesamiento estructurado de la layer-0. "Lo que el dump dice, ordenado." | Se reescribe cuando cambia layer-0. |
| **layer-2-landscape** | Investigación externa: libros, benchmarks de competencia, frameworks de la industria. | Se actualiza cuando descubrimos algo nuevo del mercado. |
| **layer-3-active-beliefs** | **Lo que creemos hoy que es verdad.** La brújula viva. Síntesis de las 3 capas anteriores filtrada por nuestra realidad operativa. | Se actualiza cuando una decisión nueva nos enseña algo. |

**Cuando dudes de algo, bajá una capa. Si layer-3 dice X y layer-1 dice Y, andá a layer-0 y resolvelo.**

### Regla 3 — Knowns, Known unknowns, Unknown unknowns

Toda decisión grande se documenta en `01-strategy/decision-log/` con tres listas explícitas:

- **Knowns** — qué sabemos con alta confianza y evidencia
- **Known unknowns** — qué sabemos que no sabemos (lista explícita, esto orienta qué investigar después)
- **Unknown unknowns** — qué territorio puede tener trampas que ni siquiera vemos (humildad institucional)

### Regla 4 — Datos > opiniones, siempre

Antes de afirmar algo en un doc:
- ¿Tenemos datos propios? (métricas de Meta, conversaciones reales de WhatsApp, presupuestos cerrados/perdidos)
- ¿Tenemos benchmark externo? (libros canónicos, estudios, ganadores comparables)
- ¿Tenemos validación reciente? (testeo en mercado, no asunción)

Si la respuesta a las tres es no, marcalo como hipótesis (`status: hypothesis`) y armá el plan para validar.

### Regla 5 — Anti-genericness

Cualquier párrafo que pueda aparecer en el blog de cualquier marketing agency genérica **no va**. Si un doc tiene "calidad premium", "servicio integral", "empresa familiar con tradición", o cualquier frase que ya leíste 1.000 veces, **no aporta**. Borralo o reemplazalo con dato específico.

---

## 3. Mapa del repositorio

```
00-brand/         identidad de marca (visual, voz, posicionamiento)
01-strategy/      avatares, mecanismo, principios operativos, decision log
02-marketing/     meta ads, copy, creatives, whatsapp
03-product/       materiales, servicios, lógica de pricing
04-operations/    procesos, ruteo de mediciones, deploy
05-ai-systems/    matriz de modelos de generación + prompt library
06-knowledge/     las 4 capas del saber
site/             la web pública (deploy 1:1 a Hostinger)
```

Cada carpeta tiene su `README.md` con detalle. Empezá por el README de la carpeta que te toque.

---

## 4. Cómo trabajar en este repo

### Si vas a tomar una decisión grande
1. Leé el `01-strategy/operating-principles.md` completo.
2. Leé el doc de `layer-3-active-beliefs/` que toque tu decisión.
3. Bajá a `layer-1-synthesis/` y `layer-0-raw/` para verificar la base.
4. Crea entry en `01-strategy/decision-log/YYYY-MM-DD-tema.md` con knowns / known-unknowns / unknown-unknowns.
5. Validá en realidad (test, conversación, dato).
6. Decidí. Documentá la decisión y por qué.

### Si vas a editar un doc existente
- Si es `layer-0-raw` → **no lo edites**. Si la data cambió, agregá un nuevo dump fechado.
- Si es `layer-1` → mirá si el cambio viene de `layer-0` actualizado. Sí: reescribí. No: paso a layer-3.
- Si es `layer-3` → actualizá con fecha y razón. Si el cambio es grande, hacelo decision-log.

### Si vas a sumar conocimiento nuevo
- Externo (libro, benchmark, estudio) → `06-knowledge/layer-2-landscape/`.
- Propio (dato, conversación, métrica) → `06-knowledge/layer-0-raw/` con fecha en el nombre.
- Síntesis tuya → `06-knowledge/layer-1-synthesis/`.

### Si vas a desplegar un cambio al sitio (Hostinger)

> **Arquitectura no negociable:**
> - **GitHub** = única fuente de verdad del repo. Toda modificación viaja por ahí.
> - **Local del usuario** = solo `D:\blackstones\.env` con credenciales FTP. **No hay clone, no hay `git pull`, no hay GitHub token.**
> - **Deploy** = la IA emite, en la misma respuesta, un bloque PowerShell con el archivo entero codificado en **Base64**. El usuario pega el bloque, PowerShell decodifica → temp → curl FTP upload → borra temp → abre browser.
> - Para cambios chicos (≤3 reemplazos puntuales) en archivos grandes (`index.html`, `calc.html`) → patch quirúrgico (Receta 2 de `deploy-snippets.md`) en vez de re-enviar el archivo entero.

Tres docs canónicos en `04-operations/`, leelos en este orden:

1. **`ftp-map.md`** — modelo mental obligatorio. La regla que evita el error #1: el FTP user **NO** aterriza en `public_html/`. Aterriza en `/home/u144473384/`. El web root real está en `domains/blackstones.com.ar/public_html/`. Toda ruta de upload tiene que empezar con eso.
2. **`deploy-snippets.md`** ⭐ — recetas listas para copy-paste. La canónica es la **Receta 1 (Base64 inline)**. Hay también receta de patch quirúrgico, listar remoto, borrar archivo, backup, y verificación de salud del sitio.
3. **`deploy-notes.md`** — referencia: camino manual desde panel Hostinger, FileZilla/Cyberduck, checklists pre/post deploy, rollback.

**Setup mínimo (1 sola vez):** crear `D:\blackstones\.env` con 4 líneas: `FTP_HOST` / `FTP_USER` / `FTP_PASS` / `FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html`. El `.env` queda local fuera del repo — es el único archivo persistente local del proyecto.

> ### 🔁 Default operativo no negociable: si tocás `site/`, das el deploy
>
> **Regla para cualquier IA o humano que modifique algo dentro de `site/public_html/` (landing, calculadora, imagen, PHP, CSS, JS, lo que sea):** terminá tu respuesta con el bloque PowerShell de deploy listo para pegar — Base64 inline (Receta 1) o patch quirúrgico (Receta 2). El bloque tiene que ser autocontenido (carga `.env`, escribe temp, sube por FTP, borra temp, abre browser).
>
> No esperes que te lo pida. **No hay cambio en `site/` que termine sin su bloque de deploy adjunto.**
>
> Cuál bloque elegir:
> - Archivo nuevo, o cambio mediano-grande en archivo chico → **Receta 1** (Base64 inline, archivo entero).
> - Cambio chico en archivo chico → **Receta 1**.
> - Cambio chico (≤3 reemplazos literales) en archivo grande (>100 KB) → **Receta 2** (patch quirúrgico, baja del FTP, replace, sube de vuelta — más rápido que enviar 273 KB en Base64).
> - Cambio grande en archivo grande → **Receta 1** (Base64, aunque el bloque sea visualmente largo).
>
> Cambios que **NO** disparan esta regla: docs en `00-` a `06-`, `CLAUDE.md`, `README.md`, `.gitignore`, cualquier archivo fuera de `site/`. Esos solo se commitean a GitHub.

> ### 🚫 Regla "nada local"
>
> **El repo NO se clona en la máquina del usuario.** No hay `git clone`, no hay `git pull`, no hay carpeta del repo en Windows, no hay GitHub token. Si una IA propone "cd al repo" o "git pull" o "agregá `GITHUB_TOKEN` al `.env`" → está violando la regla. El único archivo local persistente del proyecto es `D:\blackstones\.env` con credenciales FTP.

### Si te pegan un chat y te piden cotizar

Leé `02-marketing/whatsapp/como-cotizamos.md` antes de contestar: cómo se hace la cuenta (m², traforos, zócalo, regrueso, flete), las reglas del dueño (sin color no hay número, avisar el granito que se mancha, plazo de 20 días) y qué se entrega (mensaje listo para pegar + "para revisar antes de mandarlo"). El texto del mensaje está en `02-marketing/whatsapp/flow.md`.

Desde el 22/11/2026, antes de cotizar se mira qué trajo el cliente y si tiene el mueble colocado: el que llega con la medida y el color se resuelve rápido, con una cotización corta; al que todavía no tiene el mueble no se le hace trabajo de más. Está al principio de `como-cotizamos.md`.

### Si dudás dónde poner algo
Preguntá antes de adivinar. Una sola fuente de verdad por tema. El resto cross-linkea con paths relativos.

---

## 5. Reglas duras de este negocio (memorizar)

- **No mencionar VenarStones** en ninguna comunicación pública. (Rebrand 2026, separación de socios — interno.)
- **Plazo que se promete: 20 días corridos desde la medición**, para todos los materiales (dueño, 17/11/2026; antes "15-20"). **Nunca decir "10 días".** Si el cliente retira él, sin nuestro flete y colocación: **12 días corridos desde la medición** (dueño, 24/11/2026).
- **Primero la seña del 50 %: es la que habilita la visita del técnico** que rectifica las medidas en el domicilio (dueño, 24/11/2026). **El otro 50 % se paga con la entrega e instalación** (dueño, 27/11/2026). Cómo se le explica al cliente: `02-marketing/whatsapp/flow.md`, sección 6.
- **No coordinamos gremios** (carpinteros, plomeros). El cliente ya los tiene.
- **Medimos cuando los muebles ya están puestos.** Es un filtro, no una limitación.
- **El primer mensaje del chat es "la vieja confiable": la primera respuesta que fijó el dueño (22/11/2026).** Se copia de `02-marketing/whatsapp/flow.md`, sección 2, letra por letra; no se reescribe de memoria. Pide cuatro datos, pregunta si los muebles ya están colocados y despejados y deja el link de los colores. No se satura y se arma para leerse en un celular: si es una instalación nueva o un reemplazo se pregunta en el segundo mensaje. Lo que se le carga a la IA de WhatsApp está en `02-marketing/whatsapp/knowledge/ejemplos-de-respuestas.md`. De lo que el cliente ya dijo no se pregunta. El que llega con la medida y el color, que es el que abunda, se resuelve rápido, con una cotización corta. El trabajo de después (opciones, recálculos, seguimiento) es para el que ya tiene el mueble: "lo que no quiero es sobre-trabajar para el que no tiene el mueble listo ni siquiera". → `02-marketing/whatsapp/flow.md`, sección 2, y `01-strategy/decision-log/2026-11-22-la-primera-pregunta-clasifica.md`
- **Presupuesto cerrado por escrito en 24 hs por WhatsApp.** Es el mecanismo diferenciador. **Al cliente no se le dice "cerrado"** (dueño, 25/11/2026: "nada de presupuesto cerrado, porque post-medición pueden cambiar los m2"): se le dice "el presupuesto", y el número se cierra después de la medición. → `02-marketing/whatsapp/como-cotizamos.md`, "Reglas del dueño"
- **Granito sin garantía** se dice explícito. Suma confianza, no resta. Lo que sí se cubre es hasta la instalación (dueño, 27/11/2026): si se raja mientras lo trasladamos o lo manipulamos, corre por cuenta nuestra. En las páginas de `/trabajos/` no va (dueño, 24/11/2026: "no tiene ninguna ventaja táctica"): ahí van las ventajas comprobadas.
- **Avatar principal: Carolina** (38-55, ABC1, en obra). 80% del presupuesto va a ella.
- **Tono:** rioplatense, "vos", sin jerga, conversacional. Nada de marketing de manual. Y amable (dueño, 22/11/2026): una pregunta lleva una entrada suave, como "Para tenerlo en cuenta, ¿…?".
- **Test de Living:** ¿podría aparecer en La Nación Living sin desentonar?

---

## 5 bis. Reglas duras del sistema interno (tablero, calendario, contratos)

> Fijadas por el dueño el 16/11/2026. Si vas a tocar algo de `site/public_html/presupuestos/` o `calculadora/api/`, leelas antes. El detalle y el porqué están en los docs que se citan.

- **La etapa de una obra la dicta el tablero.** Los estados del presupuesto ya no mandan. Que no la dicte no quiere decir que no se vea: en Presupuestos cada obra viva muestra su etapa del tablero y se puede filtrar por ella (`list.php` lee el tablero, no lo toca). Por dentro, una obra del tablero es una cotización "aprobado"; archivarla la pasa a "entregado" y queda en "Archivadas" del tablero, desde donde se devuelve. → `01-strategy/decision-log/2026-11-16-la-etapa-la-dicta-el-tablero.md`
- **El presupuesto dura 180 días** en la pestaña Presupuestos y después se borra solo (`cleanup-retention.php`). **Una obra viva en el tablero no vence nunca**; su reloj empieza cuando se archiva. **Archivada dura 1 año** (17/11/2026; antes 180 días), con sus fotos y planos; después se borra de verdad, sin papelera (`BS_ARCHIVADAS_DAYS`). No tocar esa limpieza sin probarla con datos envejecidos.
- **Eliminar un presupuesto NO elimina la ficha, ni el calendario, ni el contrato.** Si la obra está viva en el tablero, borrar su presupuesto (a mano o por los 180 días) solo lo saca de la pestaña Presupuestos (marca `fuera_de_presupuestos`). La ficha solo se borra desde el tablero, que manda `desde_tablero: true`. Desde Presupuestos tampoco se puede cambiar el estado de una obra viva (`state.php` lo rechaza).
- **Un número, una persona (18/11/2026).** Antes de escribir datos de un cliente sobre un registro que ya existe, se comprueba que sea la misma persona. La calculadora no guarda un presupuesto encima del N° de otro cliente ni del de una ficha a mano: le da un N° nuevo y avisa. → `01-strategy/decision-log/2026-11-18-un-numero-una-persona.md`
- **Todo nace de Relación-Contrato (18/11/2026, decisión final del dueño).** El tablero ya no tiene "Cargar obra": una obra entra solo con "Cargar en Por medir" desde su contrato, y nace en Por medir, vacía (no hereda etapa ni tildes). Así el contrato y la ficha hacen todo el ciclo enlazados. El tablero avisa arriba "N sin contrato" si queda alguna ficha sin contrato (no debería: son las de la carga inicial). El servidor lo exige: `tablero-cargar.php` no carga nada sin el id de un contrato. → `01-strategy/decision-log/2026-11-18-todo-nace-de-relacion-contrato.md`
- **Lo que une la ficha con su contrato es un código interno, no el N° (18/11/2026).** Toda obra nueva nace con un código interno que no se ve (serie 90001+), tenga o no N° su contrato. El N° de presupuesto es un dato que comparten: se cambia en la ficha ("Editar obra") o en el contrato y cambia en los dos, sin mover la ficha ni romper el enlace. El presupuesto de la calculadora no se toca: sigue en su pestaña, donde figura "En el tablero · etapa" (se busca por N°), y vence a los 180 días como cualquiera. No se carga dos veces una obra con el mismo N°. Las fichas viejas que nacieron de un N° de presupuesto **no se convierten** (dueño, 18/11/2026: es más riesgoso tocarlas que dejar que salgan solas): siguen como estaban, con su contrato asignado, hasta que se archivan. En esas, cambiar el N° todavía es una mudanza. → `01-strategy/decision-log/2026-11-18-codigo-interno.md`
- **Cargar una obra a mano NO crea un presupuesto.** La ficha no aparece en Presupuestos ni se lleva un N° de la calculadora; dice "Sin N°" hasta que se le ponga el de su presupuesto desde "Editar obra", o hasta que se enlace con un contrato que tenga N°: ahí muestra el del contrato, en el tablero y en el calendario (17/11/2026; el dato es del contrato y lo escribe Relación-Contrato). Por dentro usa un número de otra serie (90001 en adelante) que no se muestra; las fichas a mano viejas conservan el suyo, marcadas `manual`. → `04-operations/tablero-obras.md`
- **El enlace contrato-ficha es un dato aparte del N°.** Se enlaza desde Relación-Contrato (Cargar en Por medir, Enlazar por N° o teléfono, o Crear contrato desde la ficha) y se suelta con "Desenlazar", que no borra nada. Un contrato enlazado con una ficha que ya no está lo dice: Archivada, En la papelera o "No existe en el tablero".
- **Enlazados, el contrato y la ficha comparten el N° (17/11/2026).** Una ficha a mano muestra el N° de su contrato; si en el tablero le ponen el N° de un presupuesto ("Editar obra"), el contrato la sigue y toma ese N°. → `04-operations/relacion-contrato.md`
- **"Nombre y apellido" es un solo campo, siempre con ese nombre (17/11/2026).** En Cargar obra, Editar obra, el contrato y la calculadora. → `04-operations/tablero-obras.md`
- **Enlazados, se aprovecha lo que cada lado ya sabe (18/11/2026).** El contrato le completa a la ficha el nombre, el teléfono y la dirección que le falten, sin pisar nunca lo que ya tiene. Dentro del contrato se ve lo del tablero (etapa, medición, entrega, instalación, nota e historial), solo lectura. **Y las fotos y planos de la ficha: plano, bacha y entrega (dueño, 25/11/2026: "lo debo poder ver en la ficha de Relación-Contrato también").** Se ven en miniatura y se abren en grande; también las de una obra archivada, mientras la ficha las guarde (un año). Solo para mirar: se suben, se giran y se borran en el tablero. "Enlazado" es un link a la ficha, como "Con contrato" lo es al contrato. → `04-operations/relacion-contrato.md`
- **Enlazados, la ficha se borra en los dos lados.** Borrar la ficha del tablero borra su contrato enlazado, y borrar el contrato borra su ficha. Los dos avisan antes. Archivar no es borrar: el contrato queda.
- **Sin Relación-Contrato el resto sigue andando, pero no entran obras nuevas (18/11/2026).** Contratos lee del tablero y de `clientes/`; el calendario, el hub y la calculadora **nunca** leen ni escriben nada de contratos. El tablero le pregunta a `contratos.php` qué fichas tienen contrato (marca "Con contrato" y aviso "N sin contrato"), al borrar una ficha le pide borrar su contrato enlazado y, al cambiarle el N°, le avisa para que el contrato la siga; si Relación-Contrato no existe, esas llamadas fallan en silencio y el tablero sigue igual (probado). Lo único que depende de contratos es cargar una obra nueva (`tablero-cargar.php`), por la regla de arriba. → `04-operations/relacion-contrato.md`
- **Nada se borra de una: papelera de 30 días.** Borrar una ficha del tablero o un contrato los manda a la papelera (con tildes, adjuntos y pagos) y se recuperan desde "Papelera". A los 30 días se borran de verdad. Enlazados, van y vuelven juntos.
- **"Verificado" = presupuesto adjunto.** Un contrato lleva el sello solo si tiene el PDF o Excel adjunto. El N° de presupuesto enlaza, pero no verifica (deja de existir a los 180 días).
- **Un contrato está completo con tres cosas:** N° puesto, presupuesto adjunto y enlazado con el tablero. Cada obra viva del tablero tendría que tener su contrato; Relación-Contrato avisa las que no.
- **Enlazado, manda el tablero en lo operativo y Relación-Contrato en la plata.**
- **La seña y la nota de la ficha pasan solas al contrato (19/11/2026; antes "no se pisan nunca").** Cada seña que se carga o se cambia en el tablero viaja una vez a su contrato; la nota de la ficha queda como un renglón al final de la nota del contrato ("Tablero: ..."). No pasa sola una seña en otra moneda que el total del contrato (falta la cotización), ni sobre un contrato que ya tiene pagos, ni cuando las dos ya venían distintas: ahí sigue el aviso. Las reglas viven en un solo lugar: `bs_contrato_traer()` en `contratos.php`. → `01-strategy/decision-log/2026-11-19-la-sena-y-la-nota-pasan-al-contrato.md`
- **Las notas se ven en los dos lados (dueño, 25/11/2026).** "Debería poder ver las notas que pongo en Relación-Contrato, en tablero, y viceversa. Si borro en Relación-Contrato se debe borrar su append correspondiente." La nota del contrato no se guarda en la ficha: el tablero se la pide a `contratos.php` con los enlaces (`bs_contrato_nota_propia()`), así que borrada en el contrato deja de verse. Al revés, si se borra la nota de la ficha, su renglón "Tablero: ..." se va del contrato (hasta ese día quedaba). Lo que vino del tablero no vuelve al tablero. Cada lado conserva su nota; no es una sola nota compartida. No la ve el rol Planos y bacha. El calendario sigue sin leer nada de contratos. → `01-strategy/decision-log/2026-11-25-las-notas-se-ven-en-los-dos-lados.md`
- **En el tablero las notas se leen con "Ver notas", en los tres puntitos de la ficha (dueño, 25/11/2026).** "Así no saturamos la ficha o tenemos que darle más y buscar la nota abajo." Es lo primero del menú y abre una ventana con la nota de la ficha (ahí se escribe) y la de su contrato (se lee). La ficha no muestra ninguna, ni cerrada ni abierta; si tiene alguna, al lado de los tres puntitos va una notita, que al tocarla las abre directo (el dueño la eligió entre tres propuestas, en lugar del punto negro de la primera versión). No volver a poner notas a la vista en la ficha sin preguntarle. La excepción es la ventana de la ficha que abre el calendario, que no tiene ese menú. El campo "Nota" de "Más" sigue estando. → mismo decision log, "Más tarde ese día"
- **El diseño del sistema: serio, simple y claro (19/11/2026).** El color dice una sola cosa cada vez (la etapa tiñe su columna entera, suave, y no cada ficha; rojo, atraso o freno; ámbar, pide una acción; verde, hecho o cobrado); hay un orden de lectura (etapa, tramo de la cola, ficha); lo normal no se repite en cada ficha, se dice una vez arriba; la ficha cerrada no pasa de cuatro renglones (tres desde el 25/11/2026: la nota se lee en "Ver notas"); todo lo que filtra habla igual; no hay descripciones al pasar el mouse. El color de la etapa tiñe, no escribe: ningún texto va en ese color sobre el carril. En una planilla el patrón lo hacen columnas que se ven (en Relación-Contrato, la etapa en su color y la barra de lo cobrado), todas las celdas de un renglón apoyan en la misma línea y lo que no hay queda en blanco: desaturar es sacar el color que no dice nada, no todo el color (dueño, 20/11/2026, sobre la primera versión de contratos: "desabrido, no de captación de patrón rápido y caótico"). Todo el texto llega a 4,5:1 de contraste, medido con un programa en claro y oscuro, computadora, tablet y celular (20/11/2026); un color o un gris nuevo se mide antes de entregarlo. Lo que separa también se tiene que ver y también se mide: entre contratos, una raya; entre días, una franja; dos niveles llevan dos señales distintas, no la misma un poco más fuerte (dueño, 20/11/2026, sobre la segunda versión: "no se nota la separación de una fila de otra, menos los días"; las rayas medían 1,2 y 1,3 a 1). Antes de sumar una etiqueta, un color o un texto a una ficha o a un renglón, releer esas reglas. → `01-strategy/decision-log/2026-11-19-barrido-de-diseno.md`; la investigación detrás, en `06-knowledge/layer-2-landscape/como-se-lee-una-pantalla-de-trabajo.md`
- **Una obra instalada no vence más (19/11/2026).** Con "Instalado" tildado deja de figurar como "Vence" o "Plazo vencido" en el tablero y en el calendario, aunque siga en Por instalar esperando la aprobación.
- **La pestaña "Presupuestos · Reporte" ya no existe (17/11/2026).** Sacaba la plata de los presupuestos, y la plata vive en los contratos. Presupuestos es una sola pestaña. Los números de ventas, cuando hagan falta, salen de Relación-Contrato. → `01-strategy/decision-log/2026-11-17-sin-pestana-reporte.md`
- **"→ Aprobado" ya no existe en Presupuestos.** Si una obra está en el tablero es que fue aprobada. Un presupuesto entra al tablero solo desde su contrato ("Cargar en Por medir"); `state.php` rechaza aprobar desde Presupuestos.
- **Cobro en una moneda, contrato en la otra: lleva cotización.** Cada seña o pago guarda la suya (se propone el dólar del día, se puede cambiar). "Cobrado" es lo que entró de verdad; "Saldo", lo que falta ya convertido.
- **El calendario lee del tablero.** Lo que se enlaza con el tablero queda enlazado con el calendario.
- **El Cronograma del calendario es la ruta de cada día, con una pestaña de Medición y otra de Instalación (18/11/2026).** En orden de hora, con nombre y apellido, dirección, teléfono y color. La hora y el día se cambian ahí y escriben el turno de la ficha en su etapa: no hay un dato nuevo. → `04-operations/tablero-obras.md`
- **Plazo de entrega en el sistema: 20 días corridos desde la medición**, para todos los materiales; ventana de entrega del día 15 al 20. En Por cortar y Por fabricar la cola va por semana de entrega y, dentro de la semana, mismo color junto, **esté cortado o no** (dueño, 27/11/2026: "si son de la misma semana y el mismo material", la que falta tiene que ser la siguiente): el color que ya se empezó va con lo que le falta pegado. **Y el mismo color queda pegado de una semana a la otra** (mismo día: "había una oportunidad para que blanco puro de la semana anterior quede pegado a blanco puro de la siguiente"): cada semana arranca con el color con el que terminó la anterior y termina con uno que sigue en la siguiente; en el medio, el suelto antes que el que son varios. Los ejemplos del dueño están en el decision log y son la prueba: no cambiar ese orden sin volver a correrlos. → `04-operations/tablero-obras.md` y `01-strategy/decision-log/2026-11-16-cola-por-semana-y-color.md`
- **Vendedores y procedencias son listas cerradas** (`_contratos.php`). No inventar nombres en datos de prueba. La procedencia tiene dos opciones (dueño, 20/11/2026): Alberdi y Publicidad/Página. Las cinco de antes se traducen a esas dos al leer ("Alberdi viejo" es Alberdi; todo lo que decía "Publicación", también "Publicación/Alberdi", es Publicidad/Página): no se reescribió ningún contrato. Relación-Contrato filtra por procedencia. → `04-operations/relacion-contrato.md`
- **El buscador del tablero y el de Relación-Contrato encuentran un teléfono como venga (20/11/2026).** "+54 9 11 5555-1234", "9 11 5555-1234", "011 15 5555-1234", "1155551234" o "5555-1234" son el mismo número, además de todo lo que ya buscaban. La función (`telFormas` y `telCoincide`) está copiada igual en las dos páginas y una prueba lo comprueba letra por letra: si se toca una, se toca la otra. El calendario no busca por teléfono, a propósito.
- **Datos de clientes reales nunca van al repo** (nombres, teléfonos, direcciones, planillas exportadas). Tampoco como ejemplo en comentarios o pruebas: usar ficticios (`11 5555-1234`).
- **No se puede ver ni tocar la base de producción desde acá.** Lo que el dueño ve en pantalla solo se conoce por lo que pega o exporta ("Exportar tablero"). No comparar ni actualizar datos reales salvo que lo pida. Las páginas públicas del sitio sí se pueden leer (son públicas), y conviene: antes de un deploy de la landing, bajar la publicada y compararla con el repo. El 24/11/2026 el repo traía un cambio de la landing que nunca se había subido.
- **Cada persona entra con su usuario; hay tres roles (17/11/2026).** Administrador (todo), Operador (todo menos los usuarios y Reportes; en Relación-Contrato crea, edita, cobra, borra y ve la papelera desde el 18/11/2026, sin el resumen del mes) y Planos y bacha (solo el tablero: adjunta y borra fotos y anota el modelo de la bacha; no ve la seña). Qué puede cada rol está en un solo lugar: `AUTH_PERMISOS` en `calculadora/auth_check.php`. → `04-operations/usuarios.md`
- **El permiso lo controla el servidor, no la pantalla.** Todo archivo de `calculadora/api/` empieza con `bs_require_permiso('<permiso>')` y toda página con `auth_exigir_pagina()`. Lo que un rol no puede ver (la seña para Planos y bacha) no sale del servidor. Un endpoint nuevo sin su permiso queda cerrado a administradores.
- **Cada etapa cierra con una aprobación, y queda quién la dio (17/11/2026).** Medición, Corte, Fabricación e Instalación aprobada: el tilde y el historial dicen el nombre. "Medición aprobada" pide "Medido" e "Instalación aprobada" pide "Instalado", que es el botón de Por instalar. → `04-operations/tablero-obras.md`
- **Qué frena el pase de etapa: solo los tildes del trabajo (18/11/2026).** En Por instalar frenan "Instalado" e "Instalación aprobada" (con el resto de sus tildes); **no frenan** el día ni la hora del turno, ni "Turno confirmado". Lo valida el servidor (`tablero.php`, `mover`). → `04-operations/tablero-obras.md`
- **"Turno confirmado" es el primer tilde de Por medir y de Por instalar (18/11/2026).** Dice que el cliente confirmó su turno. Solo se tilda con el día puesto (y la hora, en Por instalar), se cae solo si cambia el día o la hora, y **nunca frena el pase de etapa** (dueño, 18/11/2026). En Por instalar es el tilde que antes se llamaba "Se avisó al cliente". Se tilda en la ficha o desde el Cronograma, que muestra cuántas van confirmadas por día. No hay avisos automáticos ni mensajes al cliente desde el sistema. → `04-operations/tablero-obras.md`
- **Se ve quién está trabajando.** Arriba de cada pantalla, las iniciales de quienes están conectados y en qué pantalla; lo ven los tres roles. El aviso sale del menú (`auth_nav_html`), no de cada página. → `04-operations/usuarios.md`
- **Control (antes "Reportes") es solo para administradores y se puede borrar entera (18/11/2026).** A la izquierda, los recordatorios; a la derecha, el cierre del mes (baja todo en un ZIP, solo el último día del mes); abajo, el registro de acciones del día. Lo único que escribe es el tilde "Hecho" de cada recordatorio, en su propio archivo (`bs-data/control/hechos.json`), que nadie más lee. Nada depende de ella. Desde el 19/11/2026 tiene también la caja "Respaldo", que no es de Control: usa su propio archivo (`copias.php`) y, si Control se borra, las copias se siguen tomando. El archivo y el permiso siguen llamándose `reportes`. → `04-operations/reportes.md`
- **Respaldo: una copia de las últimas 24 horas, que se va pisando (19/11/2026).** El sistema guarda solo una copia de los datos (presupuestos, fichas, contratos e índice de números) por cada hora en que algo cambió; quedan las últimas 25, así que siempre se puede volver, como mínimo, 24 horas atrás. La toma el primer pedido de la hora, antes de cambiar nada; no hay nada que programar en el hosting. Vive en `bs-data/copias/`, fuera de la web. No copia usuarios, contraseñas, el registro ni las fotos. Todo el mecanismo está en un solo lugar (`calculadora/api/_copias.php`); si ese archivo no está, el sistema anda igual, sin copias. → `04-operations/respaldo.md`
- **Al respaldo entran solo los administradores, y regresar pide dos confirmaciones (dueño, 20/11/2026).** El botón, y escribir USAR RESPALDO en mayúsculas. Las dos cosas las exige el servidor (`copias.php`, permiso `copias`), no la pantalla. No sumar otra puerta al respaldo (bajarlo, copiarlo a mano, otro rol) sin preguntarle al dueño.
- **Usar un respaldo vuelve TODO el sistema, y se puede deshacer.** No vuelve una ficha suelta (para eso está la papelera). Antes de pisar nada se guarda sola una copia de cómo estaba todo ("Antes de usar el respaldo"). Los N° de presupuesto y los códigos de ficha dados después de la copia no se vuelven a usar.
- **Un archivo que el sistema borra no se destruye en el momento (19/11/2026).** Fotos, planos, PDF de contratos y ZIP pasan a `bs-data/copias/borrados/` y se van solos cuando ninguna copia los puede necesitar; así un respaldo no devuelve fichas con fotos rotas. Pasa por `bs_copia_borrar()` todo lo que borra una persona (una foto, un plano, el PDF de un contrato, la carpeta de una ficha, el ZIP de una obra que vuelve al tablero o de un presupuesto que se elimina) y las carpetas que vencen; lo único que se borra directo son los ZIP vencidos por antigüedad. En un endpoint nuevo, no borrar esos archivos con `unlink`.
- **El respaldo no reemplaza al cierre del mes.** Vive en el mismo servidor. La copia que se guarda afuera sigue siendo el ZIP del último día del mes.
- **Los recordatorios se hacen adentro del sistema, y sin ruido (18/11/2026).** Viven en Control y no se guardan: se calculan leyendo el tablero. Un recordatorio existe solo si tiene fecha, si alguien lo puede hacer hoy y si desaparece al hacerse; lo que es un estado (atrasadas, plazo vencido, sin contrato) queda en el tablero. **Hoy se muestran solo dos (dueño, 18/11/2026: "por los momentos, solo aviso de confirmación medir y aviso de confirmación instalar; nada más"):** confirmar los turnos de un día de medición y de un día de instalación. Aparecen dos días hábiles antes, quedan hasta ese día inclusive, y abajo dicen qué fichas faltan confirmar (nombre y N°, con link a la ficha). Hay otros once escritos y probados pero apagados (`$BS_CONTROL_PRENDIDOS` en `control.php`): datos del turno, lo que ya pasó sin marcarse, dar turno, plazo en el taller, placa, pasar a cortar, instaladas sin cerrar, seña, contratos sin cargar, presupuesto por vencer y cierre del mes. Ninguno trae teléfonos ni plata. Para prender uno o sumar otro, primero se le pregunta al dueño. → `04-operations/reportes.md`
- **En el registro no se escribe nunca una contraseña, un nombre de cliente ni plata.** Si hace falta anotar un dato nuevo, que no se llame `clave`.
- **Los usuarios viven en `bs-data/usuarios/`, fuera del repo.** Las contraseñas nunca van al repo, a un chat ni a un ejemplo.
- **"Un número, una persona" vale en todos los caminos (18/11/2026).** La regla vive en un solo lugar (`bs_misma_persona()` en `_config.php`) y la usan la calculadora, "Editar obra" (cambiarle el N° a una ficha) y "Cargar en Por medir" (si el N° del contrato es el presupuesto de otra persona, no se carga y se avisa). Un teléfono de relleno no es un teléfono (20/11/2026): si los últimos 8 dígitos son todos iguales ("111111111"), cuenta como que falta y decide el nombre; antes frenaba la carga de la misma persona y, al revés, hacía pasar por la misma a dos clientes distintos con el mismo relleno. Con dos teléfonos de verdad y distintos sigue frenando aunque el nombre sea igual, y el cartel muestra los dos. No sumar un botón "es la misma persona, cargar igual" sin preguntarle al dueño. → `01-strategy/decision-log/2026-11-20-telefono-de-relleno.md`
- **Archivar lo valida el servidor (18/11/2026).** Solo se archiva una obra que está en Por archivar con "Saldo cobrado". Y eliminar de Presupuestos una obra archivada no la borra: la saca de la pestaña y sigue en "Archivadas" su año.
- **La hora es la de Argentina en todo el sistema (18/11/2026).** La fija `_config.php`; el servidor corre en UTC.
- **El registro anota lo que se hizo, no lo que se rechazó (18/11/2026).** Se escribe al terminar la acción, y solo si salió bien.
- **Cada cambio se prueba antes de entregar** (regresión del tablero, pruebas del calendario y de contratos, en computadora, tablet y celular) y se entrega con su bloque de deploy.

---

## 6. Estado actual del negocio (snapshot mayo 2026)

- **+1.000 mesadas colocadas** en CABA y GBA. *(Actualizado jun 2026 — dato directo del dueño; el snapshot de mayo decía +500.)*
- **Zona de operación:** CABA + GBA, sale de Lanús para mediciones.
- **Meta Ads:** $1.000 USD/mes (2 campañas de $14/día — 1 video + 1 static, ambas 1-1-1).
- **Leads de hoy:** ~25 buenos por WhatsApp.
- **Funnel actual:** ad → click WhatsApp → cotización 24 hs → cierre.
- **Sitio web:** `blackstones.com.ar` en Hostinger, dominio en donweb.
- **La sección "Lo último que salió del taller" de la landing muestra las fotos que pasa el dueño: hoy son 31 (24/11/2026).** Las sube al repo o las manda por el chat, y se suman a mano, **sin sacar las que están y sin repetir ninguna** (dueño, 24/11/2026: "mosca que no hayan repetidas": antes de sumar una se compara con todas las publicadas). Se suman de a una: no hay cuenta que hacer. **Se ven de a seis, grandes, en todos los tamaños de pantalla, con un paginado debajo** ("01 / 06", una rayita y dos flechas). **En computadora las tandas pasan solas; en el celular y en la tablet nada se mueve solo: se pasan con las flechas** (dueño, 24/11/2026: "en celular que no se mueva, mejor; en PC podemos hacer una animación más elaborada"; y más tarde, del paginado de computadora: "esto no estaría mal en cel tampoco"). **En computadora la sección es una doble página, dentro del ancho del sitio: las seis fotos a la izquierda y las reseñas al lado** (dueño, mismo día, después de cuatro versiones: las doce juntas quedaban "apretujadísimo"; con la sección ensanchada "se perdió la cohesión"; con las reseñas debajo, "no me gustó"). Esta la aprobó al verla publicada: "me encantó". No volver a mostrar todas las fotos juntas, ni a ensanchar la sección, ni a apilar las reseñas en computadora, ni a hacer que en el celular pasen solas, sin preguntarle. Mostrar solos los últimos posts de @blackstones.ar (`ig-feed.php`) quedó en pausa ese mismo día: la llave no se pudo generar porque el panel de Meta ya no ofrece ese camino, y el dueño dijo "después vemos". Ese código no está en la landing ni en el servidor. → `04-operations/instagram-en-la-landing.md`
- **`/trabajos/` (nov 2026):** galería de obras, cada foto con la textura de su material; tocar una foto abre la página de ese material con su colección. Solo los materiales de los que el dueño sube fotos, y con textos cortos ("que el cliente entienda en menos de 10 segundos"). Las páginas las arma un programa; para sumar un trabajo: `04-operations/trabajos/README.md`. **El estándar** (las fotos llegan cuadradas, 1:1; medidas, textos y lo que se comprueba solo): `04-operations/trabajos/estandar.md`. **Es otra página: no es la sección "Trabajos" de la landing ni va adentro de `index.html`** (dueño, 24/11/2026: "trabajos no es el mismo trabajos de la página principal"; "es otra página, obviamente dentro del mismo ecosistema y universo"). **Publicada el 24/11/2026** (se entregó el bloque de deploy; desde acá no se ve producción). Se llega por el link "Ver más trabajos →" de la sección Trabajos de la landing (bloque entregado el 24/11/2026); el resto de esa sección no se toca. **Las fotos de clientes van sin personas y sin nada que diga de quién es la casa, y si un cliente pide que se saque, se saca ese día** (el dueño: "si lo peor que puede pasar primero es que exijan quitar su foto, banco; si es una denuncia, no banco"; lo que se encontró de la ley, que es una hipótesis, está en `06-knowledge/layer-2-landscape/publicar-fotos-de-casas-de-clientes.md`). Para mostrarle un cambio antes de subirlo hay una vista previa en un solo archivo (`vista.py`).
- **Calculadora interna:** `blackstones.com.ar/calculadora/` (cada persona con su usuario desde nov 2026; antes, contraseña compartida).
- **Sistema interno (nov 2026), con usuarios y roles:** Presupuestos (`/presupuestos/`), Tablero de obras (`/presupuestos/tablero.php`), Calendario (`/presupuestos/calendario.php`), Relación-Contrato (`/presupuestos/relacion-contrato.php`), Stock (en construcción) y Control (`/presupuestos/reportes.php`, solo administradores; antes "Reportes"; ahí está también el Respaldo). Docs: `04-operations/tablero-obras.md`, `04-operations/relacion-contrato.md`, `04-operations/usuarios.md`, `04-operations/reportes.md` y `04-operations/respaldo.md`.

---

## 7. Qué NO hacer en este repo

- No subir credenciales (FTP, DB, secrets) a archivos versionados. Usar `.env` (gitignorado).
- No commitear el `auth_config.php` de la calc rotado a producción sin avisar.
- No reescribir `06-knowledge/layer-0-raw/` — solo agregar.
- No inventar avatares, métricas o frameworks. Si no existe data, marcar `status: hypothesis`.
- No usar emojis salvo donde el sistema visual los autorice. ◾ y 📐 sí. 🔥 🚨 🙌 no.
- No documentar lo obvio. Si "se entiende del nombre del archivo", no escribas un README de 200 líneas.

---

## 8. Cuándo se actualiza este doc

- Cambia la filosofía operativa.
- Cambia la estructura de carpetas (ej: sumamos `07-finance/` algún día).
- Cambian las reglas duras del negocio (ej: ya garantizamos granito, ya coordinamos plomería).
- Default: revisión cada 90 días aunque no haya cambio aparente.

---

*Última actualización: noviembre 2026 (reglas del sistema interno, sección 5 bis; papelera, cotización, contrato completo; fichas a mano sin presupuesto y desenlazar; seña sin pisar, plazo de 20 días; sin pestaña Reporte; usuarios y roles; cómo cotizamos; enlazados comparten el N°; archivadas 1 año; nombre y apellido; lo que comparten contrato y ficha; cronograma de medición e instalación; un número, una persona; Reportes y cierre del mes; todo nace de Relación-Contrato, el Operador edita contratos y el barrido de errores del 18/11; código interno de la ficha y N° como dato compartido; tilde "Turno confirmado"; Reportes pasa a llamarse Control, con recordatorios; trece recordatorios escritos, prendidos solo los dos de confirmar turnos; buscador e historial en el calendario; qué frena el pase de etapa; menú de tres puntitos y tiempo por etapa en la ficha; la nota se guarda sola; vista por etapa en computadora; la seña y la nota pasan al contrato, instalada no vence, "Falta confirmar" en la ficha cerrada y filtro por día de instalación en el calendario; barrido de diseño, con el contraste medido; respaldo de las últimas 24 horas, solo administradores y con dos confirmaciones; Relación-Contrato rehecha con columnas que se leen de un vistazo; la separación entre contratos y entre días, medida; un teléfono de relleno no es un teléfono; procedencia en dos opciones, con filtro; el buscador encuentra el teléfono como venga; el primer mensaje del chat ("la vieja confiable"), a quién se le trabaja la cotización y los ejemplos de respuestas para la IA de WhatsApp; la sección Instagram de la landing, preparada para los últimos posts y en pausa; la página Trabajos, su estándar y su link desde la landing; las fotos que pasa el dueño en la landing: 31, sin repetir, de a seis en toda pantalla, con paginado (solas en computadora, con las flechas en el celular); el hero, con su contraste medido; cómo se sigue después del presupuesto: la seña del 50 % habilita la visita, 20 días para entregar y 12 para retirar; las fotos y planos de la ficha se ven en el contrato; las notas del contrato y de la ficha se ven en los dos lados, y en el tablero se leen con "Ver notas", en los tres puntitos; al cliente no se le dice "presupuesto cerrado"; en la cola, el mismo color va junto también con lo ya cortado y pegado de una semana a la otra; el otro 50 % se paga con la entrega e instalación; la bacha la entrega el cliente el día de la medición; qué cubre la garantía del granito). Próxima revisión obligatoria: febrero 2027.*
