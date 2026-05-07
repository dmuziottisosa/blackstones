# video-scripts/

Guiones de video para producción en CapCut + ElevenLabs.

## Archivos canónicos

| Archivo | Para qué |
|---|---|
| **[`capcut-output-format.md`](./capcut-output-format.md)** ⭐ | **Formato canónico para CapCut.** 3 bloques: CONCEPTO + NARRACIÓN + ESCENAS VISUALES. Incluye el output ganador de BlackStones como ejemplo. **Punto de entrada para crear cualquier guion nuevo.** |
| [`guion-ganador-2026-05.md`](./guion-ganador-2026-05.md) | Versión del guion ganador con cues `[excited]` `[happy]` para ElevenLabs. **Solo audio**. Cross-linkea con el de CapCut. |

## Pipeline de producción de un video

```
1. ESCRIBIR EL GUION CAPCUT
   → Archivo en este directorio: capcut-{nombre}-{YYYY-MM}.md
   → 3 bloques: CONCEPTO / NARRACIÓN / ESCENAS VISUALES
   → Ver formato en capcut-output-format.md

2. GENERAR AUDIO
   → Tomar el bloque NARRACIÓN
   → Agregar cues [excited] [happy] [thoughtful] [surprised] etc.
   → Persistir como archivo separado: {nombre}-elevenlabs.md
   → Pasar por ElevenLabs → archivo de audio

3. PRODUCIR VIDEO EN CAPCUT
   → Importar audio
   → Para cada ESCENA del guion CapCut:
     · Buscar / filmar el clip que matchea la descripción visual
     · Agregar el texto en pantalla literal (entre comillas en el doc)
     · Cortar en los timestamps indicados

4. PUBLICAR + MEDIR
   → Subir a Meta Ads
   → Loggear en meta-ads/tests-log
   → Si gana → mover a status `ganador`
   → Si pierde → status `descartado` + razón
```

## Reglas

- Cada video tiene **dos archivos hermanos**: uno CapCut (este formato), uno ElevenLabs (con cues). Se mantienen sincronizados.
- **Nunca borrar guiones descartados** — quedan con `status: descartado` y razón. Sirve para iteraciones futuras.
- Cada guion declara su avatar (Carolina, arquitecta, desarrollador, etc.) y su status (`idea | borrador | en-test | ganador | descartado`).

## Convenciones de naming

```
capcut-{angle-corto}-{YYYY-MM}.md      ← guion CapCut
{angle-corto}-elevenlabs-{YYYY-MM}.md  ← contraparte ElevenLabs
```

Ejemplos:
- `capcut-mecanismo-2026-05.md` + `mecanismo-elevenlabs-2026-05.md`
- `capcut-cliente-recurrente-2026-06.md` + `cliente-recurrente-elevenlabs-2026-06.md`
- `capcut-celestial-blue-2026-05.md` + `celestial-blue-elevenlabs-2026-05.md`
