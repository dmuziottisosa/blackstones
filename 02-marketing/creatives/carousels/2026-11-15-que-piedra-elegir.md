# Carrusel — "¿Granito, cuarzo, sinterizado o mármol?"

> **Status:** `borrador listo para generar` · 2026-11-15
> **Formato:** carrusel de Instagram, 7 placas, vertical.
> **Herramienta:** GPT Image 2 en Higgsfield, generado a mano. La placa 1 aprobada se adjunta como referencia de estilo en las demás.
> **Guía de prompts usada:** `05-ai-systems/tools/gpt-image-guide.md` (síntesis de la documentación oficial de OpenAI).
> **Pie:** según `02-marketing/copywriting/frameworks/captions-blackstones-framework.md`.

---

## 1. Verificación de lo que afirma cada placa

Nada va a la placa sin fuente. Los precios salen de la lista propia cargada en la calculadora (684 colores, agosto 2026).

| Afirmación | Veredicto | Fuente |
|---|---|---|
| Granito: el más económico | Se sostiene | Lista propia: granito nacional ARS 220.000 a 346.000 el m²; importado, mediana USD 415 |
| Granito: duro y resistente | Se sostiene | Natural Stone Institute: piedra silícea, "generalmente resistente a la mayoría de los ácidos de una cocina" |
| Granito: los claros tienden a manchar | Se sostiene, con matiz | Guías de fabricantes: los claros suelen ser más porosos y la mancha se ve más. No encontré el documento primario del NSI. **El carrusel no habla de sellado** (decisión del dueño, 2026-11-15) |
| Cuarzo: precio medio | Depende de la marca | Lista propia: Purastone mediana USD 640 y Guidoni USD 623; Silestone mediana USD 1.272 |
| Cuarzo: menos poroso | Se sostiene | Caesarstone: superficie "altamente no porosa" y "no absorbente" |
| Cuarzo: no apoyar calor directo | Se sostiene | Caesarstone: "siempre apoyar los utensilios calientes sobre un apoyaplato". Doc propio: no tolera más de 150 °C |
| Cuarzo: viene en más colores | Sin fuente externa | Es un material fabricado; lo afirma el dueño. Va como "muchos colores" |
| Sinterizado: suele costar más | Depende de la marca | Lista propia: Suprastone y Xtone USD 617 (igual que un cuarzo); Neolith mediana USD 1.219; Dekton USD 1.755 |
| Sinterizado: resiste rayas, manchas y calor | Se sostiene | Fabricantes y distribuidores: porosidad menor a 0,1 %, dureza Mohs 7-8, tolera ollas calientes. No pude abrir la página oficial de Cosentino (error del sitio) |
| Mármol: se opaca con ácidos | Se sostiene | Natural Stone Institute: productos con "limón, vinagre u otros ácidos pueden opacar o marcar las piedras calcáreas" |
| Mármol: se mancha | Se sostiene | NSI: sellar "no vuelve la piedra a prueba de manchas, la hace más resistente" |
| Mármol: se usa más en baños y revestimientos | Se sostiene (criterio propio) | `06-knowledge/layer-1-synthesis/2026-05-recomendaciones-material-por-uso.md` |
| Mármol: suele tener alto costo | **No así nomás** | Lista propia: mediana USD 812, pero va de USD 194 a USD 7.312. Hay Carrara desde USD 406, más barato que muchos cuarzos. En la placa va "el precio varía mucho" |

**Pendiente de confirmar con el dueño (`status: hypothesis`):** la debilidad del sinterizado. El doc propio dice "más frío a la vista que la piedra natural"; una fuente de fabricantes dice que puede saltarse con un golpe fuerte en el borde. La placa usa la primera, que es la ya validada internamente.

---

## 2. Texto de cada placa

> **Dirección (decisión del dueño, 2026-11-15):** paleta clara, texto negro, composición moderna. Se probó una versión minimalista de una sola frase por placa y se descartó. **No se habla de sellar el granito.**

