# FTP map — cómo está montado el hosting

> Estado: **layer-3 active belief.** Validado por test de upload el 2026-05-02.
>
> Mapa mental de cómo está estructurado el FTP de Hostinger y cómo se mapea con `site/public_html/` del repo.

---

## 1. Lo que importa en una frase

**El usuario FTP NO aterriza en `public_html/`. Aterriza en el home (`/home/u144473384/`). El web root está dentro de `domains/blackstones.com.ar/public_html/`.**

Toda ruta de upload tiene que empezar con `domains/blackstones.com.ar/public_html/`. Si la omitís, el archivo se sube al home y NO se sirve por HTTP (devuelve 404 en el browser).

---

## 2. Árbol del FTP (lo que ves al conectarte)

```
/home/u144473384/                                    ← acá aterriza el FTP user
├── .api_token                                        Hostinger interno
├── .filebrowser/                                     Hostinger interno
├── .imunify_patch_id                                 Hostinger interno (seguridad)
├── .logs/                                            logs del hosting
├── .myimunify_id                                     Hostinger interno
├── .profile                                          shell del jail
└── domains/
    └── blackstones.com.ar/
        ├── private/                                  no servido públicamente
        └── public_html/                              ⭐ web root real (servido por HTTP)
            ├── index.html                            landing
            ├── robots.txt
            ├── favicon/
            ├── logo/
            ├── Granitos/                             imágenes de materiales
            ├── Purastone/
            ├── Suprastone/
            ├── xtone/
            ├── foto_bloque_1/
            ├── foto_equipo/
            ├── foto_proyectos/
            ├── ig_section/
            └── calculadora/
                ├── calc.html                         app de cálculo
                ├── index.php                         entry point con auth
                ├── login.php                         form de login
                ├── logout.php
                ├── auth_check.php                    HMAC cookie check
                ├── auth_config.php                   ⚠️ secrets (no commitear con valores reales)
                ├── dolar.php                         scraper DolarHoy
                └── dolar_cache.json                  runtime, se regenera solo
```

---

## 3. Mapeo local ↔ remoto

| Path local (en este repo)            | Path remoto en Hostinger                                |
|--------------------------------------|---------------------------------------------------------|
| `site/public_html/`                  | `domains/blackstones.com.ar/public_html/`               |
| `site/public_html/index.html`        | `domains/blackstones.com.ar/public_html/index.html`     |
| `site/public_html/calculadora/`      | `domains/blackstones.com.ar/public_html/calculadora/`   |
| `site/public_html/calculadora/calc.html` | `domains/blackstones.com.ar/public_html/calculadora/calc.html` |

**Regla mental:** sustituí `site/public_html/` por `domains/blackstones.com.ar/public_html/`. Es 1:1 a partir de ahí.

---

## 4. URLs públicas

| Archivo / carpeta                                                | URL pública                                  |
|------------------------------------------------------------------|----------------------------------------------|
| `domains/blackstones.com.ar/public_html/index.html`              | `https://blackstones.com.ar/`                |
| `domains/blackstones.com.ar/public_html/calculadora/index.php`   | `https://blackstones.com.ar/calculadora/`    |
| `domains/blackstones.com.ar/public_html/Granitos/foo.webp`       | `https://blackstones.com.ar/Granitos/foo.webp` |

---

## 5. Gotchas confirmadas (errores que ya cometimos)

| Error | Causa | Solución |
|---|---|---|
| `curl: (9) Server denied you to change to the given directory` | Path empezaba con `/public_html/` y eso no existe en el home del FTP user | Usar `domains/blackstones.com.ar/public_html/` |
| Upload "exitoso" pero 404 en el browser | Se subió al home (`/home/u144473384/`) en vez del web root | Ídem arriba — siempre usar el path completo |
| `Invoke-WebRequest: No se encuentra ningún parámetro de posición que acepte el argumento 'u144473384'` | En PowerShell `curl` es alias de `Invoke-WebRequest` | Usar `curl.exe` (con extensión) explícito |
| `Could not resolve host: ftp.blackstones.com.ar` | El subdominio `ftp.` no resuelve en este DNS | Usar `blackstones.com.ar` directo (sin `ftp.`) |
| `curl: (67) Access denied: 530` | Password mal tipeada | Pasar credenciales en formato `--user "user:pass"` para evitar errores de typing |

---

## 6. Sesión típica de descubrimiento (referencia)

Para listar el directorio donde aterrizás:

```powershell
curl.exe --user "u144473384:$env:FTP_PASS" "ftp://blackstones.com.ar/"
```

Para listar el web root:

```powershell
curl.exe --user "u144473384:$env:FTP_PASS" "ftp://blackstones.com.ar/domains/blackstones.com.ar/public_html/"
```

Para listar la calc:

```powershell
curl.exe --user "u144473384:$env:FTP_PASS" "ftp://blackstones.com.ar/domains/blackstones.com.ar/public_html/calculadora/"
```

---

## 7. Cuándo se actualiza este doc

- Cambia la estructura del hosting (Hostinger reorganiza los jails).
- Migramos de Hostinger a otro proveedor.
- Aparece una carpeta nueva relevante en el web root.
- Cambia el dominio principal.
