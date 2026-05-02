# 02-marketing/

Todo lo que se publica al mundo: ads pagos, copy de IG/web, creativos visuales, y conversaciones de WhatsApp.

## Subcarpetas

| Carpeta | Qué vive ahí |
|---|---|
| `meta-ads/` ⭐ | Estrategia de Meta, campañas activas, library de creativos ganadores, tests-log |
| `copywriting/` | Frameworks (Schwartz, Sugarman, etc.), captions por canal, banco de hooks |
| `creatives/` | Static ads (~120 prompts), video scripts, conceptos visuales |
| `whatsapp/` | Flow de venta por WhatsApp, templates por escenario, banco de objeciones |

## Reglas de esta carpeta

- **Nada se publica sin pasar por el avatar.** Cualquier creativo / copy se filtra contra `01-strategy/avatars/[avatar].md`.
- **Naming entre canales debe coincidir.** Si el static dice "todo adentro", el WhatsApp dice "todo adentro" (no "todo incluido").
- **Lo que NO funcionó también se documenta.** Los hooks que no convirtieron, los ángulos que no pegaron — van al library con tag `tested-failed`.
- **Una variable por test, siempre.** En Meta y en WhatsApp.

## Cómo se mide qué publicar

Filtro en orden de prioridad:

1. ¿Refuerza el mecanismo (`01-strategy/mechanism.md`)?
2. ¿Habla al avatar correcto (`01-strategy/avatars/`)?
3. ¿Pasa el test de Living (`00-brand/voice-tone/voice-tone.md`)?
4. ¿Cumple el sistema visual (`00-brand/visual-identity/visual-system.md`)?

Si las 4 son sí → publicar.
Si una es no → reformular o descartar.
