# Deploy snippets — PowerShell · Base64-inline · raw fetch · infalible

> Estado: **layer-3 active belief.** Adoptado el 2026-05-02 (Base64), evolucionó el 2026-06-25 (GitHub raw fetch para repos públicos).
>
> **Dos patrones canónicos según contexto:**
> 
> **Patrón A: GitHub raw fetch (PÚBLICO, ≤3 años, mantenido)** — Ver § Receta 7. 12 líneas, cero Base64, cero auth. Commit SHA = cache-bust automático.
> 1. La IA modifica el archivo + commit a GitHub.
> 2. La IA emite un bloque PowerShell que descarga desde `raw.githubusercontent.com/{repo}/{sha}/{path}`.
> 3. Vos pegás el bloque. PowerShell descarga → escribe temp → curl FTP upload → borra temp → abre browser.
>
> **Patrón B: Base64 inline (CUALQUIER estado de repo)** — Ver § Receta 1. 6 líneas + Base64 payload. 
> 1. La IA modifica el archivo + commit a GitHub (source of truth).
> 2. La IA emite un bloque PowerShell autocontenido con el archivo entero codificado en **Base64**.
> 3. Vos pegás el bloque. PowerShell decodifica → escribe temp → curl FTP upload → borra temp → abre browser.
>
> **Cuándo usar Receta 7 (raw) vs Receta 1 (Base64):**
> - GitHub raw fetch: **repo es público, archivo existe en commit reciente, queremos el bloque más corto.**
> - Base64: **repo es privado, archivo es nuevo sin commit, o preferís máxima robustez vs. brevedad.**
>
> **Por qué estos patrones funcionan:**
> - Sin auth externa (no GitHub token, no `gh` CLI, no clone).
> - Base64 (Receta 1): ASCII puro — sin quoting, sin escape, sin encoding traps. `FromBase64String` valida byte-por-byte.
> - Raw fetch (Receta 7): HTTP directo a CDN, cache-bust con SHA. Una línea de `curl`, una línea de `FTP upload`.
> - Reproducible: bytes en GitHub = bytes en producción, garantizado.
> - El usuario ve cada paso en consola.

---

## 🔁 Default no negociable

**Si modificás cualquier cosa dentro de `site/public_html/` (landing, calc, imagen, PHP, CSS, JS), tu respuesta TIENE que terminar con un bloque de deploy Base64 listo para pegar.** No esperar que el usuario lo pida.

- 1 archivo cambiado → emitir el bloque de la § Receta 1 con el archivo Base64'd inline.
- Múltiples archivos → emitir varios bloques, uno por archivo.
- **Para cambios chicos en archivos grandes** (ej: cambiar un teléfono en `calc.html` de 273 KB) → si el cambio es ≤3 reemplazos puntuales, podés usar la § Receta 2 (patch quirúrgico via `-replace`) en vez de re-enviar el archivo entero.

Esta regla NO aplica a cambios fuera de `site/` — esos solo se commitean a GitHub.

---

## 🔑 `.env` minimalista (1 sola vez)

Ubicación: `D:\blackstones\.env`. Crearlo con Notepad, tipearlo a mano (no copy-paste de chat — el markdown autolinkea dominios y rompe el archivo).

```
FTP_HOST=blackstones.com.ar
FTP_USER=u144473384
FTP_PASS=xxx
FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html
```

Cuatro líneas. Nada más. **Si tenés `GITHUB_TOKEN`, `GITHUB_REPO`, `GITHUB_BRANCH` ahí adentro de versiones anteriores de este sistema → eliminalas. Ya no se usan.** Y revocá el PAT en GitHub Settings.

---

## ⚡ Receta 1 — Deploy de un archivo (Base64-inline)

**Forma del bloque que la IA emite:**

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$serverPath = "RUTA/RELATIVA/AL/WEB_ROOT.html"   # ← lo escribe la IA
$tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + [System.IO.Path]::GetExtension($serverPath))
$b64 = @'
BASE64_DEL_ARCHIVO_AQUI
'@
[System.IO.File]::WriteAllBytes($tmp, [System.Convert]::FromBase64String(($b64 -replace '\s','')))
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
Remove-Item $tmp
Start-Process "https://blackstones.com.ar/$serverPath`?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

**Notas técnicas:**

- `-replace '\s',''` quita whitespace antes de decodificar — te salva si tu chat client wrappea o agrega newlines en lugares raros.
- `[System.IO.File]::WriteAllBytes` escribe **bytes crudos**, sin BOM, sin encoding intermedio. Lo que está en el Base64 es lo que llega al server.
- `[System.IO.Path]::GetExtension($serverPath)` saca la extensión correcta para el temp (importante para que algunos clientes FTP infieran content-type bien).
- `Start-Process ... ?v=timestamp` rompe cache del browser para que veas el cambio inmediato.

