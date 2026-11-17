# 2026-11-17 — Usuarios y roles

**Decide:** el dueño. **Status:** decidido e implementado. Falta el deploy y crear el primer administrador.

## Decisión

El sistema interno deja la contraseña compartida. Cada persona entra con su usuario, y hay tres roles:

- **Administrador:** sin restricción.
- **Operador:** tablero completo (cargar, mover, archivar, borrar, papelera), calculadora, Presupuestos, calendario y stock. Ve Relación-Contrato sin la plata y sin poder cambiar nada.
- **Planos y bacha:** solo el tablero, y solo para adjuntar foto de bacha, plano y foto de entrega.

## Por qué

- Con una sola contraseña todos podían todo, y no se sabía quién hizo cada cosa.
- La contraseña compartida está escrita en el repo, que es público. Con usuarios deja de servir.

## Lo que definió el dueño y lo que quedó por defecto

Definido por el dueño (17/11/2026): los tres roles; el Operador puede borrar, archivar y usar la papelera; el Operador entra a Calculadora, Presupuestos y Calendario.

Puesto por defecto, con la recomendación que se le había dado y sin respuesta suya todavía. Cada uno es un renglón de `AUTH_PERMISOS`:

- El Operador no ve ningún monto de los contratos (total, seña, pagos, saldo), no solo el resumen de arriba, y no los edita.
- "Planos y bacha" no ve la seña de la ficha y solo borra las fotos que subió ella.
- "Planos y bacha" no entra al calendario.
- El Operador entra a Stock (hoy es una página vacía).
- La foto de entrega quedó habilitada para "Planos y bacha" (el dueño dijo "después vemos eso").

## Knowns

- El permiso se controla en el servidor, en cada archivo de datos. Probado rol por rol contra los 35 archivos.
- Lo que un rol no puede ver no sale del servidor (seña, historial de la seña, montos de contratos).
- Las notas llevan plata escrita a mano ("Saldo $ …"). Se tapan los montos con signo para quien no ve la plata.
- Mientras no se cree el primer administrador nada cambia para el equipo.
- Al crear el primer administrador se cierran todas las sesiones de la contraseña compartida: el resto no entra hasta tener su usuario.

## Known unknowns

- Si el dueño quiere que el Operador vea los montos de cada contrato y solo se le oculte el resumen.
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
