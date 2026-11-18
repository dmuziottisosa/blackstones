# Reportes (solo administradores)

> Pestaña creada el 18/11/2026 a pedido del dueño. Está en `/presupuestos/reportes.php` y solo la ven los administradores (permiso `reportes`). Tiene dos cosas: el **cierre del mes** y el **registro de acciones**.

## Regla: se puede borrar entera

Reportes no escribe nada en el sistema y nada depende de ella. Si se decide sacarla, se borran estos tres archivos y todo lo demás sigue igual (probado): la pestaña desaparece sola del menú.

- `site/public_html/presupuestos/reportes.php`
- `site/public_html/calculadora/api/cierre-mes.php`
- `site/public_html/calculadora/api/registro.php`

## Cierre del mes

La idea del dueño: al final de cada mes se baja todo y se guarda dos veces, **una copia impresa y una digital**.

- **Solo el último día del mes** (28, 29, 30 o 31, según el mes). El resto de los días el botón está apagado y dice qué día se habilita.
- **Es una foto del sistema en ese momento.** Baja un archivo `blackstones-cierre-AAAA-MM.zip`.
- **Opciones:** las fichas, los contratos y los presupuestos adjuntos van siempre. Tildados de entrada: **Planos** y **Fotos de entrega**. Sin tildar: Fotos de bacha. Al lado de cada una dice cuántos archivos son y cuánto pesan.

Qué trae el ZIP:

| Archivo | Para qué |
|---|---|
| `LEEME.txt` | Qué es y cómo guardarlo |
| `para-imprimir.html` | La copia impresa: una hoja por obra, con sus datos, su contrato, su historial y sus fotos |
| `fichas.csv` | Todas las obras, para Excel |
| `contratos.csv` | Todos los contratos, de todos los meses, con su seña y pagos |
| `fichas/<N° Nombre>/` | Una carpeta por obra: el presupuesto adjunto de su contrato y lo que se haya tildado |
| `datos/` | Los archivos del sistema tal cual, para poder reconstruirlo |

- **Qué obras entran:** las que están vivas en el tablero y las que se archivaron en el mes que se cierra. Las archivadas antes ya salieron en el cierre de su mes.
- **No trae los usuarios ni sus contraseñas**: el ZIP se guarda fuera del sistema.
- **Tiene nombres, teléfonos, direcciones y plata de clientes.** No se sube al repo ni se manda por canales abiertos.

### Lo que el cierre del mes no cubre

Un error que pise datos a mitad de mes (como el cruce de dos clientes del 18/11/2026) no se arregla con el cierre del mes anterior. Para eso haría falta una copia diaria; quedó conversado y sin decidir. → `01-strategy/decision-log/2026-11-18-cierre-del-mes.md`

## Registro de acciones

Quién hizo qué y a qué hora. Se puede filtrar por mes, por usuario y buscar por texto (una obra, una acción). Va de lo más nuevo a lo más viejo, agrupado por día.

- **Se anota cada cambio que se guarda**: tildes (cuál y si se tildó o destildó), cambios de etapa, turnos, altas y bajas de fichas y contratos, pagos, usuarios. Mirar una pantalla no deja registro; entrar y salir, tampoco.
- **No guarda nombres de clientes, plata ni el texto de las notas.** El nombre de la obra se busca en el momento de mostrarlo. Tampoco guarda contraseñas: el tilde del tablero se anota como `tilde=`, nunca como `clave=`, que es como se llama el campo de la contraseña.
- **La hora es siempre la de Argentina** desde el 18/11/2026. Las acciones anteriores a esa fecha pueden figurar con algunas horas de diferencia (se anotaban con la hora del servidor).
- Vive en `bs-data/usuarios/registro-AAAA-MM.log`, un archivo por mes. Lo escribe `auth_registrar()` (`calculadora/auth_check.php`); Reportes solo lo lee.
