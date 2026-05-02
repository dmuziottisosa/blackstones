# Deploy snippets — PowerShell copy-paste

> Estado: **layer-3 active belief.** Validado el 2026-05-02 con upload + verificación HTTP exitosa.
>
> Bloques listos para copiar y pegar en PowerShell. Para entender el mapa del FTP que justifica las rutas, ver [`ftp-map.md`](./ftp-map.md).

---

## 0. Setup de sesión (una vez por terminal)

Copy-paste esto al abrir una terminal nueva. Carga las credenciales como variables de entorno **solo en esa sesión** (no quedan persistidas).

```powershell
# Cargar credenciales desde .env (recomendado — el .env está gitignorado)
Get-Content .env | ForEach-Object {
    if ($_ -match '^\s*([^#=]+)=(.*)$') {
        Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim()
    }
}

# Verificar
Write-Host "FTP_USER = $env:FTP_USER"
Write-Host "FTP_HOST = $env:FTP_HOST"
```

**Si no tenés `.env` todavía:** crealo en la raíz del repo con este contenido (gitignorado por default):

```
FTP_HOST=blackstones.com.ar
FTP_USER=u144473384
FTP_PASS=tu_password_aca
FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html
FTP_LOCAL_BASE=site/public_html
```

---

## 1. Subir UN archivo

### 1.a — Landing (`index.html`)

```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T "site\public_html\index.html" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/index.html"
```

### 1.b — Calculadora (`calc.html`)

```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T "site\public_html\calculadora\calc.html" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/calc.html"
```

### 1.c — Cualquier archivo (genérico)

Reemplazá `RUTA_RELATIVA` por la ruta dentro de `site/public_html/`. Ejemplo: `calculadora/dolar.php`, `Granitos/blanco-dallas.webp`.

```powershell
$rel = "RUTA_RELATIVA"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T "site\public_html\$rel" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$rel"
```

---

## 2. Listar (`ls`) qué hay en el remoto

### 2.a — Listar el web root

```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/"
```

### 2.b — Listar la calc

```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/"
```

### 2.c — Listar una carpeta arbitraria

```powershell
$carpeta = "Granitos"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$carpeta/"
```

---

## 3. Borrar un archivo del servidor

```powershell
$rel = "RUTA_RELATIVA"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -Q "DELE ${env:FTP_REMOTE_BASE}/$rel" "ftp://${env:FTP_HOST}/"
```

⚠️ **Antes de borrar:** confirmá que no está referenciado en un ad activo, una imagen del Instagram embed, o una landing pega-link.

---

## 4. Bajar (backup) un archivo del servidor

Útil cuando alguien editó algo en el File Manager de Hostinger sin pasar por git y querés sincronizar al repo antes de tocarlo.

```powershell
$rel = "RUTA_RELATIVA"
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -o "_backup_$([System.IO.Path]::GetFileName($rel))" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$rel"
```

---

## 5. Subir una CARPETA entera (con todas las subcarpetas)

`curl` no soporta recursivo bien. Para esto se usa WinSCP. **Setup una vez:** instalar https://winscp.net/

### 5.a — Sync incremental (preview)

Compara timestamps y muestra qué subiría, **sin subir nada**.

```powershell
Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"
$opts = New-Object WinSCP.SessionOptions -Property @{
    Protocol = [WinSCP.Protocol]::Ftp
    HostName = $env:FTP_HOST
    UserName = $env:FTP_USER
    Password = $env:FTP_PASS
}
$session = New-Object WinSCP.Session
try {
    $session.Open($opts)
    $result = $session.SynchronizeDirectories(
        [WinSCP.SynchronizationMode]::Remote,
        (Resolve-Path $env:FTP_LOCAL_BASE).Path,
        "/$($env:FTP_REMOTE_BASE)",
        $false,                                      # no borrar huérfanos
        $true,                                       # PREVIEW (no sube)
        [WinSCP.SynchronizationCriteria]::Time
    )
    $result.Check()
    Write-Host "Subiría: $($result.Uploads.Count) archivos"
    $result.Uploads | ForEach-Object { Write-Host "  + $($_.FileName)" }
} finally { $session.Dispose() }
```

### 5.b — Sync incremental (real)

