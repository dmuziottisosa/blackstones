# 2026-11-16 — La etapa la dicta el tablero; el presupuesto dura 180 días

**Estado:** regla fijada por el dueño. Aplicada en la limpieza automática; lo de la pestaña Presupuestos queda pendiente de su visto bueno.

## Qué se decidió

- **Las etapas del presupuesto ya no mandan.** Dónde está una obra lo dice el tablero (Por medir, Por cortar, Por fabricar, Por instalar, Por archivar).
- **El presupuesto es una entidad que dura 180 días.** Después se borra de la pestaña Presupuestos, para que no se llene de presupuestos viejos.
- **Lo que queda para siempre es el contrato con su presupuesto adjunto.** Por eso el sello de verificado sale solo con el PDF o Excel adjunto: el N° deja de existir a los 180 días, el archivo no.
- **Es deseable que todo cliente tenga N° de presupuesto**, pero no es bloqueante.
- **Mes archivado:** el dueño le va a pedir al equipo que lo imprima y lo guarde en carpeta. No está definido cuánto tiempo se conservan los contratos en el sistema ("lo vamos viendo").

- **Eliminar un presupuesto no elimina la ficha, ni el calendario, ni el contrato.** Una obra viva sale de la pestaña Presupuestos (a mano o a los 180 días) y sigue en el tablero.
- **Presupuestos no mueve obras vivas:** se sacaron de esa pestaña los botones de estado de los aprobados (Entregado / volver a Enviado). Queda "En el tablero →".
- **Enlazados, la ficha se borra en los dos lados** (tablero y Relación-Contrato). Archivar no es borrar.

## Por qué

- Dos lugares que dicen en qué etapa está una obra terminan diciendo cosas distintas. Se elige uno: el tablero.
- La pestaña Presupuestos sirve para cotizar y cerrar; no es un archivo.

## Knowns

- Por dentro, una obra del tablero ES una cotización en estado "aprobado" (`clientes/NNNN.json`); archivarla la pasa a "entregado". O sea: el tablero ya dictaba esos dos estados.
- **Hallazgo:** la limpieza nocturna (`cleanup-retention.php`) borraba toda cotización no entregada sin cambios de estado en 180 días, **incluidas las aprobadas**, y con ellas su ficha del tablero y sus fotos. Una obra que llevara más de 180 días en el tablero (pausada, o esperando en Por archivar) desaparecía sola. Probado con datos envejecidos: la versión anterior borraba dos obras vivas, una con foto.
- **Corregido el 16/11/2026:** una cotización "aprobado" no vence por antigüedad. Sale del tablero cuando se archiva (y ahí empieza su reloj de 180 días) o cuando alguien borra la ficha.
- Una obra archivada se borra a los 180 días de archivada, con sus fotos y planos. Coincide con la idea de imprimir el mes archivado.
- El reporte mensual (`report-summary.php`, `monthly-report.php`) cuenta las cotizaciones en estado "entregado". Depende de que "Archivar" siga marcando ese estado.
- Los contratos de Relación-Contrato **no** se borran solos, ni su archivo adjunto (hasta 10 MB cada uno).

## Known unknowns

- ~~Qué hacer con los botones de estado de Presupuestos.~~ Resuelto el 16/11/2026: los aprobados ya no tienen botones de estado. Siguen Borrador / Enviado / Perdido y "→ Aprobado", que es la puerta de entrada al tablero. Falta decidir si esa puerta también se saca (hoy una obra entra por tres lados: aprobar un presupuesto, "Cargar obra" y Relación-Contrato).
- Un presupuesto marcado `fuera_de_presupuestos` no se puede volver a ver en la pestaña; si hiciera falta recuperarlo, hay que hacerlo a mano.
- Si el reloj de 180 días del presupuesto debe contar desde que se creó o desde su último cambio (hoy: último cambio de estado; y no vence mientras el cliente tenga una obra entregada hace menos de 180 días).
- Cuánto tiempo se guardan los contratos y sus adjuntos, y si se archivan por mes.
- Si la limpieza nocturna está efectivamente programada en Hostinger (el archivo dice 03:00; no se pudo verificar desde acá).

## Unknown unknowns

- Obras "aprobado" que ya nadie mira (cargadas por error) ahora no vencen nunca: quedan en el tablero hasta que alguien las borre.
- Si el equipo no imprime el mes, a los 180 días de archivada una obra se pierde con sus fotos.
