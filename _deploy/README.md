# _deploy/

> **Carpeta temporal.** Acá se publican payloads binarios para deploy por single-paste, cuando el contenido es demasiado grande para emitirse en chat directamente.

## bs-deploy-fase1y2.tar.xz

Bundle comprimido con todos los archivos del registry (Fase 1 + Fase 2 backend). Para descargarlo:

1. Abrir en GitHub (logueado): https://github.com/dmuziottisosa/blackstones/blob/claude/setup-new-repo-tR58e/_deploy/bs-deploy-fase1y2.tar.xz
2. Click en **Download raw file** (botón arriba a la derecha).
3. Guardar como `D:\blackstones\bs-deploy-fase1y2.tar.xz`.

Después correr el deploy block en PowerShell que extrae y sube todo por FTP.

## Limpieza

Una vez deployado y verificado, esta carpeta se puede borrar del repo (junto con el binary). Es transitoria.

## Política

Esta carpeta NO contiene credenciales ni datos sensibles — solo código fuente PHP empaquetado. Es seguro mantenerla en el repo aunque sea privado. Borrar cuando ya no haga falta.