Igual que la anterior pero el flag de preview en `$false`.

```powershell
Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"
$opts = New-Object WinSCP.SessionOptions -Property @{
    Protocol = [WinSCP.Protocol]::Ftp
    HostName = $env:FTP_HOST
    UserName = $env:FTP_USER
    Password = $env:FTP_PASS
}
$session = New-Object WinSCP.Session
try {
    $session.Open($opts)
    $result = $session.SynchronizeDirectories(
        [WinSCP.SynchronizationMode]::Remote,
        (Resolve-Path $env:FTP_LOCAL_BASE).Path,
        "/$($env:FTP_REMOTE_BASE)",
        $false,                                      # no borrar huérfanos
        $false,                                      # REAL (sube)
        [WinSCP.SynchronizationCriteria]::Time
    )
    $result.Check()
    Write-Host "Subidos: $($result.Uploads.Count)"
} finally { $session.Dispose() }
```

### 5.c — Sync solo de la calc

```powershell
Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"
$opts = New-Object WinSCP.SessionOptions -Property @{
    Protocol = [WinSCP.Protocol]::Ftp
    HostName = $env:FTP_HOST
    UserName = $env:FTP_USER
    Password = $env:FTP_PASS
}
$session = New-Object WinSCP.Session
try {
    $session.Open($opts)
    $result = $session.SynchronizeDirectories(
        [WinSCP.SynchronizationMode]::Remote,
        (Resolve-Path "$env:FTP_LOCAL_BASE\calculadora").Path,
        "/$($env:FTP_REMOTE_BASE)/calculadora",
        $false, $false,
        [WinSCP.SynchronizationCriteria]::Time
    )
    $result.Check()
    Write-Host "Subidos: $($result.Uploads.Count)"
} finally { $session.Dispose() }
```

---

## 6. Recetas combinadas

### 6.a — "Cambié solo `index.html`, deploy ya"

```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T "site\public_html\index.html" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/index.html"
Start-Process "https://blackstones.com.ar/?nocache=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

### 6.b — "Cambié la calc, deploy + abrir"

```powershell
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T "site\public_html\calculadora\calc.html" "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/calc.html"
Start-Process "https://blackstones.com.ar/calculadora/?nocache=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

### 6.c — "Sumé fotos nuevas a `Granitos/`, sync esa carpeta"

```powershell
Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"
$opts = New-Object WinSCP.SessionOptions -Property @{
    Protocol = [WinSCP.Protocol]::Ftp; HostName = $env:FTP_HOST
    UserName = $env:FTP_USER; Password = $env:FTP_PASS
}
$session = New-Object WinSCP.Session
try {
    $session.Open($opts)
    $r = $session.SynchronizeDirectories(
        [WinSCP.SynchronizationMode]::Remote,
        (Resolve-Path "$env:FTP_LOCAL_BASE\Granitos").Path,
        "/$($env:FTP_REMOTE_BASE)/Granitos",
        $false, $false, [WinSCP.SynchronizationCriteria]::Time)
    $r.Check(); Write-Host "Subidas: $($r.Uploads.Count)"
} finally { $session.Dispose() }
```

---

## 7. Checklist mental antes de cualquier deploy

1. ¿Los cambios están commiteados en git? (rollback rápido si algo se rompe)
2. ¿Estoy subiendo a `domains/blackstones.com.ar/public_html/`? (no al home)
3. Si toqué la calc: ¿probé el login en local antes?
4. ¿Es viernes a las 18:00? Si sí, **no.**

---

## 8. Test "¿está vivo?" rápido

Tres URLs que tienen que devolver 200. Si alguna devuelve 404 o 500, hay drift entre repo y producción.

```powershell
"https://blackstones.com.ar/",
"https://blackstones.com.ar/calculadora/",
"https://blackstones.com.ar/robots.txt" | ForEach-Object {
    try {
        $r = Invoke-WebRequest -Uri $_ -Method Head -UseBasicParsing -TimeoutSec 10
        Write-Host "$($r.StatusCode)  $_" -ForegroundColor Green
    } catch {
        Write-Host "ERROR  $_  $($_.Exception.Message)" -ForegroundColor Red
    }
}
```
