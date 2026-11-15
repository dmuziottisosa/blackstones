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
| 7 · Cierre | Preguntanos por nuestro catálogo al DM. | (sin más texto, sin ilustración) |

---

## 3. Prompts

> **Dirección visual (decisión del dueño, 2026-11-15):** cada placa es una **ilustración hermosa e impactante, de una sola idea**, en paleta clara y con texto negro. Sistema de la serie: una pila de losas de la piedra y, encima, un único motivo que cuenta lo que hay que saber de ella.

| Placa | Idea única | Motivo sobre la piedra |
|---|---|---|
| Granito | Es duro: viene de la montaña | Una cordillera que nace de la losa |
| Cuarzo | No absorbe manchas | Una gota de vino entera, sin aureola |
| Sinterizado | Aguanta el fuego | Una llama ardiendo sobre la piedra intacta |
| Mármol | El limón lo opaca | Medio limón y su aureola mate |

### Antes de empezar

- **Proporción:** elegir 4:5 si Higgsfield la ofrece; si no, 3:4 o 2:3. No sé qué proporciones muestra tu pantalla.
- **Calidad:** la más alta disponible, por el texto.
- **Logo:** no se le pide al modelo. Se agrega después con el archivo real.
- **Orden:** generar primero **Granito**, adjuntando solo tus fotos de granito. Esa placa define el universo. Una vez aprobada, se adjunta como Imagen 1 en todas las demás, incluidas la portada y el cierre.
- **Fotos por material:** 3 o 4 alcanzan. Cada foto se convierte en una losa de la pila.
- **Zona segura:** cada prompt deja un 10% libre en los cuatro bordes. No hay una especificación oficial de Instagram; guías de terceros coinciden en diseñar en 4:5 (1080 x 1350) y dejar lo importante en el 70 a 80% central, porque la grilla del perfil recorta los costados de la portada. Verificar en la vista previa del perfil antes de publicar.

### Granito (placa 3) — la que define el estilo

Adjuntar: solo tus fotos de granito.

```
RESULTADO: placa 3 de un carrusel de Instagram para una marmolería. Presenta el granito con una ilustración hermosa e impactante que transmite una sola idea.

REFERENCIAS:
- Todas las imágenes adjuntas son fotos reales de granito. Son la referencia del material: la piedra de la ilustración tiene que tener el color, el grano y las vetas de esas fotos. No inventes piedras que no estén en las fotos.

ESCENA: en los dos tercios superiores, sobre el fondo crema liso, una pila de losas gruesas de granito flotando en el aire, vistas en tres cuartos y apenas desfasadas entre sí: una losa por cada foto adjunta de granito, cada una con el aspecto de su foto. De la losa de arriba nace una pequeña cordillera tallada en el mismo granito, como si la montaña saliera de la mesada. Nada más alrededor. Debajo de la ilustración, una línea dorada fina.

TEXTO, en el tercio inferior, en negro cálido #1A1816, alineado a la izquierda:
- Arriba del título, un número grande y fino en dorado #C4A77D: "01"
- Título enorme: "Granito"
- Bajada, mediana: "El más económico de los cuatro."
- Dos renglones chicos, uno debajo del otro:
  "Duro y resistente al uso de todos los días."
  "La verdad: los claros tienden a mancharse."

ESTILO (igual en todas las placas de la serie):
- Ilustración editorial contemporánea, hermosa e impactante, hecha a mano: pintura tipo gouache con trazo fino de tinta y textura sutil de papel. No es una foto ni un render 3D.
- Una sola idea visual por placa: un único motivo central, grande, sin objetos de relleno ni decoración alrededor.
- La piedra pintada con detalle fiel a las fotos adjuntas: mismo color, mismo grano, mismas vetas.
- Paleta clara: fondo crema muy claro #FAF8F4, liso. Ningún fondo ni bloque oscuro. Fuera de los colores de la piedra y del motivo, solo negro cálido #1A1816 y dorado #C4A77D.
- Luz suave y pareja, con una sombra corta y liviana debajo del motivo. Sin brillos exagerados, sin neón.
- Todo el texto en negro cálido #1A1816, alineado a la izquierda. El dorado solo en la línea fina y en el número.
- Título en tipografía serif editorial de alto contraste, estilo Fraunces, muy grande, con mayúscula solo en la inicial. Textos en sans geométrica limpia, estilo Manrope. Nada escrito todo en mayúsculas.
- Formato vertical para carrusel de Instagram.

ZONA SEGURA:
- Dejá un margen vacío del 10% en los cuatro bordes de la placa.
- Todo el texto, el número, la línea dorada y la ilustración completa quedan adentro de ese margen. Nada toca los bordes ni se sale de la placa.
- El margen es solo fondo crema liso.

RESTRICCIONES:
- Escribí cada texto exactamente una vez, tal cual está entre comillas, con sus tildes y signos.
- No agregues ningún otro texto, número, logo, ícono, marca de agua ni firma.
- No dibujes personas ni manos.
```

