# 05-ai-systems/

Stack de herramientas IA para generación visual + prompt library.

## Filosofía operativa

> **El modelo correcto para el caso correcto vence al "modelo favorito" del operador.**

Hoy el panorama de modelos cambia cada 3-6 meses. Lo que hoy es state-of-the-art en consistencia de personaje (Higgsfield SOUL) puede ser superado el próximo trimestre. Por eso este sistema es **una matriz de decisión**, no una recomendación monolítica.

## Contenido (planificado)

| Archivo / carpeta | Qué será |
|---|---|
| `image-generation-master.md` ⭐ | Doc raíz — matriz "qué modelo para qué caso" + workflows + filtro de aprobación |
| `tools/higgsfield-guide.md` | Higgsfield SOUL 2.0 + SOUL ID — para personaje consistente recurrente |
| `tools/nano-banana-guide.md` | Nano Banana 2 (Flash) + Nano Banana Pro — velocidad/volumen + texto perfecto |
| `tools/gpt-image-guide.md` | GPT Image — edición de fotos reales + briefs creativos + reasoning complejo |
| `prompt-library.md` | Templates listos para copiar/pegar — por categoría (UGC, hero, static ad, packshot, IG feed, edición, diagrama, email) |

## Workflow estándar

Cuando vamos a generar una imagen:

1. Identificar el caso de uso (UGC / hero / static ad / packshot / edición de foto real / etc.)
2. Consultar la matriz en `image-generation-master.md` → qué modelo para este caso
3. Buscar template más cercano en `prompt-library.md`
4. Adaptar placeholders al brief específico
5. Ejecutar en el modelo correcto
6. **Filtrar contra checklist de `00-brand/visual-identity/visual-system.md` § 3** antes de publicar
7. Si pasa → archivar en `assets/` + actualizar status del prompt
8. Si no pasa → regenerar (sin excepciones)

## Aclaración importante

- **Higgsfield es plataforma**, no modelo. Aloja Soul 2.0, SOUL ID, y otras integraciones (Veo, Lyria). Cuando este sistema dice "Higgsfield" se refiere a usar Soul 2.0 + SOUL ID dentro de la plataforma.
- **Nano Banana 2 / Pro** y **GPT Image** son modelos directos.

## Estado actual (mayo 2026)

> Status: **estructura definida, contenido pendiente.** El compendio mayo 2026 tiene prompts específicos de Nano Banana para statics y mejora de fotos reales — esos son la base de la `prompt-library.md` cuando arranquemos.

## Pendientes operativos críticos

- [ ] Definir si usamos modelo real / contratada / composite generado para UGC del avatar.
- [ ] Curar 20-30 fotos para entrenar Higgsfield SOUL ID (si vamos por composite).
- [ ] Crear `assets/` con subcarpetas por tipo (statics-cocina, statics-bano, ugc, hero, etc.).
- [ ] Generar set de validación (5-10 imágenes con cada modelo) para confirmar consistencia.
- [ ] Definir presupuesto mensual de generación.

## Trigger de revisión

- Aparece un modelo nuevo relevante (ej: Midjourney v8, Flux Pro, próximo Nano Banana).
- Cambia significativamente uno de los modelos actuales.
- Cambia el sistema visual oficial (`00-brand/visual-identity/visual-system.md`).
- Se identifica un caso de uso recurrente que no está en la matriz.
- Default: revisión cada 90 días.
