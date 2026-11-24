# La sección Instagram de la landing muestra sola los últimos posts

**Fecha:** 23/11/2026 (entregado el 24/11) · **Decide:** el dueño pidió que la sección sea dinámica; la forma la eligió quien lo armó y la confirma él · **Estado:** subido a GitHub; falta el deploy y cargar la llave

## Qué pidió el dueño

Con una captura de la sección: "¿alguna forma de que esta sección IG sea más dinámica, con lo que en verdad tenemos en IG?"

Hasta hoy eran seis fotos fijas (`ig_section/1.webp` a `6.webp`) y las seis llevaban al perfil.

## Qué se hizo

La sección muestra los seis posts más nuevos de @blackstones.ar y cada foto lleva a su post. El sitio le pregunta a Instagram por la API oficial, como mucho una vez por hora, y guarda una copia propia de las fotos. Si algo falla, quedan las fotos fijas. El detalle: `04-operations/instagram-en-la-landing.md`.

## Qué se descartó

- **Un widget de terceros** (los que se pegan con un código). Ponen su marca o se pagan por mes, cargan su propio programa en la landing y el diseño es el de ellos.
- **Pegar los posts con el "insertar" de Instagram.** Cada uno carga el reproductor de Instagram entero y la grilla dejaría de ser la nuestra.
- **Leer el perfil público sin permiso** (scraping). Instagram lo bloquea y se rompe sin aviso.
- **Mostrar el texto de los posts.** La documentación de Meta marca ese campo como no disponible con este tipo de acceso; pedirlo podía hacer fallar todo el pedido. La grilla no lo mostraba.
- **Poner la llave en un archivo del sitio o en el repo.** El repo es público. Vive en `bs-data/ig/`, fuera de la web, y se carga con un bloque que no pasa por el chat.

## Knowns

- **La documentación oficial de Meta** (leída el 23 y 24/11/2026; resumen en `06-knowledge/layer-2-landscape/instagram-api-para-mostrar-posts.md`): para leer la cuenta propia alcanza el acceso estándar, sin revisión de Meta ni página de Facebook; la llave se genera con un botón del panel, dura 60 días y se renueva con una llamada; los links de las fotos vencen.
- **La sección está a menos de 400 px del borde de abajo de la primera pantalla** en computadora, tablet y celular (medido). La ve casi todo el que baja un poco.
- **Pesa menos que antes en el celular:** las seis fotos fijas pesan 365 KB; seis copias de 480 px, entre 13 y 26 KB cada una (medido con las fotos de hoy).
- **Probado contra un Instagram de mentira:** 102 comprobaciones del servidor y 51 de la landing en los tres tamaños.

## Known unknowns

- **Nada de esto se probó contra el Instagram de verdad.** Desde acá no hay llave ni se puede ver producción. Se prueba el día que el dueño carga la llave: el bloque dice en el momento si Instagram la aceptó. Los cinco puntos que pueden salir distintos están al final de `04-operations/instagram-en-la-landing.md`.
- **Si los pasos del panel de Meta son hoy tal cual los describe su documentación.** Los nombres de los botones cambian seguido.
- **Cuántos de los últimos posts son reels sin portada o con audio con licencia.** Esos no traen foto por la API y se saltean; se miran los 25 más nuevos para juntar seis.
- **Si mostrar los últimos posts ayuda a que escriban.** No hay medición de la sección: ni de antes ni de ahora.

## Unknown unknowns

- La landing pasa a mostrar lo que se publique, sin que nadie lo mire antes. Un post que no pase el test de Living queda en la vidriera hasta que se borre de Instagram.
- Meta cambió esta API de raíz en diciembre de 2024 (cerró la anterior). Puede volver a hacerlo. Si pasa, la sección sigue mostrando lo último que anduvo y después las fotos fijas: no se rompe, se queda quieta.

## Cómo sabemos si sirvió

- A la semana de cargar la llave: `https://blackstones.com.ar/ig-feed.php?estado=1` tiene que decir "Todo bien" y que la llave se renovó.
- Lo que no se mide todavía: si alguien toca las fotos. Si importa saberlo, se le pone un evento a los seis links.
