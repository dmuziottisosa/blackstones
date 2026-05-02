# 2026-05-02 — Validación del patrón Base64-inline para deploy

> **Layer-0 raw.** Log de la sesión que validó el patrón canónico de deploy.

---

## Contexto

Después de descartar GitHub raw fetch (PAT con permisos no propagaba), descartar clone local (acoplamiento al filesystem del usuario), y descartar `gh` CLI (instalación adicional), se adoptó **Base64-inline en chat** como patrón canónico de deploy.

El usuario pidió: *"LA FORMA QUE IMPLEMENTES DEBE SER INFALIBLE Y ALTAMENTE CONFIABLE"*. La hipótesis: Base64 inline en la respuesta del chat es la única forma que tiene cero dependencias externas (no auth, no clone, no install, no public mirror).

---

## Test ejecutado

**Bloque PowerShell del lado de la IA:**

```powershell
Get-Content D:\blackstones\.env | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
$serverPath = "_test_base64.txt"
$tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".txt")
$b64 = @'
QmxhY2tTdG9uZXMgQmFzZTY0IGRlcGxveSB0ZXN0IC0gMjAyNi0wNS0wMiAtIHNhZmUgdG8gZGVsZXRlCg==
'@
[System.IO.File]::WriteAllBytes($tmp, [System.Convert]::FromBase64String(($b64 -replace '\s','')))
curl.exe --user "${env:FTP_USER}:${env:FTP_PASS}" -T $tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$serverPath"
Remove-Item $tmp
Start-Process "https://blackstones.com.ar/$serverPath`?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
```

**Contenido decodificado del Base64:**

`BlackStones Base64 deploy test - 2026-05-02 - safe to delete\n`

---

## Resultado

✅ **Validado end-to-end.**

- `curl.exe` no imprimió nada (silencio = upload exitoso por FTP).
- Browser en `https://blackstones.com.ar/_test_base64.txt` mostró el texto exacto del Base64 decodificado.
- El usuario confirmó: *"PERFECTO VALIDE Y LO BORRE MANUAL EN EL GESTOR DE ARCHIVO GUI / TODO OK / FUNCIONO!"*

---

## Por qué este patrón es robusto

1. **Cero auth externa.** No GitHub token (descartado). No `gh` CLI. No clone.
2. **Base64 es ASCII puro.** Sobrevive cualquier rendering del chat (autolink, escapes, line wrap, BOM).
3. **`FromBase64String` valida byte-a-byte.** Si algo se rompió al copiar, falla con error explícito (`Invalid length for a Base-64 char array`).
4. **`-replace '\s',''` quita whitespace antes de decodificar.** Robusto frente a chat clients que insertan newlines en lugares raros.
5. **`[System.IO.File]::WriteAllBytes`** escribe bytes crudos (sin BOM, sin re-encoding). Lo que está en el Base64 es lo que llega al server.
6. **Visible en consola.** Cada paso (load .env, write temp, curl, delete temp, open browser) es observable.

---

## Lo que migra a layer-3

(Ya está en `04-operations/deploy-snippets.md` — Receta 1 Base64 inline.)

- Patrón canónico documentado.
- `.env` simplificado a 4 líneas (FTP_HOST / FTP_USER / FTP_PASS / FTP_REMOTE_BASE).
- Toda referencia a `GITHUB_TOKEN` / clone / `gh` / `git pull` removida del repo.

---

## Pendientes derivados

- [ ] Usuario debe **revocar el GitHub PAT** expuesto en chat (`github_pat_11CAH...`). Settings → Personal access tokens → Revoke. Ya no se usa.
- [ ] Eliminar las líneas `GITHUB_*` del `.env` local del usuario (si todavía están).
- [ ] Rotar password FTP cuando sea conveniente (también expuesto en chat durante el bootstrap).
- [ ] Rotar `AUTH_SECRET` y password de la calc (`roma__blue`) — expuestos en commits del repo, hay que regenerarlos antes de cualquier deploy de la calc.
