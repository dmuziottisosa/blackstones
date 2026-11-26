# Trabajos: el estándar

> Qué tiene que cumplir cada foto, cada material y cada texto que se sume a `/trabajos/`, y cómo es la página por dentro, con las medidas.
> Lo pidió el dueño el 24/11/2026: "Importante: documentá esta nueva página que hicimos, para términos de estandarización. Pasé las imágenes en 1:1. Por si se me olvida."
> `status:` vigente desde el 24/11/2026. Sale de lo que se armó y se midió ese día, con tres fotos; cuando la página cambie, este doc cambia con fecha y razón.
> Las reglas del dueño con sus palabras, los pasos para sumar un trabajo y lo que falta decidir están en [`README.md`](README.md). Acá están las medidas.

---

## 1. Para acordarse: cómo se pasa una foto

Lo que tiene que cumplir la foto que manda el dueño. Es lo único que depende de él; el resto se hace acá.

1. **Cuadrada, 1:1.** Como las tres primeras, que vinieron de 2048 × 2048 px. Sirve desde 1440 × 1440.
2. **El logo, abajo a la derecha**, como en las tres primeras.
3. **Abajo a la izquierda, nada importante.** En esa esquina va montado el cuadrito del material: en el celular tapa más o menos un tercio del ancho y un sexto del alto de la foto.
4. **La mesada, en la franja del medio.** Cuando alguien manda el link por WhatsApp, la vista previa es una franja apaisada de la foto: lo de bien arriba y lo de bien abajo no sale.
5. **Como salga de la cámara o del editor: PNG o JPG, sin achicar y sin convertir, y por GitHub, no por el chat.** El chat la entrega achicada a 1024 px (pasó el 26/11/2026 con la de Blanco Alpinus): se puede publicar igual, pero en pantallas grandes se ve menos nítida que las demás. Las tres primeras pesaban entre 5,5 y 5,9 MB; en el sitio quedan entre 38 y 195 KB.
6. **Nombre del archivo: el N° y el color**, por ejemplo `2271 NEGRO BOREAL.png`. Se sube a GitHub, a la carpeta principal ("Add file → Upload files"). En el sitio ese número no aparece.
7. **Qué decir al pasarla:** de qué material es. Si el material es nuevo: si es granito, cuarzo u otro, y algo del color (de dónde viene, cómo es). Con dos renglones alcanza: el texto se arma acá y se le muestra antes de subirlo.

8. **Que no se vea de quién es la casa, ni nadie.** Sin personas (ni de espaldas, ni reflejadas). Sin fotos familiares, papeles, cartas o imanes con nombres. Sin el frente del edificio. Y ojo con la vista por la ventana: si deja ubicar el edificio, mejor otra toma. En el sitio la foto va sin nombre, sin dirección y sin el N° de presupuesto.
9. **Si el cliente pide que se saque, se saca ese día.**

Si llega una foto que no es cuadrada, no se sube: se le avisa. Si él dice que se recorte, se corta al centro.

Los puntos 8 y 9 salen de la pregunta del dueño sobre el riesgo de publicar cocinas de clientes (24/11/2026). Lo que se encontró de la ley está en `06-knowledge/layer-2-landscape/publicar-fotos-de-casas-de-clientes.md`: es una hipótesis, no un asesoramiento. Pedirle permiso al cliente (una línea en el contrato o un "sí" por WhatsApp) es lo que cierra el tema; si se hace o no, lo decide el dueño.

---

## 2. La foto en el sitio

De cada foto original salen cinco archivos, en `site/public_html/trabajos/f/`. Los arma `foto.php`.

| Archivo | Mide | Formato | Quién lo usa | Pesaron (las tres primeras) |
|---|---|---|---|---|
| `<nombre>-1440.webp` | 1440 × 1440 | WebP, calidad 80 | Pantallas grandes o de mucha definición | 102 a 195 KB |
| `<nombre>-1080.webp` | 1080 × 1080 | WebP, calidad 80 | El celular | 70 a 125 KB |
| `<nombre>-720.webp` | 720 × 720 | WebP, calidad 80 | La galería en computadora, de a tres columnas | 38 a 63 KB |
| `<nombre>-160.webp` | 160 × 160 | WebP, calidad 80 | La miniatura, cuando un material tiene varias fotos | 4 a 5 KB |
| `<nombre>-og.jpg` | 1200 × 630 | JPG, calidad 82 | La vista previa del link en WhatsApp | 97 a 149 KB |