| # | Título | Texto |
|---|---|---|
| 1 · Portada | ¿Granito, cuarzo, sinterizado o mármol? | Lo que tenés que saber antes de elegir tu mesada. |
| 2 · La confusión | Todas parecen piedra. No se comportan igual. | Una se opaca con limón. / Otra no quiere la olla caliente encima. / Otra aguanta casi todo. — Conocelas una por una. |
| 3 · Granito | Granito | El más económico de los cuatro. / Duro y resistente al uso de todos los días. / La verdad: los claros tienden a mancharse. |
| 4 · Cuarzo | Cuarzo | Precio medio, según la marca. / No es poroso: no absorbe manchas. / La verdad: no le apoyes la olla recién sacada del fuego. Usá apoyaplato. |
| 5 · Sinterizado | Sinterizado | Desde el precio de un cuarzo hasta bastante más. / Resiste el calor, las rayas y las manchas. / La verdad: a la vista es más frío que una piedra natural. |
| 6 · Mármol | Mármol | El clásico. El más conocido: Carrara. / En cocina se usa poco: el limón y el vinagre lo opacan, y se mancha. / Donde se luce: baños, vanitorys y revestimientos. |
| 7 · Cierre | ¿Cuál va con tu cocina? | Mandanos las medidas por WhatsApp. / En 24 hs tenés el presupuesto cerrado por escrito. |

---

## 3. Prompts

### Antes de empezar

- **Proporción:** elegir 4:5 si Higgsfield la ofrece; si no, 3:4 o 2:3. No sé qué proporciones muestra tu pantalla.
- **Calidad:** la más alta disponible, por el texto.
- **Logo:** no se le pide al modelo. Se agrega después con el archivo real.
- **Orden:** generar la placa 1, aprobarla, y adjuntarla como Imagen 1 en todas las demás.

### Bloque ESTILO (va pegado entero al final de cada prompt)

```
ESTILO (igual en todas las placas de la serie):
- Pieza gráfica editorial contemporánea para carrusel de Instagram, formato vertical.
- Paleta clara: fondo crema muy claro #FAF8F4 en toda la placa. Ningún bloque ni fondo oscuro.
- Todo el texto en negro cálido #1A1816. El dorado #C4A77D se usa solo en líneas finas y en los números grandes, nunca en texto chico.
- Composición moderna y asimétrica: texto alineado a la izquierda, gran contraste de tamaño entre el título enorme y el texto chico, la foto corrida hacia un borde y saliéndose de la placa.
- Títulos en tipografía serif editorial de alto contraste, estilo Fraunces, muy grandes, con interlineado apretado y mayúscula solo en la inicial.
- Textos en tipografía sans geométrica limpia, estilo Manrope, en peso regular. Nada escrito todo en mayúsculas.
- Diseño plano: sin gradientes, sin brillos, sin sombras pesadas, sin neón, sin efectos 3D, sin recuadros de colores.
- Fotos con esquinas apenas redondeadas. Una línea dorada fina como adorno.
- Todo el texto dentro del 84% central de la placa.
- Estética de revista de arquitectura y diseño actual.

RESTRICCIONES:
- Escribí cada texto exactamente una vez, tal cual está entre comillas, con sus tildes y signos.
- No agregues ningún otro texto, número, logo, ícono, marca de agua ni firma.
- No dibujes personas ni manos.
```

### Placa 1 — Portada

Sin referencia de estilo: esta placa define el universo. Si tenés las cuatro fotos de piedra, adjuntalas y usá la variante de abajo.

```
RESULTADO: portada de un carrusel de Instagram para una marmolería. Tiene que frenar el scroll con una pregunta. Paleta clara y texto negro.

ESCENA: fondo crema muy claro #FAF8F4. En la parte superior, cuatro muestras de piedra con forma de rectángulo vertical alto y esquinas apenas redondeadas, puestas en fila y escalonadas: cada una arranca un poco más abajo que la anterior, y la última se sale por el borde derecho de la placa. Cada muestra es una foto fotorrealista de una piedra distinta, vista de frente y con luz pareja:
1) granito gris moteado, de grano fino
2) cuarzo blanco liso y parejo
3) sinterizado blanco con una veta gris muy fina
4) mármol Carrara blanco con vetas grises suaves

Debajo de las muestras, una línea dorada fina y corta alineada a la izquierda.

TEXTO, en negro cálido #1A1816, alineado a la izquierda, en la mitad inferior:
- Título enorme en tres renglones: "¿Granito, cuarzo, sinterizado o mármol?"
- Debajo, mucho más chico: "Lo que tenés que saber antes de elegir tu mesada."

[pegar acá el bloque ESTILO y RESTRICCIONES]
```