**Si algo falla, vas a ver:**
- `Invalid length for a Base-64 char array` → el Base64 está corrupto o cortado al copiar.
- `curl: (...)` → problema de FTP (revisar `.env`, la red, el path remoto).
- 404 en el browser → el `$serverPath` no es lo que esperabas.

---

## ⚡ Receta 2 — Patch quirúrgico (cambios chicos en archivos grandes)

Para cambios ≤3 reemplazos puntuales en `index.html` o `calc.html` (cambiar un teléfono, una dirección, un precio). Más rápido que re-enviar 273 KB en Base64.

**Forma del bloque que la IA emite:**

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$serverPath = "calculadora/calc.html"
$tmp = Join-Path $env:TEMP ("bs_patch_" + [guid]::NewGuid().ToString().Substring(0,8) + ".tmp")

# 1. Bajar el archivo del server
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -s -o $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
$pre = (Get-Item $tmp).Length

# 2. Hacer el reemplazo (con verificación: el patrón TIENE que existir)
$content = [System.IO.File]::ReadAllText($tmp)
$old = "PATRON_VIEJO_EXACTO"
$new = "PATRON_NUEVO"
if (-not $content.Contains($old)) {
    Remove-Item $tmp
    throw "El patrón '$old' no está en el archivo. Aborto. Nada se subió."
}
$content = $content.Replace($old, $new)
[System.IO.File]::WriteAllText($tmp, $content)

# 3. Subir
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
$post = (Get-Item $tmp).Length
Remove-Item $tmp

Write-Host "OK. Bytes: $pre → $post (diff $($post - $pre))" -ForegroundColor Green
Start-Process "https://blackstones.com.ar/$serverPath`?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

**Por qué este patch es "infalible-ish":**

- Usa `.Contains()` antes de `.Replace()` — si el patrón no está, **aborta sin tocar nada**. Cero corrupción silenciosa.
- Usa `String.Replace` (no regex) — match literal, sin sorpresas.
- Imprime diff de bytes — vos chequeás que el cambio sea del tamaño esperado.

**Cuándo NO usar este patrón:**

- Si el reemplazo aparece más de 1 vez y solo querés cambiar uno → mejor Base64 inline (Receta 1) para evitar ambigüedad.
- Si el cambio es más complejo que 3 strings literales → Receta 1.

---

## ⚡ Receta 3 — Verificar que el sitio está vivo

```powershell
"https://blackstones.com.ar/", "https://blackstones.com.ar/calculadora/", "https://blackstones.com.ar/robots.txt" | ForEach-Object {
    try {
        $r = Invoke-WebRequest -Uri $_ -Method Head -UseBasicParsing -TimeoutSec 10
        Write-Host "$($r.StatusCode)  $_" -ForegroundColor Green
    } catch {
        Write-Host "ERROR  $_  $($_.Exception.Message)" -ForegroundColor Red
    }
}
```

---

## ⚡ Receta 4 — Listar el FTP remoto (diagnóstico)

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/"
```

Para listar la calc:
```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/"
```

---

## ⚡ Receta 5 — Borrar un archivo del server

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$serverPath = "_test_archivo_que_no_va.txt"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -Q "DELE ${env:FTP_REMOTE_BASE}/$serverPath" "ftp://${env:FTP_HOST}/"
```

---

## ⚡ Receta 6 — Bajar (backup) un archivo del server

Útil para auditar producción antes de pisar algo.

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$serverPath = "calculadora/calc.html"
$dest = "$env:USERPROFILE\Desktop\bs_backup_$($serverPath -replace '/', '_')"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -o $dest "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
Write-Host "Bajado a $dest" -ForegroundColor Green
```

---

## ⚡ Receta 7 — Deploy desde GitHub raw (repos públicos, brevedad máxima)

Para repos públicos con historial limpio: en vez de emitir Base64 de 273 KB, pegás un bloque de 12 líneas que descarga directo desde GitHub.

**Forma del bloque que la IA emite:**

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$serverPath = "calculadora/calc.html"
$githubRepo = "dmuziottisosa/blackstones"
$commitSHA = "33f48926df1a629b7747d0c3baf4edded15d1357"  # ← actualizar al último commit
$tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".html")

# Descargar desde GitHub raw (sin auth, público)
curl.exe -s -o $tmp "https://raw.githubusercontent.com/$githubRepo/$commitSHA/$serverPath"
if (-not (Test-Path $tmp)) { throw "GitHub download fallo" }

# Subir a FTP
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
Remove-Item $tmp
Start-Process "https://blackstones.com.ar/$serverPath`?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

