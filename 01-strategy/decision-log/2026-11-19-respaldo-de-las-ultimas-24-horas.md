---
fecha: 2026-11-19 (las dos confirmaciones y "solo administradores", 20/11/2026)
tema: Respaldo: una copia de las últimas 24 horas, que se va pisando, y poder volver a ella
status: decidido (el dueño pidió qué; el cómo lo eligió quien lo hizo y está explicado acá)
---

# Respaldo de las últimas 24 horas

## Qué pidió el dueño

19/11/2026: "necesito que haya una copia de las últimas 24 horas siempre, y se vaya pisando, sobrescribiendo; o sea, que siempre podamos volver a las últimas 24 horas".

20/11/2026: "cuidado: solo los administradores pueden acceder al backup. Y para regresar necesita doble confirmación: una en darle al botón, y otra escribir una palabra toda en mayúscula, USAR RESPALDO".

Venía conversado desde el 18/11: el cierre del mes no arregla un error que pisa datos a mitad de mes (como el cruce de dos clientes de ese día). → `2026-11-18-cierre-del-mes.md`

## Qué se decidió y por qué

1. **Una copia por hora, no una por día.** Una sola copia que se pisa cada 24 horas no cumple "siempre podamos volver": un minuto después de pisarla, la única copia es la de recién y no hay a dónde volver. Con una por hora, siempre hay una de hace 24 horas.
2. **Se guardan 25.** Con 24, justo antes de la hora en punto la más vieja tiene 23 horas y pico. Con 25 nunca hay menos de 24 horas para atrás.
3. **Solo se copia si algo cambió.** Las pantallas abiertas mandan una señal cada tanto ("quién está trabajando"). Sin esta regla, una tablet olvidada prendida un viernes gastaba las 25 copias en copias iguales, y el lunes ya no se podía volver al viernes. Así cada copia es un estado distinto y las 25 alcanzan para más de dos días de trabajo.
4. **La copia la toma el primer pedido de la hora, antes de cambiar nada.** No depende de programar una tarea en el hosting: no se puede verificar desde acá que la de las 3 de la mañana esté configurada. Si lo está, también copia antes de borrar.
5. **Se copian los datos, no las fotos.** Los datos pesan poco (unos megas); las fotos y planos, gigas. Copiarlos cada hora no entra.
6. **Pero las fotos tienen que volver.** Un respaldo que devuelve fichas con fotos rotas no sirve justo cuando hace falta. Por eso lo que se borra no se destruye: se guarda aparte mientras alguna copia lo pueda necesitar, y vuelve con ella.
7. **Vuelve todo el sistema, no una parte.** Ficha, contrato y presupuesto están enlazados (el N°, el código interno, la seña que viaja al contrato). Volver uno solo los dejaría cruzados, que es justo el tipo de error que se quiere poder deshacer.
8. **Antes de volver se guarda cómo está todo.** Usar un respaldo es la acción más destructiva del sistema: se tiene que poder deshacer.
9. **Los números no vuelven atrás.** Si se dio el presupuesto N° 1840 después de la copia y se le mandó a un cliente, el próximo no puede ser otra vez el 1840.
10. **Solo administradores, y lo exige el servidor** (permiso `copias`). La pantalla está en Control, que ya era solo de ellos.
11. **Dos confirmaciones:** el botón "Usar" y escribir USAR RESPALDO en mayúsculas. La frase la exige el servidor, no solo la pantalla.
12. **En pantalla se llama "Respaldo".** Es la palabra que eligió el dueño para confirmar.

## Alternativas que se descartaron

- **Una copia por día.** No cumple el pedido (punto 1).
- **Copiar también las fotos cada hora.** Peso y tiempo (punto 5).
- **Enlaces duros para las fotos** (una "copia" que no ocupa lugar). Miles de entradas por copia; los hostings compartidos limitan la cantidad de archivos.
- **Volver una sola ficha o un solo contrato.** Para eso ya está la Papelera; fuera de ella, deja datos cruzados (punto 7).
- **Bajar la copia como archivo.** El cierre del mes ya baja todo, y el dueño lo limitó al último día del mes. No se abrió otra puerta para sacar datos.
- **Un botón "copiar ahora".** Las copias manuales gastarían lugares de las 25 y romperían la garantía de las 24 horas.

## Knowns

- Probado en local con el reloj simulado: 57 comprobaciones de servidor y 24 de pantalla, más la prueba de roles (Operador y Planos y bacha reciben un rechazo). → `04-operations/respaldo.md`, "Pruebas".
- Medido con 3.000 presupuestos de prueba (56 MB): 1 segundo por copia, 3 MB cada una, 4 MB de memoria.
- Sin el archivo de copias el sistema anda igual.
- El servidor ya arma ZIP: el cierre del mes y los ZIP de presupuestos usan lo mismo.

## Known unknowns

- **Cuánto pesan los datos reales.** Desde acá no se ve producción. Si fueran mucho más que la prueba, la copia de cada hora tardaría más de un segundo.
- **Lugar en el disco del hosting** para 25 copias y para lo borrado de los últimos dos días.
- **Si la limpieza de las 3 de la mañana está programada** en Hostinger. Si no lo está, no cambia nada del respaldo.
- **Dos administradores usando un respaldo a la vez.** Está hecho para que uno espere al otro; se probó de a uno.
- **Safari de iPad.** Probado en Chromium con pantalla táctil emulada.

## Unknown unknowns

- Que se use el respaldo para lo que se arregla a mano en un minuto, y se pierda el trabajo de una hora de todo el equipo. La pantalla dice cuántas acciones de cuántas personas se deshacen; si igual pasa, habrá que pensarlo de nuevo.
- Algún dato que hoy no sabemos que existe fuera de los archivos que se copian.

## Validación pendiente

Después del deploy: entrar a Control y ver que la caja "Respaldo" diga "Último respaldo: hoy HH:MM". Con eso se sabe que el hosting puede copiar. Usar un respaldo de verdad, solo si hace falta.
