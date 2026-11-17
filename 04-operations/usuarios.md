# Usuarios y roles del sistema interno

> Desde el 17/11/2026 cada persona entra con su usuario y su contraseña. Decisión y porqué: `01-strategy/decision-log/2026-11-17-usuarios-y-roles.md`.

## Los tres roles

| | Administrador | Operador | Planos y bacha |
|---|---|---|---|
| Ver el tablero y abrir fichas | Sí | Sí | Sí |
| Adjuntar foto de bacha, plano y entrega | Sí | Sí | Sí |
| Borrar o girar una foto, también las que subió otro | Sí | Sí | Sí |
| Cargar, mover, tildar, editar, archivar y borrar obras; papelera | Sí | Sí | No |
| Ver la seña de la ficha | Sí | Sí | No |
| Calculadora, Presupuestos, Calendario, Stock | Sí | Sí | No entra |
| Ver Relación-Contrato, con la plata de cada contrato | Sí | Sí, solo mirar | No entra |
| Ver el resumen de plata del mes (contratado, cobrado, por cobrar) | Sí | No | No |
| Cargar y cambiar contratos, cobros y pagos | Sí | No | No |
| Usuarios | Sí | No | No |

- **Operador en Relación-Contrato (dueño, 17/11/2026):** ve cada contrato con su total, seña, pagos y saldo. No ve el resumen del mes ni las sumas del pie. No puede crear, editar ni borrar: al abrir un contrato lo ve sin formulario.
- **Planos y bacha:** entra directo al tablero. Ve las fichas enteras menos la seña. No puede tildar, mover, editar ni escribir notas. Adjunta fotos y puede borrar o girar cualquiera, también las que subió otro (dueño, 17/11/2026). No entra al calendario. Si escribe otra dirección, vuelve al tablero.
- **Las notas de las fichas son texto libre.** A "Planos y bacha" se le tapan los montos escritos con signo: "Saldo $ 285.720" le llega como "Saldo $ ···". Un monto sin signo ("200 mil") no se reconoce y se ve. Si no tiene que verse, no va en la nota.

## Cómo se usa

- **Usuarios** está al final del menú, solo para administradores: crear, cambiar el rol, dar de baja, reactivar, cambiar la contraseña y borrar.
- El resto ve **Mi usuario** en ese lugar: cambiar su propia contraseña.
- La baja y el cambio de rol valen al instante, sin esperar a que la persona salga.
- Cambiarle la contraseña a alguien le cierra las sesiones abiertas.
- Siempre queda al menos un administrador activo. Nadie se saca su propio rol ni se da de baja a sí mismo. **Conviene que haya dos administradores.**
- Contraseña: 8 caracteres o más. El usuario va en minúsculas, sin espacios.
- **Cinco contraseñas mal seguidas bloquean a ese usuario 15 minutos**, no a todo el taller: los demás siguen entrando desde la misma conexión. Si desde una conexión se prueban 30 veces usuarios distintos, se frena la conexión entera.
- **Las contraseñas no se pasan por chat ni por escrito.** El administrador la carga y se la dice a la persona, que después la cambia desde "Mi usuario".

## El arranque (una sola vez)

1. Mientras no haya usuarios, el sistema anda como antes: una contraseña compartida y todos administradores.
2. El dueño entra con esa contraseña, abre **Usuarios** y crea su usuario administrador.
3. En ese momento la contraseña compartida deja de servir y se cierran todas las sesiones abiertas con ella.
4. El dueño crea los usuarios de los demás.

## Cómo está hecho

- **Dónde viven:** `bs-data/usuarios/usuarios.json`, fuera de la web y fuera del repo. Guarda usuario, nombre, rol, si está activo y la contraseña cifrada (`password_hash`). Tiene copia `.bak`.
- **La tabla de permisos** está en un solo lugar: `AUTH_PERMISOS` en `site/public_html/calculadora/auth_check.php`. Cambiar qué puede un rol es cambiar un renglón ahí.
- **El bloqueo lo hace el servidor.** Cada archivo de `calculadora/api/` empieza con `bs_require_permiso('<permiso>')` (`_auth.php`): sin sesión responde 401, sin permiso 403. Las páginas llaman a `auth_exigir_pagina()`. Esconder botones en pantalla es solo para que quede limpia.
- **Lo que un rol no ve no sale del servidor:** `tablero.php` no manda la seña (ni en el historial) sin `tablero_plata`; `contratos.php` no manda totales, seña ni pagos sin `contratos_plata` (hoy lo tienen Administrador y Operador). El resumen del mes se arma en la página a partir de los contratos: sin `contratos_resumen` no se arma, pero quien ve cada contrato podría sumarlos a mano.
- **El menú** lo arma `auth_nav_html()` para todas las páginas: solo lo que el rol puede abrir.
- **La sesión** es una cookie firmada con un secreto que se genera en el servidor al crear el primer administrador (campo `secreto` del archivo). No está en el repo. Dura 60 días.
- **Permisos que van de a dos** (`AUTH_REQUIERE`): cambiar el tablero pide también ver su plata, y cambiar contratos pide ver la de los contratos. Un rol que no recibe los montos guardaría de vuelta las notas tapadas o los totales en cero; por eso, si se le saca el de ver, pierde el de cambiar.
- **Un endpoint nuevo** tiene que llamar a `bs_require_permiso()`. `bs_require_auth()` a secas quedó cerrado a administradores, para que un olvido no abra nada.
- **Fotos:** cada adjunto guarda quién lo subió (`por`). Hoy los tres roles pueden borrar las de cualquiera (`tablero_adj_ajeno`); si se le saca ese permiso a un rol, solo borra las suyas.
- **Registro:** cada cambio queda en `bs-data/usuarios/registro-AAAA-MM.log` (fecha, usuario, archivo, permiso, N° de ficha). No guarda contraseñas ni nombres de clientes. No tiene pantalla: se baja por FTP.
- **Relación-Contrato sigue pudiendo borrarse entera:** los usuarios no dependen de ella.

## Si algo sale mal

- **Alguien olvidó su contraseña:** un administrador le pone otra desde Usuarios.
- **El único administrador olvidó la suya:** entrar al administrador de archivos de Hostinger y borrar `bs-data/usuarios/usuarios.json` y `usuarios.json.bak`. El sistema vuelve a la contraseña compartida y hay que crear el administrador de nuevo, en el momento. Se pierden todos los usuarios. Por eso conviene tener dos administradores.
- **El archivo se dañó:** se usa la copia `.bak`, y el próximo guardado rehace el principal sin pisar la copia sana. Si las dos están dañadas no entra nadie, y la contraseña compartida no se reabre sola.

## Pruebas

312 comprobaciones contra un servidor local: cada rol contra cada página y cada archivo de datos (lectura y escritura), que la plata no salga del servidor, fotos propias y ajenas, bajas y cambios de rol al instante, cookie adulterada, archivo dañado, bloqueo por intentos fallidos. Más los flujos completos del Operador en el navegador (borrar una ficha con contrato y recuperarla, archivar y devolver) y las pantallas de los tres roles en computadora, tablet y celular. La regresión completa del tablero, calendario, contratos y hub pasa igual con la contraseña compartida y entrando como administrador.