### Cuarzo (placa 4)

Adjuntar: Imagen 1 = la placa de granito aprobada · después, tus fotos de cuarzo.

```
RESULTADO: placa 4 de un carrusel de Instagram para una marmolería. Presenta el cuarzo con una ilustración hermosa e impactante que transmite una sola idea.

REFERENCIAS:
- Imagen 1 es la placa de granito ya aprobada de esta serie. Usala como referencia de estilo: el mismo tipo de ilustración, la misma paleta, las mismas tipografías, la misma composición y la misma línea dorada. No copies su piedra ni su motivo.
- Todas las demás imágenes adjuntas (de la Imagen 2 en adelante) son fotos reales de cuarzo. Son la referencia del material: la piedra de la ilustración tiene que tener el color, el grano y las vetas de esas fotos. No inventes piedras que no estén en las fotos.

ESCENA: en los dos tercios superiores, sobre el fondo crema liso, una pila de losas gruesas de cuarzo flotando en el aire, vistas en tres cuartos y apenas desfasadas entre sí: una losa por cada foto adjunta de cuarzo, cada una con el aspecto de su foto. Sobre la losa de arriba, una sola gota grande de vino tinto, entera y redonda como una joya, apoyada sin extenderse y sin dejar aureola: la piedra no la absorbe. Nada más alrededor. Debajo de la ilustración, una línea dorada fina.

TEXTO, en el tercio inferior, en negro cálido #1A1816, alineado a la izquierda:
- Arriba del título, un número grande y fino en dorado #C4A77D: "02"
- Título enorme: "Cuarzo"
- Bajada, mediana: "Precio medio, según la marca."
- Dos renglones chicos, uno debajo del otro:
  "No es poroso: no absorbe manchas."
  "La verdad: no le apoyes la olla recién sacada del fuego. Usá apoyaplato."

ESTILO (igual en todas las placas de la serie):
- Ilustración editorial contemporánea, hermosa e impactante, hecha a mano: pintura tipo gouache con trazo fino de tinta y textura sutil de papel. No es una foto ni un render 3D.
- Una sola idea visual por placa: un único motivo central, grande, sin objetos de relleno ni decoración alrededor.
- La piedra pintada con detalle fiel a las fotos adjuntas: mismo color, mismo grano, mismas vetas.
- Paleta clara: fondo crema muy claro #FAF8F4, liso. Ningún fondo ni bloque oscuro. Fuera de los colores de la piedra y del motivo, solo negro cálido #1A1816 y dorado #C4A77D.
- Luz suave y pareja, con una sombra corta y liviana debajo del motivo. Sin brillos exagerados, sin neón.
- Todo el texto en negro cálido #1A1816, alineado a la izquierda. El dorado solo en la línea fina y en el número.
- Título en tipografía serif editorial de alto contraste, estilo Fraunces, muy grande, con mayúscula solo en la inicial. Textos en sans geométrica limpia, estilo Manrope. Nada escrito todo en mayúsculas.
- Formato vertical para carrusel de Instagram.

ZONA SEGURA:
- Dejá un margen vacío del 10% en los cuatro bordes de la placa.
- Todo el texto, el número, la línea dorada y la ilustración completa quedan adentro de ese margen. Nada toca los bordes ni se sale de la placa.
- El margen es solo fondo crema liso.

RESTRICCIONES:
- Escribí cada texto exactamente una vez, tal cual está entre comillas, con sus tildes y signos.
- No agregues ningún otro texto, número, logo, ícono, marca de agua ni firma.
- No dibujes personas ni manos.
```