- **El nombre** es el material y un número de orden: `negro-boreal-1`, `negro-boreal-2`. Una foto que reemplaza a otra lleva el número siguiente, no el mismo: el navegador guarda las fotos 7 días y con el mismo nombre se seguiría viendo la vieja. En minúsculas, con guiones, sin tildes. **Nunca el N° de presupuesto: el archivo es público.**
- **La franja de la vista previa** toma todo el ancho y un poco más de la mitad del alto, centrada algo por debajo del medio: más o menos del 29% al 81% de la altura.
- **Cada pantalla baja una sola copia**, la que le corresponde. Las fotos de más abajo se bajan recién cuando se llega a ellas. La galería con tres fotos pesó 341 KB en un celular, sin contar las letras; con cuatro, 449 KB.

---

## 3. El material

Un material tiene página recién cuando llega su primera foto. Se anota en `datos.json`, en `materiales`.

| Dato | Regla | Ejemplo |
|---|---|---|
| `slug` | La dirección de su página. Minúsculas, guiones, sin tildes | `negro-boreal` → `/trabajos/negro-boreal/` |
| `nombre` | Como se le dice al cliente | Negro Boreal |
| `tipo` | Uno de los de `tipos` | `granito` |
| `textura` | La miniatura del catálogo de la landing: `/granitos/t/<color>.webp`, `/purastone/t/<color>.webp`. No las `-muestra`, que son fotos de mesadas. Si el material no está en el catálogo, la imagen de la piedra que pasa el dueño, cuadrada, de 360 px, en la misma carpeta | `/granitos/t/negro-boreal.webp` |
| `descripcion` | **Hasta 30 palabras.** Qué es y cómo se ve. Sale de lo que pasa el dueño, acortado | "Granito natural argentino. Fondo negro profundo con nubes grises y destellos que brillan con la luz." |
| `dato` | Un dato corto que acompaña al espesor: `Origen` en la piedra natural, `Marca` en la que tiene | Origen: Argentina · Marca: Purastone |

## 4. El tipo de material

Lo que vale para todos los materiales de un tipo. En `datos.json`, en `tipos`.

| Dato | Regla | Hoy |
|---|---|---|
| `espesor_mm` | El de la calculadora (`MM_DEFAULTS_POR_MARCA`) | Granito 20 · Cuarzo 20 · (Sinterizado, cuando llegue, 12) |
| `caracteristicas` | **Hasta 5.** Cada una, una clave de **hasta 3 palabras** y una línea de **hasta 9**. Solo ventajas, y comprobadas | Abajo |

Las que hay:

- **Granito:** Extrema dureza (No se raya con el uso diario.) · Resiste el calor (Aguanta ollas y sartenes directo.) · Larga durabilidad (Hecha para el uso de todos los días.) · Pieza única (Cada plancha sale distinta de la cantera.)
- **Cuarzo:** Cuarzo natural (90%, prensado con resinas.) · Baja porosidad (Resiste las manchas.) · Aguanta los ácidos (El limón y el vinagre no lo marcan.) · Color parejo (Igual en toda la mesada.)

Lo que no va en estas páginas: avisos. Que el granito no lleva garantía, que el claro se mancha o que el cuarzo pide posafuente se dice en la cotización.

## 5. El trabajo

Cada foto es un trabajo. En `datos.json`, en `trabajos`, **la más nueva arriba**: ese es el orden de la galería.

| Dato | Regla | Ejemplo |
|---|---|---|
| `foto` | El nombre de sus cinco archivos | `negro-boreal-1` |
| `material` | El `slug` de su material | `negro-boreal` |
| `alt` | Lo que se ve, en una frase: la forma de la mesada, el material y lo que tiene. Es lo que lee quien no ve la foto | "Mesada en L de granito Negro Boreal con bacha negra" |

---

## 6. La galería (`/trabajos/`)

- **Arriba:** el rótulo "Trabajos", el título "Cada mesada, *con su piedra.*" y una línea: "Obras nuestras, ya colocadas. Tocá una foto para ver el material."
- **Las fotos:** una columna en el celular, dos desde 640 px de ancho, tres desde 1000 px.
- **Cada foto:** cuadrada, con las puntas redondeadas (14 px). Abajo a la izquierda, montado mitad adentro y mitad afuera, el cuadrito del material, de 100 × 100 px. Al lado, el nombre del material y, en chico y en mayúsculas, el tipo.
- **Al tocar una foto** se abre la página de su material. Si ese material tiene varias fotos, se abre en la que se tocó.
- **Abajo:** el cierre con el botón de WhatsApp ("¿Te gustó alguna? Mandanos las medidas.").

## 7. La página de un material (`/trabajos/<material>/`)

De arriba hacia abajo en el celular. En computadora (desde 900 px), la foto queda a la izquierda y todo lo demás a la derecha.

