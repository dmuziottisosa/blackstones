# Usuarios y roles del sistema interno

> Desde el 17/11/2026 cada persona entra con su usuario y su contraseña. Decisión y porqué: `01-strategy/decision-log/2026-11-17-usuarios-y-roles.md`.

## Los tres roles

| | Administrador | Operador | Planos y bacha |
|---|---|---|---|
| Ver el tablero y abrir fichas | Sí | Sí | Sí |
| Adjuntar foto de bacha, plano y entrega | Sí | Sí | Sí |
| Borrar o girar una foto | Todas | Todas | Solo las que subió ella |
| Cargar, mover, tildar, editar, archivar y borrar obras; papelera | Sí | Sí | No |
| Ver la seña de la ficha | Sí | Sí | No |
| Calculadora, Presupuestos, Calendario, Stock | Sí | Sí | No entra |
| Ver Relación-Contrato | Sí | Sin la plata, solo mirar | No entra |
| Cargar y cambiar contratos, cobros y pagos | Sí | No | No |
| Usuarios | Sí | No | No |

- **Operador en Relación-Contrato:** ve los contratos (cliente, N°, material, estado, enlace, adjunto), pero no el total, la seña, los pagos, el saldo ni el resumen del mes. No puede crear, editar ni borrar. Sí ve los precios en Calculadora y Presupuestos, y la seña en la ficha del tablero.
- **Planos y bacha:** entra directo al tablero. Ve las fichas enteras menos la plata. No puede tildar, mover, editar ni escribir notas. Si escribe otra dirección, vuelve al tablero.
- **Las notas son texto libre.** A quien no ve la plata se le tapan los montos escritos con signo: "Saldo $ 285.720" le llega como "Saldo $ ···". Un monto sin signo ("200 mil") no se reconoce y se ve. Si no tiene que verse, no va en la nota.

## Cómo se usa

- **Usuarios** está al final del menú, solo para administradores: crear, cambiar el rol, dar de baja, reactivar, cambiar la contraseña y borrar.
- El resto ve **Mi usuario** en ese lugar: cambiar su propia contraseña.
- La baja y el cambio de rol valen al instante, sin esperar a que la persona salga.
- Cambiarle la contraseña a alguien le cierra las sesiones abiertas.
- Siempre queda al menos un administrador activo. Nadie se saca su propio rol ni se da de baja a sí mismo. **Conviene que haya dos administradores.**
- Contraseña: 8 caracteres o más. El usuario va en minúsculas, sin espacios.
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
- **Lo que un rol no ve no sale del servidor:** `tablero.php` no manda la seña (ni en el historial) sin `tablero_plata`; `contratos.php` no manda totales, seña ni pagos sin `contratos_plata`.
- **El menú** lo arma `auth_nav_html()` para todas las páginas: solo lo que el rol puede abrir.
- **La sesión** es una cookie firmada con un secreto que se genera en el servidor al crear el primer administrador (campo `secreto` del archivo). No está en el repo. Dura 60 días.
- **Un endpoint nuevo** tiene que llamar a `bs_require_permiso()`. `bs_require_auth()` a secas quedó cerrado a administradores, para que un olvido no abra nada.
- **Fotos:** cada adjunto guarda quién lo subió (`por`). Las de antes de los usuarios no dicen de quién son: "Planos y bacha" no las puede borrar.
- **Registro:** cada cambio queda en `bs-data/usuarios/registro-AAAA-MM.log` (fecha, usuario, archivo, permiso, N° de ficha). No guarda contraseñas ni nombres de clientes. No tiene pantalla: se baja por FTP.
- **Relación-Contrato sigue pudiendo borrarse entera:** los usuarios no dependen de ella.

## Si algo sale mal

- **Alguien olvidó su contraseña:** un administrador le pone otra desde Usuarios.
- **El único administrador olvidó la suya:** entrar al administrador de archivos de Hostinger y borrar `bs-data/usuarios/usuarios.json` y `usuarios.json.bak`. El sistema vuelve a la contraseña compartida y hay que crear el administrador de nuevo, en el momento. Se pierden todos los usuarios. Por eso conviene tener dos administradores.
- **El archivo se dañó:** se usa la copia `.bak`. Si las dos están dañadas no entra nadie, y la contraseña compartida no se reabre sola.

## Pruebas

303 comprobaciones contra un servidor local: cada rol contra cada página y cada archivo de datos (lectura y escritura), que la plata no salga del servidor, fotos propias y ajenas, bajas y cambios de rol al instante, cookie adulterada, archivo dañado. Más las pantallas de los tres roles en computadora, tablet y celular. La regresión completa del tablero, calendario, contratos y hub pasa igual con la contraseña compartida y entrando como administrador.
