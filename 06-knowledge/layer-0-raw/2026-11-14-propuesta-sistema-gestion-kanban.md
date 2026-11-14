# Propuesta original del dueño: sistema de gestión kanban (dump crudo)

> Layer 0. Texto tal cual lo pegó el dueño el 2026-11-14 al pedir el tablero de obras. No se edita.
> Lo que se construyó a partir de esto: `04-operations/tablero-obras.md` y `01-strategy/decision-log/2026-11-14-tablero-de-obras.md`.

---

quiero armar mi sistema de operaciones al estilo kanban por etapas

esta es mi propuesta inicial dime donde mejorar, como enriquecer si hay algo que no veo 

y le metemos de una

markdown
# Sistema de gestión — Marmolería
## Especificación funcional para desarrollo

---

## 1. Principio rector

**Un solo rol ve el mapa completo: el gerente.**

Cada puesto operativo recibe únicamente la información necesaria para ejecutar su
tarea, sin contexto adicional. La planificación y la resolución de ambigüedades
ocurren antes de que la tarea llegue al operario. El operario ejecuta, no decide.

Esto aplica a todo el sistema: pantallas, fichas impresas, notificaciones.

---

## 2. Estructura del tablero

Cuatro columnas, nombradas como colas de trabajo pendiente:

| # | Columna | Significado |
|---|---------|-------------|
| 1 | **Por medir** | Contrato señado, esperando medición y cierre administrativo |
| 2 | **Por fabricar** | Ficha cerrada, esperando o en producción |
| 3 | **Por instalar** | Pieza terminada y aprobada, esperando despacho |
| 4 | **Por archivar** | Instalada, esperando cobro de saldo y vencimiento de garantía |

Cada columna es una **agenda**, no una pila: toda tarjeta tiene fecha y el orden
se calcula a partir de ella.

---

## 3. Identificación de obras

### Formato en tarjeta


2611 · Juan P. · Belgrano


- **Código**: el mismo que genera la calculadora de presupuestos. Es el
  identificador oficial y único.
- **Nombre**: nombre + inicial del apellido. Es como la gente habla de la obra.
- **Zona**: usada para agrupar viajes de medición e instalación.

### Reglas del código
- **No se reutiliza ni se reasigna.** Presupuesto no cerrado queda como perdido;
  si el cliente vuelve, es código nuevo.
- **Sobrevive a los cambios** mediante sufijo: `2611`, `2611-B`, `2611-C`.
  El tablero muestra la última versión; el número raíz es el que va en la hoja de
  corte, la etiqueta de bacha y el caballete.
- **Prefijo opcional de material** si el volumen lo justifica: `G-2611` (granito),
  `S-2611` (cuarzo). Omitir si se trabaja mayormente un solo material.

### Presencia física del código
El código debe estar escrito en: hoja de corte, etiqueta de la bacha reservada,
caballete donde espera la pieza, remito de salida.

### Métrica derivada
Presupuestos emitidos vs. tarjetas creadas = tasa de conversión, sin medición
adicional.

---

## 4. Etapa 1 — Por medir

### Entrada
Contrato con **seña cobrada**. No entra por confirmación verbal.

### Datos de la ficha de medición (la que lleva el medidor)
- Código de presupuesto
- Nombre del cliente
- **Dirección completa y teléfono** (el medidor sale del taller)
- Monto a cobrar
- Fecha de medición acordada

### Checklist previo de obra apta
Confirmado por el vendedor vía WhatsApp 48 h antes. Si falla un ítem, **no se
despacha al medidor**:
- Muebles bajo mesada instalados y nivelados
- Bacha, anafe, canilla definidos y en obra o con ficha técnica
- Electricidad y agua resueltas
- Acceso y escalera disponibles

### Cobro en la medición — BLOQUEANTE
El medidor cobra en el domicilio. Reglas:
- El monto va en su ficha **antes de salir**.
- El **recibo sale numerado desde el taller**, con el código de presupuesto. No se
  escribe a mano en la obra.
