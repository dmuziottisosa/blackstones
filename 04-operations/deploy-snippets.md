# Deploy snippets — PowerShell · GitHub → FTP · sin clone

> Estado: **layer-3 active belief.** Última actualización: 2026-05-02.
>
> **Arquitectura:**
> - **GitHub** = única fuente de verdad del repo. Todo cambio viaja por ahí.
> - **Local del usuario** = solo `D:\blackstones\.env` (credenciales). **No hay repo clonado, no hay `git pull`.**
> - **Deploy** = PowerShell baja el archivo de **GitHub raw** a un `$temp`, lo sube por **FTP** a Hostinger, borra el temp.
>
> Cada bloque de abajo es autocontenido: 2 acciones (copiar + pegar en PowerShell). Lee `.env`, fetcha de GitHub, sube por FTP, borra temp, abre browser.

---

## 🔁 Default no negociable

**Si modificás cualquier cosa dentro de `site/public_html/` (landing, calc, imagen, PHP, CSS, JS), tu respuesta TIENE que terminar con el bloque PowerShell de deploy correspondiente.** No esperar que el usuario lo pida.

- 1 archivo cambiado → adaptar Receta 1 / 2 / 3 con el path correcto.
- Múltiples archivos o carpeta entera → Receta 4.
- Siempre incluir `Start-Process` final con cache-bust para verificación en browser.

Esta regla NO aplica a cambios fuera de `site/` (docs, CLAUDE.md, READMEs, etc.) — esos solo se commitean a GitHub.

---

## 🔑 Setup `.env` (1 sola vez en la vida del usuario)

Ubicación recomendada: `D:\blackstones\.env`. Crearlo con Notepad (no copy-paste de chat — los renderers markdown autolinkean dominios y rompen el archivo).

```
FTP_HOST=blackstones.com.ar
FTP_USER=u144473384
FTP_PASS=xxx
FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html
GITHUB_REPO=dmuziottisosa/blackstones
GITHUB_BRANCH=claude/setup-new-repo-tR58e
GITHUB_TOKEN=ghp_xxx
```

**Cómo obtener `GITHUB_TOKEN`:**

1. GitHub → Settings → Developer settings → Personal access tokens → **Fine-grained tokens** → Generate new token.
2. Repository access: solo `dmuziottisosa/blackstones`.
3. Permissions: **Contents: Read-only**. Nada más.
4. Expiration: 90 días (rotás cada 90).
5. Copiar el token (`ghp_...`) y pegarlo en `.env` con Notepad.

⚠️ **El token reemplaza la necesidad de tener el repo local.** Con el token, PowerShell baja archivos individuales de GitHub directamente.

---

## ⚡ Receta 1 — Cambié `index.html` (la landing), deploy

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".html")
Invoke-WebRequest -Uri "https://raw.githubusercontent.com/$($env:GITHUB_REPO)/$($env:GITHUB_BRANCH)/site/public_html/index.html" -Headers @{ Authorization = "token $($env:GITHUB_TOKEN)" } -OutFile $tmp
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/index.html"
Remove-Item $tmp
Start-Process "https://blackstones.com.ar/?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

---

## ⚡ Receta 2 — Cambié `calc.html`, deploy

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$tmp = Join-Path $env:TEMP ("bs_calc_" + [guid]::NewGuid().ToString().Substring(0,8) + ".html")
Invoke-WebRequest -Uri "https://raw.githubusercontent.com/$($env:GITHUB_REPO)/$($env:GITHUB_BRANCH)/site/public_html/calculadora/calc.html" -Headers @{ Authorization = "token $($env:GITHUB_TOKEN)" } -OutFile $tmp
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/calc.html"
Remove-Item $tmp
Start-Process "https://blackstones.com.ar/calculadora/?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

---

## ⚡ Receta 3 — Cambié otro archivo (genérico)

Editá las dos primeras líneas con la ruta del archivo en el repo y la ruta destino en el server. Todo lo demás corre igual.

```powershell
$repoPath   = "site/public_html/calculadora/dolar.php"   # ← path del archivo en el repo
$serverPath = "calculadora/dolar.php"                    # ← path relativo al web root del server
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + [System.IO.Path]::GetExtension($repoPath))
Invoke-WebRequest -Uri "https://raw.githubusercontent.com/$($env:GITHUB_REPO)/$($env:GITHUB_BRANCH)/$repoPath" -Headers @{ Authorization = "token $($env:GITHUB_TOKEN)" } -OutFile $tmp
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
Remove-Item $tmp
Write-Host "OK → https://blackstones.com.ar/$serverPath" -ForegroundColor Green
```

---

## ⚡ Receta 4 — Subí varios archivos / carpeta entera

