# Tablero de obras dentro del hub de presupuestos

**Fecha:** 2026-11-14
**Autor:** dueño + IA
**Status:** ejecutándose

---

## 1. Qué estamos decidiendo

Llevar el seguimiento de obras en un tablero kanban de cuatro columnas dentro del hub de presupuestos, en vez de construir un sistema de gestión aparte.

---

## 2. Por qué ahora

El dueño escribió una especificación completa de sistema de gestión (roles, hojas imprimibles, lotes de corte, métricas). El hub ya tiene la mitad: presupuestos con código único y estado "aprobado". Faltaba ver qué pasa entre "aprobado" y "entregado".

---

## 3. Investigación previa (4 fuentes)

### Datos propios
- Propuesta original del dueño: `06-knowledge/layer-0-raw/2026-11-14-propuesta-sistema-gestion-kanban.md`.
- Máquina de estados del hub (`state.php`): borrador → enviado → aprobado → entregado / perdido.
- Reglas operativas que se repiten en las conversaciones de venta: seña del 50% el día de la medición, plazo 15/20 días desde la medición, mesada vieja retirada antes de medir, $30.000 por piso por escalera, retiro en local sin cargo.

### Mercado
- Sin relevar. `status: hypothesis` para todo lo que no sea operación propia.

### Benchmarks de ganadores
- Sin relevar.

### Validación científica / libros
- Sin relevar.

---

## 4. Knowns / Known unknowns / Unknown unknowns

### Knowns (alta confianza, evidencia clara)
- Requisito del dueño, textual: intuitivo y simple, fácil y preciso, ordenado. Nada de pasos que nadie va a completar ni texto engorroso.
- La idea principal es que la tarjeta se arrastre, con la opción de un botón Siguiente.
- La seña se cobra el día de la medición y el medidor vuelve con ella (algunos señan antes). Es el bloqueante de salida de "Por medir" y lleva monto.
- La entrega se muestra como semana estimada, a modo de guía. El orden lo da la cola.
- Retiro en local va en un cuadro aparte, no mezclado con las columnas.
- Archivar desde el tablero no borra nada. Retención máxima 6 meses; administración guarda copia física.
- La plata (saldos, conversión) la mira administración. El tablero es del gerente de operaciones.

### Known unknowns (sabemos que no sabemos — listar explícito)
- Si cinco tildes en "Por medir" se completan en el día a día o se saltean.
- Si hace falta un acceso separado por rol o alcanza con la contraseña compartida.
- Cuántas obras simultáneas hay en "Por fabricar" y si la cola sola alcanza sin tope de trabajos en curso.
- Si el plazo 15/20 días se cumple hoy: no hay medición histórica.
- Cómo se resuelve "tenemos placa" cuando el color se pide al proveedor (¿tilde al reservar o al recibir?).

### Unknown unknowns (territorio con potencial trampa)
- Obras que no nacen de un presupuesto del hub (arreglos, reclamos, trabajos de palabra): hoy no tienen forma de entrar al tablero.
- Dos personas moviendo la misma tarjeta a la vez.
- Que el tablero se abandone si el taller sigue funcionando en papel y nadie lo actualiza.

---

## 5. Decisión

Se construye la v1 mínima: una página nueva en el hub (`/presupuestos/tablero.php`), un endpoint (`/calculadora/api/tablero.php`) y un archivo de datos propio (`bs-data/tablero/obras.json`). Las obras entran solas al aprobar el presupuesto. Detalle operativo en `04-operations/tablero-obras.md`.

Quedan afuera de la v1: roles, hojas imprimibles, lotes de corte por material, etiquetas, métricas, fotos y reclamos.

---

## 6. Hipótesis testeable

> "Si el gerente de operaciones usa el tablero como única vista de obras, en 30 días todas las obras aprobadas tienen su tarjeta en la columna correcta, medible revisando el tablero contra el taller un día cualquiera."

### Métrica de éxito (definida ANTES de ejecutar)
- A 30 días: ninguna obra en curso fuera del tablero y ninguna tarjeta en una columna que no corresponde.

### Métrica de "abortar" (cuándo cancelamos)
- A 30 días el tablero tiene tarjetas sin tocar hace más de dos semanas: el problema es de hábito o de exceso de pasos, y se recorta antes de sumar nada.

---

## 7. Plan de ejecución

- [x] Endpoint, página y pestaña en el menú del hub y de la calculadora.
- [x] Prueba local: reglas del endpoint, arrastre con mouse y con dedo, seña con monto, archivar.
- [ ] Deploy a Hostinger (bloque PowerShell entregado al dueño).
- [ ] Dos semanas de uso real y recorte de tildes que no se usen.
- [ ] Decidir el siguiente agregado según lo que más duela (candidatos: lista de presupuestos enviados sin respuesta, hoja imprimible de medición, accesos por rol).

---

## 8. Riesgos y mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Nadie actualiza el tablero | media | alto | Pocos tildes, arrastre y botón; revisión a 30 días |
| Archivar borra información | baja | alto | El tablero archiva con `conservar_hermanas`; no se borra nada |
| El archivo del tablero se rompe | baja | medio | Vive aparte de los presupuestos; escritura atómica con backup |
| Se pisa un cambio entre dos usuarios | baja | bajo | Lock de escritura y refresco automático cada minuto |

---

## 9. Retro (a completar después de la ventana de evaluación)

> Completar entre 2026-12-14 y 2026-12-21.

### Qué pasó
...

### Métrica vs hipótesis
- Hipótesis: ...
- Realidad: ...

### Qué aprendimos
...

### Acción siguiente
- [ ] ...
