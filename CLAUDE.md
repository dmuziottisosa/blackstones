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

### Si dudás dónde poner algo
Preguntá antes de adivinar. Una sola fuente de verdad por tema. El resto cross-linkea con paths relativos.

---

## 5. Reglas duras de este negocio (memorizar)

- **No mencionar VenarStones** en ninguna comunicación pública. (Rebrand 2026, separación de socios — interno.)
- **Plazo que se promete: 20 días corridos desde la medición**, para todos los materiales (dueño, 17/11/2026; antes "15-20"). **Nunca decir "10 días".**
- **No coordinamos gremios** (carpinteros, plomeros). El cliente ya los tiene.
- **Medimos cuando los muebles ya están puestos.** Es un filtro, no una limitación.
- **Presupuesto cerrado por escrito en 24 hs por WhatsApp.** Es el mecanismo diferenciador.
- **Granito sin garantía** se dice explícito. Suma confianza, no resta.
- **Avatar principal: Carolina** (38-55, ABC1, en obra). 80% del presupuesto va a ella.
- **Tono:** rioplatense, "vos", sin jerga, conversacional. Nada de marketing de manual.
- **Test de Living:** ¿podría aparecer en La Nación Living sin desentonar?

---

## 5 bis. Reglas duras del sistema interno (tablero, calendario, contratos)

> Fijadas por el dueño el 16/11/2026. Si vas a tocar algo de `site/public_html/presupuestos/` o `calculadora/api/`, leelas antes. El detalle y el porqué están en los docs que se citan.

- **La etapa de una obra la dicta el tablero.** Los estados del presupuesto ya no mandan. Que no la dicte no quiere decir que no se vea: en Presupuestos cada obra viva muestra su etapa del tablero y se puede filtrar por ella (`list.php` lee el tablero, no lo toca). Por dentro, una obra del tablero es una cotización "aprobado"; archivarla la pasa a "entregado" y queda en "Archivadas" del tablero, desde donde se devuelve. → `01-strategy/decision-log/2026-11-16-la-etapa-la-dicta-el-tablero.md`
- **El presupuesto dura 180 días** en la pestaña Presupuestos y después se borra solo (`cleanup-retention.php`). **Una obra viva en el tablero no vence nunca**; su reloj empieza cuando se archiva. **Archivada dura 1 año** (17/11/2026; antes 180 días), con sus fotos y planos; después se borra de verdad, sin papelera (`BS_ARCHIVADAS_DAYS`). No tocar esa limpieza sin probarla con datos envejecidos.
- **Eliminar un presupuesto NO elimina la ficha, ni el calendario, ni el contrato.** Si la obra está viva en el tablero, borrar su presupuesto (a mano o por los 180 días) solo lo saca de la pestaña Presupuestos (marca `fuera_de_presupuestos`). La ficha solo se borra desde el tablero, que manda `desde_tablero: true`. Desde Presupuestos tampoco se puede cambiar el estado de una obra viva (`state.php` lo rechaza).
- **Cargar una obra a mano NO crea un presupuesto.** La ficha no aparece en Presupuestos ni se lleva un N° de la calculadora; dice "Sin N°" hasta que se le ponga el de su presupuesto desde "Editar obra", o hasta que se enlace con un contrato que tenga N°: ahí muestra el del contrato, en el tablero y en el calendario (17/11/2026; el dato es del contrato y lo escribe Relación-Contrato). Por dentro usa un número de otra serie (90001 en adelante) que no se muestra; las fichas a mano viejas conservan el suyo, marcadas `manual`. → `04-operations/tablero-obras.md`
- **El enlace contrato-ficha es un dato aparte del N°.** Se enlaza desde Relación-Contrato (Cargar en Por medir, Enlazar por N° o teléfono, o Crear contrato desde la ficha) y se suelta con "Desenlazar", que no borra nada. Un contrato enlazado con una ficha que ya no está lo dice: Archivada, En la papelera o "No existe en el tablero".
- **Enlazados, el contrato y la ficha comparten el N° (17/11/2026).** Una ficha a mano muestra el N° de su contrato; si en el tablero le ponen el N° de un presupuesto ("Editar obra"), el contrato la sigue y toma ese N°. → `04-operations/relacion-contrato.md`
- **Enlazados, la ficha se borra en los dos lados.** Borrar la ficha del tablero borra su contrato enlazado, y borrar el contrato borra su ficha. Los dos avisan antes. Archivar no es borrar: el contrato queda.
- **Relación-Contrato se tiene que poder borrar entera sin que se caiga nada.** Contratos lee del tablero y de `clientes/`; el calendario, el hub y la calculadora **nunca** leen ni escriben nada de contratos. Única excepción: la página del tablero le pregunta a `contratos.php` qué fichas tienen contrato (para la marca "Con contrato" de la ficha abierta), al borrar una ficha le pide borrar su contrato enlazado y, al cambiarle el N°, le avisa para que el contrato la siga; si Relación-Contrato no existe, esas llamadas fallan en silencio y el tablero sigue igual (probado). → `04-operations/relacion-contrato.md`
- **Nada se borra de una: papelera de 30 días.** Borrar una ficha del tablero o un contrato los manda a la papelera (con tildes, adjuntos y pagos) y se recuperan desde "Papelera". A los 30 días se borran de verdad. Enlazados, van y vuelven juntos.
- **"Verificado" = presupuesto adjunto.** Un contrato lleva el sello solo si tiene el PDF o Excel adjunto. El N° de presupuesto enlaza, pero no verifica (deja de existir a los 180 días).
- **Un contrato está completo con tres cosas:** N° puesto, presupuesto adjunto y enlazado con el tablero. Cada obra viva del tablero tendría que tener su contrato; Relación-Contrato avisa las que no.
- **Enlazado, manda el tablero en lo operativo y Relación-Contrato en la plata.**
- **La seña del tablero y la del contrato no se pisan nunca.** Si difieren, hay un aviso en Relación-Contrato y nada más; cada una se corrige a mano. Nada cambia plata solo.
- **La pestaña "Presupuestos · Reporte" ya no existe (17/11/2026).** Sacaba la plata de los presupuestos, y la plata vive en los contratos. Presupuestos es una sola pestaña. Los números de ventas, cuando hagan falta, salen de Relación-Contrato. → `01-strategy/decision-log/2026-11-17-sin-pestana-reporte.md`
- **"→ Aprobado" ya no existe en Presupuestos.** Si una obra está en el tablero es que fue aprobada. Un presupuesto entra al tablero desde el tablero ("Cargar obra" con su N°) o desde su contrato ("Cargar en Por medir"); `state.php` rechaza aprobar desde Presupuestos.
- **Cobro en una moneda, contrato en la otra: lleva cotización.** Cada seña o pago guarda la suya (se propone el dólar del día, se puede cambiar). "Cobrado" es lo que entró de verdad; "Saldo", lo que falta ya convertido.
- **El calendario lee del tablero.** Lo que se enlaza con el tablero queda enlazado con el calendario.
- **Plazo de entrega en el sistema: 20 días corridos desde la medición**, para todos los materiales; ventana de entrega del día 15 al 20. En Por cortar y Por fabricar la cola va por semana de entrega y, dentro de la semana, mismo color junto. → `04-operations/tablero-obras.md`
- **Vendedores y procedencias son listas cerradas** (`_contratos.php`). No inventar nombres en datos de prueba.
- **Datos de clientes reales nunca van al repo** (nombres, teléfonos, direcciones, planillas exportadas). Tampoco como ejemplo en comentarios o pruebas: usar ficticios (`11 5555-1234`).
- **No se puede ver ni tocar la base de producción desde acá.** Lo que el dueño ve en pantalla solo se conoce por lo que pega o exporta ("Exportar tablero"). No comparar ni actualizar datos reales salvo que lo pida.
- **Cada persona entra con su usuario; hay tres roles (17/11/2026).** Administrador (todo), Operador (todo menos los usuarios; Relación-Contrato solo la mira, con la plata de cada contrato pero sin el resumen del mes) y Planos y bacha (solo el tablero: adjunta y borra fotos y anota el modelo de la bacha; no ve la seña). Qué puede cada rol está en un solo lugar: `AUTH_PERMISOS` en `calculadora/auth_check.php`. → `04-operations/usuarios.md`
- **El permiso lo controla el servidor, no la pantalla.** Todo archivo de `calculadora/api/` empieza con `bs_require_permiso('<permiso>')` y toda página con `auth_exigir_pagina()`. Lo que un rol no puede ver (la seña para Planos y bacha) no sale del servidor. Un endpoint nuevo sin su permiso queda cerrado a administradores.
- **Cada etapa cierra con una aprobación, y queda quién la dio (17/11/2026).** Medición, Corte, Fabricación e Instalación aprobada: el tilde y el historial dicen el nombre. "Medición aprobada" pide "Medido" e "Instalación aprobada" pide "Instalado", que es el botón de Por instalar. → `04-operations/tablero-obras.md`
- **Se ve quién está trabajando.** Arriba de cada pantalla, las iniciales de quienes están conectados y en qué pantalla; lo ven los tres roles. El aviso sale del menú (`auth_nav_html`), no de cada página. → `04-operations/usuarios.md`
- **Los usuarios viven en `bs-data/usuarios/`, fuera del repo.** Las contraseñas nunca van al repo, a un chat ni a un ejemplo.
- **Cada cambio se prueba antes de entregar** (regresión del tablero, pruebas del calendario y de contratos, en computadora, tablet y celular) y se entrega con su bloque de deploy.

