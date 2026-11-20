# Cómo se lee una pantalla de trabajo: lo que dice la investigación

> **Status:** `layer-2-landscape` — investigación externa, 19 y 20 de noviembre de 2026.
> **Trigger de creación:** el dueño pidió un barrido de diseño del tablero por columnas y de Relación-Contrato ("abría el tablero formato columna y me perdía en 3 segundos") y pidió que se investigara: "colores, el ojo humano, el cerebro, los patrones, la memoria, el orden".
> **Validez:** alta en lo que sale de un paper o de una norma (Healey, Cowan, WCAG). Media en lo que es práctica de oficio (NN/g, Ström, Refactoring UI, lean): son criterios de gente que diseña mucho, no experimentos. Cómo aplica a nuestras pantallas es interpretación nuestra y está marcado como tal en cada punto.
> **Dónde se aplicó:** `01-strategy/decision-log/2026-11-19-barrido-de-diseno.md`. Lo que medimos nosotros está en `06-knowledge/layer-0-raw/2026-11-20-contraste-medido.md`.

Regla del repo: esto es brújula, no mapa. Si el uso real del tablero contradice algo de acá, gana el uso real.

---

## 1. Cuántos colores se distinguen sin leer

**Fuente.** Healey, C. G. (1996), *Choosing Effective Colours for Data Visualization*. Experimentos con 38 observadores que tenían que encontrar un elemento de un color entre otros.

**Qué encontró.**
- Que un color se encuentre rápido depende de tres cosas: la distancia entre los colores, que el color buscado se pueda "separar con una línea" de los demás en el espacio de color, y que caiga en otra categoría con nombre (rojo, azul, verde).
- El techo: "seven isoluminant colours is the maximum we can display at one time, while still allowing rapid and accurate identification". Con más de siete, hay que mirar de a uno.

**Qué hicimos con eso (interpretación nuestra).** El sistema ya gastaba ocho: cinco etapas más rojo, ámbar y verde. Y dos de ellos, el ámbar de "pide una acción" y el naranja de Por fabricar, caen en la misma categoría. Por eso el color de la etapa dejó de repetirse en cada ficha y pasó a ser del carril entero: en la ficha quedan tres colores con significado (rojo, ámbar, verde) y tinta. El choque ámbar/naranja no se resolvió del todo: quedó anotado en el decision log.

---

## 2. Cuántas cosas entran en la cabeza a la vez

**Fuente.** Cowan, N. (2001), *The magical number 4 in short-term memory: A reconsideration of mental storage capacity*, Behavioral and Brain Sciences 24: 87–185.

**Qué dice.** La memoria de trabajo sostiene alrededor de cuatro bloques (entre tres y cinco según la persona y la tarea), no los "siete más o menos dos" que se repiten de memoria.

**Qué hicimos con eso (interpretación nuestra).** La ficha cerrada tiene cuatro voces y no más: quién, qué material, cómo está y la nota. Lo que era una quinta y una sexta (el contador de tildes, "Sin entregar" en cada ficha) salió de la ficha y se dice una vez arriba. No es que Cowan hable de fichas: es la razón para ponerle un tope y no seguir sumando.

---

## 3. Qué hace que dos cosas se vean "del mismo grupo"

**Fuente.** Nielsen Norman Group, Aurora Harley: *The Principle of Common Region: Containers Create Groupings* (12/07/2020) y *Proximity Principle in Visual Design* (02/08/2020).

**Qué dicen.**
- Un borde o un fondo compartido agrupa con mucha fuerza: puede ganarle a la cercanía y al parecido.
- Es fácil abusar: cada caja suma ruido. Antes de dibujar una, preguntarse si el espacio en blanco no alcanza.
- Lo que está cerca se lee junto; lo que está separado, aparte.