### Sinterizado (placa 5)

Adjuntar: Imagen 1 = la placa de granito aprobada · después, tus fotos de sinterizado.

```
RESULTADO: placa 5 de un carrusel de Instagram para una marmolería. Presenta el sinterizado con una ilustración hermosa e impactante que transmite una sola idea.

REFERENCIAS:
- Imagen 1 es la placa de granito ya aprobada de esta serie. Usala como referencia de estilo: el mismo tipo de ilustración, la misma paleta, las mismas tipografías, la misma composición y la misma línea dorada. No copies su piedra ni su motivo.
- Todas las demás imágenes adjuntas (de la Imagen 2 en adelante) son fotos reales de sinterizado. Son la referencia del material: la piedra de la ilustración tiene que tener el color, el grano y las vetas de esas fotos. No inventes piedras que no estén en las fotos.

ESCENA: en los dos tercios superiores, sobre el fondo crema liso, una pila de losas gruesas de sinterizado flotando en el aire, vistas en tres cuartos y apenas desfasadas entre sí: una losa por cada foto adjunta de sinterizado, cada una con el aspecto de su foto. Sobre la losa de arriba, una sola llama viva, naranja y dorada, ardiendo directamente sobre la piedra, que queda intacta: sin marca, sin humo negro y sin grieta. Nada más alrededor. Debajo de la ilustración, una línea dorada fina.

TEXTO, en el tercio inferior, en negro cálido #1A1816, alineado a la izquierda:
- Arriba del título, un número grande y fino en dorado #C4A77D: "03"
- Título enorme: "Sinterizado"
- Bajada, mediana: "Desde el precio de un cuarzo hasta bastante más."
- Dos renglones chicos, uno debajo del otro:
  "Resiste el calor, las rayas y las manchas."
  "La verdad: a la vista es más frío que una piedra natural."

ESTILO (igual en todas las placas de la serie):
- Ilustración editorial contemporánea, hermosa e impactante, hecha a mano: pintura tipo gouache con trazo fino de tinta y textura sutil de papel. No es una foto ni un render 3D.
- Una sola idea visual por placa: un único motivo central, grande, sin objetos de relleno ni decoración alrededor.
- La piedra pintada con detalle fiel a las fotos adjuntas: mismo color, mismo grano, mismas vetas.
- Paleta clara: fondo crema muy claro #FAF8F4, liso. Ningún fondo ni bloque oscuro. Fuera de los colores de la piedra y del motivo, solo negro cálido #1A1816 y dorado #C4A77D.
- Luz suave y pareja, con una sombra corta y liviana debajo del motivo. Sin brillos exagerados, sin neón.
- Todo el texto en negro cálido #1A1816, alineado a la izquierda. El dorado solo en la línea fina y en el número.
- Título en tipografía serif editorial de alto contraste, estilo Fraunces, muy grande, con mayúscula solo en la inicial. Textos en sans geométrica limpia, estilo Manrope. Nada escrito todo en mayúsculas.
- Formato vertical para carrusel de Instagram.

ZONA SEGURA:
- Dejá un margen vacío del 10% en los cuatro bordes de la placa.
- Todo el texto, el número, la línea dorada y la ilustración completa quedan adentro de ese margen. Nada toca los bordes ni se sale de la placa.
- El margen es solo fondo crema liso.

RESTRICCIONES:
- Escribí cada texto exactamente una vez, tal cual está entre comillas, con sus tildes y signos.
- No agregues ningún otro texto, número, logo, ícono, marca de agua ni firma.
- No dibujes personas ni manos.
```

### Mármol (placa 6)

