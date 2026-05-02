# 02-marketing/meta-ads/

Operación completa de Meta Ads (Facebook + Instagram + WhatsApp click-to-chat).

## Contenido

| Archivo / carpeta | Qué es |
|---|---|
| `strategy.md` ⭐ | Estado actual + framework de testeo + plan de escalamiento |
| `tests-log.md` | Registro de cada test corrido (qué variable, qué hipótesis, qué resultado) |
| `campaigns/` | Una carpeta por campaña activa con brief + métricas + creativos asociados |
| `library/` | Creativos ganadores reusables (statics, videos, copy) con tag de performance |

## Filosofía Meta Ads

- **El creative ES el targeting** (Andromeda 2025-2026). El avatar lo eligen los algoritmos a partir de quién resuena con el creative.
- **El static vende el click, no la mesada.** El cierre lo hace WhatsApp.
- **Una variable por test.** Cambiar 2 cosas y ganar = no aprendiste cuál ganó.
- **Formatos separados por ad set.** Statics con statics, videos con videos. Mezclar baja CTR.
- **7-14 días sin tocar nada.** Ansiedad mata aprendizaje.
- **Carolina es Solution Aware.** No le tenemos que explicar qué es una mesada — le tenemos que explicar por qué BlackStones es la marmolería.

## Convenciones de nombres en Meta

Estandarizamos en Ads Manager:

```
Campaña:   [Objetivo] - [Avatar] - [Mes]
           Ej: "Mensajes - Carolina - 2026-05"

Ad Set:    [Formato] - [Tema] - [Audiencia]
           Ej: "Static - Mecanismo 24hs - Open Targeting CABA"

Anuncio:   [Hook] - [Variante]
           Ej: "Si ya tenes muebles - V1 video"
```

## Métricas que importan

| Métrica | Threshold de alerta |
|---|---|
| CPM | > USD 8 → revisar audience / creative |
| CTR (link) | < 1% → creative no engancha |
| CPM mensaje | > USD 5 → ver flow WhatsApp también |
| Cost per lead bueno | > USD 15 → revisar ángulo o audience |
| Leads / día (acumulado) | < 10 → escalar / cambiar pool |

> Status: thresholds son **hypothesis** (mayo 2026). Validar con 30 días de data acumulada.
