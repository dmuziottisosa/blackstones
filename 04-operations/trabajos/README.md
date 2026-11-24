# Trabajos: la galería del sitio

> `/trabajos/` es una galería de obras: cada foto, con la textura de su material al pie. Tocar una foto abre la página de ese material, con toda su colección de fotos, el título, una descripción corta, el espesor y las características.
> Pedida por el dueño el 24/11/2026. `status:` armada y probada acá; falta el deploy.

## Reglas del dueño (24/11/2026)

1. **Solo lo que él pasa.** "Vamos a ir solo con lo que yo te vaya pasando. No hay que armar todos los materiales, sino de los que vayamos subiendo." Un material tiene página recién cuando llega su primera foto.
2. **Nada de testamentos.** "Toda descripción tiene prohibido ser un testamento largo de texto. Vamos a priorizar que el cliente entienda en menos de 10 segundos." La descripción de un material no pasa de 30 palabras. Las características son 2 o 4: un ícono y hasta 3 palabras cada una. `armar.py` no arma nada si algo se pasa.
3. **Qué lleva la página de un material:** "título, descripción, milímetros" y las características del tipo de material "en bullets", "siempre cuidando la estética".
4. **Las características: elegantes y de entender rápido.** "Esa sección debe ser algo elegante, moderno, estético, aesthetic, de rápido entendimiento." Quedó una tira liviana de cuatro íconos de línea fina, cada uno con dos o tres palabras. Lo que se probó antes y no quedó: una lista con puntos, cuatro recuadros blancos numerados y una ficha negra con dorado ("no, bloque negro no queda").
5. **Y son ventajas, comprobadas.** "No tiene garantía de fábrica: quitalo, no tiene ninguna ventaja táctica." Y: "Las mesadas de granito destacan por su extrema dureza, resistencia al calor y larga durabilidad. Lo comprobado." Acá no van avisos (garantía, manchas, calor): eso se dice en la cotización y está en la landing.
6. **La descripción sale de los textos que pasa él** ("mixes de esto"), acortados.
7. **La colección crece sola.** "Cuando vayamos agregando nuevos trabajos, cuando yo abra Negro Boreal ahí se debe ir adjuntando toda la colección Negro Boreal."
8. **Él no convierte fotos.** Las sube como salen; la conversión se hace acá.
9. **Un crema más claro.** "Consideremos ese crema que usamos: aclararlo un poco a algo más premium. No queremos la sensación de libro viejo o pergamino." En estas páginas el fondo es `#FCFBF9` (el de la landing es `#FAF8F4`). La landing no se tocó: falta que el dueño diga si va también ahí.

## Cómo sumar un trabajo

1. El dueño sube la foto a GitHub ("Add file → Upload files"), a la raíz de la rama por defecto, con el nombre `N° COLOR.png` (por ejemplo `2271 NEGRO BOREAL.png`).
2. Bajarla sin cambiar de rama: `git fetch origin` y `git show "origin/<rama por defecto>:2271 NEGRO BOREAL.png" > /tmp/foto.png`.
3. Prepararla: `php 04-operations/trabajos/foto.php /tmp/foto.png negro-boreal-2`. El nombre es el material y un número de orden. **No lleva el N° de presupuesto: el archivo es público.**
4. Anotarla en `datos.json`, en `trabajos`, arriba de todo (la más nueva va primero), con su texto alternativo.
5. Si el material es nuevo, sumarlo en `materiales`: nombre, tipo, la textura del catálogo de la landing (`/granitos/t/…`, `/purastone/t/…`), la descripción y el dato. Si el tipo es nuevo (sinterizado, mármol), sumarlo en `tipos` con su espesor y sus características (cada una, `["ícono", "dos o tres palabras"]`; los íconos que hay están en `ICONOS`, al principio de `armar.py`).
6. `python3 04-operations/trabajos/armar.py`
7. Probar en computadora, tablet y celular y entregar con el bloque de deploy: las cinco copias de la foto y las páginas que cambiaron.

El HTML de `site/public_html/trabajos/` no se edita a mano: lo escribe `armar.py`.

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

- **`datos.json`**: todo lo que se muestra. **`armar.py`**: escribe la galería, una página por material y el bloque de `/trabajos/` del `sitemap.xml`. **`foto.php`**: de la foto original saca cuatro copias WebP (1440, 1080, 720 y 160 px) y un JPG de 1200 × 630, que es la imagen que muestra WhatsApp cuando se manda el link.
- **El menú, los colores, el botón de WhatsApp y el pie se copian de la landing** cada vez que se arma. Si la landing cambia, se vuelve a correr `armar.py`.
- **El cuadrito va abajo a la izquierda** porque las fotos traen el logo abajo a la derecha, y no se tapa.
- **En la página de un material**, la foto grande es el trabajo más nuevo. Con más de uno aparece una tira de miniaturas que se desliza de costado; desde la galería se llega directo a la foto que se tocó.
- **El botón de WhatsApp de cada material** manda el mensaje con el material ya escrito ("vi en la página los trabajos en granito Negro Boreal…"): el cliente llega diciendo el color.
- **La landing** tiene el link "Ver más trabajos →" en su sección Trabajos. El "Trabajos" del menú de la landing sigue bajando a esa sección.

## Cómo se probó

193 comprobaciones en computadora, tablet y celular: que cada foto abra su material, textos y datos de cada página, el mensaje de WhatsApp, la colección con varias fotos (con datos de prueba), que nada desborde, que todas las imágenes carguen y el contraste del texto propio (4,5 a 1 o más). Las pruebas no están en el repo.

El botón verde, el menú y el pie son los de la landing tal cual. El blanco sobre el verde de WhatsApp mide 1,98 a 1: viene así de la landing y no se tocó acá.

## Sin confirmar

- Las descripciones de Gris Mara y de Blanco Cana.
- El título de la galería ("Cada mesada, con su piedra.") y el dato que acompaña al espesor (Origen: Argentina; Marca: Purastone).
- Si el "Trabajos" del menú de la landing tiene que abrir esta página en vez de bajar a la sección.
- El crema nuevo: quedó el intermedio (`#FCFBF9`). Se le mostró también uno casi blanco (`#FEFEFD`). Y si se lleva a la landing.
