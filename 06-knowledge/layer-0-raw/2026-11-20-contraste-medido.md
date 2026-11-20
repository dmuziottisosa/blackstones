# Contraste medido de las pantallas del sistema interno (19 y 20/11/2026)

Salida cruda del programa de medición, sin editar. Lo único que se sacó es el texto de ejemplo de cada renglón.

## Cómo se midió

- Un programa abre cada pantalla en Chromium (Playwright), con datos de prueba, y recorre todos los textos visibles.
- De cada uno toma el color que el navegador calculó y lo compara con el fondo que tiene de verdad (sumando las capas con transparencia y la opacidad heredada).
- Razón de contraste según WCAG 2.x. Se anota lo que queda por debajo de 4,5:1 (o de 3:1 si el texto es de 24 px o más, o de 18,66 px o más en negrita).
- No se mide lo apagado (`disabled`) ni el encabezado negro con el menú.
- Cada renglón: razón de contraste, tamaño y peso de la letra, elemento.
- En la medición de "después", a la raya de una celda vacía de contratos (`.raya`) se le pide 3:1.

## Antes

19/11/2026, con el barrido de diseño ya hecho y antes de corregir el contraste. Computadora, 1440 px.

```
== tablero · light: 9 pares por debajo de AA
   2.85 13px/800  span.col-n
   2.93 13px/800  span.col-n
   3.55 13px/800  span.col-n
   3.78 13px/800  span.col-n
   4.14 10px/800  div.c-prox
   4.14 11.5px/700  span.c-pos
   4.24 10px/800  div.c-prox
   4.24 11.5px/700  span.c-pos
   4.14 15px/800  button.c-hecho.listo
== tablero · dark: todo el texto llega a AA
== contratos · light: 4 pares por debajo de AA
   2.24 13.5px/500  div.plata.nada.raya
   2.24 12px/600  span.sn.raya
   2.92 12.5px/600  i
   4.14 12px/700  a
== contratos · dark: 3 pares por debajo de AA
   2.52 13.5px/500  div.plata.nada.raya
   2.52 12px/600  span.sn.raya
   3.32 12.5px/600  i
== calendario · light: 22 pares por debajo de AA
   1.6  12px/500  button.ev.vence.prev
   1.6  12px/700  b
   1.66 11.5px/700  s
   1.66 11.5px/700  span
   1.69 13px/600  div.n
   1.69 12px/500  button.ev.hecha.et-medir
   1.71 11.5px/700  s
   1.71 11.5px/700  span
   1.75 13px/600  div.n
   1.75 12px/500  button.ev.hecha.et-medir
   1.85 10.5px/800  em.ok
   1.94 12px/700  b
   1.94 13px/700  div.n
   1.96 11.5px/600  span
   2    12px/700  b
   2    13px/700  div.n
   2.15 10.5px/800  span.mes-n
   2.15 10px/800  i
   2.22 10.5px/800  em.ok.inst.et-instalar
   3.36 11px/900  b
   4.04 12px/700  i
   4.09 12px/500  span
== calendario · dark: 22 pares por debajo de AA
   1.69 12px/500  button.ev.vence.prev
   1.69 12px/700  b
   1.89 13px/600  div.n
   1.89 12px/500  button.ev.hecha.et-medir
   1.9  13px/600  div.n
   1.9  12px/500  button.ev.hecha.et-medir
   2.03 11.5px/700  s
   2.03 11.5px/700  span
   2.09 11.5px/700  s
   2.09 11.5px/700  span
   2.12 10.5px/800  em.ok.inst.et-instalar
   2.15 10.5px/800  span.mes-n
   2.15 10px/800  i
   2.19 10.5px/800  em.ok
   2.41 11.5px/600  span
   2.46 12px/700  b
   2.46 13px/700  div.n
   2.47 12px/700  b
   2.47 13px/700  div.n
   3.31 11px/900  b
   4.08 12px/500  span
   4.44 12px/500  span
== control · light: 3 pares por debajo de AA
   4.24 12.5px/400  small
   4.24 12.5px/400  div.rg-cuenta
   4.24 12.5px/600  span.o
== control · dark: 3 pares por debajo de AA
   4.34 12.5px/400  small
   4.34 12.5px/400  div.rg-cuenta
   4.34 12.5px/600  span.o
TOTAL: 66
```

## Después

20/11/2026. Computadora (1440 px), tablet (834 px) y celular (390 px); tablero por columnas y por etapa, con una ficha abierta; contratos con un contrato abierto; Control con la lista de respaldos abierta.

```
== tablero · light: todo el texto llega a AA
== tablero · dark: todo el texto llega a AA
== tablero por etapa · light: todo el texto llega a AA
== tablero por etapa · dark: todo el texto llega a AA
== tablero tablet · light: todo el texto llega a AA
== tablero tablet · dark: todo el texto llega a AA
== tablero celular · light: todo el texto llega a AA
== tablero celular · dark: todo el texto llega a AA
== contratos · light: todo el texto llega a AA
== contratos · dark: todo el texto llega a AA
== contratos tablet · light: todo el texto llega a AA
== contratos tablet · dark: todo el texto llega a AA
== contratos celular · light: todo el texto llega a AA
== contratos celular · dark: todo el texto llega a AA
== control · light: todo el texto llega a AA
== control · dark: todo el texto llega a AA
== control celular · light: todo el texto llega a AA
== control celular · dark: todo el texto llega a AA
TODO AA
```

## Calendario

20/11/2026. No se cambió nada del calendario salvo el tono del verde. Computadora, 1440 px.

```
== calendario · light: 22 pares por debajo de AA
   1.6  12px/500  button.ev.vence.prev
   1.6  12px/700  b
   1.66 11.5px/700  s
   1.66 11.5px/700  span
   1.69 13px/600  div.n
   1.69 12px/500  button.ev.hecha.et-medir
   1.71 11.5px/700  s
   1.71 11.5px/700  span
   1.75 13px/600  div.n
   1.75 12px/500  button.ev.hecha.et-medir
   1.94 12px/700  b
   1.94 13px/700  div.n
   1.96 11.5px/600  span
   2    12px/700  b
   2    13px/700  div.n
   2.13 10.5px/800  em.ok
   2.15 10.5px/800  span.mes-n
   2.15 10px/800  i
   2.22 10.5px/800  em.ok.inst.et-instalar
   3.36 11px/900  b
   4.04 12px/700  i
   4.09 12px/500  span
== calendario · dark: 22 pares por debajo de AA
   1.69 12px/500  button.ev.vence.prev
   1.69 12px/700  b
   1.89 13px/600  div.n
   1.89 12px/500  button.ev.hecha.et-medir
   1.9  13px/600  div.n
   1.9  12px/500  button.ev.hecha.et-medir
   2.03 11.5px/700  s
   2.03 11.5px/700  span
   2.09 11.5px/700  s
   2.09 11.5px/700  span
   2.12 10.5px/800  em.ok.inst.et-instalar
   2.15 10.5px/800  span.mes-n
   2.15 10px/800  i
   2.19 10.5px/800  em.ok
   2.41 11.5px/600  span
   2.46 12px/700  b
   2.46 13px/700  div.n
   2.47 12px/700  b
   2.47 13px/700  div.n
   3.31 11px/900  b
   4.08 12px/500  span
   4.44 12px/500  span
TOTAL: 44
```
