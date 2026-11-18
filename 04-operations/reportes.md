# Reportes (solo administradores)

> Pestaña creada el 18/11/2026 a pedido del dueño. Está en `/presupuestos/reportes.php` y solo la ven los administradores (permiso `reportes`). Tiene dos cosas: el **cierre del mes** y el **registro de acciones**.
>
> **Desde el 18/11/2026 se llama "Control".** El archivo (`reportes.php`), el permiso (`reportes`) y este doc conservan el nombre viejo para no romper links; en pantalla dice Control. Se sumaron los recordatorios y cambió la disposición: recordatorios a la izquierda (70 %), cierre del mes a la derecha (30 %), registro del día abajo.

## Regla: se puede borrar entera

Reportes no escribe nada en el sistema y nada depende de ella. Si se decide sacarla, se borran estos tres archivos y todo lo demás sigue igual (probado): la pestaña desaparece sola del menú.

- `site/public_html/presupuestos/reportes.php`
- `site/public_html/calculadora/api/cierre-mes.php`
- `site/public_html/calculadora/api/registro.php`

## Cierre del mes

La idea del dueño: al final de cada mes se baja todo y se guarda dos veces, **una copia impresa y una digital**.

- **Solo el último día del mes** (28, 29, 30 o 31, según el mes). El resto de los días el botón está apagado y dice qué día se habilita.
- **Es una foto del sistema en ese momento.** Baja un archivo `blackstones-cierre-AAAA-MM.zip`.
- **Sirve para dos cosas:** una **carpeta por cliente** lista para guardar (su ficha, el presupuesto, planos, fotos de entrega y de bacha) y la **foto completa del sistema** de ese día.
- **Opciones:** las fichas, los contratos y los presupuestos adjuntos van siempre. **Planos, Fotos de entrega y Fotos de bacha** vienen tildados y se pueden destildar. Al lado de cada una dice cuántos archivos son y cuánto pesan.

Qué trae el ZIP:

| Archivo | Para qué |
|---|---|
| `LEEME.txt` | Qué es y cómo guardarlo |
| `para-imprimir.html` | La copia impresa: una hoja por obra, con sus datos, su contrato, su historial y sus fotos |
| `fichas.csv` | Todas las obras, para Excel |
| `contratos.csv` | Todos los contratos, de todos los meses, con su seña y pagos |
| `fichas/<N° Nombre>/` | La carpeta del cliente: `ficha.html` (su ficha, se abre e imprime sola, con sus fotos), el presupuesto adjunto de su contrato y las fotos tildadas |
| `datos/` | Los archivos del sistema tal cual, para poder reconstruirlo |

- **Qué obras entran:** las que están vivas en el tablero y las que se archivaron en el mes que se cierra. Las archivadas antes ya salieron en el cierre de su mes.
- **No trae los usuarios ni sus contraseñas**: el ZIP se guarda fuera del sistema.
- **Tiene nombres, teléfonos, direcciones y plata de clientes.** No se sube al repo ni se manda por canales abiertos.

### Lo que el cierre del mes no cubre

Un error que pise datos a mitad de mes (como el cruce de dos clientes del 18/11/2026) no se arregla con el cierre del mes anterior. Para eso haría falta una copia diaria; quedó conversado y sin decidir. → `01-strategy/decision-log/2026-11-18-cierre-del-mes.md`

## Registro de acciones

Quién hizo qué y a qué hora, **en el día**. Abre siempre en hoy y se actualiza solo cada 15 segundos (dueño, 18/11/2026: "lo que quiero ver es el real time del día"). Va de lo más nuevo a lo más viejo, de a 20, con "Ver más". Se puede filtrar por usuario, buscar por texto (una obra, una acción) y, con el selector de fecha, mirar un día anterior: se ve un día por vez, nunca mezclados.

