# Instagram en la landing

> La sección "Lo último que salió del taller" de la landing muestra **doce fotos que pasa el dueño**. Se suman a mano, sin sacar las que están.
> `status:` vigente desde el 24/11/2026 (bloque de deploy entregado ese día). Es la forma que eligió el dueño después de que no se pudo conectar el Instagram en vivo.
> Más abajo queda descripto lo que se había armado para mostrar los posts solos (`ig-feed.php`): **no está en la landing ni en el servidor**, y está en pausa.

## Cómo está hoy: las fotos las pasa el dueño

El 24/11/2026 subió cuatro fotos de Instagram al repo y mandó dos más por el chat: "Te lo subí al repo. Sin quitar los otros, agregar a lo último que salió del taller." Con las seis que ya había son doce.

- **El mosaico.** En el celular va de 4 columnas: las cuatro fotos más nuevas, grandes (2 × 2), y las otras ocho en dos filas. Desde 640 px va de 6 columnas: una principal (4 × 4), tres medianas (2 × 2) y ocho chicas.
- **En pantallas anchas (desde 1400 px) la sección usa más lugar.** El dueño, 24/11/2026, mirándola en su monitor de 1920: "esta sección podría ocupar más espacio, ¿no? Tiene para ocupar más espacio, para que no se vea apretujadísimo". El contenido de la sección pasa de 1120 a 1400 px y todo lo que se gana va al mosaico, que pasa a 8 columnas por 6 filas: una principal, **siete medianas** y solo cuatro chicas. Va apaisado (856 × 639 px en una pantalla de 1920, contra 579 × 579 de antes) para crecer a lo ancho, que es donde sobra pantalla, sin pasarse de alto. Las reseñas quedan del ancho que tenían, y el resto de la página y el menú no cambian. En notebooks de menos de 1400 px no hay de dónde sacar lugar y queda como estaba.
- **El mosaico no se estira a lo alto.** Hasta el 24/11/2026 tenía una regla que lo hacía llenar el alto de su columna. En ventanas de 900 a 1100 px las reseñas quedan más altas que el mosaico, y ahí se deformaba: con las seis fotos de antes se salía de su columna, y con las doce una foto quedaba de 25 px. Se encontró midiendo ancho por ancho, no en los tamaños de siempre. Ahora mantiene su forma en cualquier ancho.
- **El orden.** Lo más nuevo adelante: la principal es la más nueva. Las seis de antes quedan al final.
- **Cada foto tiene dos archivos** en `site/public_html/ig_section/`: `N.webp` (1080 px) y `N-c.webp` (540 px). La principal usa el de 1080; todas las demás, el de 540. Las doce fotos pesan 302 KB; las seis de antes pesaban 357.
- **Todas las fotos llevan al perfil** de Instagram. No se sabe a qué post corresponde cada una.

### El movimiento

El dueño, 24/11/2026, primero: "¿Qué animación le darías para no hacerlo tan estático? Animaciones que no sobrecarguen." Y cuando vio la primera versión, que se movía igual en todos los tamaños: "en celular que no se mueva, mejor; en PC podemos hacer una animación más elaborada."

- **En el celular y en la tablet el mosaico queda quieto:** las doce fotos a la vista desde que abre la página. Vale también para la tablet acostada: no decide solo el ancho, decide que se use con el dedo.
- **En computadora** (pantalla de 900 px o más, con mouse) se mueve, y son tres cosas:
  1. **La entrada**, cuando la sección llega a la pantalla: la principal aparece asentándose, las tres medianas entran desde la derecha y las ocho chicas van apareciendo en cadena. Dura menos de dos segundos.
  2. **La principal cambia cada 6 segundos:** se intercambia con una de las medianas, con un fundido, y la que queda de principal hace un zoom muy lento. Así pasan por el lugar grande las cuatro fotos más nuevas. Las ocho chicas no cambian de lugar.
  3. **Con el mouse encima**, los cambios se frenan, y la foto que se mira queda clara mientras las demás bajan un poco.

Qué lo hace liviano, y qué tiene que cumplir cualquier animación que se sume acá:

