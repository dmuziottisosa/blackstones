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
