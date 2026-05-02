# Calculadora — Baseline v1.2

> **Marca formal: la versión funcional sin errores conocidos al 2026-05-02.**
>
> **Cambios desde v1.1 (2026-05-02):**
> - Layout de exports PDF y Excel reorganizado: el bloque "Alternativas de color" (variantes referenciales) ahora se renderiza al **final** del documento, después del Subtotal General / TOTAL del presupuesto principal. Antes se intercalaba entre los items principales y los adicionales, lo que dejaba el subtotal de lo principal visualmente DEBAJO de los subtotales de las variantes — rompiendo la jerarquía visual cuando el toggle "Total" estaba desactivado. Implementación vía closure `_renderVarsXls()` (Excel) y string buffer `_varsHtmlPdf` (PDF). Lógica de cálculo intacta. Detalle en `functional-map.md` § 8.
>
> **Cambios desde v1.0 (2026-05-02):**
> - Lógica del +0,60 m² en bacha de baño endurecida: ahora se aplica **solo** cuando `tipo === 'Bacha armada'`. Antes se aplicaba a cualquier `tipo` distinto de `'Con Traforo'`, lo que dejaba un bug latente para futuros tipos. Comportamiento preservado para los dos tipos existentes (default sigue siendo Bacha armada con +0,60). Razón documentada en `functional-map.md` § 6.1.

---

## Estado declarado por el dueño

> "QUIERO QUE ENTIENDAS LA CALCULADORA Y QUE ENTIENDAS QUE ES LA ULTIMA VERSION FUNCIONAL PARA MI SIN ERRORES CONOCIDOS."
>
> — Dueño de BlackStones, 2026-05-02

---

## Snapshot de la baseline

- **Fecha de declaración:** 2026-05-02
- **Branch en GitHub:** `claude/setup-new-repo-tR58e`
- **Path:** `site/public_html/calculadora/`
- **Archivos versionados:**

| Archivo | Líneas | Bytes |
|---|---|---|
| `calc.html` | 4703 | 273.314 |
| `index.php` | 31 | 883 |
| `login.php` | 219 | 5.533 |
| `logout.php` | 5 | 100 |
| `auth_check.php` | 148 | 4.105 |
| `auth_config.php` | 35 | 1.483 |
| `dolar.php` | 158 | 6.327 |
| `.htaccess` | 24 | 835 |

(`dolar_cache.json` y `.auth_attempts.json` son runtime y NO forman parte de la baseline — se regeneran solos.)

- **Funcionalidades garantizadas:** las descritas en `functional-map.md`. Resumen:
  - Login con password (rate limited).
  - 5 secciones de cotización (Mesada, Alzada, L, Isla, Baño).
  - 11 materiales (Guidoni, Mármol, Granito Nacional, Granito Importado, Xtone, Purastone, Pura Prima, Dekton, Silestone, Neolith, Suprastone) con ~200 colores.
  - Adicionales: flete, escalera, ángulos, zócalos >5 cm, extras custom, bachas/accesorios (~120).
  - Hasta 5 variantes de color alternativas.
  - IVA 21% opcional.
  - Conversión USD→ARS con DolarHoy (cache 5 min, fallbacks 3 niveles).
  - Export Excel (ExcelJS) y PDF (browser print).
  - Mobile-first responsive.
  - Tema dark/light toggle.
  - Logout.

---

## Compromiso operativo

1. **No se modifica `site/public_html/calculadora/` sin entender qué se está tocando.** Lectura obligatoria de `functional-map.md` antes.
2. **Cualquier modificación que rompa una funcionalidad listada arriba = REGRESIÓN.** Se revierte o se documenta en `01-strategy/decision-log/YYYY-MM-DD-regresion-calc.md` con razón explícita.
3. **Si el dueño reporta un bug nuevo:** se loggea en `bugs-log.md` (a crear cuando aparezca el primero) con repro, severidad, y baseline a la que se compara.
4. **Si se planea un cambio mayor (refactor de pricing, nuevo tipo de sección, nuevo material):** entry previa en `01-strategy/decision-log/` con knowns / known-unknowns / unknown-unknowns. NO improvisación.

---

## Cómo verificar que sigue siendo la baseline

Estas son las pruebas mínimas de "no rompí nada" antes de declarar un cambio como deployable:

1. **Login funciona:** `https://blackstones.com.ar/calculadora/` → form de password → ingresar → ver la calc.
2. **Dolar carga:** la cotización aparece en el header (no muestra "Error al cargar").
3. **Cotización mínima:** crear 1 ítem en Mesada con material Guidoni, color cualquiera, dimensiones 2×0,6, regrueso L+A 5 cm → m² calcula > 0, total USD > 0.
4. **Excel exporta:** botón Excel → archivo descargado abre en Excel/Google Sheets sin error.
5. **PDF exporta:** botón PDF → ventana de print abre con el contenido formateado.
6. **Logout funciona:** redirige a login.
7. **Cookie persiste 60 días:** cerrar y reabrir browser dentro del lifetime → no pide re-login.

Si las 7 pasan → la modificación no rompió la baseline.

---

## Próxima revisión

- Cuando se rote la password (`AUTH_PASSWORD_HASH`) y/o el `AUTH_SECRET` → actualizar este doc con la fecha de rotación.
- Cuando se sume material #12 → actualizar.
- Cuando se cambie el dominio o el path → actualizar.
- Default: revisar cada vez que se modifique cualquier archivo de `site/public_html/calculadora/`.
