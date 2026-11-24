# El hero de la landing

> La portada de `blackstones.com.ar`: la foto de fondo, el sombreado y el texto encima.
> `status:` vigente desde el 24/11/2026. La foto de computadora está en prueba: la eligió el dueño ese día.

## Cómo está hecho

- **Una foto por dispositivo**, cada una en su capa (`.hero-img-pc` y `.hero-img-mb`); la otra no se baja.
  - **Computadora (desde 900 px):** `foto_bloque_1/showroom_hero.webp`, el showroom con la mesada y los muestrarios. 2000 × 1342 px, 156 KB. Va centrada al 55 % del alto.
  - **Celular y tablet:** `foto_bloque_1/locale_interno.jpeg`, el local por dentro. Sin cambios.
- **La foto se pide por adelantado** (`<link rel="preload">` en el `<head>`, uno por dispositivo). Si se cambia la foto, se cambia también ahí: si no, el navegador baja las dos.
- **El sombreado** (`.hero-shade`) es lo que hace que el texto se lea. En computadora tiene dos formas:
  - **De 900 a 1199 px**, parejo: a ese ancho el texto ocupa casi todo el hero.
  - **Desde 1200 px**, de izquierda a derecha: oscuro donde está el texto, con un refuerzo justo detrás, y abierto a la derecha, donde se ve el showroom. Abajo cierra en oscuro para empalmar con la franja de los números.

## Lo que pidió el dueño (24/11/2026)

- "Probemos esta imagen fondo hero de desktop": mandó la foto del showroom por el chat.
- Al verla publicada: "al hero esclarecerlo un poquito más", con "criterio senior de diseñador".

Se aclaró abriendo el lado derecho en vez de aclarar todo parejo: aclarar parejo le saca fondo al texto justo donde lo necesita.

## El contraste está medido

Con `node 04-operations/tools/contraste-hero.js [url]`. De cada texto da el contraste contra el promedio de lo que tiene detrás y, entre paréntesis, contra lo más claro (el 2 % más claro, que es donde peor se lee). Medido el 24/11/2026:

| Ancho | Rótulo | Título | Título, dorado | Bajada | "Ver catálogo" |
|---|---|---|---|---|---|
| 900 px | 11,1 (5,1) | 11,0 (6,7) | 7,2 (5,1) | 9,7 (7,3) | 12,5 (10,4) |
| 1024 px | 11,1 (8,9) | 12,2 (6,8) | 7,9 (5,1) | 9,3 (7,4) | 14,0 (10,6) |
| 1200 px | 11,4 (10,6) | 14,7 (9,8) | 9,6 (6,4) | 8,4 (7,1) | 15,3 (14,3) |
| 1366 px | 11,5 (10,7) | 13,6 (8,2) | 8,9 (5,3) | 8,9 (7,4) | 15,1 (14,4) |
| 1440 px | 11,6 (10,9) | 13,0 (7,6) | 8,5 (4,9) | 9,1 (7,4) | 15,0 (12,1) |
| 1905 px | 10,7 (8,4) | 11,0 (7,7) | 7,2 (5,0) | 9,7 (7,4) | 13,1 (11,2) |
| 2560 px | 9,5 (7,6) | 10,3 (7,1) | 6,7 (5,1) | 8,6 (7,3) | 13,3 (10,2) |

Se le pide 4,5 a 1 al texto chico y 3 a 1 al título, también en lo más claro. Con el sombreado de antes, a 1905 px, el título daba 12,7 (9,2) y el dorado 8,3 (7,1): se aclaró y sigue por encima de lo que se pide.

**Si se cambia la foto o el sombreado, se vuelve a medir antes de entregar.** Una foto con una zona clara justo detrás del texto puede dejar el dorado por debajo de lo que se pide aunque "se vea bien" en una pantalla.

## Para tener en cuenta

- **En la foto se ve el exhibidor de Xtone, con su marca**, arriba a la derecha. Es una marca que vendemos. Si el dueño no la quiere en la portada, se corre la foto hacia abajo (`background-position`) hasta que quede afuera.
- **La foto anterior** (`foto_bloque_1/mesada_bloque1.webp`) sigue en su carpeta, por si se vuelve.
- **El texto del hero dice "instalada en 15–20 días"**. El plazo que se promete es 20 días corridos (regla dura de `CLAUDE.md`): está pendiente de corregir, junto con los otros lugares de la landing que dicen lo mismo.
