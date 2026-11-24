# Trabajos: la galería del sitio

> `/trabajos/` es una galería de obras: cada foto, con la textura de su material al pie. Tocar una foto abre la página de ese material, con toda su colección de fotos, el título, una descripción corta, el espesor y las características.
> Pedida por el dueño el 24/11/2026. `status:` **publicada el 24/11/2026**: ese día se entregó el bloque de deploy con la galería, las tres páginas de materiales, las fotos y el sitemap. Desde acá no se ve producción: que esté arriba lo confirma el dueño.
> Es una página aparte, no una sección de la landing. La landing no la linkea todavía: falta que el dueño diga desde dónde se llega.
> **El estándar** (cómo se pasa una foto, las medidas de todo y lo que se comprueba solo): [`estandar.md`](estandar.md).

## Reglas del dueño (24/11/2026)

1. **Solo lo que él pasa.** "Vamos a ir solo con lo que yo te vaya pasando. No hay que armar todos los materiales, sino de los que vayamos subiendo." Un material tiene página recién cuando llega su primera foto.
2. **Nada de testamentos.** "Toda descripción tiene prohibido ser un testamento largo de texto. Vamos a priorizar que el cliente entienda en menos de 10 segundos." La descripción de un material no pasa de 30 palabras. Las características son hasta 5: una clave de hasta 3 palabras y una línea de hasta 9 que dice qué quiere decir. `armar.py` no arma nada si algo se pasa.
3. **Qué lleva la página de un material:** "título, descripción, milímetros" y las características del tipo de material "en bullets", "siempre cuidando la estética".
4. **Las características: la clave en una píldora.** "Esa sección debe ser algo elegante, moderno, estético, aesthetic, de rápido entendimiento." La idea que quedó es de él: "extrema dureza en pill; resto del texto clave, normal". En el celular, la píldora arriba y el texto abajo, con una raya entre una y otra: la eligió entre seis ("la opción C es la respuesta"). En computadora y tablet, las píldoras en una columna y los textos al lado ("así lo veo bien en PC, me gusta"). Lo que se probó antes y no va: la lista con puntos ("puede ser mucho más estética/moderna"; puesta de nuevo, "el celular queda paupérrimo"), cuatro recuadros blancos numerados, una ficha negra con dorado ("no, bloque negro no queda") y una tira de cuatro íconos de línea fina ("no quedó tan aesthetic"). Sin decidir del todo: el estilo de la píldora (se le mostraron tres; está la de fondo dorado suave).
5. **Y son ventajas, comprobadas.** "No tiene garantía de fábrica: quitalo, no tiene ninguna ventaja táctica." Y: "Las mesadas de granito destacan por su extrema dureza, resistencia al calor y larga durabilidad. Lo comprobado." Acá no van avisos (garantía, manchas, calor): eso se dice en la cotización y está en la landing.
6. **La descripción sale de los textos que pasa él** ("mixes de esto"), acortados.
7. **La colección crece sola.** "Cuando vayamos agregando nuevos trabajos, cuando yo abra Negro Boreal ahí se debe ir adjuntando toda la colección Negro Boreal."
8. **Él no convierte fotos.** Las sube como salen; la conversión se hace acá.
9. **Un crema más claro.** "Consideremos ese crema que usamos: aclararlo un poco a algo más premium. No queremos la sensación de libro viejo o pergamino." En estas páginas el fondo es `#FCFBF9` (el de la landing es `#FAF8F4`). La landing no se tocó: falta que el dueño diga si va también ahí.
10. **Es otra página, no la sección Trabajos de la landing.** "Cuidado, porque trabajos no es el mismo trabajos de la página principal: este iría en otra página." La sección "Trabajos" de la landing (la tira de obras con su título) y el "Trabajos" de su menú quedan como están; esta galería vive aparte, en `/trabajos/`. Nada de lo que se haga acá toca esa sección.
11. **La publica él, cuando la ve bien.** Primero la quiso guardada ("antes de subirlo prefiero tenerlo local, hasta tener suficientes imágenes") y, al verla terminada, el mismo día: "Excelente, vamos a subirlo a producción. Recordá que no está dentro del index.html, sino que es otra página, obviamente dentro del mismo ecosistema y universo. Es parte de la página como tal." De ahí: va al sitio con el mismo menú, los mismos colores y el mismo pie que la landing, y sin tocar `index.html`.
12. **Las fotos llegan cuadradas, 1:1, y esto queda escrito.** "Importante: documentá esta nueva página que hicimos, para términos de estandarización. Pasé las imágenes en 1:1. Por si se me olvida." El estándar está en [`estandar.md`](estandar.md); `foto.php` no arma nada con una foto que no sea cuadrada.

