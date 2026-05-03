# Backup — Calc baseline v1.3 (pre-registry)

> **Snapshot del estado funcional de la calculadora antes de implementar el sistema de registro de presupuestos.**
>
> Fecha: 2026-05-02
> Branch: `claude/setup-new-repo-tR58e`
> Baseline lockeada: v1.3 (ver `03-product/calculadora/baseline-v1.0.md`)

---

## Qué hay acá

Copia byte-a-byte de los 8 archivos canónicos de `site/public_html/calculadora/` al momento del último deploy verificado.

| Archivo | Bytes | Función |
|---|---|---|
| `.htaccess` | 835 | Bloqueo de acceso directo a archivos sensibles |
| `auth_check.php` | 4.105 | HMAC cookie + rate limiting |
| `auth_config.php` | 1.483 | Secrets (bcrypt hash + AUTH_SECRET) |
| `calc.html` | 274.559 | App principal — 4703+ líneas |
| `dolar.php` | 6.327 | Scraper DolarHoy v5 |
| `index.php` | 883 | Auth gate |
| `login.php` | 5.533 | Form de login |
| `logout.php` | 100 | Borra cookie + redirect |

Excluidos del backup (runtime, regenerable):
- `dolar_cache.json` — cache de cotización (5 min TTL)
- `.auth_attempts.json` — rate limiting state

---

## Cuándo restaurar

Si la implementación del registry rompe la calc en producción y no podemos arreglar con un revert quirúrgico:

```powershell
# Restaurar la calc completa desde este backup
# (asume que el .env está cargado y tenés FTP creds)

$base = "site/_backups/2026-05-02-calc-baseline-v1.3"
$files = @("calc.html", "index.php", "login.php", "logout.php", "auth_check.php", "auth_config.php", "dolar.php", ".htaccess")

foreach ($file in $files) {
    $remote = if ($file -eq ".htaccess") { "htaccess" } else { $file }
    # Subir cada archivo via Receta 1 (Base64) o leyendo del repo y subiendo
    Write-Host "Restaurar: $base/$file -> calculadora/$remote"
    # ... aplicar deploy block apropiado por archivo ...
}
```

(El comando real de restore se arma cuando haga falta. Lo importante es que los bytes están preservados acá.)

---

## Cómo verificar integridad antes de restaurar

```bash
# En el repo, comparar bytes con la baseline
sha256sum site/_backups/2026-05-02-calc-baseline-v1.3/*.* site/_backups/2026-05-02-calc-baseline-v1.3/.htaccess
```

Estos hashes son la fuente de verdad de "esto funcionaba".

---

## Rotación de backups

Política simple:
- Crear backup antes de cualquier cambio mayor estructural (no por fix puntual).
- Nombrar con fecha + descripción: `YYYY-MM-DD-{tag}/`.
- No borrar backups viejos automáticamente — el repo aguanta tranquilo.

Próximo backup probable: post-registry implementación, antes del siguiente cambio mayor.