- **Se anota cada cambio que se guarda**: tildes (cuál y si se tildó o destildó), cambios de etapa, turnos, altas y bajas de fichas y contratos, pagos, usuarios. Mirar una pantalla no deja registro; entrar y salir, tampoco.
- **No guarda nombres de clientes, plata ni el texto de las notas.** El nombre de la obra se busca en el momento de mostrarlo. Tampoco guarda contraseñas: el tilde del tablero se anota como `tilde=`, nunca como `clave=`, que es como se llama el campo de la contraseña.
- **La hora es siempre la de Argentina (UTC-3).** Las líneas nuevas terminan en `ART` y ya se anotan así. Las anteriores al 18/11/2026 se anotaron con la hora del servidor (UTC, 3 horas adelantada): `registro.php` les resta las 3 horas al mostrarlas. El archivo no se reescribe.
- Vive en `bs-data/usuarios/registro-AAAA-MM.log`, un archivo por mes. Lo escribe `auth_registrar()` (`calculadora/auth_check.php`); Reportes solo lo lee.

## Recordatorios (18/11/2026)

El dueño: "¿Por qué hacer el recordatorio fuera del sistema cuando se puede hacer adentro más fácil?" y "super organizado, pero no saturado bajo ningún motivo: simple y efectivo". Se descartó Telegram.

- **Qué es un recordatorio:** algo con fecha, que una persona puede hacer hoy, y que desaparece cuando se hizo. Lo que es un estado (atrasadas, plazo vencido, fichas sin contrato) no va acá: ya está en el tablero.
- **No se guardan.** `calculadora/api/control.php` los calcula cada vez leyendo el tablero. Lo único que se guarda es el tilde "Hecho" de cada uno (`bs-data/control/hechos.json`), con quién y a qué hora. Los tildes de más de 45 días se borran solos.
- **Confirmar los turnos** de un día de medición o de instalación (el primero que se hizo).
  - Aparece **dos días hábiles antes** (lunes a viernes): un bloque de sábado y otro de lunes aparecen los dos el jueves. Se va cuando pasa ese día.
  - Dice cuántos turnos son, cuántos ya están en "Turno confirmado" y cuántos no tienen hora. Abajo nombra las fichas que faltan confirmar. No trae teléfonos: "Ver la ruta →" abre el Cronograma del calendario en ese día, que es donde están.
  - **"Hecho"** lo tilda una persona cuando ya les avisó. Si después se suma un turno a ese día, vuelve a quedar pendiente y lo dice.
  - Si todas las obras de ese día tienen "Turno confirmado" en su ficha, se da por hecho solo.
- **Por qué el de turnos queda hasta el mismo día:** aparece dos días hábiles antes y sigue hasta el día del turno inclusive ("es hoy"), porque un turno sin confirmar el mismo día es justo el que más importa. Se va al día siguiente, o antes si todas las obras quedan en "Turno confirmado" o se marcan Medido / Instalado.

### Qué está prendido (18/11/2026)

El dueño, después de verlos todos en uso: "Por los momentos vamos a dejar solo aviso de confirmación medir y aviso de confirmación instalar. Nada más."

- **Se muestran solo los dos de "Confirmar los turnos"** (medición e instalación).
- **Abajo de cada uno dice qué fichas faltan confirmar** (dueño: "abajito debe aparecer qué ficha es"): nombre y N°, en orden de hora, cada una con link a su ficha en el tablero. Nombra hasta seis; si hay más, dice "y N más". El N° es el que muestra el tablero; el código interno no se ve.
- **Los demás quedaron apagados, no borrados.** Siguen escritos en `control.php` con sus pruebas. La lista de los prendidos es `$BS_CONTROL_PRENDIDOS`, arriba de ese archivo: para volver a prender uno se suma su tipo ahí, con el OK del dueño.
- **No se armaron** (quedaron aprobados antes del apagón, por si vuelven): el aviso de seña distinta entre tablero y contrato, solo para contratos nuevos; y un tilde "Plano y monto rectificados" en Por medir, que no frenaría el pase a Por cortar.
- Con un solo grupo en pantalla, el rótulo del grupo no se muestra.

### Los que hay escritos, por grupo (hoy solo está prendido "Confirmar los turnos")

El dueño aprobó cada uno (los cuatro primeros el 18/11/2026; los nueve siguientes el mismo día, de una lista de la que rechazó uno). En pantalla van en este orden, con un rótulo chico por grupo.