---

## 6. Estado actual del negocio (snapshot mayo 2026)

- **+1.000 mesadas colocadas** en CABA y GBA. *(Actualizado jun 2026 — dato directo del dueño; el snapshot de mayo decía +500.)*
- **Zona de operación:** CABA + GBA, sale de Lanús para mediciones.
- **Meta Ads:** $1.000 USD/mes (2 campañas de $14/día — 1 video + 1 static, ambas 1-1-1).
- **Leads de hoy:** ~25 buenos por WhatsApp.
- **Funnel actual:** ad → click WhatsApp → cotización 24 hs → cierre.
- **Sitio web:** `blackstones.com.ar` en Hostinger, dominio en donweb.
- **Calculadora interna:** `blackstones.com.ar/calculadora/` (cada persona con su usuario desde nov 2026; antes, contraseña compartida).
- **Sistema interno (nov 2026), con usuarios y roles:** Presupuestos (`/presupuestos/`), Tablero de obras (`/presupuestos/tablero.php`), Calendario (`/presupuestos/calendario.php`), Relación-Contrato (`/presupuestos/relacion-contrato.php`) y Stock (en construcción). Docs: `04-operations/tablero-obras.md`, `04-operations/relacion-contrato.md` y `04-operations/usuarios.md`.

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

*Última actualización: noviembre 2026 (reglas del sistema interno, sección 5 bis; papelera, cotización, contrato completo; fichas a mano sin presupuesto y desenlazar; seña sin pisar, plazo de 20 días; sin pestaña Reporte; usuarios y roles; cómo cotizamos; enlazados comparten el N°; archivadas 1 año). Próxima revisión obligatoria: febrero 2027.*
