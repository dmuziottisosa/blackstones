# Instagram en la landing

> La sección "Lo último que salió del taller" de la landing muestra sola los seis últimos posts de @blackstones.ar. Antes eran seis fotos fijas que llevaban al perfil.
> Hecho el 24/11/2026, a pedido del dueño ("que esta sección sea más dinámica, con lo que en verdad tenemos en IG").
> `status:` armado y probado contra un Instagram de mentira. **Contra el Instagram de verdad se prueba el día que se carga la llave**: hasta ese día, lo de la API es lo que dice la documentación de Meta, no algo visto andar.

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

Según la documentación oficial de Meta, leída el 23 y 24/11/2026. Los nombres de los botones pueden aparecer traducidos.

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

Las pruebas no están en el repo (ninguna del sitio lo está todavía).

## Lo que no se sabe todavía

- **Si la dirección de la lista es la que dice la documentación.** Meta documenta `/<id de la cuenta>/media`. El archivo prueba esa, después la del otro id que devuelve `/me` y después `/me/media`, y se queda con la que anda. El estado dice cuál fue.
- **Si el hosting deja contestar antes de terminar** (`corte` en el estado). Si dice "ninguno", un visitante por hora espera hasta que termine la consulta; si pasa de 3 segundos, ve las fotos fijas esa vez.
- **Si el hosting tiene la librería de imágenes con WebP** (`fotos` en el estado). Si no, las fotos se guardan como vienen: pesan más, se ven igual.
- **Qué devuelve Instagram como foto de un álbum.** La documentación no lo dice. Si no es una foto, se busca la primera de adentro.
- **Si Meta pide revalidar la app cada tanto.** La documentación leída no lo dice.

La investigación de la API, con las fuentes: `06-knowledge/layer-2-landscape/instagram-api-para-mostrar-posts.md`.
La decisión: `01-strategy/decision-log/2026-11-23-instagram-en-la-landing.md`.
