# 03-product/calculadora/

Documentación de la calculadora interna de cotizaciones (`/calculadora/` del sitio).

## Contenido

| Archivo | Qué es |
|---|---|
| `functional-map.md` ⭐ | Mapa funcional completo: auth, modelo de datos, lógica de pricing, materiales, exports, integraciones, fragilidad |
| `baseline-v1.0.md` ⭐ | Marca formal de la versión funcional sin errores conocidos al 2026-05-02. Cualquier modificación tiene que respetarla o documentar regresión |

## Reglas

- **La baseline v1.0 es la referencia.** Cualquier cambio que rompa una funcionalidad descrita en `functional-map.md` es regresión y debe revertirse o explicarse en `01-strategy/decision-log/`.
- **Antes de modificar `calc.html`:** leer `functional-map.md` entero. La calc tiene 4703 líneas y 11 materiales con lógicas distintas — no es modificable a ciegas.
- **Materiales hardcoded:** agregar un material nuevo toca al menos 3 estructuras (MATS, COLORS_DB, SINT). Ver § Fragilidad #1.