**Qué hicimos con eso (interpretación nuestra).** Es la respuesta directa a lo que marcó el dueño: "si todas las etapas tienen el mismo color o no hay separación clara, todo parece parte de lo mismo". Cada etapa es ahora una región: un fondo apenas teñido con su color, una línea arriba y más aire entre columnas. Y al revés: adentro de la ficha se sacaron cajas (la nota ámbar, el marco rojo entero), porque una región adentro de otra adentro de otra es lo que la fuente llama ruido.

---

## 4. Lo normal y lo anormal

**Fuentes.** Lean Enterprise Institute, *Andon* (léxico). Mark Graban, Lean Blog, 04/08/2015, citando a Fujio Cho (Toyota): "Know normal from abnormal… right now!".

**Qué dicen.** Un tablero visual sirve si de un vistazo se distingue lo que va bien de lo que no. Para eso hacen falta dos cosas: que esté definido qué es lo normal, y que cada señal de anormal tenga una respuesta (alguien hace algo). Una señal que está siempre prendida deja de ser señal.

**Qué hicimos con eso (interpretación nuestra).** En Por cortar casi todas las fichas decían "Sin entregar" en rojo: era lo normal pintado de anormal. Ahora lo normal no se marca en cada ficha: se cuenta una vez arriba ("9 sin entregar", que además filtra). En la ficha el rojo quedó para lo que frena o está atrasado, y la pastilla, para lo que pide una acción hoy. En Relación-Contrato, deber un saldo es lo normal de un contrato en curso: pasó de rojo a tinta.

---

## 5. Texto chico: etiquetas y jerarquía

**Fuente.** Refactoring UI (Wathan y Schoger), *Labels are a last resort*.

**Qué dice.** El formato "etiqueta: valor" repetido aplana la jerarquía: todo pesa lo mismo. Cuando el dato se entiende solo, la etiqueta sobra; cuando hace falta, se junta con el valor ("3 en stock") o se la apaga para que mande el dato.

**Qué hicimos con eso (interpretación nuestra).** Es la pregunta del dueño sobre la primera versión: "¿no hay mucho microtexto sin jerarquía, mezclado?". Se sacaron rótulos y renglones que explicaban otros renglones ("11 por avanzar" más su explicación pasó a "8 atrasadas · 3 ya medidas").

---

## 6. Planillas de números

**Fuente.** Matthew Ström, *Design Better Data Tables*.

**Qué dice.** Los números, a la derecha y con cifras tabulares (todas del mismo ancho); el texto, a la izquierda; el encabezado sigue a su columna; nada centrado. La misma cantidad de decimales en toda la columna, y los menos posibles. Pocas rayas, un solo color, la unidad dicha una vez. Fondo solo para los totales.

**Qué hicimos con eso (interpretación nuestra).** En Relación-Contrato: plata a la derecha con cifras tabulares, "Factura" y "Muebles" a la izquierda como texto que son, montos sin centavos en la planilla (el exacto está al abrir el contrato), sin la barra verde por renglón y con la etapa como un punto de color y texto en tinta en vez de treinta pastillas.

---

## 7. Que se pueda leer: contraste, y no depender solo del color

**Fuente.** W3C, WCAG 2.x. Criterio 1.4.1, *Use of Color* (leído en esta investigación): el color no puede ser el único medio para decir algo. Criterio 1.4.3, *Contrast (Minimum)*: 4,5:1 para texto; 3:1 para texto grande (desde 24 px, o 18,66 px en negrita). El 1.4.3 se cita por la norma: el número es el de la norma y no se volvió a leer la página en esta sesión.

**Qué hicimos con eso.** Esto no es interpretación: se midió. Un programa recorre cada texto visible de cada pantalla y calcula su contraste contra el fondo que tiene de verdad, en claro y en oscuro, en computadora, tablet y celular. Lo que no llegaba a 4,5:1 se corrigió en el tablero, Relación-Contrato y Control. Los números de antes y después están en `06-knowledge/layer-0-raw/2026-11-20-contraste-medido.md`. Y por el 1.4.1: cada color va acompañado de una palabra o un signo ("Plazo vencido", "?", "✓"), nunca solo.