**Por qué esta estrategia es limpia:**

- **Commit SHA en lugar de branch:** evita el problema de CDN cache (~5 min de staleness con branch names). SHA = inmutable.
- **Sin Base64:** 12 líneas legibles vs. 273 KB de ruido. Si lo necesitás copiar a otra machine, es trivial.
- **Sin auth:** `raw.githubusercontent.com` es accesible sin GitHub token. Validado en repos públicos.
- **Reproducible:** el SHA es el hash del commit exacto. Vos podés verificar en GitHub el archivo que vas a bajar.
- **Fallback:** si el download falla, `Test-Path` lo atrapa antes de subir.

**Cuándo NO usar Receta 7:**

- Repo es **privado** → GitHub raw exige auth (GITHUB_TOKEN + vos no tenés PAT guardado).
- El archivo **no existe en el commit** → 404 antes de que llegue a FTP.
- El repo es **fork o muy viejo** → raw.githubusercontent.com puede no tener el commit cacheado.
- Querés **máxima robustez sin pensar en detalles** → Receta 1 (Base64) funciona en cualquier contexto.

**Actualización del SHA cuando la IA hace commit:**

La IA va a decir: "Commit: `abc123def456...`". Vos tomás esos primeros 40 caracteres (el SHA completo), reemplazás en el script y corres. Ejemplo:

```
✔ Committed: 33f48926df1a629b7747d0c3baf4edded15d1357
                                                    ↑
Copias este valor y lo pegás en $commitSHA del script.
```

---

## Gotchas conocidas

| Error | Causa | Fix |
|---|---|---|
| `Invalid length for a Base-64 char array` | Base64 corrupto al copiar (truncado, espacios raros, BOM) | Volver a copiar el bloque entero |
| `Invoke-WebRequest: ... 'u144473384'` | En PowerShell, `curl` es alias de `Invoke-WebRequest` | Usar `curl.exe` con extensión |
| `Could not resolve host: ftp.blackstones.com.ar` | Subdominio `ftp.` no existe | Usar `blackstones.com.ar` directo |
| `curl: (67) Access denied: 530` | Password mal | `--user "user:pass"` desde clipboard |
| `curl: (9) Server denied you to change to the given directory` | Path sin `domains/blackstones.com.ar/public_html/` | Usar `FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html` |
| Upload "OK" pero browser 404 | Archivo en home, no en web root | Ídem arriba |
| `.env` con `[blackstones.com.ar](http://...)` | Markdown autolink en chat | Notepad + tipeo manual; o reconstruir con `[char]46` para los puntos del dominio |
| `[[System.IO](http://System.IO).File]::...` o `[r.total](http://r.total)>0` en el script pegado | El chat autolinkea cualquier cadena con forma `palabra.palabra` que parezca dominio o namespace, **incluso dentro de bloques de código** | Ver § "Reglas para que el chat no rompa el script" abajo |
| `curl: (67) Access denied: 500` después de un download silencioso | El download falló (`-s` lo ocultó) → `$tmp` no existe → upload sube archivo de 0 bytes | Sacar el `-s` del `curl` de download; agregar `if (-not (Test-Path $tmp))` guard |
| Variables del `.env` aparecen vacías en `Write-Host` pero curl funciona OK | Es display-only: el chat autolinkea valores que parecen dominio en el output del terminal cuando se copia de vuelta al chat. El valor real está OK | Ignorar el display, confiar en que curl funcione |

---

## Reglas para que el chat no rompa el script (anti-autolink)

El chat autolinkea cualquier patrón `palabra.palabra` o `palabra.palabra.palabra` que parezca dominio/TLD/namespace, convirtiéndolo en `[texto](http://texto)`. Esto pasa **dentro de bloques de código** y **al copiar el output del terminal de vuelta al chat**. Si tu script tiene esos patrones, el paste rompe la sintaxis de PowerShell.

**Patrones tóxicos a evitar en el script:**

| Tóxico (autolinkea) | Reemplazo seguro |
|---|---|
| `[System.IO.File]::...` | `[IO.File]::...` (PowerShell auto-resuelve `System.*`) |
| `[System.Text.Encoding]::...` | `[Text.Encoding]::...` |
| `[System.Text.UTF8Encoding]::new(...)` | `[Text.UTF8Encoding]::new(...)` |
| `[System.Convert]::...` | `[Convert]::...` |
| `[System.IO.Path]::...` | `[IO.Path]::...` |
| Strings con `r.total`, `data.json`, `config.ini`, etc. dentro de patrones literales | Truncar el marcador antes del patrón tóxico (usar parte ÚNICA pero sin la palabra-con-punto) |
| `blackstones.com.ar` en el `.env` o como literal | Construir por concatenación: `"blackstones" + [char]46 + "com" + [char]46 + "ar"` |
| `FTP_HOST=valor` con valor = dominio puro en .env | El valor literal en .env es OK (PowerShell lo lee correctamente). Solo evitar imprimirlo en chat con `Write-Host` |