- Usa solo `opacity` y `transform`, que el navegador mueve sin volver a dibujar la página.
- Los cambios corren solo mientras la sección se ve y la pestaña está abierta.
- No baja nada de antemano. Para los cambios baja una copia chica de la principal (17 KB); en pantallas de mucha definición baja además la copia grande de cada mediana, de a una y recién cuando le toca.
- Con "reducir movimiento" activado, o sin JavaScript, queda todo quieto y a la vista.
- Si una foto no baja, ese cambio se saltea: no queda un cuadro vacío. Si la ventana se achica, queda quieto con las fotos que se estaban viendo.
- En el celular no se mueve.

Las que giran son la principal (`ig-p`) y las medianas (`ig-g`): al sumar fotos no hay que tocar el programa, toma las que tengan esas marcas. Las cuatro que son medianas solo en pantallas anchas (`ig-m`) no giran.

### Cómo sumar fotos

1. El dueño las pasa: subidas a GitHub (a la carpeta principal de la rama por defecto) o por el chat. Por el chat llegan enteras, con su tamaño original.
2. Prepararlas: `php 04-operations/tools/foto-mosaico.php <foto> <número que sigue>`. Si no es cuadrada, mirar antes de dónde conviene cortarla (`arriba`, `centro`, `abajo` o `x,y,lado`): en las fotos más altas que anchas el logo queda bien abajo y un corte al centro lo deja por la mitad. Y si trae la marca de un celular ("Galaxy A71"), cortarla afuera.
3. Sumar los tiles en `index.html`, adelante, y decidir cuáles van grandes (abajo).
4. Probar en celular, tablet y computadora que el mosaico no tenga huecos (y, en computadora, que la principal siga cambiando). En computadora, **ancho por ancho, de 900 a 1920 px**, no solo en uno: la falla del 24/11/2026 estaba entre 900 y 1100. Y entregar el bloque de deploy (`04-operations/tools/bloque-deploy.py`): las fotos primero, `index.html` al final.

**Para que el mosaico cierre sin huecos, las fotos se suman de a tres.** La cuenta: la principal ocupa 16 lugares, cada mediana 4 y cada chica 1. En 6 columnas el total tiene que dar 36; en 8 columnas (pantallas anchas), 48; en 4 columnas (celular), la principal ocupa 4 y el total tiene que dar 24.

| Fotos | Principal (`ig-p`) | Medianas (`ig-g`) | Medianas solo en pantalla ancha (`ig-m`) | Sin marca |
|---|---|---|---|---|
| 12 (hoy) | 1 | 3 | 4 | 4 |
| 15 | 1 | 2 | 4 | 8 |
| 18 | 1 | 1 | 4 | 12 |
| 21 | 1 | 0 | 4 | 16 |

Las `ig-m` son siempre cuatro y van justo después de las `ig-g`. Al sumar tres fotos, una `ig-g` pasa a ser `ig-m` y una `ig-m` queda sin marca.

Con 13 o 14 no cierra: o se espera a tener las tres, o se saca alguna vieja (y eso lo decide el dueño: pidió no sacar ninguna). Pasando las 21 hay que repensarlo.

### Lo que hay que saber de la landing publicada

El 24/11/2026 se bajó la página publicada y se vio que **el cambio para mostrar los posts solos nunca se había subido**: el `index.html` del servidor era el de antes, y el del repo ya traía el script. Desde ese día el `index.html` del repo vuelve a ser el publicado (más lo que se le fue sumando), y el script de Instagram ya no está en la landing: quedó en el commit `695d407`. `ig-feed.php` sigue en el repo, sin subir.

## Qué pasó al generar la llave (24/11/2026)

El dueño creó la app en Meta siguiendo los pasos de abajo. Lo que apareció no fue lo que dice la documentación:

- **El panel no ofreció "API setup with Instagram login"**, que es el camino para el que está escrito `ig-feed.php`. Ofreció dos: "API setup with Unified login" (lo marca como el recomendado) y "API setup with Facebook login" (lo marca como viejo).
- **"Unified login" da otro tipo de llave**, que se usa contra otro servidor (`graph.facebook.com`) y con otros pedidos. `ig-feed.php` no la sabe usar tal como está.
- **"Unified login" exige que la app sea de un portfolio comercial**, y la documentación pide que ese portfolio sea distinto del que tiene el Instagram. La cuenta no tiene hoy un segundo portfolio que sirva para eso, y no se crea uno nuevo solo para esto.

