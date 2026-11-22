# 02-marketing/whatsapp/

Operación de venta por WhatsApp — el verdadero motor de cierre del negocio.

## Contenido

| Archivo / carpeta | Qué es |
|---|---|
| `flow.md` ⭐ | Flow de venta completo: primer contacto → calificación → cotización → seguimiento → cierre |
| `como-cotizamos.md` | Cómo se cotiza cuando el dueño pega un chat: la cuenta, sus reglas y qué se entrega |
| `recommendation-patterns.md` | Cómo se contesta "¿qué me recomendás?" |
| `knowledge/` | Versiones viejas de lo que se le cargó al bot (la última, Pilar v1.7, mayo 2026). **No es lo que el bot tiene hoy:** en noviembre de 2026 escribe distinto, y sus instrucciones actuales no están en el repo |
| `templates/` | Mensajes por escenario (primer respuesta, follow-up 24h, no contestó en 48h, dudas frecuentes, cierre) |
| `objection-bank.md` (pendiente) | Objeciones reales del avatar + respuestas que funcionaron |

## Por qué WhatsApp es donde se gana o se pierde

- El ad es el cebo. El sitio es el brochure. **El cierre vive en WhatsApp.**
- Carolina manda WhatsApp a 3-5 marmolerías al mismo tiempo. **El que responde primero, claro y por escrito gana 60-70%.**
- Tasa de respuesta < 2 horas = ventaja estructural sobre la competencia que tarda días.
- Los presupuestos cerrados por escrito en este chat = ahorro mental brutal para el cliente.

## Reglas operativas

- **< 2 horas de respuesta** en horario hábil (7am-9pm). Default sagrado.
- **Saludar + presentarse en 1 línea.** No mandar 5 mensajes seguidos como WhatsApp marketing genérico.
- **El primer mensaje es el de cinco puntos del dueño** (22/11/2026): medidas, material, bacha y anafe, zona y "¿El mueble ya está colocado? Si no, ¿para cuándo estaría?". De lo que el cliente ya dijo no se pregunta. Ver `flow.md`, sección 2.
- **El que llega con la medida y el color se resuelve rápido**, con una cotización corta. El trabajo de después (opciones, recálculos, seguimiento) es para el que ya tiene el mueble.
- **Cotización por escrito** dentro de 24 hs (idealmente < 4 hs).
- **Nunca audios largos** para responder. Si el cliente manda audio, lo escuchamos y respondemos por escrito.
- **Una respuesta clara > 10 mensajes fragmentados.**
- **Tono = test de Living.** Vos, conversacional, sin jerga, sin emojis spam.
- **Si el cliente está dudando entre 2 materiales** → asesoramos honestamente, no empujamos el más caro.

## Loggeo (pendiente sistematizar)

Hoy no loggeamos cada conversación. **Acción pendiente:** sistema simple para taggear cada lead con outcome final (cerrado / perdido / enfriado) + razón. Sin esto no podemos calcular tasa real de cierre ni atribuir a creativos.

Opciones a evaluar:
- Spreadsheet manual (más simple, depende de disciplina).
- Plugin de WhatsApp Business + tags nativos.
- CRM lite (Notion / Airtable con form de entrada).
- Custom script si Hostinger lo soporta.

Decisión por documentar en `01-strategy/decision-log/` cuando se elija.
