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
- **Hoy hay un solo tipo: confirmar los turnos** de un día de medición o de instalación.
  - Aparece **dos días hábiles antes** (lunes a viernes): un bloque de sábado y otro de lunes aparecen los dos el jueves. Se va cuando pasa ese día.
  - Dice cuántos turnos son, cuántos ya están en "Turno confirmado" y cuántos no tienen hora. No trae nombres ni teléfonos: "Ver la ruta →" abre el Cronograma del calendario en ese día, que es donde están.
  - **"Hecho"** lo tilda una persona cuando ya les avisó. Si después se suma un turno a ese día, vuelve a quedar pendiente y lo dice.
  - Si todas las obras de ese día tienen "Turno confirmado" en su ficha, se da por hecho solo.
- **En pantalla:** primero los pendientes; abajo, "Hechos", tachados. Sin pendientes dice "No hay nada para recordar hoy". Se actualiza solo cada minuto.
- **Solo lo ven los administradores**, como el resto de Control.
- **Para sumar un tipo nuevo:** se agrega en `control.php` (cada uno arma su `id`, título, detalle y link). Antes, preguntarle al dueño: la regla es cero ruido.