Lista los archivos de una carpeta del repo vía GitHub API, baja cada uno a un staging temporal, los sube al server, borra el staging.

Editá la primera línea con la carpeta a deployar.

```powershell
$repoFolder   = "site/public_html/calculadora"           # ← carpeta del repo
$serverFolder = "calculadora"                            # ← carpeta destino en el server
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$staging = Join-Path $env:TEMP ("bs_stage_" + [guid]::NewGuid().ToString().Substring(0,8))
New-Item -ItemType Directory -Path $staging | Out-Null
$headers = @{ Authorization = "token $($env:GITHUB_TOKEN)"; 'User-Agent' = 'bs-deploy' }
$apiUrl  = "https://api.github.com/repos/$($env:GITHUB_REPO)/contents/$repoFolder`?ref=$($env:GITHUB_BRANCH)"
$items   = Invoke-RestMethod -Uri $apiUrl -Headers $headers
foreach ($item in $items) {
    if ($item.type -ne 'file') { continue }
    if ($item.name -like '.*') { continue }   # skip dotfiles (.htaccess se sube aparte si querés)
    $localPath = Join-Path $staging $item.name
    Invoke-WebRequest -Uri $item.download_url -Headers $headers -OutFile $localPath
    curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $localPath "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverFolder/$($item.name)"
    Write-Host "  ✓ $($item.name)" -ForegroundColor Green
}
Remove-Item -Recurse -Force $staging
Write-Host "Deploy de $repoFolder completo." -ForegroundColor Green
```

⚠️ Esta receta **no recursea sub-carpetas**. Si tenés sub-folders, repetí el bloque con el `$repoFolder` apuntando a cada uno.

---

## ⚡ Receta 5 — Verificar que el sitio está vivo

Pinguea las 3 URLs críticas. Si alguna devuelve 404 / 500, hay drift.

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

## ⚡ Receta 6 — Listar el FTP remoto (para diagnosticar)

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/"
```

Para listar la calc:
```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/"
```

---

## ⚡ Receta 7 — Borrar un archivo del server

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$serverPath = "_test_archivo_que_no_va.txt"   # ← path relativo al web root
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -Q "DELE ${env:FTP_REMOTE_BASE}/$serverPath" "ftp://${env:FTP_HOST}/"
```

⚠️ Antes de borrar: confirmá que el archivo no está referenciado en un ad activo, embed de Instagram, o landing pega-link.

---

## ⚡ Receta 8 — Bajar (backup) un archivo del server

Útil cuando alguien editó algo en el File Manager de Hostinger sin pasar por GitHub y querés ver qué hay en producción antes de pisarlo.

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$serverPath = "calculadora/calc.html"        # ← path relativo al web root
$dest = "$env:USERPROFILE\Desktop\bs_backup_$($serverPath -replace '/', '_')"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -o $dest "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
Write-Host "Bajado a $dest" -ForegroundColor Green
```

---

## Cleanup: si tenés el repo clonado en local, borralo

Decisión arquitectónica: el repo NO vive local. Solo `.env`. Si tenés `D:\blackstones\git\` u otro clone, eliminalo:

```powershell
Remove-Item -Recurse -Force D:\blackstones\git
```

---

## Gotchas conocidas (errores ya cometidos)

| Error | Causa | Fix |
|---|---|---|
| `Invoke-WebRequest: No se encuentra ningún parámetro de posición que acepte el argumento 'u144473384'` | En PowerShell, `curl` es alias de `Invoke-WebRequest` | Usar `curl.exe` con extensión |
| `Could not resolve host: ftp.blackstones.com.ar` | El subdominio `ftp.` no existe en el DNS | Usar `blackstones.com.ar` directo |
| `curl: (67) Access denied: 530` | Password mal tipeada | Pasar `--user "user:pass"` desde clipboard |
| `curl: (9) Server denied you to change to the given directory` | Path empezó con `/public_html/` que no existe en el home del FTP user | Usar `domains/blackstones.com.ar/public_html/` |
| Upload "exitoso" pero browser devuelve 404 | Se subió a `/home/u144473384/`, no al web root | Ídem arriba |
| `.env` quedó con `[blackstones.com.ar](http://blackstones.com.ar)` | Chat client autolinkea dominios al copy-paste | Crear `.env` con Notepad, tipearlo a mano |
| `Invoke-WebRequest: 401 Unauthorized` al fetchar GitHub raw | El repo es privado, sin token válido | Generar PAT con permission "Contents: Read" y meterlo en `.env` |

---

## Checklist mental antes de cualquier deploy

1. ¿El cambio está pusheado a la branch en GitHub? (Si no está allá, no se puede deployar.)
2. ¿`GITHUB_BRANCH` en `.env` apunta a la branch correcta?
3. ¿Es viernes 18:00? Si sí, **no.**