| Grupo | Recordatorio | Cuándo aparece | Cuándo se va solo | Lleva a |
|---|---|---|---|---|
| Turnos | **Marcar o reprogramar** las mediciones / instalaciones que ya pasaron | Turno de los últimos 14 días sin "Medido" / "Instalado" | Al marcarlas o cambiarles el día | Cronograma |
| Turnos | **Confirmar los turnos** del día | 2 días hábiles antes, hasta el día | Todas en "Turno confirmado" | Cronograma de ese día |
| Turnos | **Completar los datos de la medición** | El día hábil anterior, solo si a alguna le falta dirección o teléfono | Al completarlos | Cronograma de ese día |
| Turnos | **Preparar la instalación** | El día hábil anterior, solo si a alguna le faltan instaladores o "Dirección confirmada" | Al tildarlos | Cronograma de ese día |
| Taller y plazo | **Dar turno de instalación** | Por instalar, sin día u hora, plazo a 7 días o menos (o vencido) | Todas con turno | Cronograma de instalación |
| Taller y plazo | **Apurar** lo que vence en 5 días o menos | Plazo a 5 días o menos (o vencido) y sigue en Por cortar o Por fabricar | Al pasar a Por instalar | Tablero |
| Taller y plazo | **Pedir la placa** | Ya medida y sin "Tenemos la placa" | Al tildarla | Tablero |
| Taller y plazo | **Pasar a cortar** | Medida hace más de 2 días, con placa y seña, y sigue en Por medir | Al pasar a Por cortar | Tablero |
| Taller y plazo | **Cerrar lo ya instalado** | "Instalado" hace más de 2 días y sigue en Por instalar | Al pasar a Por archivar | Tablero |
| Plata y contratos | **Cobrar la seña** | Ya medida y sin "Trajo la seña" | Al tildarla | Tablero |
| Plata y contratos | **Cargar en Por medir** un contrato | Contrato cargado hace 2 días o más, sin enlazar con el tablero | Al cargarlo o enlazarlo | Relación-Contrato |
| Plata y contratos | **Adjuntar el presupuesto** al contrato | Al presupuesto de la calculadora le quedan 15 días o menos de sus 180, y el contrato no lo tiene adjunto | Al adjuntarlo | Relación-Contrato |
| Fin de mes | **Bajar el cierre** | Solo el último día del mes | Cuando alguien lo baja (lo lee del registro) | El cierre, en la misma pantalla |

Detalles que no se ven en la tabla:

- **Una obra no se repite.** "Pasar a cortar" no cuenta las que esperan la placa o la seña: esas ya están en su propio recordatorio, que dice qué las frena.
- **El presupuesto sigue durando 180 días.** Los "15 días" de "Adjuntar el presupuesto" son la anticipación del aviso, no un plazo nuevo. La cuenta es la misma de la limpieza nocturna (`cleanup-retention.php`): 180 días desde la última vez que se tocó. Si ese N° ya es una obra (viva o archivada) no avisa: ahí el presupuesto tiene otro reloj.
- **"Cargar en Por medir" mira solo contratos de los últimos 30 días** (por la fecha del contrato), para que los históricos importados no hagan ruido. Los de prueba no cuentan.
- **Si Relación-Contrato no está instalada**, los dos que leen contratos simplemente no aparecen; el resto sigue.
- **Los que no son de un día puntual son de todos los días:** el "Hecho" vale por hoy. Si mañana siguen sin resolverse, vuelven a aparecer. Marcado "Hecho", cualquiera vuelve a quedar pendiente si después se le suma una obra, y lo dice.
- **Ninguno trae teléfonos, direcciones ni plata.** Dicen cuántas obras son, nombran las fichas (nombre y N°) y llevan a la pantalla donde están.
- **Orden:** por grupo; dentro de cada grupo, por fecha (lo que ya pasó, después lo más cercano). Nunca hay más de un renglón por tipo (salvo los de turnos, que van uno por día).
- **En pantalla:** primero los pendientes, por grupo; abajo, "Hechos", tachados. Sin pendientes dice "No hay nada para recordar hoy". Se actualiza solo cada minuto.
- **Solo lo ven los administradores**, como el resto de Control.
- **Para sumar un tipo nuevo:** se agrega en `control.php` (cada uno arma su `id`, título, detalle y link). Antes, preguntarle al dueño: la regla es cero ruido.