- El medidor vuelve con el dinero **y la copia firmada**. Sin papel no hubo cobro.
- **Rendición el mismo día** a administración.
- Si el cliente no tiene el dinero: el medidor **igual mide** (el viaje ya está
  hecho), pero la tarjeta queda en Por medir con etiqueta de cobro pendiente.
  Administración llama al día siguiente.
- **El medidor no negocia.** Si el cliente propone algo, llama al gerente desde la
  obra.

### Gestión de fechas
Dos fechas distintas en la tarjeta:

1. **Fecha de medición** — el día que va el medidor. Reprogramable por el cliente.
2. **Fecha de vencimiento de etapa** — límite para que la tarjeta salga de Por
   medir con todo cerrado. Típicamente fecha de medición + N días hábiles.

Reglas:
- **Nadie entra a Por medir sin fecha de medición asignada.**
- Reprogramar es libre; **no avisar no lo es**.
- Cada reprogramación **queda registrada en la tarjeta**. Tres corrimientos
  seguidos es señal de obra no lista, no de cliente flexible.
- Si una tarjeta supera X semanas sin fecha confirmada o con obra sin condiciones,
  pasa a etiqueta **"en espera del cliente"**: no sale del tablero, pero deja de
  contar como trabajo activo.

### Rectificación de medidas vs. presupuesto
- La diferencia la procesa **administración**, la misma que verifica la bacha.
- **No frena la cola.** La tarjeta sigue su recorrido.
- Se marca la tarjeta con **saldo pendiente** mientras se resuelve.
- **Umbral en pesos** (no en metros): por debajo, se avanza y se discute después;
  por encima, el gerente decide si avanza (puede implicar compra de placa no
  autorizada).
- **La diferencia se registra, no solo se cobra.** Desvíos sistemáticos indican
  problema en el método de presupuestación, no en la medición.
- **Administración llama el mismo día que vuelve el medidor.** La conversación es
  distinta si el cliente sabe que su obra ya está en marcha.

### Protección contractual
El contrato firmado al señar debe decir que **las medidas definitivas surgen del
plantillado y pueden ajustar el precio**. Firmado al señar, no discutido al medir.

### Punto de no retorno
No es la cola: es **el corte**. Mientras la placa esté entera, no hay pérdida.
Por eso la cola avanza libre y es el ingreso a corte el que requiere saldo conforme
(según umbral).

### Gestión de bacha — BLOQUEANTE
- **Reservada físicamente**, no "hay en stock". La bacha concreta, separada en
  depósito, con el número de obra escrito encima.
- Si la provee el cliente: **llega antes de que la tarjeta pase a fabricar**.
  Promesa de traerla después = causa principal de mesada terminada sin poder pegar.
- Administración **persigue la bacha** en el mismo llamado del saldo.

### Salida de la etapa — 3 tildes obligatorias
La tarjeta sale de Por medir solo con:
1. ✅ Medidas firmadas por el cliente
2. ✅ Saldo rectificado o marcado como pendiente
3. ✅ Bacha reservada con número de obra

Si falta cualquiera, la tarjeta se queda con etiqueta visible de qué falta.

> ⚠️ **Nota para administración**: son dos decisiones con consecuencias distintas.
> La diferencia de metros **no frena**. La bacha ausente **sí frena**, porque sin
> bacha física no hay calado posible.

### Output de la etapa
Es el **input cerrado** del fabricante:
- Medidas definitivas
- **Medida del calado**, no solo el modelo de bacha
- Color y regrueso

> El gerente hace la traducción modelo → calado **una vez, mirando la bacha real**.
> Modelos parecidos tienen calados distintos. El fabricante no busca plantillas.

---

## 5. Agrupamiento por material

Entre Por medir y Por fabricar hay un paso de **agrupamiento (batch)**.

### Mecanismo
- Al salir de medición, la tarjeta entra a un **grupo por material**, no
  directamente a la cola de corte.
