# layer-0-raw/

Data virgen. **Nunca se edita.** Solo se agrega.

## Qué va acá

- Briefings originales tal cual nos llegaron.
- Transcripts de conversaciones (WhatsApp, llamadas, reuniones).
- Compendios y dumps históricos sin procesar.
- Capturas de pantalla con timestamp.
- Resultados crudos de campañas exportados.

## Convención de nombres

```
YYYY-MM-tema-corto.md
YYYY-MM-DD-tema-corto.md     ← cuando importa el día específico
```

Ejemplos:
- `2026-05-compendio-original.md`
- `2026-05-brief-tecnico-original.md`
- `2026-05-15-transcripts-whatsapp-leads-meta.md`
- `2026-06-02-reunion-arquitecta-cuesta.md`

## Reglas

1. **No editar archivos existentes.** Si la data cambió, agregar uno nuevo fechado.
2. **Mantener el formato original** del dump. Si vino desordenado, queda desordenado. La layer-1 lo ordena.
3. **No agregar opinión.** "Esto significa X" no va. La interpretación es layer-1 o layer-3.
4. **Si tiene PII** (datos personales del cliente), considerar redactar antes de commitear o mantener fuera del repo si es sensible.

## Estado actual (mayo 2026)

| Archivo | Origen | Tamaño aprox |
|---|---|---|
| `2026-05-brief-tecnico-original.md` | Brief técnico de continuidad pasado al inicio del repo | ~10k palabras |
| `2026-05-compendio-original.md` | Compendio completo de marca + ads + creativos + flujo | ~12k palabras |

## Pendientes de ingesta

- Transcripts de WhatsApp del último mes (30+ conversaciones para validar lenguaje real del avatar).
- Export de métricas de Meta del primer trimestre 2026.
- Reseñas Google del rebrand VenarStones (89 reseñas a migrar / archivar).
- Avatar research original — `avatar_marmoleria_caba_gba.md` (mencionado en brief).
- WhatsApp flow original — `whatsapp_flow_marmoleria.md` (mencionado en brief).