1. **La miga:** "Trabajos / Granito". "Trabajos" vuelve a la galería.
2. **El título:** el tipo y, en itálica, el nombre: "Granito *Negro Boreal*".
3. **La foto**, cuadrada. La que se ve al entrar es la más nueva de ese material.
4. **El cuadrito del material**, de 112 × 112 px, montado abajo a la izquierda. Con más de una foto, al lado va una tira de miniaturas de 72 px que se desliza de costado y cambia la foto grande.
5. **La descripción.**
6. **Dos datos en un renglón:** Espesor y el dato del material.
7. **"Cómo es el granito"** (o el tipo que sea): las características. La clave va en una píldora y el resto del texto, normal. En el celular, la píldora arriba y el texto abajo, con una raya entre una y otra; desde 640 px, las píldoras en una columna y los textos al lado, alineados.
8. **El botón verde:** "Cotizar en Negro Boreal". Abre WhatsApp con el mensaje escrito: "Hola, vi en la página los trabajos en granito Negro Boreal y me gustaría cotizar una mesada". Si el material tiene marca, va: "…en cuarzo Purastone Blanco Cana…".
9. **"← Todos los trabajos".**
10. **"Otros materiales":** una pastilla por cada otro material que tenga fotos.

## 8. El aspecto

"Es otra página, obviamente dentro del mismo ecosistema y universo" (dueño, 24/11/2026).

- **El menú, el botón de WhatsApp, el cierre y el pie son los de la landing.** No están copiados a mano: `armar.py` los toma de `index.html` cada vez que arma. Si la landing cambia, se vuelve a correr y quedan iguales.
- **Las letras:** Fraunces en los títulos y en los datos, Manrope en el resto. Las mismas de la landing.
- **Los colores:**

| Qué | Color |
|---|---|
| Fondo | `#FCFBF9` (más claro que el de la landing, `#FAF8F4`: "no queremos la sensación de libro viejo o pergamino") |
| Fondo de recuadros | `#F4F1EB` |
| Rayas | `#EBE7E0` |
| Texto | `#1A1816` |
| Texto secundario | `#4A453E` |
| Rótulos y links | `#7A6649` |
| Dorado | `#C4A77D` (el fondo de la píldora es ese dorado al 22%) |
| Menú | `#15130F` |
| Pie | `#0F0D0A` |
| WhatsApp | `#25D366`, el único color fuerte de la página |

- **Todo el texto propio de estas páginas llega a 4,5 a 1 de contraste**, medido. El blanco sobre el verde de WhatsApp mide 1,98 a 1: viene así de la landing y acá no se tocó.

## 9. Lo que lleva cada página para Google y para WhatsApp

| | Galería | Material |
|---|---|---|
| Título de la pestaña | Trabajos — BlackStones Marmolería | Granito Negro Boreal — Trabajos \| BlackStones Marmolería |
| Descripción | Una línea fija sobre la galería | La descripción del material |
| Imagen del link | El `-og.jpg` de la foto más nueva | El `-og.jpg` de la foto más nueva de ese material |
| En el `sitemap.xml` | Sí | Sí, una línea por material |

---

## 10. Lo que se comprueba solo

Si algo de esto no se cumple, la herramienta no arma nada y dice qué pasó.

| Qué | Quién |
|---|---|
| La foto original es cuadrada (hasta 1% de diferencia se empareja sola) y mide 1440 px de lado o más | `foto.php` |
| Cada copia mide lo que dice su nombre, y es cuadrada | `armar.py` |
| Están las cinco copias de cada foto y la textura de cada material | `armar.py` |
| El nombre de la foto no empieza con un número (el N° de presupuesto) | `armar.py` |
| La descripción no pasa de 30 palabras | `armar.py` |
| Las características son hasta 5, con clave de hasta 3 palabras y línea de hasta 9 | `armar.py` |
| Si la página no está publicada, la landing no la linkea y no va en el sitemap | `armar.py` |

Lo que no comprueba ningún programa, y hay que mirar: que no se vea de quién es la casa ni aparezca nadie, que la esquina de abajo a la izquierda no tape nada, que el logo esté abajo a la derecha, que la franja de la vista previa muestre la mesada y que las características sean ventajas.

Antes de entregar un cambio se corren las pruebas en computadora, tablet y celular (237 comprobaciones el 24/11/2026 y 301 el 26/11/2026, con el cuarto material; no están en el repo) y se entrega el bloque de deploy armado con `04-operations/tools/bloque-deploy.py`.

## 11. Lo que todavía no es estándar

Está en "Sin confirmar", al final de [`README.md`](README.md): el estilo de la píldora, si el fondo más claro va también a la landing y los textos que escribió quien armó la página.
