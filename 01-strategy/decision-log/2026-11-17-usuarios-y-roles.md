# 2026-11-17 — Usuarios y roles

**Decide:** el dueño. **Status:** decidido e implementado. Falta el deploy y crear el primer administrador.

## Decisión

El sistema interno deja la contraseña compartida. Cada persona entra con su usuario, y hay tres roles:

- **Administrador:** sin restricción.
- **Operador:** tablero completo (cargar, mover, archivar, borrar, papelera), calculadora, Presupuestos, calendario y stock. Ve Relación-Contrato con la plata de cada contrato, sin el resumen del mes y sin poder cambiar nada.
- **Planos y bacha:** solo el tablero, y solo para las fotos: adjunta bacha, plano y entrega, y puede borrar cualquiera.

## Por qué

- Con una sola contraseña todos podían todo, y no se sabía quién hizo cada cosa.
- La contraseña compartida está escrita en el repo, que es público. Con usuarios deja de servir.

## Lo que definió el dueño y lo que quedó por defecto

Definido por el dueño (17/11/2026):

- Los tres roles.
- El Operador puede borrar, archivar y usar la papelera; entra a Calculadora, Presupuestos y Calendario.
- El Operador **sí ve los montos** de cada contrato. Lo que no ve es el resumen de plata. (La primera versión le tapaba todo; el dueño lo corrigió el mismo día.)
- "Planos y bacha" **puede borrar fotos que subió otro**. (La primera versión solo le dejaba las suyas; corregido el mismo día.)
- "Planos y bacha" no entra al calendario.
- "Planos y bacha" **puede poner el modelo de la bacha** (y de quién es), sin operar nada más del tablero.
- **Que se vea quién está trabajando**, en tiempo real y en todas las pantallas, no solo en Usuarios ("que se vea la comunidad trabajando"). Lo ven los tres roles.

Puesto por defecto, sin respuesta del dueño. Cada uno es un renglón de `AUTH_PERMISOS`:

- El Operador no edita contratos ni carga pagos (el dueño dijo "puede ver").
- "Planos y bacha" no ve la seña de la ficha.
- El Operador entra a Stock (hoy es una página vacía).
- La foto de entrega quedó habilitada para "Planos y bacha" (el dueño dijo "después vemos eso").

## Knowns

- El permiso se controla en el servidor, en cada archivo de datos. Probado rol por rol contra los 35 archivos.
- Lo que un rol no puede ver no sale del servidor (seña e historial de la seña para "Planos y bacha").
- Las notas de las fichas llevan plata escrita a mano ("Saldo $ …"). A "Planos y bacha" se le tapan los montos con signo.
- El resumen del mes se arma en la página con los contratos; ningún archivo del servidor lo controla. El Operador no lo ve, pero tiene los montos de cada contrato y podría sumarlos.
- Mientras no se cree el primer administrador nada cambia para el equipo.
- Al crear el primer administrador se cierran todas las sesiones de la contraseña compartida: el resto no entra hasta tener su usuario.

## Lo que encontró la búsqueda de bugs (17/11/2026)

Una revisión propia de casos límite y una revisión independiente del código, antes del deploy. Todo arreglado y con su prueba:

- Cinco contraseñas mal de una persona bloqueaban el ingreso de todo el taller (todos salen por la misma conexión). Ahora se bloquea ese usuario.
- Quien opera el tablero podía mandar a la papelera el contrato de una ficha viva llamando directo al servidor. Ahora el servidor exige que la ficha haya salido del tablero.
- Reactivar a alguien dado de baja le revivía las sesiones viejas. Ahora la baja las cierra para siempre.
- Cambiar el rol desde una pantalla desactualizada podía reactivar a alguien dado de baja. Ahora cada cambio manda solo lo que cambia.
- Los montos en notas escritos como "200.000$", "ARS 90.000", "USD: 1200" o "350mil" no se tapaban.
- Un guardado con el archivo de usuarios dañado pisaba la copia de respaldo sana.

No se encontró ninguna forma de saltear permisos, falsificar la sesión ni reabrir la contraseña compartida.

## Known unknowns

- Si el dueño quiere que la presencia diga algo más que la pantalla (por ejemplo, en qué ficha o presupuesto está cada uno). Hoy dice solo dónde.

- Qué hace exactamente "Planos y bacha" con la foto de entrega.
- Cuántos administradores va a haber. Con uno solo, perder la contraseña obliga a borrar el archivo de usuarios por el panel de Hostinger.
- Montos escritos en notas sin signo de pesos: no se tapan.

## Unknown unknowns

- Alguien del equipo que hoy usa una pantalla que su rol nuevo no le deja abrir.
- Datos con plata en algún campo de texto libre que no miramos (concepto, medidas).

## Pendiente

- `site/public_html/calculadora/auth_config.php` sigue en el repo con la contraseña compartida vieja. Después del primer administrador no abre nada, salvo que alguien borre el archivo de usuarios. Conviene rotarla y sacarla del repo igual.
- El registro de cambios no tiene pantalla.
- Las pruebas viven fuera del repo.