Adjuntar: Imagen 1 = la placa de granito aprobada · después, tus fotos de mármol.

```
RESULTADO: placa 6 de un carrusel de Instagram para una marmolería. Presenta el mármol con una ilustración hermosa e impactante que transmite una sola idea.

REFERENCIAS:
- Imagen 1 es la placa de granito ya aprobada de esta serie. Usala como referencia de estilo: el mismo tipo de ilustración, la misma paleta, las mismas tipografías, la misma composición y la misma línea dorada. No copies su piedra ni su motivo.
- Todas las demás imágenes adjuntas (de la Imagen 2 en adelante) son fotos reales de mármol. Son la referencia del material: la piedra de la ilustración tiene que tener el color, el grano y las vetas de esas fotos. No inventes piedras que no estén en las fotos.

ESCENA: en los dos tercios superiores, sobre el fondo crema liso, una pila de losas gruesas de mármol flotando en el aire, vistas en tres cuartos y apenas desfasadas entre sí: una losa por cada foto adjunta de mármol, cada una con el aspecto de su foto. Sobre la losa de arriba, medio limón apoyado boca abajo. Alrededor del limón, una aureola mate y apenas más clara, donde el mármol perdió el brillo. Nada más alrededor. Debajo de la ilustración, una línea dorada fina.

TEXTO, en el tercio inferior, en negro cálido #1A1816, alineado a la izquierda:
- Arriba del título, un número grande y fino en dorado #C4A77D: "04"
- Título enorme: "Mármol"
- Bajada, mediana: "El clásico. El más conocido: Carrara."
- Dos renglones chicos, uno debajo del otro:
  "En cocina se usa poco: el limón y el vinagre lo opacan, y se mancha."
  "Donde se luce: baños, vanitorys y revestimientos."

ESTILO (igual en todas las placas de la serie):
- Ilustración editorial contemporánea, hermosa e impactante, hecha a mano: pintura tipo gouache con trazo fino de tinta y textura sutil de papel. No es una foto ni un render 3D.
- Una sola idea visual por placa: un único motivo central, grande, sin objetos de relleno ni decoración alrededor.
- La piedra pintada con detalle fiel a las fotos adjuntas: mismo color, mismo grano, mismas vetas.
- Paleta clara: fondo crema muy claro #FAF8F4, liso. Ningún fondo ni bloque oscuro. Fuera de los colores de la piedra y del motivo, solo negro cálido #1A1816 y dorado #C4A77D.
- Luz suave y pareja, con una sombra corta y liviana debajo del motivo. Sin brillos exagerados, sin neón.
- Todo el texto en negro cálido #1A1816, alineado a la izquierda. El dorado solo en la línea fina y en el número.
- Título en tipografía serif editorial de alto contraste, estilo Fraunces, muy grande, con mayúscula solo en la inicial. Textos en sans geométrica limpia, estilo Manrope. Nada escrito todo en mayúsculas.
- Formato vertical para carrusel de Instagram.

ZONA SEGURA:
- Dejá un margen vacío del 10% en los cuatro bordes de la placa.
- Todo el texto, el número, la línea dorada y la ilustración completa quedan adentro de ese margen. Nada toca los bordes ni se sale de la placa.
- El margen es solo fondo crema liso.

RESTRICCIONES:
- Escribí cada texto exactamente una vez, tal cual está entre comillas, con sus tildes y signos.
- No agregues ningún otro texto, número, logo, ícono, marca de agua ni firma.
- No dibujes personas ni manos.
```

### Portada (placa 1)

Adjuntar: Imagen 1 = la placa de granito aprobada. Si querés, sumá una foto de cada piedra.

