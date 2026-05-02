# Deploy notes — sitio público a Hostinger

> Estado: **layer-3 active belief.** Última actualización: 2026-05-02.
>
> Cómo subir cambios al web root del hosting.
>
> **Arquitectura:** GitHub = fuente de verdad. **Sin clone local.** El deploy en PowerShell baja el archivo de GitHub raw a un `$temp`, lo sube por FTP, borra el temp. Lo único persistente local es `D:\blackstones\.env` con credenciales.
>
> **Acompañantes obligatorios:**
> - [`ftp-map.md`](./ftp-map.md) — cómo está montado el FTP (qué se ve al conectar, dónde está el web root real).
> - [`deploy-snippets.md`](./deploy-snippets.md) ⭐ — bloques de PowerShell copy-paste para uso diario (este es el doc que usás 99% de las veces).
>
> Este doc (`deploy-notes.md`) cubre los caminos **alternativos**: deploy manual desde panel Hostinger, cliente FTP gráfico (FileZilla / Cyberduck), checklists, rollback. Para el camino default (PowerShell + GitHub raw + FTP) → ir directo a `deploy-snippets.md`.

---

## 1. Datos del hosting

- **Hosting:** Hostinger
- **Dominio:** `blackstones.com.ar` (registrado en donweb)
- **FTP host:** `blackstones.com.ar` (sin prefijo `ftp.` — no resuelve. También accesible por IP `147.79.84.44`)
- **Usuario FTP:** `u144473384`
- **Puerto:** 21 (FTP plano)
- **Path remoto del web root:** `domains/blackstones.com.ar/public_html/` (⚠️ **el FTP user aterriza un nivel arriba, en `/home/u144473384/` — el path completo es obligatorio**, ver `ftp-map.md`)
- **Path local del repo:** `site/public_html/`
- **Password FTP:** ⚠️ **Ver `.env` (gitignorado).** Nunca commitear acá.

---

## 2. Setup inicial (una sola vez)

### Crear `.env` en `D:\blackstones\.env` (no dentro del repo — el repo no vive en local)

```bash
# D:\blackstones\.env  — único archivo persistente local del proyecto
FTP_HOST=blackstones.com.ar
FTP_USER=u144473384
FTP_PASS=tu_password_aca
FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html
GITHUB_REPO=dmuziottisosa/blackstones
GITHUB_BRANCH=claude/setup-new-repo-tR58e
GITHUB_TOKEN=ghp_xxx
```

**Cómo crear `GITHUB_TOKEN` (PAT fine-grained):**

1. GitHub → Settings → Developer settings → Personal access tokens → Fine-grained tokens → Generate.
2. Repo access: solo `dmuziottisosa/blackstones`.
3. Permissions: **Contents: Read-only**. Nada más.
4. Expiration: 90 días.
5. Copy el token (`ghp_...`) y pegarlo en `.env` con Notepad.

⚠️ **Crear `.env` con Notepad, no por copy-paste de chat.** Los renderers markdown autolinkean dominios y rompen el archivo (te queda `FTP_HOST=[blackstones.com.ar](http://blackstones.com.ar)` literal en el archivo).

⚠️ **La password actual del FTP** fue expuesta durante el bootstrap → rotarla desde el panel Hostinger antes de meterla acá.

---

## 3. Caminos alternativos de deploy

> **El camino default está en `deploy-snippets.md` (PowerShell + GitHub raw + FTP).** Lo que sigue son alternativas para cuando ese camino no aplica: deploy manual desde el panel, cliente FTP gráfico, etc.

### Camino A — Manual desde panel Hostinger

1. Entrar a `hpanel.hostinger.com` con cuenta del dueño.
2. Hosting → "Sitios web" → click en `blackstones.com.ar` → "Administrador de Archivos".
3. Navegar a `public_html/` (ya estás dentro del path correcto, el panel te lleva directo al web root).
4. Subir / sobrescribir el archivo o carpeta cambiada.
5. Verificar en `blackstones.com.ar`.

**Cuándo usarlo:** un solo archivo cambiado, sin script todavía configurado.
**Pros:** cero setup. **Contras:** lento, error humano alto, no hay log.

---

### Camino B — Cliente FTP gráfico (FileZilla / Cyberduck)

1. Configurar conexión:
   - Host: `blackstones.com.ar` (sin prefijo `ftp.`)
   - Usuario: `u144473384`
   - Password: la del `.env`
   - Puerto: 21 (FTP) o 22 (SFTP si Hostinger lo expone)
