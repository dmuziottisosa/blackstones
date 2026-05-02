# Deploy snippets — PowerShell, 2 acciones

> Estado: **layer-3 active belief.** Validado el 2026-05-02 con upload + verificación HTTP exitosa.
>
> **Flujo total: copiar el bloque que corresponda, pegarlo en PowerShell parado en la raíz del repo, listo.** Cada bloque es autocontenido (carga `.env`, sube, abre el browser para verificar).
>
> Si no entendés por qué los paths tienen `domains/blackstones.com.ar/public_html/`, leé primero [`ftp-map.md`](./ftp-map.md). Si todavía no creaste `.env`, mirá [`§ Setup .env (1 sola vez)`](#setup-env-1-sola-vez) abajo.

---

## ⚡ Receta 1 — Cambié `index.html`, deploy ya

Editaste `site/public_html/index.html` y querés verlo arriba.

```powershell
Get-Content .env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T "site\public_html\index.html" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/index.html"
Start-Process "https://blackstones.com.ar/?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

---

## ⚡ Receta 2 — Cambié `calc.html`, deploy ya

```powershell
Get-Content .env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T "site\public_html\calculadora\calc.html" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/calc.html"
Start-Process "https://blackstones.com.ar/calculadora/?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

---

## ⚡ Receta 3 — Cambié otro archivo cualquiera, deploy ya

Cambiá la línea `$rel = "..."` por la ruta dentro de `site/public_html/`. Ejemplos: `calculadora/dolar.php`, `Granitos/blanco-dallas.webp`, `robots.txt`.

```powershell
Get-Content .env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$rel = "calculadora/dolar.php"   # ← cambiá esto
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T "site\public_html\$($rel.Replace('/','\'))" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$rel"
Write-Host "OK → https://blackstones.com.ar/$rel" -ForegroundColor Green
```

---

## ⚡ Receta 4 — Cambié varios archivos / una carpeta entera, sync incremental

Sube **solo** los archivos cuya fecha local es más nueva que la remota. Requiere [WinSCP](https://winscp.net/) instalado una sola vez. **No borra archivos remotos huérfanos.**

```powershell
Get-Content .env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"
$opts = New-Object WinSCP.SessionOptions -Property @{ Protocol = [WinSCP.Protocol]::Ftp; HostName = $env:FTP_HOST; UserName = $env:FTP_USER; Password = $env:FTP_PASS }
$session = New-Object WinSCP.Session
try {
    $session.Open($opts)
    $r = $session.SynchronizeDirectories([WinSCP.SynchronizationMode]::Remote, (Resolve-Path $env:FTP_LOCAL_BASE).Path, "/$($env:FTP_REMOTE_BASE)", $false, $false, [WinSCP.SynchronizationCriteria]::Time)
    $r.Check()
    Write-Host ("Subidos: {0}" -f $r.Uploads.Count) -ForegroundColor Green
    $r.Uploads | ForEach-Object { Write-Host "  + $($_.FileName)" }
} finally { $session.Dispose() }
```

---

## ⚡ Receta 5 — Preview: ¿qué subiría sin subir?

Igual a la Receta 4 pero **dry-run**. Te dice qué subiría, no sube nada. Ideal antes de un sync grande.

```powershell
Get-Content .env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"
$opts = New-Object WinSCP.SessionOptions -Property @{ Protocol = [WinSCP.Protocol]::Ftp; HostName = $env:FTP_HOST; UserName = $env:FTP_USER; Password = $env:FTP_PASS }
$session = New-Object WinSCP.Session
try {
    $session.Open($opts)
    $r = $session.SynchronizeDirectories([WinSCP.SynchronizationMode]::Remote, (Resolve-Path $env:FTP_LOCAL_BASE).Path, "/$($env:FTP_REMOTE_BASE)", $false, $true, [WinSCP.SynchronizationCriteria]::Time)
    $r.Check()
    Write-Host ("Subiría: {0} archivos" -f $r.Uploads.Count) -ForegroundColor Yellow
    $r.Uploads | ForEach-Object { Write-Host "  + $($_.FileName)" }
} finally { $session.Dispose() }
```

---

## ⚡ Receta 6 — "¿Está vivo el sitio?"

Pinguea las 3 URLs críticas. Si alguna devuelve 404 / 500, hay drift entre repo y producción.

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

---

# Referencia

Lo de arriba es el 95% del uso real. Lo de abajo es **referencia**: comandos sueltos para casos puntuales (listar remoto, borrar, backup), explicación del setup, y todas las gotchas conocidas.

---

## Setup `.env` (1 sola vez)

Crear archivo `.env` en la raíz del repo (ya está en `.gitignore`, no se commitea) con este contenido:

```
FTP_HOST=blackstones.com.ar
FTP_USER=u144473384
FTP_PASS=tu_password_aca
FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html
FTP_LOCAL_BASE=site/public_html
```

A partir de ahí, todas las recetas funcionan sin tocar nada más.

⚠️ **La password actual fue expuesta en chat durante el bootstrap.** Rotala desde el panel Hostinger → "Cuentas FTP" → editar `u144473384` antes de usarla en producción.

---

## Comandos sueltos (referencia)

### Listar el web root

```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/"
```

### Listar la calc

```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/"
```

### Listar una carpeta arbitraria

```powershell
$carpeta = "Granitos"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$carpeta/"
```

### Borrar un archivo del servidor

```powershell
$rel = "calculadora/archivo_que_no_va.html"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -Q "DELE ${env:FTP_REMOTE_BASE}/$rel" "ftp://${env:FTP_HOST}/"
```

⚠️ Antes de borrar: confirmá que el archivo no está referenciado en un ad activo, embed de Instagram, o landing-pega-link.

### Bajar (backup) un archivo del servidor

Útil cuando alguien editó algo en el File Manager de Hostinger sin pasar por git y querés sincronizarlo de vuelta al repo antes de tocarlo.

```powershell
$rel = "calculadora/calc.html"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -o "_backup_$([System.IO.Path]::GetFileName($rel))" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$rel"
```

---

## Gotchas conocidas (errores ya cometidos, fix listo)

| Error | Causa | Fix |
|---|---|---|
| `Invoke-WebRequest: No se encuentra ningún parámetro de posición que acepte el argumento 'u144473384'` | En PowerShell, `curl` es alias de `Invoke-WebRequest` | Usar `curl.exe` con extensión |
| `Could not resolve host: ftp.blackstones.com.ar` | El subdominio `ftp.` no existe en el DNS | Usar `blackstones.com.ar` directo |
| `curl: (67) Access denied: 530` | Password mal tipeada | Pasar `--user "user:pass"` desde el clipboard, no tipear |
| `curl: (9) Server denied you to change to the given directory` | Path comenzaba con `/public_html/` que no existe en el home del FTP user | Usar `domains/blackstones.com.ar/public_html/` |
| Upload "exitoso" pero browser devuelve 404 | El archivo se subió a `/home/u144473384/` (home del FTP user), no al web root | Ídem arriba — siempre el path completo |

---

## Checklist mental antes de cualquier deploy

1. ¿Los cambios están commiteados en git? (rollback rápido si algo se rompe)
2. ¿El path remoto empieza con `domains/blackstones.com.ar/public_html/`?
3. Si toqué la calc: ¿probé el login en local antes?
4. ¿Es viernes 18:00? Si sí, **no.**