Ahí se frenó. Lo que dice la documentación de Meta sobre "Unified login" está en `06-knowledge/layer-2-landscape/instagram-api-para-mostrar-posts.md`.

## Formas que quedan (sin decidir)

- **Cargar los posts desde el sistema.** Una pantalla para administradores: se pega el link del post y se sube la foto; la landing muestra los últimos seis. Sin Meta ni llaves, y se elige qué se ve. Es la que se le recomendó al dueño. No está hecha. `ig-feed.php` y el script de la landing sirven tal cual: cambia quién escribe la lista.
- **Un servicio externo que lee el Instagram** (por ejemplo Behold). Es automático, depende de otra empresa y sí conecta el Instagram. Según su página de precios, vista el 24/11/2026 y sin comprobar más: gratis con una actualización por día y hasta 6 posts; USD 10 por mes con actualización por hora.
- **Dejar las fotos fijas** y cambiarlas a mano cuando haga falta. **Es lo que se hace desde el 24/11/2026** (arriba, "Cómo está hoy").
- **Retomar Meta** el día que haya un portfolio que sirva: habría que enseñarle a `ig-feed.php` la llave nueva.

Lo que sigue describe lo que se había armado para mostrar los posts solos. **Hoy no está en la landing ni en el servidor.** Para retomarlo: `ig-feed.php` está en el repo, y el cambio de la landing (los tiles con `data-fija` y el script) está en el commit `695d407`; habría que adaptarlo a un mosaico de doce fotos (`IG_TILES` vale 6).

## Qué ve el visitante

- **Seis fotos: los seis posts más nuevos que tienen foto.** Cuentan las fotos, los álbumes (la primera foto) y los reels (la portada). El más nuevo es el grande.
- **Cada foto lleva a su post.**
- **Un post nuevo aparece dentro de la hora.** El navegador de cada visitante guarda la lista cinco minutos más.
- **Un post que se borra de Instagram** sale de la landing dentro de la hora.
- **Si algo falla, quedan las seis fotos fijas** de `ig_section/` con el link al perfil, como antes. La sección no queda vacía.

Todo lo que se publique en la cuenta aparece en la landing sin que nadie lo revise. Es el punto de hacerla dinámica y es también el cuidado: la cuenta es la vidriera.

## Cómo está hecho

Dos piezas:

- **`site/public_html/ig-feed.php`**. Tiene la lista guardada y la devuelve al instante. Como mucho una vez por hora le pregunta a Instagram por la API oficial, baja la foto de cada post nuevo y guarda dos copias propias (900 y 480 px de lado corto, WebP): los links de fotos de Instagram vencen. Le pregunta **después** de contestarle al visitante, así nadie espera a Instagram.
- **`site/public_html/index.html`**. Un script al final pide la lista y pone en cada tile su foto, su link y su etiqueta. Los tiles ya no traen la foto fija en el HTML: la traen en `data-fija` y se usa solo si la lista no llega en 3 segundos, viene mal o una foto no carga. Así no se bajan las dos tandas.

| Qué | Dónde | Está en el repo |
|---|---|---|
| La llave de Instagram | `bs-data/ig/token.txt` (fuera de la web) | **No, nunca** |
| La última lista buena | `bs-data/ig/feed.json` | No |
| Qué pasó la última vez | `bs-data/ig/estado.json` | No |
| Las copias de las fotos | `public_html/ig_section/cache/` | No |
| Las seis fotos fijas | `public_html/ig_section/1.webp` a `6.webp` | Sí |

El respaldo y el cierre del mes del sistema interno no copian `bs-data/ig/`: copian carpetas con nombre (clientes, tablero, contratos, registro). La llave no termina en ningún ZIP.

## Reglas