**Variante con tus fotos:** reemplazar la lista de las cuatro piedras por:

```
Cada muestra usa una de las fotos adjuntas, en este orden: Imagen 1 granito, Imagen 2 cuarzo, Imagen 3 sinterizado, Imagen 4 mármol. Usá la textura y el color reales de cada foto. No rediseñes ni retoques la piedra.
```

### Placa 2 — La confusión

Adjuntar: Imagen 1 = la portada aprobada.

```
RESULTADO: segunda placa del mismo carrusel. Es una placa tipográfica, de solo texto. Paleta clara y texto negro.

REFERENCIA: Imagen 1 es la portada de esta serie. Usala como referencia de estilo: mismo fondo crema, mismo texto negro, mismas tipografías, mismos márgenes y la misma línea dorada. No copies su composición ni sus fotos.

ESCENA: fondo crema muy claro #FAF8F4. Sin fotos. El título ocupa los dos tercios superiores; el resto queda abajo.

TEXTO, en negro cálido #1A1816, todo alineado a la izquierda:
- Título enorme en cuatro renglones: "Todas parecen piedra. No se comportan igual."
- Una línea dorada fina y corta debajo del título.
- Tres renglones chicos, uno debajo del otro:
  "Una se opaca con limón."
  "Otra no quiere la olla caliente encima."
  "Otra aguanta casi todo."
- Abajo de todo, separado del resto: "Conocelas una por una."

[pegar acá el bloque ESTILO y RESTRICCIONES]
```

### Placas 3 a 6 — Una por material

Adjuntar en cada una: Imagen 1 = la portada aprobada · Imagen 2 = tu foto de esa piedra.

Plantilla (cambiar solo lo que está entre corchetes):

```
RESULTADO: placa [3 / 4 / 5 / 6] del mismo carrusel. Presenta un material de mesada. Paleta clara y texto negro.

REFERENCIAS:
- Imagen 1 es la portada de esta serie. Usala como referencia de estilo: mismo fondo crema, mismo texto negro, mismas tipografías, mismos márgenes y la misma línea dorada. No copies su composición.
- Imagen 2 es una foto real de la piedra. Es el sujeto: usá su textura, su color y sus vetas tal cual. No rediseñes la piedra, no le cambies el tono ni le agregues brillo.

ESCENA: fondo crema muy claro #FAF8F4. La foto de la piedra (Imagen 2) es un rectángulo grande con esquinas apenas redondeadas, corrido hacia arriba y a la derecha: se sale de la placa por el borde superior y por el borde derecho, y ocupa cerca de la mitad de la altura. A la izquierda de la foto, en el espacio libre, un número muy grande y fino en dorado #C4A77D: "[01]". Debajo de la foto, una línea dorada fina.

TEXTO, en la mitad inferior, en negro cálido #1A1816, alineado a la izquierda:
- Título enorme: "[Granito]"
- Bajada, mediana: "[El más económico de los cuatro.]"
- Dos renglones chicos, uno debajo del otro:
  "[Duro y resistente al uso de todos los días.]"
  "[La verdad: los claros tienden a mancharse.]"

[pegar acá el bloque ESTILO y RESTRICCIONES]
```

Textos para reemplazar en cada placa:

| Placa | Número | Título | Bajada | Renglón 1 | Renglón 2 |
|---|---|---|---|---|---|
| 3 | 01 | Granito | El más económico de los cuatro. | Duro y resistente al uso de todos los días. | La verdad: los claros tienden a mancharse. |
| 4 | 02 | Cuarzo | Precio medio, según la marca. | No es poroso: no absorbe manchas. | La verdad: no le apoyes la olla recién sacada del fuego. Usá apoyaplato. |
| 5 | 03 | Sinterizado | Desde el precio de un cuarzo hasta bastante más. | Resiste el calor, las rayas y las manchas. | La verdad: a la vista es más frío que una piedra natural. |
| 6 | 04 | Mármol | El clásico. El más conocido: Carrara. | En cocina se usa poco: el limón y el vinagre lo opacan, y se mancha. | Donde se luce: baños, vanitorys y revestimientos. |

### Placa 7 — Cierre

Adjuntar: Imagen 1 = la portada aprobada.

```
RESULTADO: última placa del mismo carrusel. Invita a escribir por WhatsApp. Es una placa tipográfica. Paleta clara y texto negro.

REFERENCIA: Imagen 1 es la portada de esta serie. Usala como referencia de estilo: mismo fondo crema, mismo texto negro, mismas tipografías, mismos márgenes y la misma línea dorada. No copies sus fotos.

ESCENA: fondo crema muy claro #FAF8F4. Sin fotos y sin bloques oscuros. El título queda en el tercio superior; el texto chico, abajo a la izquierda.

TEXTO, en negro cálido #1A1816, alineado a la izquierda:
- Título enorme en dos renglones: "¿Cuál va con tu cocina?"
- Una línea dorada fina y corta debajo del título.
- Dos renglones chicos:
  "Mandanos las medidas por WhatsApp."
  "En 24 hs tenés el presupuesto cerrado por escrito."

[pegar acá el bloque ESTILO y RESTRICCIONES]
```

### Si una placa sale mal

La guía oficial pide corregir de a una cosa: adjuntar la placa fallida y pedir un solo cambio, repitiendo lo que tiene que quedar igual.

```
Imagen 1 es la placa a corregir. Cambiá solo esto: [el texto "X" tiene que decir exactamente "Y"]. Dejá igual todo lo demás: composición, foto, colores, tipografías y el resto de los textos.
```

---

## 4. Revisión antes de publicar

1. Cada palabra se lee y coincide con la tabla de la sección 2, con tildes y signos de pregunta.
2. No hay texto, logo ni ícono de más.
3. La piedra de cada placa es la de tu foto, sin retoques.
4. Fondo claro y texto negro en las 7 placas, sin gradientes ni brillos, y pasa el test de Living (`00-brand/visual-identity/visual-system.md` § 6).
5. En ninguna placa ni en el pie se habla de sellar el granito.
6. El logo, si va, se agrega después con el archivo real.

---

## 5. Pie de la publicación

```
Cuatro piedras. Ninguna es mejor: cada una sirve para otra cosa.

Granito. Cuarzo. Sinterizado. Mármol.
Deslizá y mirá cuál aguanta lo que pasa en tu cocina.

Elegir bien no es elegir la más cara.
Es elegir la que va con cómo cocinás.

Si seguís con dudas, escribinos con las medidas. En 24 hs tenés el presupuesto cerrado y te decimos cuál conviene.

📐 BlackStones · Mesadas a medida
```

Primer comentario:

```
#mesadasdecocina #marmoleriacaba #blackstones #granito #cuarzo #sinterizado
```

---

## Fuentes

- [Image prompting — OpenAI (docs oficiales)](https://developers.openai.com/api/docs/guides/image-prompting)
- [Cookbook: image prompting — OpenAI](https://developers.openai.com/cookbook/examples/multimodal/image-gen-models-prompting-guide)
- [GPT Image 2 — Higgsfield](https://higgsfield.ai/gpt-2)
- [Care and cleaning — Natural Stone Institute](https://www.naturalstoneinstitute.org/consumers/care/)
- [Care and maintenance — Caesarstone](https://www.caesarstoneus.com/customer-care/care-maintenance/)
- [Dekton vs Neolith — GoSource (secundaria)](https://www.gosource.us/blog/dekton-vs-neolith-guide)
- [Guías de sellado de granito — Slabwise (secundaria)](https://slabwise.com/guides/how-to-seal-granite-countertops)
- Lista de precios propia: `03-product/calculadora/lista-precios-2026-08/` (cargada en `calc.html`)
