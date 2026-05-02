# 2026-05-02 — Sesión de descubrimiento de la estructura FTP de Hostinger

> **Layer-0 raw.** Log crudo de la sesión donde se descubrió cómo está montado el FTP. No editar — si la realidad cambia, agregar dump nuevo.

---

## Contexto

Primer test de deploy automatizable. Se intentó subir un archivo `_deploy_test.txt` por FTP desde la PowerShell del usuario para validar que el camino "edit local → push FTP → ver en HTTP" funcionaba end-to-end. Datos de partida del panel Hostinger:

- Host: `ftp://blackstones.com.ar` (también IP `147.79.84.44`)
- Usuario: `u144473384`
- Password: `j~cD2jX_Nh%qcVw` (⚠️ expuesta en chat — hay que rotar)
- Puerto: 21

---

## Secuencia de errores y diagnóstico

### Error 1 — `curl` no es `curl` en PowerShell

```
curl --user u144473384:... ...
Invoke-WebRequest : No se encuentra ningún parámetro de posición que acepte
el argumento 'u144473384'
```

**Causa:** en PowerShell, `curl` es un alias de `Invoke-WebRequest`. Sintaxis distinta.
**Fix:** usar `curl.exe` con extensión explícita.

### Error 2 — DNS

```
curl.exe ... ftp://ftp.blackstones.com.ar/...
curl: (6) Could not resolve host: ftp.blackstones.com.ar
```

**Causa:** se asumió que el hostname era `ftp.blackstones.com.ar`. El `ftp://` que mostraba el panel era el esquema de protocolo, no parte del hostname. El subdominio `ftp.` no existe.
**Fix:** usar `blackstones.com.ar` directo.

### Error 3 — Auth

```
curl: (67) Access denied: 530
```

**Causa:** typo en la password al tipearla en el comando.
**Fix:** pasar credenciales completas en formato `--user "user:pass"` desde el clipboard.

### Error 4 — Path inválido

```
curl.exe --user "u144473384:..." -T _deploy_test.txt \
  "ftp://blackstones.com.ar/public_html/_deploy_test.txt"
curl: (9) Server denied you to change to the given directory
```

**Causa:** se asumió que el FTP user aterriza dentro de `public_html/`. No.
**Fix temporal aplicado:** dropear `/public_html/` del path → upload "exitoso" pero a una ubicación equivocada.

### Verificación HTTP — el archivo no estaba donde se sirve

Browser en `https://blackstones.com.ar/_deploy_test.txt` → 404.

**Causa:** el archivo se subió al home del FTP user (`/home/u144473384/_deploy_test.txt`), que no es servido por HTTP.

### `ls` del FTP root — se descubre la estructura real

```
PS D:\> curl.exe --user "u144473384:..." "ftp://blackstones.com.ar/"
drwx--x---   7 u144473384 48           4096 May  2 05:19 .
drwx--x---   7 u144473384 48           4096 May  2 05:19 ..
-r--r-----   1 u144473384 o1009204452       40 Apr 24 00:52 .api_token
-rw-r--r--   1 u144473384 o1009204452       52 May  2 05:19 _deploy_test.txt
drwxr-xr-x   3 u144473384 o1009204452     4096 Apr 27 20:32 domains
drwxr-xr-x   3 u144473384 o1009204452     4096 May  2 05:19 .filebrowser
-rw-r--r--   1 u144473384 48            106 Apr 24 06:35 .imunify_patch_id
drwxr-xr-x   2 u144473384 o1009204452     4096 Apr 24 00:51 .logs
-rw-r--r--   1 u144473384 48            102 Apr 24 01:05 .myimunify_id
-rw-r--r--   1 u144473384 o1009204452      701 Apr 24 00:51 .profile
```

**Hallazgo:** el FTP user aterriza en `/home/u144473384/`. El web root está en `domains/blackstones.com.ar/public_html/`.

### Upload exitoso al path correcto

```powershell
curl.exe --user "u144473384:..." -T _deploy_test.txt \
  "ftp://blackstones.com.ar/domains/blackstones.com.ar/public_html/"
```

Browser en `https://blackstones.com.ar/_deploy_test.txt` → 200 con el contenido `BlackStones FTP test - 2026-05-02 - safe to delete`.

✅ Deploy end-to-end validado.

---

## Lecciones que migran a layer-3

(Estas se trasladan a `04-operations/ftp-map.md` y `04-operations/deploy-snippets.md` — ver allá. Acá solo queda el log crudo.)

1. El path remoto correcto siempre empieza con `domains/blackstones.com.ar/public_html/`.
2. El hostname para FTP es `blackstones.com.ar`, sin prefijo `ftp.`.
3. En PowerShell hay que usar `curl.exe`, no `curl`.
4. La sandbox de Claude Code **no tiene salida a puertos FTP** (ni 21, 22, 990, 2121, 21100). Cualquier deploy automático tiene que correr desde la máquina del usuario o desde GitHub Actions.

---

## Pendientes derivados de esta sesión

- [ ] Rotar el password FTP desde el panel Hostinger (la actual fue expuesta en chat).
- [ ] Rotar `AUTH_SECRET` y password de la calc (`roma__blue`) — ambos también expuestos en chat.
- [ ] Crear `.env` con las nuevas credenciales rotadas.
- [ ] Probar `deploy.ps1` (Opción C.1 de `deploy-notes.md`) con el path correcto.