- Cada material tiene un **día de corte asignado** (ej.: granitos martes, cuarzos
  jueves).
- Ese día entra a corte **todo lo que haya acumulado**, sean tres obras o una.

### Regla clave
**El calendario corta la espera, no la cantidad.** Nunca se espera a juntar N
obras. Nadie espera más de una semana por definición.

### Beneficios
- Despiece conjunto sobre la misma placa → mejor aprovechamiento
- Lote homogéneo para el fabricante → un solo setup de máquina

### Excepciones
1. Obra con **fecha de instalación comprometida** entra al corte siguiente aunque
   esté sola.
2. **Material raro** de un solo cliente no espera a nadie: se corta cuando llega la
   placa.

### Quién decide
El **gerente** arma la hoja de despiece conjunto, acomodando las piezas de varias
obras en la placa, cada una con su código. El cortador recibe placa y dibujo, y no
sabe que son tres clientes.

---

## 6. Etapa 2 — Por fabricar

### Tratamiento como bloque único
Fabricación se maneja como **una sola columna y un solo responsable**. No se
subdivide en corte / bacha / canto / pulido: agregaría complejidad al encargado sin
beneficio.

### Fecha de salida
Al recibir la ficha, el encargado de fábrica **pone una fecha de salida**
("sale el martes 24"). Es un dato puesto una vez, no un seguimiento.

Regla de disciplina: **mover la fecha es libre, no avisar no lo es.**
No hay castigo por reprogramar; sí por silencio.

Métrica que surge sola: obras que salieron en la fecha que el propio encargado se
puso.

### Bloqueantes de ENTRADA (controla el gerente)
La ficha que recibe el fabricante debe traer:
- Código de obra
- Medidas de corte
- Medida de calado + bacha asignada
- Color
- Regrueso

### Bloqueantes de SALIDA (controla el gerente)
Antes de que la pieza pase a Por instalar:
- Medidas correctas
- Bacha correcta, correctamente pegada
- Color correcto
- Regrueso correcto

> ⚠️ **No puede controlarlo el fabricante**: sería controlarse a sí mismo.
> **Formato**: control **en bloque, una vez por día** (ej. media hora al final de
> la tarde), no obra por obra cuando el fabricante avisa. Con la ficha en mano son
> ~5 min por pieza.

### Organización del puesto de corte
El cortador es **monotarea**. No ve el tablero, no ve fechas, no ve clientes.

- **Una hoja de corte por obra**, en papel.
- Contenido de la hoja: **código de obra, placa a usar, despiece con medidas
  dibujado**. Nada más. Sin cliente, sin zona, sin fecha de instalación.
- **Una sola hoja viva en la mesa.** Termina, entrega firmada, recibe la siguiente.
- **Pila física ordenada** (carpeta, clip, gancho). El cortador toma la de arriba.
  **Nunca elige.** Si hay que reordenar prioridades, el encargado reordena la pila.
- **El despiece viene decidido**, no lo improvisa el cortador. Si improvisa, el
  desperdicio depende de su día; si viene dibujado, es constante.

El mismo principio aplica a bachas y pulido: una tarea por vez, información mínima.

### Andon (regla de parada)
Si la placa viene fallada o la medida no cierra: **el operario para y llama**.
No resuelve por su cuenta, no improvisa. Esta es la única autonomía del puesto.

---

## 7. Etapa 3 — Por instalar

### Entrada
Pieza terminada y **aprobada en control de salida**. La fecha de instalación se
asigna **después**, cuando el fabricante confirma que sale.

> Prometer fecha de instalación antes de que la pieza esté terminada es inventar.

### Remito de salida (checklist consolidado)
Única hoja donde converge todo el recorrido. El instalador sale solo con esto:

- [ ] Cliente (nombre completo)
- [ ] Código de presupuesto
- [ ] Presupuesto
- [ ] **Dirección completa y teléfono**
- [ ] Medidas
- [ ] Color
- [ ] Bacha
- [ ] Zócalos
- [ ] **Cantidad de piezas y bultos**
- [ ] **Accesorios**: ménsulas, pegamento del color, grifería si la provee la empresa
- [ ] **Saldo a cobrar en obra** (sí/no + monto)
- [ ] **Conformidad del cliente**: firma y fecha

**Conteo de piezas**: el instalador cuenta al cargar el camión y al llegar a obra.

**Zócalos explícitos**: es la pieza que más se olvida porque no es la mesada, y
volver por un zócalo es un viaje entero perdido.

### Verificación 48 h antes — BLOQUEANTE
Llamado al cliente antes de cargar el camión. Lo verificado antes de medir pudo
haber cambiado: mueble movido, piso recién hecho, nadie con llave.

### Agrupamiento
Instalaciones agrupadas por zona en ventanas fijas
(ej.: norte lunes y jueves, sur martes y viernes).

### Salida
La **firma de conformidad** es lo que mueve la tarjeta a Por archivar y lo que
**arranca el reloj de la garantía**.

---

## 8. Etapa 4 — Por archivar

No es un cesto de basura: es la **cola de cierre**.

### Contenido
- Saldo cobrado
- Acta de conformidad firmada
- Reloj de garantía corriendo (30 días o lo que defina la empresa)

### Responsable
**Quien factura, no el gerente.** Si no tiene dueño explícito, se llena y nadie la
mira.

### Archivo definitivo
A los 30 días post-instalación **o** cuando se cobra el saldo — lo que ocurra
último.

### Reclamos
Si entra un reclamo dentro de la garantía, **la tarjeta vuelve al tablero** a la
etapa que corresponda. Archivar al instalar hace que los reclamos entren por
WhatsApp y se pierdan.

---

## 9. Visibilidad por rol

| Rol | Ve |
|-----|-----|
| **Gerente** | Mapa completo. Orden de las colas, relación bacha-mesada, despiece conjunto, control de salida, decisión de ingreso a corte |
| **Administración** | Detalle de cliente, presupuestos, saldos, stock de bachas, cobros |
| **Medidor** | Su ficha: código, cliente, dirección, teléfono, monto a cobrar, fecha |
| **Encargado de fábrica** | Su columna + cola de instalación (para saber qué apurar) |
| **Cortador** | Una hoja: código, placa, despiece. Nada más |
| **Instalador** | Remito de salida de su obra |

**Regla transversal**: quien sale del taller lleva dirección y teléfono. Quien se
queda adentro, solo el código.

### Maduración
La visibilidad se abre **de a una persona**, no por antigüedad.
Señal de que alguien está listo: **cuando pregunta "por qué" en vez de "qué sigue"**.
El cortador puede quedarse indefinidamente con la pila de hojas; no todos los
puestos necesitan crecer en visibilidad.

---

## 10. Riesgo estructural: el gerente

El gerente concentra: presupuestos, relación bacha-mesada, orden de las colas,
despiece conjunto, control de salida, decisión de ingreso a corte.

**Es el cuello de botella real del diseño.** Si falta una semana, el taller se
detiene.

### Mitigación
Lo que **no se delega** (requiere el mapa): control de salida, relación
bacha-mesada, orden de la cola.

Lo que **sí se suelta** cuando haya equipo: carga administrativa (ya en curso hacia
administración) y carga de fichas (automatizable).

**La protección real**: que todo esté escrito. Si la ficha tiene medidas, color,
regrueso y calado, el control de salida lo puede hacer cualquiera con la ficha en
la mano. El conocimiento vive en el papel, no en la cabeza del gerente.

---

## 11. Datos de la tarjeta

### Frente (visible sin abrir)
- Código
- Nombre + inicial
- Zona
- Material
- m²
- Fecha comprometida de la etapa actual
- Responsable actual
- Etiquetas activas

