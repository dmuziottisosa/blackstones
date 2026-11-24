# La página Trabajos: fotos de obras, cada una con su material

**Fecha:** 24/11/2026 · **Decide:** el dueño, que la fue corrigiendo mientras se armaba · **Estado:** publicada el 24/11/2026 (se entregó el bloque de deploy; que esté arriba lo confirma el dueño). Se llega por un link en la sección Trabajos de la landing (bloque entregado ese día)

## Qué pidió el dueño

Subió tres fotos de obras (Gris Mara, Negro Boreal y Blanco Cana) y una captura de referencia: "Quiero ponerlos en una nueva página llamada Trabajos. La idea es que sea como una galería que va bajando. Lo importante: foto, y cuadrito de color de material. Ahí mismo todo."

Después: "Si doy click en la foto de la galería nos debe llevar a una página por material. Cuando vayamos agregando nuevos trabajos, cuando yo abra Negro Boreal ahí se debe ir adjuntando toda la colección Negro Boreal. Ponemos título, descripción, milímetros", y las características del material.

## Qué se hizo

`/trabajos/` (la galería) y una página por material. Las reglas que fijó, con sus palabras, y cómo se suma un trabajo: `04-operations/trabajos/README.md`.

## Lo que corrigió mientras se armaba

- **Textos cortos:** "Toda descripción tiene prohibido ser un testamento largo de texto. Vamos a priorizar que el cliente entienda en menos de 10 segundos."
- **Solo lo que él sube:** "No hay que armar todos los materiales, sino de los que vayamos subiendo."
- **Fuera "no tiene garantía de fábrica":** "No tiene ninguna ventaja táctica. Poné otra cosa." Y qué va: "extrema dureza, resistencia al calor y larga durabilidad. Lo comprobado." La regla de decir que el granito no lleva garantía sigue en la cotización; en estas páginas no va.
- **Un crema más claro:** "Aclararlo un poco a algo más premium. No queremos la sensación de libro viejo o pergamino."
- **Las características, seis veces:** la lista con puntos "puede ser mucho más estética/moderna"; cuatro recuadros numerados; la ficha negra, "no, bloque negro no queda"; una tira de íconos finos, "no quedó tan aesthetic". Pidió volver al primer diseño y, al verlo, "el celular queda paupérrimo". La idea que quedó la tiró él: "extrema dureza en pill; resto del texto clave, normal". En el celular eligió la píldora arriba y el texto abajo ("la opción C es la respuesta"); en computadora, las píldoras en columna con el texto al lado ("así lo veo bien en PC, me gusta").

## Qué pasó después, el mismo día

- **El primer bloque de deploy falló y no subió nada.** El bloque probado estaba bien; el que se pegó en el chat tenía cuatro tamaños de fotos mal copiados, y la revisión de tamaños frenó la subida. Lo malo: pegado en la consola, el bloque siguió corriendo después del error y dijo "Subidos 8 archivos" sin haber subido ninguno. De ahí salen dos reglas, en `04-operations/deploy-snippets.md` (Receta 9): el bloque se arma con un programa y se copia del archivo probado, y va entero adentro de un freno para que un error corte todo.
- **El dueño primero la quiso guardada:** "antes de subirlo prefiero tenerlo local, hasta tener suficientes imágenes". Se sacó el link de la landing y el bloque del sitemap (los dos archivos volvieron a como estaban antes de esta página) y se le mandó la vista previa: un solo archivo con todo adentro, que arma `vista.py`.
- **Y al verla terminada, la mandó a producción:** "Excelente, vamos a subirlo a producción. Recordá que no está dentro del index.html, sino que es otra página, obviamente dentro del mismo ecosistema y universo. Es parte de la página como tal." Se subió la página sola (galería, tres materiales, fotos y sitemap), sin tocar `index.html`. `datos.json` lleva `"publicada": true`.

- **Y aclaró que es otra página:** "cuidado, porque trabajos no es el mismo trabajos de la página principal: este iría en otra página". La sección Trabajos de la landing no se toca.

- **Preguntó cómo se llega:** "¿Cómo accedo a esa nueva página desde la página?". Se le entregó el bloque que suma un link "Ver más trabajos →" al lado de "Más en Instagram →", en la sección Trabajos de la landing, sin cambiar nada más de la landing.
- **Pidió que quede como estándar:** "Importante: documentá esta nueva página que hicimos, para términos de estandarización. Pasé las imágenes en 1:1. Por si se me olvida." → `04-operations/trabajos/estandar.md`.
- **Y preguntó por el riesgo:** "¿Es ilegal publicar mesadas de clientes sin su expreso 100% conocimiento? Si lo peor que puede pasar primero es que ellos exijan quitar su foto, banco. Si lo peor que puede pasar primero es una denuncia o algo así, ahí sí no banco." Lo que se encontró: `06-knowledge/layer-2-landscape/publicar-fotos-de-casas-de-clientes.md`. Su criterio queda como regla: se publica mientras el primer paso esperable sea un pedido de sacar la foto.

## Knowns

- El espesor sale de la calculadora (20 mm granito y cuarzo, 12 mm sinterizado); la textura de cada material, del catálogo de la landing.
- Las fotos pasaron de unos 6 MB a entre 40 y 200 KB según el tamaño que pide cada pantalla. El dueño no convierte nada.
- Probado acá: 237 comprobaciones en computadora, tablet y celular, y 78 de la vista previa (sin internet y sin JavaScript, midiendo que cada bloque sea igual al de la página de verdad).

## Known unknowns

- El riesgo legal de publicar la cocina de un cliente sin avisarle: no se encontró un fallo sobre este caso y quien lo miró no es abogado. Falta una consulta, o pedir el permiso.
- Si la vista por la ventana de la foto de Gris Mara deja ubicar el edificio.
- El estilo de la píldora: se le mostraron tres y no eligió entre ellas; está la de fondo dorado suave.
- Las descripciones de Gris Mara y de Blanco Cana las escribió quien armó la página: las confirma el dueño.
- Si el crema más claro va también a la landing.
- Que el link "Ver más trabajos →" en la sección Trabajos de la landing sea el lugar que quiere el dueño: fue una propuesta de quien la armó, entregada cuando preguntó cómo se llegaba.
- Que "la opción C" sea la píldora arriba y el texto abajo: así se leyó; se le mostró aplicada y falta su visto bueno.
- Si la página trae consultas. El botón de cada material manda el mensaje de WhatsApp con el material escrito: se puede contar cuántos chats llegan así.

## Unknown unknowns

- Cada página nueva es una promesa a la vista. Lo que ahí se dice del material ("no se raya", "resiste el calor") lo va a citar algún cliente.
