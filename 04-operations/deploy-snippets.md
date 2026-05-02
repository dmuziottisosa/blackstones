# Deploy snippets — PowerShell · Base64-inline · infalible

> Estado: **layer-3 active belief.** Adoptado el 2026-05-02 después de descartar GitHub raw fetch (PAT fricción) y clone local (acoplamiento al filesystem del usuario).
>
> **Patrón canónico:**
> 1. La IA modifica el archivo en el repo + commit a GitHub (source of truth).
> 2. La IA emite, en la misma respuesta, un bloque PowerShell autocontenido con el archivo entero codificado en **Base64**.
> 3. Vos copy-pasteás el bloque. PowerShell decodifica → escribe temp → curl FTP upload → borra temp → abre browser.
>
> **Por qué es infalible:**
> - Sin auth externa (no GitHub token, no `gh` CLI, no clone).
> - Base64 es ASCII puro — sin quoting, sin escape, sin encoding traps.
> - `FromBase64String` valida byte-por-byte. Si algo falla, falla con error visible.
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

## Gotchas conocidas

| Error | Causa | Fix |
|---|---|---|
| `Invalid length for a Base-64 char array` | Base64 corrupto al copiar (truncado, espacios raros, BOM) | Volver a copiar el bloque entero |
| `Invoke-WebRequest: ... 'u144473384'` | En PowerShell, `curl` es alias de `Invoke-WebRequest` | Usar `curl.exe` con extensión |
| `Could not resolve host: ftp.blackstones.com.ar` | Subdominio `ftp.` no existe | Usar `blackstones.com.ar` directo |
| `curl: (67) Access denied: 530` | Password mal | `--user "user:pass"` desde clipboard |
| `curl: (9) Server denied you to change to the given directory` | Path sin `domains/blackstones.com.ar/public_html/` | Usar `FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html` |
| Upload "OK" pero browser 404 | Archivo en home, no en web root | Ídem arriba |
| `.env` con `[blackstones.com.ar](http://...)` | Markdown autolink en chat | Notepad + tipeo manual |

---

## Checklist mental antes de cualquier deploy

1. ¿La IA me commiteó el cambio en GitHub? (Mirar el último commit en la branch.)
2. ¿El `$serverPath` en el bloque corresponde al archivo que edité?
3. ¿El `.env` tiene los 4 valores y el `FTP_HOST` está limpio?
4. ¿Es viernes 18:00? Si sí, **no.**
