#!/usr/bin/env python3
"""Arma el bloque de PowerShell que sube al sitio varios archivos de un commit.

    python3 04-operations/tools/bloque-deploy.py <commit> <salida.ps1> "<titulo>" "<url>" ruta[=marca] [ruta[=marca] ...]

    commit    el commit de donde se bajan los archivos. Tiene que estar subido a GitHub.
    salida    donde queda el bloque. Ese archivo es el que se prueba y el que se entrega.
    titulo    que se sube, en una linea, sin tildes.
    url       la pagina que se abre al terminar. Puede llevar "#seccion" al final, para que
              abra en esa parte de la pagina.
    ruta      relativa a site/public_html. Se suben en el orden dado: lo que "activa" el
              cambio (la pagina que apunta a lo demas) va al final.
    =marca    para archivos de texto: un texto corto que el archivo tiene que traer.

El bloque baja todo de GitHub, lo revisa (tamano exacto, y la marca si la hay) y recien
despues sube. Si la url que se abre es una de las paginas subidas, al final le pide esa
pagina al sitio y dice si ya responde con lo nuevo.

Va entero adentro de "& { try { } catch { } finally { } }": pegado en la consola, cada
renglon suelto corre por su cuenta y un error no frena los que siguen (24/11/2026: fallo
la bajada y la consola igual dijo "Subidos 8 archivos" y abrio el navegador). Asi un
error corta todo y se lee en un solo renglon que empieza con "ERROR:".

Reglas del bloque, que este programa comprueba: solo ASCII, sin tabulaciones y sin
renglones vacios (en una consola sin PSReadLine un renglon vacio corta el bloque por
la mitad).

Los tamanos salen de aca, del commit. No se escriben a mano ni de memoria: el bloque que
se entrega se copia del archivo de salida, letra por letra. Ver deploy-snippets.md, Receta 9.
"""
import os
import re
import subprocess
import sys

REPO = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
GITHUB = 'dmuziottisosa/blackstones'
SITIO = 'https://blackstones.com.ar/'
MAX_ARCHIVOS = 25   # el FTP de Hostinger bloquea la IP cerca de las 29 conexiones seguidas (una por archivo)

PLANTILLA = r'''# === BlackStones - deploy: %(titulo)s. Commit %(corto)s ===
& { $listos = @(); $n = 0; try {
  Get-Content D:\blackstones\.env -ErrorAction SilentlyContinue | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
  if (-not $env:FTP_HOST -or -not $env:FTP_USER -or -not $env:FTP_REMOTE_BASE) { throw "No se pudo leer D:\blackstones\.env. No se subio nada." }
  $sha = "%(sha)s"
  $base = "https://raw.githubusercontent.com/%(github)s/$sha/site/public_html"
  $archivos = @(
%(filas)s
  )
  # 1) Bajar todo de GitHub y revisarlo ANTES de subir nada
  foreach ($a in $archivos) {
    $tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".bin")
    $listos += @{ ruta = $a.ruta; tmp = $tmp }
    curl.exe -s -L -f -o $tmp "$base/$($a.ruta)"
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path $tmp)) { throw ("No se pudo bajar " + $a.ruta + " de GitHub (curl " + $LASTEXITCODE + "). No se subio nada.") }
    $largo = (Get-Item $tmp).Length
    if ($largo -ne $a.bytes) { throw ($a.ruta + " bajo con " + $largo + " bytes y se esperaban " + $a.bytes + ". No se subio nada.") }
    if ($a.marca -and -not (Select-String -Path $tmp -Pattern $a.marca -SimpleMatch -Quiet)) { throw ($a.ruta + " no es el archivo esperado. No se subio nada.") }
  }
  $total = $listos.Count
  Write-Host ("Bajados y revisados " + $total + " archivos. Subiendo...") -ForegroundColor Cyan
  # 2) Subir por FTP a Hostinger (crea las carpetas que falten), en el orden de la lista
  foreach ($l in $listos) {
    curl.exe -s --ftp-create-dirs --user "${env:FTP_USER}:${env:FTP_PASS}" -T $l.tmp "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/$($l.ruta)"
    if ($LASTEXITCODE -ne 0) { throw ("Fallo la subida de " + $l.ruta + " (curl " + $LASTEXITCODE + "). Se habian subido " + $n + " de " + $total + ". Volve a pegar el bloque entero.") }
    $n++
  }
  Write-Host ("Subidos " + $n + " archivos.") -ForegroundColor Green
%(final)s
} catch {
  Write-Host ("ERROR: " + $_.Exception.Message) -ForegroundColor Red
} finally {
  foreach ($l in $listos) { Remove-Item $l.tmp -ErrorAction SilentlyContinue }
} }
'''

ABRIR = r'''  # 3) Abrir la pagina
  Start-Process "%(url)s?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())%(ancla)s"'''

