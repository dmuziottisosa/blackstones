---
fecha: 2026-11-17
tema: La seña no se pisa (solo aviso), "→ Aprobado" fuera de Presupuestos y plazo de 20 días
status: decidido (dueño)
---

# Seña sin pisar, sin "→ Aprobado" y 20 días

Salieron de la evaluación del sistema que pidió el dueño el 17/11/2026.

## Qué se decidió

1. **La seña del tablero y la del contrato no se pisan nunca.** "No vamos a poner ninguna sobrescritura. Si ficha del tablero y contrato difieren en seña, debe haber un aviso, nada más."
2. **"→ Aprobado" ya no existe en Presupuestos.** "Si está en el tablero es que fue aprobado."
3. **El plazo que se promete es 20 días corridos**, para todos los materiales. La web decía "15 a 20".
4. El dólar que se propone en los cobros es el **oficial** que ya trae la calculadora de DolarHoy.
5. "Dioge" es un vendedor, no un error de tipeo.

## Por qué

- Hasta el 16/11, al abrir Relación-Contrato la seña del tablero reemplazaba a la del contrato. En la simulación con el tablero real, dos contratos quedaban con "debe pesos y tiene dólares a favor" porque el tablero tenía la seña en dólares sin cotización y el contrato en pesos. Un dato de plata cambiaba sin que nadie lo decidiera.
- La etapa la dicta el tablero desde el 16/11; un botón "→ Aprobado" en Presupuestos era una segunda puerta de entrada al tablero.
- El sistema ya calculaba la entrega a 20 días; la web prometía otra cosa.

## Knowns

- Con el tablero exportado el 16/11 y la planilla: 7 de 38 contratos muestran el aviso de seña (4 con montos distintos, 3 con seña en la planilla y sin tildar en el tablero). Ninguno queda con saldo "a favor". Abrir la página no modifica ningún contrato (probado).
- No cuenta como diferencia la misma plata dicha de otra forma (dólares con cotización contra pesos; o el tablero con todo lo cobrado como seña).
- Aprobar desde Presupuestos se rechaza en el servidor. Siguen andando: perdido y recuperar, "Cargar obra" con N°, archivar, y devolver un entregado al tablero desde el Reporte.
- El asistente de WhatsApp (conocimiento v1.7) no promete plazos, así que no hubo que tocarlo.
- Las tareas automáticas de Hostinger corren: el chequeo de salud público mostró el log con 22,7 horas el 17/11.

## Known unknowns

- El aviso de seña hoy se ve solo en Relación-Contrato. En el tablero la ficha dice "Con contrato", pero no si la seña difiere.
- Cuál de las dos tareas automáticas escribió ese log. `health.php` ahora las informa por separado; falta desplegarlo y leerlo.
- `v1-backup.html` sigue en la carpeta pública diciendo "15 a 20 días". No se tocó por ser una copia de respaldo.
- Piezas ya publicadas (anuncios, videos) que digan "15-20". Los documentos de copy quedaron en 20; lo publicado no se ve desde acá.

## Unknown unknowns

- Alguien que usaba "→ Aprobado" como su forma habitual de pasar obras al tablero.
- Que 20 días fijos alargue lo que antes se decía para granito ("15") y eso pese en el cierre.
