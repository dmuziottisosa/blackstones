# 02-marketing/creatives/

Activos visuales: static ads, video scripts, conceptos de campaña.

## Subcarpetas

| Carpeta | Qué vive ahí |
|---|---|
| `static-ads/` | ~120 prompts de static ads del compendio + nuevos. Organizados por avatar y ángulo. Ver `index.md`. |
| `video-scripts/` | Guiones de video: ganador actual + variantes en testeo + descartados. |
| `visual-concepts/` | Concepts de campaña que abarcan múltiples piezas (ej: serie "Mirá de cerca", serie "Antes/después"). |

## Reglas de esta carpeta

- **Cada static / video tiene un avatar declarado.** Si no sabés a quién le habla, no está terminado.
- **Cada creativo tiene un status:** `idea | prompt-listo | generado | en-test | ganador | descartado`.
- **Los ganadores se documentan con su métrica.** "Funciona" no es métrica — CTR, CPM, costo por mensaje sí.
- **Los descartados también se guardan** con razón. No tirar trabajo — sirve para futuras iteraciones.

## Pipeline estándar

```
1. IDEA          → texto suelto en este README o en visual-concepts/
2. PROMPT-LISTO  → archivo .md en static-ads/ o video-scripts/
3. GENERADO      → asset en assets/ + entry actualizada con preview
4. EN-TEST       → publicado en Meta, anotado en tests-log
5. GANADOR       → movido al library/ de meta-ads, replicar variantes
6. DESCARTADO    → tag descartado + razón. No borrar.
```
