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
| `tablero-obras.md` | Tablero de obras del hub: columnas, tildes, reglas para avanzar, retiro en local y archivado |
| `usuarios.md` | Usuarios y roles del sistema interno: qué puede cada rol, arranque, cómo está hecho y qué hacer si alguien pierde la contraseña |
| `relacion-contrato.md` | Relación-Contrato: la planilla de contratos, la plata, el enlace con el tablero y la papelera |
| `reportes.md` | Control (antes "Reportes"): recordatorios, cierre del mes y registro de acciones. Solo administradores |
| `respaldo.md` | Respaldo: la copia de las últimas 24 horas, cómo se usa, qué vuelve y qué no. Solo administradores |
| `trabajos/` | La galería `/trabajos/` del sitio (publicada el 24/11/2026): reglas del dueño, cómo sumar un trabajo, el estándar (`estandar.md`: fotos 1:1, medidas, textos) y las herramientas que arman las páginas |
| `instagram-en-la-landing.md` | La sección Instagram de la landing muestra sola los últimos posts: cómo está hecho, cómo se carga la llave y cómo mirar el estado |

## Reglas

- **Lo que pasa acá afecta calidad de vida del cliente.** Una medición mal coordinada = obra demorada = avatar furioso.
- **Process = promesa.** Si decimos "20 días corridos" en ads, este es el doc que asegura que se cumpla.
- **Loggeo de excepciones.** Cada vez que el proceso se rompe (medición reagendada, plazo incumplido, error de fabricación) → línea en `excepciones.log` (pendiente). Sin esto no aprendemos.