**Patrones seguros que igual hay que cuidar:**

- `—` (em-dash, U+2014): la mayoría de chats lo preservan en UTF-8, pero si el clipboard mangle → usar `[char]8212`.
- `ó / á / í` (acentos): generalmente OK en UTF-8, pero para máxima seguridad usar `[char]243` (ó), `[char]225` (á), etc.
- `·` (middle dot, U+00B7): `[char]183`.
- `°` (degree sign, U+00B0): `[char]176`.
- Backticks `` ` ``: `[char]96` o escapar con `` `` ``.

**Antes de emitir un deploy block:**

1. Re-leer el script en busca de `[System.X.Y]` → reemplazar por `[X.Y]`.
2. Re-leer los marcadores de patches en busca de strings con punto-tipo-dominio → truncar o usar otro marcador único.
3. Si hay caracteres no-ASCII en marcadores críticos (los que `.Contains()` necesita matchear) → considerar `[char]X` para los más sensibles.

---

## Patrón validado: download + multi-patch + upload (Receta 2 evolucionada)

Estructura base que ya funcionó end-to-end (cambios en `calc.html` el 2026-05-02):

```powershell
# Cargar .env y validar
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
if (-not $env:FTP_HOST -or -not $env:FTP_USER) { throw ".env no cargo bien, aborto" }

$serverPath = "RUTA_AL_ARCHIVO"
$tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".html")

# Download SIN -s para ver errores reales
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -o $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
if (-not (Test-Path $tmp)) { throw "Download fallo, no se creo el temp" }
$pre = (Get-Item $tmp).Length
Write-Host "Bajados $pre bytes" -ForegroundColor Cyan

# Leer + normalizar line endings (CRLF -> LF para que los patches matcheen)
$content = [IO.File]::ReadAllText($tmp, [Text.Encoding]::UTF8)
$origCRLF = $content.Contains("`r`n")
$content = $content -replace "`r`n", "`n"

# Char codes para no-ASCII / autolink-trampa
$LF = [char]10
$BT = [char]96
$EM = [char]8212

# === Patches con .Contains() guard cada uno ===
$o1 = '...'
$n1 = '...'
if (-not $content.Contains($o1)) { Remove-Item $tmp; throw "P1 marcador no encontrado" }
$content = $content.Replace($o1, $n1)

# ... más patches ...

# Restaurar line endings originales (si era CRLF)
if ($origCRLF) { $content = $content -replace "`n", "`r`n" }

# Guardar UTF-8 sin BOM
[IO.File]::WriteAllText($tmp, $content, [Text.UTF8Encoding]::new($false))

# Upload
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
$post = (Get-Item $tmp).Length
Remove-Item $tmp

Write-Host "OK. Bytes: $pre -> $post (diff $($post - $pre))" -ForegroundColor Green
Start-Process "https://blackstones.com.ar/$serverPath`?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

**Por qué esta estructura es robusta:**

1. **`if (-not $env:FTP_HOST...)` early abort:** detecta `.env` corrupto antes de tocar nada.
2. **`-o $tmp` sin `-s`:** errores de download se ven inmediatos.
3. **`Test-Path $tmp` guard:** atrapa download silencioso fallido.
4. **Cyan banner del tamaño bajado:** confirma que el archivo llegó.
5. **Normalización de line endings:** los patches multi-line matchean independientemente de si el FTP devuelve LF o CRLF.
6. **Cada patch con `.Contains()` guard:** si un marcador no está, aborta antes de tocar el server. La live queda intacta.
7. **`[IO.File]` y `[Text.Encoding]` (sin `System.`):** evita el chat-autolink trap.
8. **UTF-8 sin BOM al escribir:** preserva encoding del archivo original sin agregar bytes.
9. **`$post - $pre` final:** muestra el diff de bytes para verificación visual rápida.
10. **`Start-Process` con cache-bust:** abre el browser con `?v=timestamp` para evitar caché.

---

## Checklist mental antes de cualquier deploy

1. ¿La IA me commiteó el cambio en GitHub? (Mirar el último commit en la branch.)
2. ¿El `$serverPath` en el bloque corresponde al archivo que edité?
3. ¿El `.env` tiene los 4 valores y el `FTP_HOST` está limpio?
4. ¿El bloque PowerShell pasó por la "anti-autolink check" (sin `[System.X]`, sin `r.total` en marcadores)?
5. ¿Es viernes 18:00? Si sí, **no.**
