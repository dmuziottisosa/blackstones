# Separación de la planilla de Relación-Contrato, medida (20/11/2026)

Números crudos de la raya entre contratos y de la separación entre días, antes y después de la segunda corrección del 20/11/2026. Motivo: el dueño, al ver la planilla publicada con sus datos, dijo "no se nota la separación de una fila de otra, menos los días".

Qué se hizo con esto: `01-strategy/decision-log/2026-11-19-barrido-de-diseno.md`, "Segunda corrección del 20/11/2026".

## Cómo se midió

- **Contraste:** la fórmula de WCAG 2 (luminancia relativa de cada color; el más claro más 0,05, dividido el más oscuro más 0,05). Es la misma que usa el programa de `2026-11-20-contraste-medido.md` para el texto.
- **Antes:** calculado sobre los colores del código publicado (commit `c5732f9`): la raya entre contratos era `border-top: 1px solid var(--border-soft)` y la del día, la misma con `var(--border)`.
- **Ahora:** leído del navegador (Chromium), del estilo calculado de cada renglón, en la página de prueba con 36 contratos de 17 días distintos.
- Contra qué: el blanco de la planilla (`--card`), que es el fondo de los renglones.

## Antes y ahora, por colores

```
claro (contra la planilla, #FFFFFF)
  1.20 : 1   #F0EAD8   raya entre contratos, antes (--border-soft)
  1.34 : 1   #E5DECF   raya entre días, antes (--border)
  1.71 : 1   #CFC5AF   raya, ahora (--raya)
  1.19 : 1   #F1EBDD   franja del día, ahora (--franja)
  1.14 : 1   #F5F0E5   encabezado de la planilla (--card-alt), para comparar
  1.06 : 1   #FAF8F4   fondo de la página (--bg), para comparar
oscuro (contra la planilla, #16140F)
  1.11 : 1   #221E18   raya entre contratos, antes (--border-soft)
  1.26 : 1   #2D2820   raya entre días, antes (--border)
  1.88 : 1   #4A4336   raya, ahora (--raya)
  1.21 : 1   #2A251C   franja del día, ahora (--franja)
  1.07 : 1   #1F1B14   encabezado de la planilla (--card-alt), para comparar
  1.08 : 1   #0A0907   fondo de la página (--bg), para comparar
entre las dos rayas de antes, claro: 1.11 : 1; oscuro: 1.13 : 1
```

Lectura: antes, la raya del contrato y la del día eran dos líneas de 1 px que se diferenciaban entre sí por 1,11 a 1. La separación del día era la misma señal que la del contrato, apenas más oscura.

## Ahora, leído del navegador

`c` es el contraste contra la planilla; `izq`, dónde empieza la raya, en px desde el borde de la planilla; `nro`, dónde empieza la columna del N°; `borde`, el contraste de las dos rayas que cierran la franja.

```
compu 1720 claro: {"tarjeta":"rgb(255, 255, 255)","rayas":[{"c":1.71,"alto":"1px","izq":96,"nro":96}],"nRayas":19,"franjas":[{"c":1.19,"alto":"12px","borde":1.71,"izq":0}],"nFranjas":16,"dias":17,"fechaDesborda":0}
compu 1720 oscuro: {"tarjeta":"rgb(22, 20, 15)","rayas":[{"c":1.88,"alto":"1px","izq":96,"nro":96}],"nRayas":19,"franjas":[{"c":1.21,"alto":"12px","borde":1.88,"izq":0}],"nFranjas":16,"dias":17,"fechaDesborda":0}
compu 1440 claro: {"tarjeta":"rgb(255, 255, 255)","rayas":[{"c":1.71,"alto":"1px","izq":96,"nro":96}],"nRayas":19,"franjas":[{"c":1.19,"alto":"12px","borde":1.71,"izq":0}],"nFranjas":16,"dias":17,"fechaDesborda":0}
notebook 1280 claro: {"tarjeta":"rgb(255, 255, 255)","rayas":[{"c":1.71,"alto":"1px","izq":92,"nro":92}],"nRayas":19,"franjas":[{"c":1.19,"alto":"12px","borde":1.71,"izq":0}],"nFranjas":16,"dias":17,"fechaDesborda":0}
notebook 1150 claro: {"tarjeta":"rgb(255, 255, 255)","rayas":[{"c":1.71,"alto":"1px","izq":88,"nro":88}],"nRayas":19,"franjas":[{"c":1.19,"alto":"12px","borde":1.71,"izq":0}],"nFranjas":16,"dias":17,"fechaDesborda":0}
```