```
RESULTADO: portada de un carrusel de Instagram para una marmolería. Una ilustración hermosa e impactante con una sola idea: cuatro piedras, cuatro comportamientos.

REFERENCIAS:
- Imagen 1 es la placa de granito ya aprobada de esta serie. Usala como referencia de estilo: el mismo tipo de ilustración, la misma paleta, las mismas tipografías y la misma línea dorada. No copies su composición.
- Si hay más imágenes adjuntas, son fotos reales de las piedras: usá su color y sus vetas.

ESCENA: en los dos tercios superiores, sobre el fondo crema liso, cuatro losas gruesas flotando en fila, vistas en tres cuartos, cada una con su motivo encima: una losa de granito gris con una pequeña cordillera, una de cuarzo blanco con una gota de vino tinto, una de sinterizado blanco con una llama, y una de mármol Carrara con medio limón. Nada más alrededor. Debajo, una línea dorada fina.

TEXTO, en el tercio inferior, en negro cálido #1A1816, alineado a la izquierda:
- Título enorme en tres renglones: "¿Granito, cuarzo, sinterizado o mármol?"
- Debajo, mucho más chico: "Lo que tenés que saber antes de elegir tu mesada."

ESTILO (igual en todas las placas de la serie):
- Ilustración editorial contemporánea, hermosa e impactante, hecha a mano: pintura tipo gouache con trazo fino de tinta y textura sutil de papel. No es una foto ni un render 3D.
- Una sola idea visual por placa: un único motivo central, grande, sin objetos de relleno ni decoración alrededor.
- La piedra pintada con detalle fiel a las fotos adjuntas: mismo color, mismo grano, mismas vetas.
- Paleta clara: fondo crema muy claro #FAF8F4, liso. Ningún fondo ni bloque oscuro. Fuera de los colores de la piedra y del motivo, solo negro cálido #1A1816 y dorado #C4A77D.
- Luz suave y pareja, con una sombra corta y liviana debajo del motivo. Sin brillos exagerados, sin neón.
- Todo el texto en negro cálido #1A1816, alineado a la izquierda. El dorado solo en la línea fina y en el número.
- Título en tipografía serif editorial de alto contraste, estilo Fraunces, muy grande, con mayúscula solo en la inicial. Textos en sans geométrica limpia, estilo Manrope. Nada escrito todo en mayúsculas.
- Formato vertical para carrusel de Instagram.

ZONA SEGURA:
- Dejá un margen vacío del 10% en los cuatro bordes de la placa.
- Todo el texto, el número, la línea dorada y la ilustración completa quedan adentro de ese margen. Nada toca los bordes ni se sale de la placa.
- El margen es solo fondo crema liso.

RESTRICCIONES:
- Escribí cada texto exactamente una vez, tal cual está entre comillas, con sus tildes y signos.
- No agregues ningún otro texto, número, logo, ícono, marca de agua ni firma.
- No dibujes personas ni manos.
```

### La confusión (placa 2)

Adjuntar: Imagen 1 = la placa de granito aprobada.

```
RESULTADO: segunda placa del mismo carrusel. Placa tipográfica, sin ilustración.

REFERENCIA: Imagen 1 es la placa de granito ya aprobada de esta serie. Usala como referencia de estilo: la misma paleta, las mismas tipografías, los mismos márgenes y la misma línea dorada. No copies su ilustración.

ESCENA: fondo crema muy claro #FAF8F4, liso. Sin ilustración ni fotos.

TEXTO, en negro cálido #1A1816, todo alineado a la izquierda:
- Título enorme en cuatro renglones: "Todas parecen piedra. No se comportan igual."
- Una línea dorada fina y corta debajo del título.
- Tres renglones chicos, uno debajo del otro:
  "Una se opaca con limón."
  "Otra no quiere la olla caliente encima."
  "Otra aguanta casi todo."
- Abajo de todo, separado del resto: "Conocelas una por una."

ESTILO (igual en todas las placas de la serie):
- Placa tipográfica de la misma serie: solo texto sobre fondo liso, con textura sutil de papel. Sin ilustración, sin fotos, sin objetos.
- Paleta clara: fondo crema muy claro #FAF8F4, liso. Ningún fondo ni bloque oscuro. Solo negro cálido #1A1816 y dorado #C4A77D.
- Todo el texto en negro cálido #1A1816, alineado a la izquierda. El dorado solo en la línea fina.
- Título en tipografía serif editorial de alto contraste, estilo Fraunces, muy grande, con mayúscula solo en la inicial. Textos en sans geométrica limpia, estilo Manrope. Nada escrito todo en mayúsculas.
- Formato vertical para carrusel de Instagram.

ZONA SEGURA:
- Dejá un margen vacío del 10% en los cuatro bordes de la placa.
- Todo el texto y la línea dorada quedan adentro de ese margen. Nada toca los bordes ni se sale de la placa.
- El margen es solo fondo crema liso.

RESTRICCIONES:
- Escribí cada texto exactamente una vez, tal cual está entre comillas, con sus tildes y signos.
- No agregues ningún otro texto, número, logo, ícono, marca de agua ni firma.
- No dibujes personas ni manos.
```

