# 04-operations/

Cómo se ejecuta el día a día: proceso de venta-fabricación-colocación, ruteo de mediciones, deploy del sitio.

## Contenido

| Archivo / carpeta | Qué es |
|---|---|
| `process.md` (pendiente) | El proceso completo: cotizás → medimos → aprobás → fabricamos → colocamos |
| `routing/` | Lógica de ruteo de mediciones (sale de Lanús, agrupar visitas cercanas) |
| `deploy-notes.md` ⭐ | Cómo subir cambios del sitio a Hostinger (manual, FTP, PowerShell script) |
| `ftp-map.md` ⭐ | Mapa mental del FTP de Hostinger: dónde aterriza el user, dónde está el web root, mapeo local↔remoto |
| `deploy-snippets.md` ⭐ | Bloques de PowerShell copy-paste para uso diario (subir 1 archivo, sync carpeta, listar, borrar, backup) |

## Reglas

- **Lo que pasa acá afecta calidad de vida del cliente.** Una medición mal coordinada = obra demorada = avatar furioso.
- **Process = promesa.** Si decimos "15-20 días corridos" en ads, este es el doc que asegura que se cumpla.
- **Loggeo de excepciones.** Cada vez que el proceso se rompe (medición reagendada, plazo incumplido, error de fabricación) → línea en `excepciones.log` (pendiente). Sin esto no aprendemos.
