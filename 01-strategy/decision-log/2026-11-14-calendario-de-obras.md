# Calendario de obras y plazo de entrega modificable

**Fecha:** 2026-11-14
**Autor:** dueño + IA
**Status:** ejecutándose

---

## 1. Qué estamos decidiendo

Sumar al hub una página hermana del tablero, el calendario, que muestra las mismas obras en el mes y permite agendar mediciones e instalaciones y **modificar el plazo de entrega** de una obra puntual.

## 2. Por qué ahora

El tablero ordena por etapa; no deja ver de un vistazo qué cae cada día ni qué vence cuándo. El dueño pidió una "vista de halcón" desde la que también se pueda actuar. Decisión de base: ver [2026-11-14-tablero-de-obras.md](2026-11-14-tablero-de-obras.md).

## 3. Knowns / Known unknowns / Unknown unknowns

**Knowns**
- El plazo real es 15-20 días corridos desde la medición (regla dura del negocio). El calendario no cambia esa regla: el plazo calculado sigue siendo el default.
- El calendario no guarda datos propios: usa el mismo endpoint y el mismo archivo que el tablero. No hay nada que sincronizar.
- Hay obras con fecha comprometida distinta del cálculo (el cliente pide más tarde, o se acuerda otra cosa). Hasta hoy eso no tenía dónde anotarse.

**Known unknowns**
- Con cuántas obras por día la grilla del mes deja de leerse bien (probado con pocas). `status: hypothesis` que alcanza para el volumen actual.
- Si modificar el plazo debería dejar registro de quién y por qué. Hoy no lo deja: depende de la decisión pendiente sobre usuarios.
- Si en celular hace falta la grilla además de la lista.

**Unknown unknowns**
- Un plazo modificable puede usarse para "tapar" atrasos corriendo la fecha en vez de resolverlos. No hay freno técnico; se mira en la retro del tablero.

## 4. Decisión

Se construye. El plazo modificado se guarda por obra y manda sobre el calculado; se puede volver al normal con un toque. No se agregan permisos ni registro de autor en esta versión.

## 5. Retro

Junto con la del tablero (2026-12-14 a 2026-12-21): ¿se usa el calendario para agendar o se sigue agendando desde el tablero? ¿Cuántas obras tienen el plazo modificado y hacia dónde (antes o después del normal)?

---

## Actualización 2026-11-16 — plazo único de 20 días y bloques de medición

**Decisión del dueño:**

- El estándar de entrega pasa a ser **20 días corridos desde la medición para todos los materiales**. Antes el tablero daba 15 al granito cotizado en la calculadora y 20 al resto. El dueño se ocupa de que ese estándar se cumpla y se comunique.
- La ventana "15 a 20" queda como **idea semanal** de dónde entra cada entrega, no como dos plazos distintos: el día 20 vence.
- Las mediciones se hacen en tandas (hoy sábados y lunes). Todas las obras medidas un mismo día se tratan como un **bloque**, también cuando es una sola.

**Knowns**

- Casi todas las fichas actuales están cargadas a mano y ya usaban 20 días: el cambio de regla solo mueve las de granito cotizadas en la calculadora.
- Con 8 mediciones en un día, el calendario apilaba 8 vencimientos en un solo día y no mostraba la semana de entrega. El dueño pidió conservar esos cartelitos y sumar la franja del bloque.

**Known unknowns**

- Si la ventana 15-20 refleja lo que el taller realmente tarda. Se va a ver comparando la fecha de instalación real contra la franja de cada bloque.
- Las páginas públicas de materiales (granito y cuarzo) dicen "15 días corridos". Quedó avisado al dueño; no se tocaron.

**Unknown unknowns**

- Si en algún momento se mide todos los días, habría hasta seis franjas superpuestas por día y la vista se cargaría. Hoy son dos tandas por semana.