- **La llave no va al repo, a un chat ni a un ejemplo.** Se carga con el bloque de abajo, que la toma del portapapeles y la sube por FTP.
- **La llave no sale del servidor.** `ig-feed.php` no la escribe en ninguna respuesta. Si un error de Instagram la trae adentro, se tapa antes de guardarlo.
- **Solo se muestra @blackstones.ar.** Antes de publicar nada, el archivo le pregunta a Instagram de qué cuenta es la llave. Si es de otra (una cuenta personal, por ejemplo), no publica nada y lo dice en el estado. La cuenta está escrita en `IG_CUENTA`; si un día cambia el usuario de Instagram, se cambia ahí y en los links de la landing.
- **Si Instagram falla, se sigue mostrando lo último que anduvo.** Reintenta solo a los 15 minutos. Si rechaza la llave, cada 6 horas: eso no se arregla solo.
- **A Instagram se le pregunta, como mucho, una vez por hora**, aunque entren mil visitantes. El límite de Meta está muy lejos.
- **Las copias de fotos se limpian solas.** Una copia que ya no se muestra se borra a la semana. Solo toca archivos con el nombre que el mismo archivo les pone, dentro de `ig_section/cache/`. Las fotos fijas no se tocan.
- **No se pide el texto del post.** La documentación de Meta marca ese campo como disponible solo con el otro tipo de acceso. La etiqueta de cada foto (para lectores de pantalla) es "Post de Instagram del 22 de noviembre".

## La llave

Es un permiso de solo lectura que Meta le da a una app para leer los posts de la cuenta. Dura 60 días y `ig-feed.php` la renueva sola cada semana. Mientras entre al menos una visita cada dos meses y nadie cambie la contraseña de Instagram ni quite el permiso, no hay que volver a generarla.

### Generarla (una vez, en el navegador)

**Estos pasos son los de la documentación de Meta del 23/11/2026 y ya no coinciden con el panel:** el paso 3 no lleva a "API setup with Instagram login". Quedan como registro de lo que se intentó.

1. Entrar a `developers.facebook.com` con el Facebook que administra la cuenta del negocio. Si es la primera vez, pide registrarse como desarrollador (es gratis).
2. **My Apps → Create App**. Caso de uso: **Other**. Tipo: **Business**. Nombre (por ejemplo "BlackStones web") y un mail de contacto.
3. En el panel de la app, agregar el producto **Instagram** con **Set up**. Queda "API setup with Instagram login".
4. Ahí, **agregar la cuenta de Instagram**: iniciar sesión como @blackstones.ar y aceptar. La cuenta tiene que ser profesional (empresa o creador) y pública. No hace falta una página de Facebook.
5. Al lado de la cuenta, **Generate token**. Copiar la llave.

No hace falta publicar la app ni pedir revisión: para leer la cuenta propia alcanza el acceso estándar.

### Cargarla (bloque PowerShell)

Toma la llave del portapapeles (o pegada en la consola, donde no se ve), la sube a `bs-data/ig/token.txt` y le pregunta al sitio si Instagram la aceptó. La llave no pasa por el chat ni por GitHub. Va todo entre `& { }` para que, al pegarlo, el resto del bloque no caiga adentro de la pregunta.

```powershell
# === BlackStones - cargar la llave de Instagram (no pasa por el chat ni por GitHub) ===
& {
  Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
  while ($true) {
    $seg = Read-Host "Copia la llave en la pagina de Meta, volve a esta ventana y apreta Enter" -AsSecureString
    $llave = [System.Net.NetworkCredential]::new("", $seg).Password.Trim()
    if ($llave.Length -eq 0) { $llave = ("" + (Get-Clipboard -Raw)).Trim() }
    if ($llave.Length -ge 60 -and $llave -match '^[A-Za-z0-9_.|%=-]+$') { break }
    Write-Host "Lo copiado no es una llave. Copiala de nuevo en Meta y apreta Enter." -ForegroundColor Yellow
  }
  $tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".txt")
  [System.IO.File]::WriteAllText($tmp, $llave)
  curl.exe -s --ftp-create-dirs --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/bs-data/ig/token.txt"
  $subio = $LASTEXITCODE
  Remove-Item $tmp
  if ($subio -ne 0) { throw "Fallo la subida de la llave. Volve a pegar el bloque." }
  Write-Host "Llave subida. Preguntandole a Instagram (puede tardar unos segundos)..." -ForegroundColor Cyan
  [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
  try { $r = ((curl.exe -s -m 90 "https://blackstones.com.ar/ig-feed.php?estado=1") -join "") | ConvertFrom-Json } catch { $r = $null }
  if (-not $r -or -not $r.diagnostico) { Write-Host "La llave quedo subida, pero el sitio no contesto. Fijate que el bloque de deploy ya este pegado y volve a pegar este." -ForegroundColor Yellow }
  elseif ($r.ok) { Write-Host $r.diagnostico.resumen -ForegroundColor Green; Start-Process "https://blackstones.com.ar/?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())#resenas" }
  else { Write-Host $r.diagnostico.resumen -ForegroundColor Yellow; if ($r.diagnostico.error) { Write-Host ("Instagram dijo: " + $r.diagnostico.error.mensaje) -ForegroundColor Yellow } }
}
```

