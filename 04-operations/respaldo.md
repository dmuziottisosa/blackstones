# Respaldo: la copia de las últimas 24 horas

> Pedido del dueño. 19/11/2026: "necesito que haya una copia de las últimas 24 horas siempre, y se vaya pisando; o sea, que siempre podamos volver a las últimas 24 horas". 20/11/2026: "solo los administradores pueden acceder al backup; y para regresar necesita doble confirmación: una en darle al botón y otra escribir una palabra toda en mayúscula, USAR RESPALDO".
>
> El porqué de cada decisión está en `01-strategy/decision-log/2026-11-19-respaldo-de-las-ultimas-24-horas.md`.

## Qué es

El sistema guarda solo una copia de sus datos por cada hora en que algo cambió. Guarda las últimas 25 y va pisando la más vieja. Con eso siempre se puede volver, como mínimo, 24 horas atrás; como de noche y los fines de semana no se trabaja, en la práctica son más de dos días.

No hay que hacer nada para que funcione: no se programa nada en el hosting y nadie tiene que acordarse.

## Cómo se usa

Solo administradores. Pantalla **Control**, caja **Respaldo** (a la derecha, debajo de "Cierre del mes").

1. La caja dice cuándo fue el último respaldo y cuántos hay guardados. Si un respaldo no se pudo guardar, lo dice en rojo.
2. **Usar un respaldo** abre la lista, del más nuevo al más viejo. Cada uno dice cuánto se hizo después ("Después: 12 acciones de 3 personas"): eso es lo que se deshace si se vuelve a él.
3. **Usar**, en el respaldo elegido (primera confirmación). Se abre un detalle con lo que se va a deshacer y lo que vuelve.
4. Escribir **USAR RESPALDO**, en mayúsculas (segunda confirmación), y tocar "Usar este respaldo". En minúscula, a medias o con otra palabra, el botón no se prende; y aunque alguien lo saltee, el servidor lo rechaza.
5. Listo: todo el sistema vuelve a como estaba en ese momento. Hay que avisarle al equipo que actualice la pantalla.

**Si fue un error, se deshace.** Antes de pisar nada, el sistema guarda solo una copia de cómo estaba todo en ese instante. Queda arriba de la lista como "Antes de usar el respaldo" y se usa igual que cualquier otro.

Para elegir a qué hora volver, mirar el **Registro de acciones** de Control (abajo): dice quién hizo qué y a qué hora. Se elige el último respaldo anterior al problema.

## Qué vuelve y qué no

| Vuelve a como estaba | No cambia |
|---|---|
| Presupuestos de la calculadora | Usuarios y contraseñas |
| Fichas del tablero: etapa, tildes, turnos, señas, notas | El registro de acciones (y anota que se usó un respaldo) |
| Contratos: totales, seña, pagos, enlaces, papelera | Los tildes "Hecho" de los recordatorios de Control |
| Fotos, planos, PDF y ZIP que se borraron después del respaldo | Lo que se subió después: queda guardado en el disco, suelto |

- **Vuelve todo junto.** No se puede volver una sola ficha o un solo contrato: una ficha, su contrato y su presupuesto están atados, y volver uno solo los dejaría cruzados.
- **Los números no se repiten.** Los N° de presupuesto y los códigos de ficha que se dieron después del respaldo quedan usados: un presupuesto nuevo no puede llevar el N° de uno que ya se le mandó a un cliente.

## Cómo anda por dentro