### Cierre (placa 7)

Un solo pedido, sin ilustraciones (decisión del dueño, 2026-11-15). Adjuntar: Imagen 1 = la placa de granito aprobada.

```
RESULTADO: última placa del mismo carrusel. Placa tipográfica con un solo mensaje: que nos pregunten por el catálogo por mensaje directo.

REFERENCIA: Imagen 1 es la placa de granito ya aprobada de esta serie. Usala solo como referencia de paleta, tipografías, márgenes y línea dorada. No copies su ilustración ni dibujes ninguna otra.

ESCENA: fondo crema muy claro #FAF8F4, liso. Sin ilustraciones, sin fotos, sin objetos, sin íconos. Solo el texto, con mucho espacio vacío alrededor.

TEXTO, en negro cálido #1A1816, alineado a la izquierda, centrado en altura:
- Una sola frase, como título enorme en tres renglones: "Preguntanos por nuestro catálogo al DM."
- Las letras "DM" van en mayúsculas, tal cual están escritas.
- Una línea dorada fina y corta debajo.

ESTILO (igual en todas las placas de la serie):
- Placa tipográfica de la misma serie: solo texto sobre fondo liso, con textura sutil de papel.
- Paleta clara: fondo crema muy claro #FAF8F4, liso. Ningún fondo ni bloque oscuro. Solo negro cálido #1A1816 y dorado #C4A77D.
- Todo el texto en negro cálido #1A1816, alineado a la izquierda. El dorado solo en la línea fina.
- Título en tipografía serif editorial de alto contraste, estilo Fraunces, muy grande, con interlineado apretado.
- Formato vertical para carrusel de Instagram.

ZONA SEGURA:
- Dejá un margen vacío del 10% en los cuatro bordes de la placa.
- El texto y la línea dorada quedan adentro de ese margen. Nada toca los bordes ni se sale de la placa.
- El margen es solo fondo crema liso.

RESTRICCIONES:
- Escribí el texto exactamente una vez, tal cual está entre comillas, con su tilde.
- No agregues ningún otro texto, número, logo, ícono, dibujo, marca de agua ni firma.
- No dibujes personas ni manos.
```

### Si una placa sale mal

La guía oficial pide corregir de a una cosa: adjuntar la placa fallida y pedir un solo cambio, repitiendo lo que tiene que quedar igual.

```
Imagen 1 es la placa a corregir. Cambiá solo esto: [el texto "X" tiene que decir exactamente "Y"]. Dejá igual todo lo demás: ilustración, composición, colores, tipografías y el resto de los textos.
```

---

## 4. Revisión antes de publicar

1. Cada palabra se lee y coincide con la tabla de la sección 2, con tildes y signos de pregunta.
2. No hay texto, logo ni ícono de más.
3. La piedra de cada ilustración tiene el color y las vetas de tus fotos, y cada placa muestra un solo motivo.
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

Preguntanos por nuestro catálogo al DM.

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
- [Instagram post size 2026 — Inro (secundaria)](https://www.inro.social/blog/instagram-post-size)
- [Instagram grid size guide — Oktopost (secundaria)](https://www.oktopost.com/blog/instagram-grid-size-guide/)
- [Guías de sellado de granito — Slabwise (secundaria)](https://slabwise.com/guides/how-to-seal-granite-countertops)
- Lista de precios propia: `03-product/calculadora/lista-precios-2026-08/` (cargada en `calc.html`)