## Cómo sumar un trabajo

1. El dueño sube la foto a GitHub ("Add file → Upload files"), a la raíz de la rama por defecto, con el nombre `N° COLOR.png` (por ejemplo `2271 NEGRO BOREAL.png`).
2. Bajarla sin cambiar de rama: `git fetch origin` y `git show "origin/<rama por defecto>:2271 NEGRO BOREAL.png" > /tmp/foto.png`.
3. Prepararla: `php 04-operations/trabajos/foto.php /tmp/foto.png negro-boreal-2`. El nombre es el material y un número de orden. **No lleva el N° de presupuesto: el archivo es público.** Si la foto no es cuadrada o mide menos de 1440 px de lado, no arma nada: se le avisa al dueño (`estandar.md`, punto 1).
4. Anotarla en `datos.json`, en `trabajos`, arriba de todo (la más nueva va primero), con su texto alternativo.
5. Si el material es nuevo, sumarlo en `materiales`: nombre, tipo, la textura del catálogo de la landing (`/granitos/t/…`, `/purastone/t/…`), la descripción y el dato. Si el tipo es nuevo (sinterizado, mármol), sumarlo en `tipos` con su espesor y sus características (cada una, `["la clave", "qué quiere decir"]`).
6. `python3 04-operations/trabajos/armar.py`
7. Probar en computadora, tablet y celular.
8. Si conviene que el dueño lo vea antes de subirlo, armarle la vista previa: `python3 04-operations/trabajos/vista.py <salida>/trabajos-vista-previa.html`. Es un solo archivo, con las fotos adentro. No se guarda en el repo.
9. Entregar el bloque de deploy, armado con `04-operations/tools/bloque-deploy.py` (`04-operations/deploy-snippets.md`, Receta 9), en este orden: las cinco copias de cada foto nueva, las páginas de los materiales que cambiaron, `trabajos/index.html` y, si hay un material nuevo, `sitemap.xml`.

El HTML de `site/public_html/trabajos/` no se edita a mano: lo escribe `armar.py`.

## Lo que falta: desde dónde se llega

La página está en el sitio, pero la landing no la linkea. El dueño fue claro en que no es la sección Trabajos de la landing (regla 10) y en que no va adentro de `index.html` (regla 11); desde dónde se llega no lo dijo. Antes de tocar la landing, preguntarle.

Lo que estuvo armado, y él no llegó a confirmar, era un link "Ver más trabajos →" al lado de "Más en Instagram →", en la sección Trabajos de la landing. Se sacó el 24/11/2026; se ve entero con `git show 8932aa8 -- site/public_html/index.html`. Si va ese, es una regla de estilo después de `.work-more{…}`:

```css
.work-links{display:flex;flex-wrap:wrap;gap:.6rem 1.6rem}
```

Y en la sección Trabajos, en lugar del link suelto a Instagram:

```html
<div class="work-links">
  <a class="work-more" href="trabajos/">Ver más trabajos →</a>
  <a class="work-more" href="https://www.instagram.com/blackstones.ar/" target="_blank" rel="noopener">Más en Instagram →</a>
</div>
```

`"publicada"` (en `datos.json`) dice si la página está en el sitio. En `true`, `armar.py` escribe el bloque de `/trabajos/` en `sitemap.xml` y avisa mientras la landing no la linkee. En `false` no la pone en el sitemap y no arma nada si la landing la linkea: sirve para volver a guardarla.

## De dónde sale cada dato

