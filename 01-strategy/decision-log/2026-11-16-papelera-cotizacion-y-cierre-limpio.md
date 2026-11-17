---
fecha: 2026-11-16
tema: Papelera de 30 días, cotización del dólar en los cobros, contrato completo y carga de la planilla
status: decidido (dueño)
---

# Papelera, cotización y cierre limpio de Relación-Contrato

## Qué se decidió

Después de revisar los huecos del sistema (16/11/2026), el dueño resolvió cuatro cosas:

1. **Papelera de 30 días, "no hay excusa".** Borrar una ficha del tablero o un contrato ya no es definitivo: van a la papelera y se recuperan durante 30 días. Enlazados, van y vuelven juntos.
2. **Cotización del dólar en la seña y en cada pago.** Se cobra en pesos o en dólares; si el cobro es en una moneda y el contrato está en la otra, se guarda la cotización. Se propone el dólar del día que ya trae la calculadora.
3. **Un contrato cierra limpio** cuando tiene N° puesto, presupuesto adjunto y está enlazado con el tablero. Y cada obra del tablero tiene que tener su contrato: la página avisa las que no y deja crearlo.
4. **Se cargan en Relación-Contrato los que están en el tablero.** Se parte del tablero: cada ficha recibe su contrato, con los datos de la planilla del equipo cuando está ahí. El sistema toma el formato de la planilla ("ajustalo a nuestro formato, no nosotros ajustarnos").

**Corrección del 17/11 (dueño):** la primera versión recorría la planilla y buscaba fichas; el dueño no la corrió porque "iba a cargar de más". Se dio vuelta: manda el tablero, y **las dudas se resuelven con la fecha de medición**.

## Por qué

- Borrar una ficha enlazada se llevaba el contrato, sus pagos y el presupuesto adjunto, sin vuelta atrás y sin respaldo.
- Con contrato en pesos y pago en dólares, el saldo quedaba partido: debía pesos y tenía dólares "a favor". Nadie podía leer cuánto faltaba cobrar.
- Una obra podía estar en el tablero sin contrato, y nadie se enteraba.

## Knowns

- La seña habitual es 50% del total, o 75% si no hay muebles (dueño). La página lo informa y no bloquea: "no podemos confiar ciegamente".
- La planilla de octubre y noviembre 2026 tiene 79 contratos, todos con total en pesos. En 57 el detalle de cobros suma exactamente lo cobrado (17 de ellos con dólares, a cotizaciones de 1530 a 1570 que salen de la propia planilla). En 17 no suma y en 5 no hay detalle.
- Entre el contrato y la medición pasan pocos días: en las fichas que coinciden por teléfono, de 33 días antes a 5 días después. Por eso la fecha de medición sirve para desempatar, pero no para reconocer a alguien.
- **Simulado contra el tablero real exportado el 16/11 (38 fichas), antes de cargar nada:** 38 contratos para 38 fichas, ninguno de más. 33 con los datos de la planilla (28 por teléfono, 5 por nombre con una segunda prueba) y 5 que no están en la planilla.
- **Nombre y fecha solos no alcanzan.** En esa simulación, una ficha quedaba enlazada con el contrato de otro cliente del mismo nombre de pila, con fecha razonable pero otra dirección y otra seña. Por nombre ahora se exige además teléfono casi igual, misma dirección o misma seña.
- La seña del tablero y lo cobrado según la planilla son la misma plata en casi todos los casos (a veces el tablero la tiene en pesos y la planilla en dólares con su cotización). En 2 fichas no coinciden y en 2 difieren por un dígito: se cargan con nota.
- Probado: tirar y restaurar fichas y contratos, vencimiento de la papelera, regresión del tablero, calendario, y el tablero funcionando con Relación-Contrato borrada.

## Known unknowns

- **Qué son las cuatro columnas de montos en pesos de la planilla** (no vinieron los encabezados): el medio de cada cobro queda sin marcar.
- En las 17 filas donde el detalle no suma lo cobrado, cuál de los dos números es el bueno. Se cargó lo cobrado y quedó una nota en cada contrato.
- Si el tablero cambió después de la exportación del 16/11: una ficha nueva aparecería como "no está en la planilla". La vista previa lo muestra antes de cargar.
- En las 2 fichas donde la seña del tablero y la planilla no coinciden, cuál es el número bueno.
- Si la ventana de 35 días antes y 7 después alcanza para obras más viejas que octubre.
- Si el dólar que se propone tiene que ser el oficial (lo que trae hoy la calculadora) o el blue.
- Sigue sin haber respaldo automático diario de los datos.

## Unknown unknowns

- Clientes con dos contratos y una sola ficha (cocina y baño): el sistema enlaza un contrato por ficha.
- Señas que el taller ya cargó en el tablero con un monto distinto al de la planilla: enlazado, hoy se muestra la del tablero. El importador lo avisa fila por fila.

## Dónde quedó

`04-operations/relacion-contrato.md` (cotización, completo, obras sin contrato, papelera, carga de la planilla) y `04-operations/tablero-obras.md` (papelera).