---

## 8. Que no se pierda el renglón: separar contratos y separar grupos

**Fuente.** Nielsen Norman Group, Page Laubheimer: *Data Tables: Four Major User Tasks* (03/04/2022), leído el 20/11/2026.

**Qué dice.** En la tarea de comparar datos: "The visual design needs to enable users to keep their place as they move their eyes across the table. Borders, zebra striping, and hover-triggered highlighting of a record can all help." Nombra tres recursos (bordes, renglones alternados y resaltar el renglón al pasar el mouse) y no elige uno. No da ningún número (ni contraste, ni grosor, ni color) y no habla de separar grupos de renglones.

**Qué hicimos con eso (interpretación nuestra).** El dueño vio la planilla de Relación-Contrato publicada y dijo: "no se nota la separación de una fila de otra, menos los días". Medido, tenía razón: la raya entre contratos tenía 1,20 a 1 de contraste y la del día 1,34 a 1. Quedó así:
- Entre contratos, una raya (borde) a 1,71 a 1. El número es nuestro: la fuente no da ninguno.
- Entre días, una franja de fondo de lado a lado. Es el punto 3 de este documento (una región agrupa más que una línea) aplicado a los días.
- No renglones alternados: la franja del día ya es un fondo, y dos fondos para decir dos cosas se confunden.
- Resaltar al pasar el mouse ya estaba.

Los números de antes y después están en `06-knowledge/layer-0-raw/2026-11-20-separacion-medida.md`.

---

## Lo que esta investigación NO dice

- No hay un estudio sobre tableros de marmolerías ni sobre este equipo. Todo lo de arriba es general; que funcione acá se sabe usándolo.
- Stephen Few (color con cuentagotas, reservarlo para lo que importa) se consultó por fuentes secundarias, no por sus libros. Coincide con Healey y con lean, pero no se cita como fuente directa.
- El orden de lectura "etapa, tramo, ficha" es una decisión de diseño apoyada en los puntos 3 y 4, no un resultado medido con usuarios.
- Sobre los renglones alternados hay un estudio conocido (Jessica Enders, A List Apart, 2008). No se leyó: se vio por resúmenes de terceros, que dicen que no encontró una mejora clara en velocidad ni en aciertos y sí una leve preferencia de la gente. No se usa como fuente.
- Que una raya de 1,71 a 1 alcance es una elección nuestra, vista en capturas. No hay norma que fije un mínimo para una raya entre renglones, y no se probó en el monitor del dueño.

## Fuentes

- Healey (1996): https://www.cs.ubc.ca/sites/default/files/tr/1996/TR-96-10_0.pdf
- Cowan (2001): https://memory.psych.missouri.edu/assets/doc/articles/2001/cowan-bbs-2001.pdf
- NN/g, Common Region: https://www.nngroup.com/articles/common-region/
- NN/g, Proximity: https://www.nngroup.com/articles/gestalt-proximity/
- Lean Enterprise Institute, Andon: https://www.lean.org/lexicon-terms/andon/
- Mark Graban, Lean Blog (04/08/2015): https://www.leanblog.org/2015/08/moving-from-visuals-to-visual-management-and-lean-thinking/
- Refactoring UI, Labels are a last resort: https://www.refactoringui.com/previews/labels-are-a-last-resort
- Matthew Ström, Design Better Data Tables: https://mattstromawn.com/writing/tables/
- NN/g, Data Tables: Four Major User Tasks (Laubheimer, 2022): https://www.nngroup.com/articles/data-tables/
- W3C, Understanding SC 1.4.1 Use of Color: https://w3c.github.io/wcag/understanding/use-of-color.html
- W3C, WCAG 2.2, SC 1.4.3 Contrast (Minimum): https://www.w3.org/TR/WCAG22/#contrast-minimum

*Revisión: cuando se haga el barrido de Presupuestos y de la Calculadora, o si el uso del tablero muestra que algo de esto no aplica.*