| Dato | De dónde |
|---|---|
| Espesor (20 mm granito y cuarzo, 12 mm sinterizado) | `MM_DEFAULTS_POR_MARCA` de la calculadora |
| Textura (el cuadrito) | La miniatura del catálogo de la landing |
| Características del granito | El dueño, 24/11/2026 |
| Características del cuarzo | Lo que ya dice el sitio (landing y `/materiales/cuarzo/`), solo las ventajas |
| Descripción de Negro Boreal | Mezcla de los dos textos que pasó el dueño |
| Descripción de Gris Mara y de Blanco Cana | Las escribió quien armó la página, mirando la textura. **A confirmar por el dueño** |

## Cómo está hecho

- **`datos.json`**: todo lo que se muestra, y `"publicada"`, que dice si la página ya está en el sitio. **`armar.py`**: escribe la galería, una página por material y, si está publicada, el bloque de `/trabajos/` del `sitemap.xml`. **`vista.py`**: junta lo que deja `armar.py` en un solo archivo, para mostrarle al dueño un cambio antes de subirlo (las fotos, las texturas, el logo y las letras van adentro; se navega igual y anda sin internet y sin JavaScript). **`foto.php`**: de la foto original saca cuatro copias WebP (1440, 1080, 720 y 160 px) y un JPG de 1200 × 630, que es la imagen que muestra WhatsApp cuando se manda el link.
- **El menú, los colores, el botón de WhatsApp y el pie se copian de la landing** cada vez que se arma. Si la landing cambia, se vuelve a correr `armar.py`.
- **El cuadrito va abajo a la izquierda** porque las fotos traen el logo abajo a la derecha, y no se tapa. Mide 100 px en la galería y 112 en la página del material (eran 84 y 96; el dueño, 24/11/2026: "el cuadrito del material, un pelito más grande").
- **En la página de un material**, la foto grande es el trabajo más nuevo. Con más de uno aparece una tira de miniaturas que se desliza de costado; desde la galería se llega directo a la foto que se tocó.
- **El botón de WhatsApp de cada material** manda el mensaje con el material ya escrito ("vi en la página los trabajos en granito Negro Boreal…"): el cliente llega diciendo el color.
- **La landing** no la linkea y su sección Trabajos no se tocó (regla 10). En las páginas de `/trabajos/` el menú es el de la landing: cada ítem lleva a su sección de la landing, salvo "Trabajos", que ahí abre esta galería.

## Cómo se probó

237 comprobaciones en computadora, tablet y celular: que cada foto abra su material, textos y datos de cada página, las píldoras (apiladas en el celular, en columna desde tablet), el mensaje de WhatsApp, la colección con varias fotos (con datos de prueba), que nada desborde, que todas las imágenes carguen, el contraste del texto propio (4,5 a 1 o más), que el sitemap nombre la galería y los tres materiales y que cada una de esas direcciones exista, y que la landing haya quedado como estaba.

La vista previa, otras 78: abierta sin internet y sin JavaScript, que no pida nada afuera, que cada bloque mida lo mismo que la página de verdad (con un píxel de tolerancia), que se pase de la galería a cada material y de vuelta, y las miniaturas de una colección. Las pruebas no están en el repo.

El botón verde, el menú y el pie son los de la landing tal cual. El blanco sobre el verde de WhatsApp mide 1,98 a 1: viene así de la landing y no se tocó acá.

## Sin confirmar

- Desde dónde se llega a la página (arriba, "Lo que falta").
- El estilo de la píldora: está la de fondo dorado suave; se le mostraron también una blanca con borde y una en mayúsculas con borde dorado.
- Las descripciones de Gris Mara y de Blanco Cana, y las líneas que acompañan a cada clave (las escribió quien armó la página).
- El título de la galería ("Cada mesada, con su piedra.") y el dato que acompaña al espesor (Origen: Argentina; Marca: Purastone).
- Si, estando en `/trabajos/`, el "Trabajos" del menú tiene que abrir esta galería (como está) o bajar a la sección de la landing como los demás ítems.
- Que "la opción C" sea la píldora arriba y el texto abajo: así se leyó (era la C de seis variantes que vio mientras se armaban). Se le mostró aplicada; falta su visto bueno.
- El crema nuevo: quedó el intermedio (`#FCFBF9`). Se le mostró dos veces también uno casi blanco (`#FEFEFD`). Y si se lleva a la landing.