2. Conectar — aterrizás en `/home/u144473384/`.
3. Navegar al web root: `domains/blackstones.com.ar/public_html/`.
4. Local: `site/public_html/` ↔ Remoto: `domains/blackstones.com.ar/public_html/`.
5. Sync por carpeta o drag-and-drop por archivo.

**Cuándo usarlo:** deploy puntual de varios archivos, querés ver visual el árbol remoto.
**Pros:** UI clara, sync por carpeta. **Contras:** todavía manual.

---

### Camino C — PowerShell con clone local (DEPRECATED desde 2026-05-02)

> **No usar.** El proyecto adoptó "GitHub-as-source" sin clone. Se conserva esta sección como referencia histórica por si en el futuro se rearma un workflow con clone (ej: para CI/CD desde un runner que sí tiene checkout).

#### Opción C.1 — Con WinSCP (más sólida)

**Setup una sola vez:**
1. Bajar WinSCP: https://winscp.net/
2. Instalar (incluye el assembly `WinSCPnet.dll`).
3. Crear archivo `deploy.ps1` en raíz del repo (gitignorado si tiene secretos, o leyendo `.env`).

**`deploy.ps1`** (template):

```powershell
# deploy.ps1 — Sync site/public_html a Hostinger via WinSCP
# Uso: .\deploy.ps1                    → sync incremental (solo archivos nuevos/cambiados)
#      .\deploy.ps1 -DryRun             → muestra qué subiria sin subir
#      .\deploy.ps1 -File index.html    → sube un solo archivo

param(
    [switch]$DryRun,
    [string]$File
)

# Cargar .env
$envFile = ".env"
if (-not (Test-Path $envFile)) {
    Write-Error ".env no encontrado en la raíz del repo."
    exit 1
}
Get-Content $envFile | ForEach-Object {
    if ($_ -match '^\s*([^#=]+)=(.*)$') {
        Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim()
    }
}

# Cargar WinSCP assembly
Add-Type -Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"

# Configuración de sesión
$sessionOptions = New-Object WinSCP.SessionOptions -Property @{
    Protocol = [WinSCP.Protocol]::Ftp
    HostName = $env:FTP_HOST
    UserName = $env:FTP_USER
    Password = $env:FTP_PASS
}

$session = New-Object WinSCP.Session

try {
    $session.Open($sessionOptions)

    if ($File) {
        # Modo single-file
        $localPath = Join-Path $env:FTP_LOCAL_BASE $File
        $remotePath = "/$($env:FTP_REMOTE_BASE)/$File"
        Write-Host "Subiendo $localPath → $remotePath"
        if (-not $DryRun) {
            $session.PutFiles($localPath, $remotePath, $false).Check()
        }
    } else {
        # Modo sync incremental
        $transferOptions = New-Object WinSCP.TransferOptions
        $transferOptions.TransferMode = [WinSCP.TransferMode]::Binary

        Write-Host "Sync $($env:FTP_LOCAL_BASE) → /$($env:FTP_REMOTE_BASE) (DryRun: $DryRun)"

        $synchronizationResult = $session.SynchronizeDirectories(
            [WinSCP.SynchronizationMode]::Remote,
            (Resolve-Path $env:FTP_LOCAL_BASE).Path,
            "/$($env:FTP_REMOTE_BASE)",
            $false,                                      # No borrar archivos remotos huérfanos
            $DryRun,                                     # Preview mode
            [WinSCP.SynchronizationCriteria]::Time,      # Comparar por fecha de modificación
            $transferOptions
        )

        $synchronizationResult.Check()

        Write-Host ""
        Write-Host "===== Resultado ====="
        Write-Host "Subidos: $($synchronizationResult.Uploads.Count)"
        Write-Host "Bajados: $($synchronizationResult.Downloads.Count)"
        Write-Host "Removidos: $($synchronizationResult.Removals.Count)"
    }

    Write-Host ""
    Write-Host "Deploy completado." -ForegroundColor Green
}
finally {
    $session.Dispose()
}
```

**Uso:**
```powershell
# Sync completo (preview)
.\deploy.ps1 -DryRun

# Sync completo (real)
.\deploy.ps1

# Solo un archivo
.\deploy.ps1 -File index.html
.\deploy.ps1 -File "calculadora\calc.html"
```

**Notas de seguridad:**
- El script **NO borra archivos remotos huérfanos** por default (`$false` en argumento `removeFiles`). Cambialo a `$true` solo si querés mirror exacto y sabés lo que hacés.
- Si querés SFTP en vez de FTP plano: cambiar `Protocol = [WinSCP.Protocol]::Sftp` + agregar `SshHostKeyFingerprint` (Hostinger lo da en el panel).