### Etiquetas (no mueven la tarjeta, la marcan)
| Color | Significado |
|-------|-------------|
| 🔴 Roja | Bloqueado — **con el motivo escrito** (falta bacha, cliente no responde, placa rota) |
| 🟡 Amarilla | Fecha comprometida en riesgo |
| 🔵 Azul | Saldo pendiente de rectificación |
| ⚪ Gris | En espera del cliente (no cuenta como trabajo activo) |

> **No se usa una columna de bloqueados**: la etiqueta permite ver el bloqueo sin
> sacar la obra de su etapa real.

### Interior
Dirección completa, teléfono, presupuesto, planos, fotos de obra, historial de
reprogramaciones, registro de diferencias de medida.

---

## 12. Límites de trabajo en curso (WIP)

Sin tope por columna, el tablero se convierte en lista de deseos.

- **Por medir**: sin límite estricto (entra lo que se vende), pero con regla de
  tiempo máximo en etapa.
- **Por fabricar**: tope sugerido en función de capacidad real — no más obras
  abiertas que las que el taller procesa en N días.
- **Por instalar**: pieza terminada esperando camión es plata parada. Si crece,
  es señal de desbalance entre fabricación e instalación.

---

## 13. Ritual operativo

**Reunión diaria de 10 minutos**, parados frente al tablero, leyendo
**de derecha a izquierda** (lo más cerca de entregar primero).

Es lo que convierte el tablero de decoración en herramienta.

---

## 14. Métricas

| Métrica | Fuente | Qué revela |
|---------|--------|------------|
| Instalaciones completas a la primera visita | Remito | Calidad del proceso completo |
| Días promedio medición → instalación | Fechas de tarjeta | Velocidad real |
| Viajes perdidos | Registro de medición/instalación | Calidad del checklist previo |
| m² de desperdicio por placa | Hoja de corte | Calidad del despiece |
| Obras con retrabajo + causa | Etiquetas rojas | Dónde está el problema |
| Cumplimiento de fecha autoimpuesta por fábrica | Fecha de salida | Confiabilidad de fabricación |
| Desvío medición vs. presupuesto | Registro de rectificaciones | Calidad de la presupuestación |
| Presupuestos emitidos vs. tarjetas creadas | Códigos | Tasa de conversión |

---

## 15. Roadmap

### Fase actual
Estandarizar. Tablero de 4 columnas, fichas cerradas, hoja de corte con despiece
dibujado. **No se puede mejorar un proceso que no existe.**

### Siguiente
Integración con la calculadora de presupuestos: el presupuesto aprobado genera la
tarjeta automáticamente, sin tipeo. El código ya es común a ambos sistemas.

### Más adelante
- Plantillado digital (Proliner, LT-55): elimina error de medición y conecta
  directo al corte.
- Software de gestión del rubro (tipo Moraware Systemize) si el volumen supera
  ~20-25 obras mensuales.
- Apertura de visibilidad por rol, de a uno, según madurez.

---

## 16. Notas para el desarrollador

1. **La visibilidad por rol no es cosmética**: es el requisito central. Cada vista
   debe mostrar lo mínimo necesario, no "todo con filtros".
2. **Las fechas son dos, no una**, en cada etapa que las necesite: fecha de evento
   y fecha de vencimiento de etapa.
3. **Las reprogramaciones se registran**, no se sobrescriben. El historial es el
   dato.
4. **Los bloqueantes de salida son checklist explícito**, no campo libre. La
   tarjeta no avanza sin tildes.
5. **Las etiquetas no mueven tarjetas.** Marcan estado dentro de la etapa real.
6. **El agrupamiento por material es un paso intermedio** entre etapa 1 y 2, con
   calendario fijo por material, no una cola más.
7. **El código de presupuesto es la llave de todo el sistema.** Único, no
   reutilizable, versionable por sufijo.
8. Las hojas operativas (medición, corte, remito) deben ser **imprimibles y
   autocontenidas**. El taller funciona en papel aunque el sistema sea digital.


Lo único que no pude hacer es entregártelo como archivo; el resto está completo.