Lectura: 36 contratos, 17 días, 16 franjas (una menos que días: el primero apoya en el encabezado) y 19 rayas (los contratos que no abren un día). En todos los anchos la raya empieza exactamente donde empieza el N° (`izq` igual a `nro`).

## Con el zoom de la pantalla (100 % a 200 %)

Pregunta: una raya de 1 px, ¿se ve igual si Windows o el navegador están al 110 %, 125 % o 150 %? Se sacó una captura con cada zoom (Chromium, ancho de 1500 px) y se contó, en una columna de píxeles que cruza las rayas, cuántos píxeles de pantalla ocupa cada raya y cuál es el contraste del más oscuro.

Como quedó (la raya es una caja de 1 px con fondo, en `::before`):

```
zoom 100 %: 7 rayas · alto en píxeles de pantalla {"1":7} · contraste del más oscuro de cada una: de 1.71 a 1.71
zoom 110 %: 7 rayas · alto en píxeles de pantalla {"2":7} · contraste del más oscuro de cada una: de 1.37 a 1.71
zoom 125 %: 7 rayas · alto en píxeles de pantalla {"2":7} · contraste del más oscuro de cada una: de 1.49 a 1.71
zoom 150 %: 7 rayas · alto en píxeles de pantalla {"2":7} · contraste del más oscuro de cada una: de 1.71 a 1.71
zoom 175 %: 7 rayas · alto en píxeles de pantalla {"2":5,"3":2} · contraste del más oscuro de cada una: de 1.71 a 1.71
zoom 200 %: 7 rayas · alto en píxeles de pantalla {"2":7} · contraste del más oscuro de cada una: de 1.71 a 1.71
```

Variante probada y descartada (la misma raya dibujada como borde del `::before`): los mismos números, renglón por renglón.

Control (la raya como borde del renglón mismo, que es como estaba antes, con el color nuevo):

```
zoom 100 %: 11 rayas · alto en píxeles de pantalla {"1":11} · contraste del más oscuro de cada una: de 1.71 a 1.71
zoom 110 %: 11 rayas · alto en píxeles de pantalla {"2":11} · contraste del más oscuro de cada una: de 1.45 a 1.71
zoom 125 %: 11 rayas · alto en píxeles de pantalla {"2":11} · contraste del más oscuro de cada una: de 1.49 a 1.71
zoom 150 %: 11 rayas · alto en píxeles de pantalla {"2":11} · contraste del más oscuro de cada una: de 1.71 a 1.71
zoom 175 %: 11 rayas · alto en píxeles de pantalla {"2":5,"3":6} · contraste del más oscuro de cada una: de 1.71 a 1.71
zoom 200 %: 11 rayas · alto en píxeles de pantalla {"2":11} · contraste del más oscuro de cada una: de 1.71 a 1.71
```

Lectura: al 110 % y al 125 % el navegador reparte una raya de 1 px en dos píxeles de pantalla y, en algunas, ninguno de los dos llega al color entero (el más oscuro queda entre 1,37 y 1,49 a 1). Pasa igual con cualquier forma de dibujarla, así que no depende de cómo se hizo. Sirve para entender la queja: una raya que al 100 % ya era de 1,20 a 1, con ese zoom queda todavía más lavada. No sabemos qué zoom usa el dueño.

## Lo que estos números no dicen

- No hay un mínimo de norma para una raya que separa renglones: WCAG pide 4,5:1 para texto, y esto no es texto. 1,71:1 es una elección nuestra, no un umbral.
- No se midió en el monitor del dueño. Un monitor con mucho brillo o poco contraste puede lavar una raya clara.
- La franja, como fondo, tiene menos contraste que la raya (1,19:1) y se ve igual: un área se nota con mucho menos contraste que una línea de 1 px. Eso se vio en las capturas, no sale de una medición.