#### Opción C.2 — Native PowerShell (sin instalar nada)

Para casos puntuales, PowerShell viene con `WebRequest` que soporta FTP nativo. Más verboso, no recomendado para sync grandes pero sirve de fallback:

```powershell
# Subir un solo archivo via FTP nativo
$ftpHost = "ftp://blackstones.com.ar/domains/blackstones.com.ar/public_html/index.html"
$ftpUser = "u144473384"
$ftpPass = "TU_PASSWORD"
$localFile = "site\public_html\index.html"

$ftp = [System.Net.FtpWebRequest]::Create($ftpHost)
$ftp.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$ftp.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
$ftp.UseBinary = $true
$ftp.UsePassive = $true

$content = [System.IO.File]::ReadAllBytes($localFile)
$rs = $ftp.GetRequestStream()
$rs.Write($content, 0, $content.Length)
$rs.Close()
```

#### Opción C.3 — `curl.exe` (Windows 10+, una línea)

```powershell
curl.exe --user "u144473384:TU_PASSWORD" -T "site\public_html\index.html" "ftp://blackstones.com.ar/domains/blackstones.com.ar/public_html/index.html"
```

⚠️ **`curl.exe` con extensión** (en PowerShell `curl` es alias de `Invoke-WebRequest` y falla).

Bueno para deploys puntuales de un solo archivo desde la terminal sin instalar nada. **Para más recetas one-liner ver [`deploy-snippets.md`](./deploy-snippets.md).**

---

## 4. Checklist pre-deploy (siempre)

Antes de subir a producción:

- [ ] Cambios commiteados en git (sirve como rollback).
- [ ] Si tocaste la calc: probaste login con el password actual.
- [ ] Si tocaste algo PHP: revisaste sintaxis (`php -l archivo.php`).
- [ ] Si tocaste imágenes: están en `.webp` (salvo causa justificada).
- [ ] Si cambiaste pricing en la calc: validaste que PDF y Excel reflejan el cambio.
- [ ] **No estás subiendo `.env` ni `auth_config.php` por accidente** (ambos están gitignorados pero verificá).

---

## 5. Checklist post-deploy

- [ ] Abrir `blackstones.com.ar` en navegador limpio (incognito) para verificar.
- [ ] Si tocaste la calc: hacer login + abrir calc + cerrar PDF + cerrar Excel para confirmar que nada se rompió.
- [ ] Si tocaste responsive: revisar mobile (DevTools o teléfono).
- [ ] Si cambiaste algo crítico: avisar al equipo por el canal interno.

---

## 6. Rollback rápido (sin clone)

Si algo se rompió en producción:

1. En GitHub: identificar el commit anterior funcional para el archivo afectado.
2. Cambiar temporalmente `GITHUB_BRANCH` en `.env` por el SHA del commit bueno (la API de raw acepta SHAs además de branches).
3. Correr la receta correspondiente de `deploy-snippets.md` para ese archivo. Eso baja la versión vieja y la sube por FTP.
4. Restaurar `GITHUB_BRANCH` al valor original.
5. En GitHub: hacer un revert commit de los cambios malos para que la branch quede consistente con producción.
6. Crear entry en `01-strategy/decision-log/` post-mortem si fue grave.

Ejemplo: si el commit bueno de `index.html` es `abc1234`, en `.env` reemplazar temporalmente `GITHUB_BRANCH=claude/setup-new-repo-tR58e` por `GITHUB_BRANCH=abc1234`, correr Receta 1, y volver a poner la branch original.

---

## 7. Lo que NO se hace

- **Nunca** editar archivos directamente desde el File Manager de Hostinger sin commitearlo después en el repo. Crea drift entre git y producción.
- **Nunca** subir `.env`, `auth_config.php` (con secrets reales), `dolar_cache.json`, `.auth_attempts.json`. Los runtime files se generan solos en el servidor.
- **Nunca** borrar archivos del servidor sin verificar que no están servidos por algo (ej: una imagen referenciada en un ad pago corriendo).
- **Nunca** desplegar viernes a las 6 PM. Si algo se rompe, no estás para arreglarlo.

---

## 8. Trigger de revisión de este doc

- Cambia el hosting (de Hostinger a otro).
- Hostinger expone SFTP / SSH y migramos.
- Sumamos staging environment.
- Cambian las credenciales (rotación periódica).
- Default: revisión cada 6 meses.