- **Cuándo se copia.** El primer pedido de cada hora, de cualquier persona con sesión, toma la copia antes de cambiar nada. La limpieza de las 3 de la mañana (`cleanup-retention.php`) también toma la suya antes de borrar.
- **Si no cambió nada, no se copia.** Antes de copiar se compara una huella de los datos con la de la última copia. Una pantalla que quedó abierta toda la noche o todo el fin de semana no gasta copias: cada una de las 25 es un estado distinto.
- **Qué entra.** Los datos: `clientes/*.json` (presupuestos y fichas), `tablero/obras.json`, `contratos/contratos.json`, `registro/clientes-index.json` y `registro/by-month/`.
- **Qué no entra.** Usuarios y contraseñas, el registro de acciones, Control, y las fotos, planos, PDF y ZIP: pesan demasiado para copiarlos cada hora.
- **Las fotos, igual vuelven.** Un archivo que el sistema borra (una foto, un plano, el PDF de un contrato que se cambia, la carpeta de una ficha que se elimina) no se destruye: pasa a `copias/borrados/` y se queda ahí mientras exista una copia que lo pueda necesitar. Al usar un respaldo, vuelve a su lugar. Cuando ya no queda ninguna copia anterior a su borrado, se borra de verdad.
- **Dónde vive.** `bs-data/copias/`, fuera de la web, igual que el resto de los datos. Cada copia es un ZIP que solo puede leer el usuario del servidor.
- **Quién entra.** Solo administradores, y lo controla el servidor: `copias.php` pide el permiso `copias` (`AUTH_PERMISOS` en `auth_check.php`). Un Operador o un Planos y bacha reciben un rechazo aunque armen el pedido a mano.
- **Si el archivo de copias no está**, el sistema anda igual, sin copias (probado).

## Lo que NO hace

- **No es una copia fuera del servidor.** Vive en el mismo hosting: si el hosting se cae o se pierde, las copias se van con él. Para eso están el cierre del mes (el ZIP que se baja y se guarda afuera) y las copias de Hostinger.
- **No vuelve al minuto.** Vuelve al estado del comienzo de una hora. Lo que se hizo en esa hora después de la copia se deshace con todo lo demás.
- **No vuelve una sola cosa.** Para recuperar una ficha o un contrato borrado, primero la Papelera (30 días), que no toca nada más.
- **No guarda más de 25.** Lo anterior se pisa.

## Cuánto pesa y cuánto tarda (medido)

Con 3.000 presupuestos de prueba (56 MB de datos, bastante más que lo real): la copia tarda 1 segundo, pesa 3 MB y usa 4 MB de memoria. Comparar la huella en una hora sin cambios tarda 0,13 segundos. Las 25 copias juntas, unos 75 MB en ese caso.

Ese segundo lo paga el primer pedido de cada hora en que hubo cambios; el resto no nota nada (se lee un archivo de 11 letras).

## Si algo sale mal

| Qué se ve | Qué hacer |
|---|---|
| "El último respaldo no se pudo guardar" (rojo, en Control) | Avisar. El sistema reintenta solo cada 5 minutos. Lo más probable: disco lleno o sin permiso de escritura en `bs-data/copias/` |
| "Este servidor no puede armar respaldos" | Falta la extensión ZIP de PHP en el hosting. Avisar |
| "Ese respaldo ya no está: se pisó con uno más nuevo" | Actualizar la lista y elegir otro |
| "Ese respaldo está dañado y no se puede usar" | Elegir otro. No se cambió nada |
| Se usó un respaldo por error | Usar "Antes de usar el respaldo" |

## Archivos

- `site/public_html/calculadora/api/_copias.php`: todo el mecanismo (copiar, pisar, guardar lo borrado, volver).
- `site/public_html/calculadora/api/copias.php`: la lista y la vuelta. Solo administradores.
- `site/public_html/calculadora/api/_auth.php`: llama a la copia en cada pedido con sesión.
- `site/public_html/presupuestos/reportes.php`: la caja "Respaldo" de Control.
- Lo borrado pasa por `bs_copia_borrar()` en: `_tablero.php`, `_contratos.php`, `tablero-adjunto.php`, `contratos-adjunto.php`, `state.php`, `delete.php`.

Constantes en `_copias.php`: `BS_COPIAS_CUANTAS` (25) y `BS_COPIAS_CONFIRMA` ("USAR RESPALDO").

## Pruebas

En local, con el reloj simulado (solo en el servidor de pruebas): 57 comprobaciones de servidor y 24 de pantalla. Cubren: la copia se toma antes del primer cambio de la hora; una hora sin cambios no gasta copia; quedan 25 y se pisa la más vieja; volver y deshacer la vuelta; los números no se repiten; fotos, planos y PDF borrados vuelven; una copia dañada no se usa; un archivo roto al copiar; sin el archivo de copias el sistema anda; la limpieza nocturna copia antes de borrar; las dos confirmaciones; y, en la prueba de roles, que Operador y Planos y bacha no pueden ver ni usar el respaldo.

No se probó en producción: desde acá no se puede ver ni tocar. La primera copia se toma sola con el primer movimiento después del deploy.