La ruta `bs-data/ig/` es desde donde aterriza el FTP (`/home/u144473384/`), no desde `FTP_REMOTE_BASE`: está fuera de la web a propósito. → `ftp-map.md`

### Cuándo hay que generarla de nuevo

Cuando el estado dice "Instagram no acepta la llave": se cambió la contraseña de Instagram, se quitó el permiso de la app o pasaron más de 60 días sin que se renovara. Se repite el paso 5 y el bloque.

## Cómo saber qué está pasando

`https://blackstones.com.ar/ig-feed.php?estado=1`

El primer renglón, `resumen`, lo dice en castellano:

- "Todo bien: 6 posts de @blackstones.ar, actualizados hace 12 minutos. La llave vence en 53 días y se renueva sola."
- "Falta cargar la llave de Instagram."
- "Instagram no acepta la llave (código 190). Hay que generar una nueva y cargarla."
- "La llave es de la cuenta @…, no de @blackstones.ar."
- "Instagram no respondió bien la última vez. Se reintenta solo en unos minutos."
- "Instagram devolvió 4 posts con foto y la grilla necesita 6."

Abajo trae el detalle para quien lo arregle: el error que devolvió Instagram, cuándo fue el último intento y el próximo, cuándo se renovó la llave, y tres datos del servidor (`fotos`: si puede achicar las fotos; `corte`: si puede contestar antes de terminar; `ruta`: qué dirección de la API anduvo). Pedir el estado no muestra la llave.

## Cómo se probó

Contra un Instagram de mentira en la computadora de desarrollo: 102 comprobaciones del servidor y 51 de la landing en computadora, tablet y celular. Entre otras: sin llave no crea nada ni llama a nadie; lista normal con foto, reel, álbum y álbum que empieza con video; llave vencida, llave nueva, llave de otra cuenta; Instagram caído o contestando basura; fotos rotas, enormes o de otro servidor; renovación de la llave y renovación fallida; doce visitantes a la vez (una sola llamada); un pedido cortado a la mitad; el servidor sin librería de imágenes y sin cURL; y que la llave no aparezca en ninguna respuesta, archivo ni registro.

También se ensayaron los dos bloques de PowerShell (el de deploy y el de la llave) con el FTP y el navegador de mentira.

Las pruebas no están en el repo (ninguna del sitio lo está todavía).

## Lo que no se sabe todavía

- **Si la dirección de la lista es la que dice la documentación.** Meta documenta `/<id de la cuenta>/media`. El archivo prueba esa, después la del otro id que devuelve `/me` y después `/me/media`, y se queda con la que anda. El estado dice cuál fue.
- **Si el hosting deja contestar antes de terminar** (`corte` en el estado). Si dice "ninguno", un visitante por hora espera hasta que termine la consulta; si pasa de 3 segundos, ve las fotos fijas esa vez.
- **Si el hosting tiene la librería de imágenes con WebP** (`fotos` en el estado). Si no, las fotos se guardan como vienen: pesan más, se ven igual.
- **Qué devuelve Instagram como foto de un álbum.** La documentación no lo dice. Si no es una foto, se busca la primera de adentro.
- **Si Meta pide revalidar la app cada tanto.** La documentación leída no lo dice.

La investigación de la API, con las fuentes: `06-knowledge/layer-2-landscape/instagram-api-para-mostrar-posts.md`.
La decisión: `01-strategy/decision-log/2026-11-23-instagram-en-la-landing.md`.
