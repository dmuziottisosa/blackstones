# El hero de la landing

> La portada de `blackstones.com.ar`: la foto de fondo, el sombreado y el texto encima.
> `status:` vigente. El 24/11/2026 se probó otra foto en computadora y se volvió a la de siempre el mismo día.

## Cómo está hecho

- **Una foto por dispositivo**, cada una en su capa (`.hero-img-pc` y `.hero-img-mb`); la otra no se baja.
  - **Computadora (desde 900 px):** `foto_bloque_1/mesada_bloque1.webp`, la mesada con el anafe y la bacha.
  - **Celular y tablet:** `foto_bloque_1/locale_interno.jpeg`, el local por dentro.
- **La foto se pide por adelantado** (`<link rel="preload">` en el `<head>`, uno por dispositivo). Si se cambia la foto, se cambia también ahí: si no, el navegador baja las dos.
- **El sombreado** (`.hero-shade`) es lo que hace que el texto se lea: un velo oscuro, más cerrado abajo, con un refuerzo detrás del texto.

## La prueba del 24/11/2026

- El dueño mandó una foto del showroom: "probemos esta imagen fondo hero de desktop". Se publicó, solo en computadora.
- Al verla: "al hero esclarecerlo un poquito más". Se aclaró abriendo el lado derecho, donde se veía el showroom, y dejando oscuro detrás del texto.
- Después: "volvamos al hero que teníamos antes". Se volvió a la foto y al sombreado de siempre, tal cual estaban.

Lo que quedó de la prueba:

- **La foto del showroom sigue en su carpeta** (`foto_bloque_1/showroom_hero.webp`, 2000 × 1342 px, 156 KB), sin usar. La versión publicada con esa foto está en los commits `4f04562` (sombreado de siempre) y `00e39fc` (sombreado más claro).
- **El programa que mide el contraste del texto**, que sirve para cualquier foto que se pruebe.

## El contraste está medido

Con `node 04-operations/tools/contraste-hero.js [url]`. De cada texto da el contraste contra el promedio de lo que tiene detrás y, entre paréntesis, contra lo más claro (el 2 % más claro, que es donde peor se lee). Con la foto y el sombreado de siempre, medido el 24/11/2026:

| Ancho | Rótulo | Título | Título, dorado | Bajada | "Ver catálogo" |
|---|---|---|---|---|---|
| 900 px | 6,7 (5,2) | 11,9 (8,7) | 9,2 (7,9) | 11,0 (10,4) | 14,6 (14,2) |
| 1200 px | 6,8 (5,0) | 13,1 (10,0) | 9,3 (7,7) | 11,7 (10,9) | 14,4 (14,0) |
| 1366 px | 7,2 (5,4) | 12,7 (9,6) | 8,7 (7,4) | 11,8 (10,7) | 14,4 (14,0) |
| 1905 px | 8,5 (7,1) | 12,6 (10,2) | 8,9 (7,5) | 11,7 (10,7) | 14,6 (14,2) |
| 2560 px | 8,3 (7,6) | 12,5 (10,8) | 9,6 (8,2) | 11,5 (11,0) | 14,6 (14,5) |

Se le pide 4,5 a 1 al texto chico y 3 a 1 al título, también en lo más claro.

**Si se cambia la foto o el sombreado, se vuelve a medir antes de entregar.** Una foto con una zona clara justo detrás del texto puede dejar el dorado por debajo de lo que se pide aunque "se vea bien" en una pantalla.

## Para tener en cuenta

- **El texto del hero dice "instalada en 15–20 días"**. El plazo que se promete es 20 días corridos (regla dura de `CLAUDE.md`): está pendiente de corregir, junto con los otros lugares de la landing que dicen lo mismo.
- **Si se vuelve a probar la foto del showroom:** arriba a la derecha se ve el exhibidor de Xtone, con su marca. Con el sombreado más claro se nota más.
