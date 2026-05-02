# BlackStones

Centro de operaciones de **BlackStones Marmolería** — marca, estrategia, marketing, creativos, operaciones, conocimiento y código del sitio.

> Si sos una IA o un humano nuevo en este repo: **leé `CLAUDE.md` primero**. Te orienta en 60 segundos.

## Mapa rápido

```
CLAUDE.md         entry point (filosofía + reglas + mapa)
00-brand/         identidad visual, voz, posicionamiento
01-strategy/      avatares, mecanismo, principios, decision log
02-marketing/     meta ads, copywriting, creatives, whatsapp
03-product/       materiales, servicios, lógica de pricing
04-operations/    procesos, ruteo, deploy
05-ai-systems/    matriz de modelos de generación + prompt library
06-knowledge/     4 capas del saber (raw / synthesis / landscape / active)
site/             sitio público (deploy 1:1 a Hostinger)
```

## Stack del sitio

HTML + CSS + JS plano. PHP para auth de la calculadora. Sin frameworks, sin bundler, sin npm. Única dependencia runtime: ExcelJS por CDN para los exports.

## Deploy

El sitio vive en `site/public_html/` y se sube a `domains/blackstones.com.ar/public_html/` en Hostinger.

- **Para desplegar un cambio rápido en PowerShell:** copy-paste de las recetas en `04-operations/deploy-snippets.md`.
- **Para entender cómo está montado el FTP:** `04-operations/ftp-map.md`.
- **Para referencia detallada:** `04-operations/deploy-notes.md`.

## Filosofía

> Decidimos rápido, pero solo después de haber investigado lento — cruzando datos propios, mercado, benchmarks de ganadores y validación científica — para operar siempre desde la probabilidad más alta posible, sabiendo que no hay certezas, solo sistemas que se retroalimentan.

Detalle en `01-strategy/operating-principles.md`.

## Estado

- Producción activa en `blackstones.com.ar` (Hostinger, dominio en donweb).
- Repo privado.
- Última actualización estructural: mayo 2026.