COMPROBAR_Y_ABRIR = r'''  # 3) Pedirle la pagina al sitio para ver que ya responde con lo nuevo, y abrirla
  $url = "%(url)s?v=$([DateTimeOffset]::Now.ToUnixTimeSeconds())"
  $ver = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".bin")
  $listos += @{ ruta = "comprobacion"; tmp = $ver }
  curl.exe -s -L -f -o $ver $url
  if ($LASTEXITCODE -eq 0 -and (Test-Path $ver) -and (Select-String -Path $ver -Pattern "%(marca)s" -SimpleMatch -Quiet)) { Write-Host "La pagina ya responde con lo nuevo." -ForegroundColor Green }
  else { Write-Host "OJO: se subio todo, pero la pagina todavia no responde con lo nuevo. Espera un minuto y actualizala." -ForegroundColor Yellow }
  Start-Process "$url%(ancla)s"'''


def fallar(msg):
    sys.exit('bloque-deploy: ' + msg)


def git(*args, texto=True):
    return subprocess.check_output(('git',) + args, cwd=REPO, text=texto, stderr=subprocess.DEVNULL)


def limpio(s, que, prohibidos='"$`\t'):
    if not s or any(ord(c) < 32 or ord(c) > 126 or c in prohibidos for c in s):
        fallar('%s: solo ASCII, sin comillas dobles, $, acento grave ni tabulaciones: %r' % (que, s))


def main():
    if len(sys.argv) < 6:
        fallar('uso: bloque-deploy.py <commit> <salida.ps1> "<titulo>" "<url>" ruta[=marca] [ruta[=marca] ...]')
    commit, salida, titulo, url = sys.argv[1:5]
    try:
        sha = git('rev-parse', '--verify', commit + '^{commit}').strip()
    except subprocess.CalledProcessError:
        fallar('no existe el commit %r' % commit)
    if not git('branch', '-r', '--contains', sha).strip():
        fallar('el commit %s no esta en GitHub: hacer git push antes de armar el bloque' % sha[:7])
    limpio(titulo, 'titulo')
    limpio(url, 'url', '"$`\t ')
    url, _, ancla = url.partition('#')
    if not url.startswith(SITIO) or '?' in url or (ancla and not re.match(r'^[a-z0-9-]+$', ancla)):
        fallar('la url tiene que ser de %s, sin "?", y la seccion (despues de "#") en minusculas: %r' % (SITIO, url))
    ancla = ('#' + ancla) if ancla else ''
    pedidos = sys.argv[5:]
    if len(pedidos) > MAX_ARCHIVOS:
        fallar('%d archivos son muchos para un bloque asi (el FTP bloquea cerca de las 29 conexiones). '
               'Ver deploy-snippets.md, Receta 8.' % len(pedidos))
    filas, marcas, total = [], {}, 0
    for pedido in pedidos:
        ruta, _, marca = pedido.partition('=')
        limpio(ruta, 'ruta', '"$`\t =')
        if ruta.startswith('/') or '..' in ruta or '\\' in ruta:
            fallar('la ruta va relativa a site/public_html: %r' % ruta)
        if ruta in marcas:
            fallar('ruta repetida: ' + ruta)
        try:
            datos = git('show', '%s:site/public_html/%s' % (sha, ruta), texto=False)
        except subprocess.CalledProcessError:
            fallar('%s no existe en el commit %s' % (ruta, sha[:7]))
        if not datos:
            fallar(ruta + ' esta vacio')
        total += len(datos)
        marcas[ruta] = marca
        if marca:
            limpio(marca, 'marca de ' + ruta)
            if marca.encode() not in datos:
                fallar('%s no trae la marca %r' % (ruta, marca))
            filas.append('    @{ ruta = "%s"; bytes = %d; marca = "%s" }' % (ruta, len(datos), marca))
        else:
            if b'\0' not in datos[:8000]:
                fallar('%s es un archivo de texto: lleva marca (ruta=marca)' % ruta)
            filas.append('    @{ ruta = "%s"; bytes = %d }' % (ruta, len(datos)))
    # si la pagina que se abre es una de las que se suben, al final se comprueba que el sitio la entregue
    pagina = url[len(SITIO):]
    pagina += 'index.html' if pagina == '' or pagina.endswith('/') else ''
    final = (COMPROBAR_Y_ABRIR % {'url': url, 'marca': marcas[pagina], 'ancla': ancla}) if marcas.get(pagina) else (ABRIR % {'url': url, 'ancla': ancla})
    bloque = PLANTILLA % {'titulo': titulo, 'corto': sha[:7], 'sha': sha, 'github': GITHUB,
                          'filas': ',\n'.join(filas), 'final': final}
    renglones = bloque.split('\n')
    if renglones[-1] != '' or any(not r.strip() for r in renglones[:-1]):
        fallar('el bloque quedo con un renglon vacio')
    if any(ord(c) > 126 or (ord(c) < 32 and c != '\n') for c in bloque):
        fallar('el bloque quedo con un caracter que no es ASCII, o con una tabulacion')
    with open(salida, 'w', encoding='ascii', newline='\n') as f:
        f.write(bloque)
    print('%s: %d archivos, %d KB, %d renglones%s' % (salida, len(filas), total // 1024, len(renglones) - 1,
                                                    ', con comprobacion al final' if marcas.get(pagina) else ''))


if __name__ == '__main__':
    main()
