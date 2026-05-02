# site/

Código público de `blackstones.com.ar`. Mirror 1:1 de `public_html/` en Hostinger.

```
site/
└── public_html/
    ├── index.html              landing single-file
    ├── robots.txt
    ├── calculadora/            calc protegida con auth PHP
    │   ├── calc.html           single-file con la app
    │   ├── index.php           entry point (verifica auth)
    │   ├── login.php · logout.php
    │   ├── auth_check.php      HMAC firmado de cookie
    │   ├── auth_config.php     hash bcrypt + AUTH_SECRET (rotar antes de hacer público)
    │   ├── dolar.php           scraper DolarHoy con cache
    │   └── .htaccess           bloquea acceso directo
    └── [carpetas de imágenes]  Granitos, Purastone, Suprastone, xtone, etc.
```

## Reglas de edición

- **Single-file siempre.** Landing en `index.html`, calc en `calc.html`. No partir.
- **Plain stack.** No agregar frameworks, build tools, npm. La única dependencia runtime es ExcelJS por CDN para los exports de la calc.
- **Naming PDF ↔ Excel debe coincidir.** Si cambiás un texto en uno, cambialo en el otro. Test mental: si imprimo el PDF y lo comparo con el Excel, dicen lo mismo.
- **Imágenes en `.webp`** salvo causa justificada. Performance > formato preferido.
- **Auth de la calc:** ver `04-operations/deploy-notes.md` para rotación de password / secret.

## Deploy

Ver `04-operations/deploy-notes.md`. Tres caminos:

1. **Manual desde panel Hostinger** — descargar carpeta/archivo del repo, subir por File Manager.
2. **FTP manual** — cliente FTP (FileZilla, Cyberduck) con credenciales de Hostinger.
3. **Script `deploy.sh` (futuro)** — automatiza con `lftp`. Credenciales en `.env` gitignorado.

## Cambios estructurales históricos

- 2026-05: Migrado el contenido desde un único `public_html/` flat a `site/public_html/` para liberar la raíz del repo para el cerebro estratégico (00-06).
