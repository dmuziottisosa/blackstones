#!/usr/bin/env python3
"""Arma el bloque de PowerShell que sube las reglas de acceso de la calculadora
(site/public_html/calculadora/.htaccess), con comprobacion y vuelta atras.

    python3 04-operations/tools/bloque-htaccess.py <commit> <salida.ps1>

    commit    el commit de donde se bajan las reglas. Tiene que estar subido a GitHub.
    salida    donde queda el bloque. Ese archivo es el que se prueba y el que se entrega.

Un .htaccess mal puesto deja sin entrar a todo el sistema, y desde aca no se puede probar
contra el servidor de verdad. Por eso este bloque no es el de bloque-deploy.py:

  1. Baja las reglas de GitHub y las revisa (tamano exacto y una marca).
  2. Anota como responde el sitio ANTES: el ingreso, la calculadora, un endpoint y el tablero.
     Si el ingreso no responde 200, no toca nada. Si lo que hay que cerrar ya esta cerrado, tampoco.
  3. Baja por FTP las reglas que haya hoy en el servidor. Si hay, deja una copia en el servidor,
     fuera de la web (respaldo-htaccess/, al lado de bs-data/). Si no puede saber si hay, no toca nada.
  4. Sube las reglas nuevas.
  5. Comprueba que lo que habia que cerrar responde 403 y que todo lo demas responde igual que antes.
     Si algo cambio, vuelve atras solo.
  6. Abre la calculadora y le pregunta a la persona si le abre con su usuario. Con N, vuelve atras.

Volver atras es subir de nuevo las reglas que habia. Si no habia, sube un archivo con un solo
comentario, que es lo mismo que no tener reglas: el bloque no borra nada del servidor.

Reglas del bloque, que este programa comprueba: solo ASCII, sin tabulaciones y sin renglones
vacios. Va entero adentro de "& { try { } catch { } finally { } }". Ver deploy-snippets.md, Receta 10.
"""
import os
import subprocess
import sys

REPO = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
GITHUB = 'dmuziottisosa/blackstones'
RUTA = 'site/public_html/calculadora/.htaccess'
MARCA = 'auth_attempts'   # un texto que las reglas tienen que traer

PLANTILLA = r'''# === BlackStones - cerrar lo que abre sin usuario en /calculadora/ (reglas de acceso). Commit @@CORTO@@ ===
& { $tmps = @(); $tocado = $false; $habia = $false; $viejo = ""; $antes = @{}; try {
  Get-Content D:\blackstones\.env -ErrorAction SilentlyContinue | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
  if (-not $env:FTP_HOST -or -not $env:FTP_USER -or -not $env:FTP_REMOTE_BASE) { throw "No se pudo leer D:\blackstones\.env. No se cambio nada." }
  $sha = "@@SHA@@"
  $sitio = "https://blackstones.com.ar/"
  $cred = "${env:FTP_USER}:${env:FTP_PASS}"
  $carpeta = "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/calculadora/"
  $ftp = $carpeta + ".htaccess"
  $sanas = @("calculadora/login.php", "calculadora/", "calculadora/api/tablero.php", "presupuestos/tablero.php")
  $cerrar = @("calculadora/calc.html", "calculadora/.auth_attempts.json", "calculadora/auth_check.php", "calculadora/auth_config.php")
  $cuerpo = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".bin"); $tmps += $cuerpo
  function Cod($ruta) { $c = curl.exe -s -m 25 -o $cuerpo -w "%{http_code}" ($sitio + $ruta + "?v=" + [DateTimeOffset]::Now.ToUnixTimeMilliseconds()); return "$c" }
  # 1) Bajar de GitHub las reglas nuevas y revisarlas ANTES de tocar nada
  $nuevo = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".bin"); $tmps += $nuevo
  curl.exe -s -L -f -o $nuevo "https://raw.githubusercontent.com/@@GITHUB@@/$sha/@@RUTA@@"
  if ($LASTEXITCODE -ne 0 -or -not (Test-Path $nuevo)) { throw ("No se pudieron bajar las reglas de GitHub (curl " + $LASTEXITCODE + "). No se cambio nada.") }
  if ((Get-Item $nuevo).Length -ne @@BYTES@@ -or -not (Select-String -Path $nuevo -Pattern "@@MARCA@@" -SimpleMatch -Quiet)) { throw "Las reglas que bajaron de GitHub no son las esperadas. No se cambio nada." }
  # 2) Como responde el sitio ANTES de tocar nada
  foreach ($r in $sanas) { $antes[$r] = Cod $r }
  if ($antes[$sanas[0]] -ne "200") { throw ("La pagina de ingreso no responde bien ahora (codigo " + $antes[$sanas[0]] + "). No se cambio nada.") }
  $abiertos = @(); foreach ($r in $cerrar) { $c = Cod $r; if ($c -ne "403" -and $c -ne "404") { $abiertos += $r } }
  if (-not $abiertos.Count) { Write-Host "LISTO: ya estaba cerrado. No se cambio nada." -ForegroundColor Green; return }
  # 3) Bajar las reglas que hay hoy en el servidor. Si hay, dejar una copia en el servidor, fuera de la web
  $viejo = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".bin"); $tmps += $viejo
  curl.exe -s -m 60 --user $cred -o $viejo $ftp
  $e = $LASTEXITCODE
  if ($e -eq 0) {
    $habia = $true
    if (-not (Test-Path $viejo)) { [IO.File]::WriteAllBytes($viejo, [byte[]]@()) }
    $copia = "respaldo-htaccess/calculadora-htaccess-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".txt"
    curl.exe -s -m 60 --ftp-create-dirs --user $cred -T $viejo ("ftp://${env:FTP_HOST}/" + $copia)
    if ($LASTEXITCODE -ne 0) { throw ("No se pudo guardar la copia de las reglas anteriores (curl " + $LASTEXITCODE + "). No se cambio nada.") }
    Write-Host ("Habia reglas anteriores (" + (Get-Item $viejo).Length + " bytes). Quedo una copia en el servidor: " + $copia) -ForegroundColor Cyan
  } elseif ($e -eq 78) {
    $lista = curl.exe -s -m 60 --user $cred -X "LIST -a" $carpeta
    if ($LASTEXITCODE -ne 0) { throw ("No se pudo leer la carpeta por FTP (curl " + $LASTEXITCODE + "). No se cambio nada.") }
    if ($lista | Where-Object { $_ -match '\s\.htaccess\s*$' }) { throw "En el servidor hay reglas anteriores que no se pudieron bajar. No se cambio nada." }
  } else { throw ("No se pudo leer el servidor por FTP (curl " + $e + "). No se cambio nada.") }
  # 4) Subir las reglas nuevas
  $tocado = $true
  curl.exe -s -m 60 --user $cred -T $nuevo $ftp
  if ($LASTEXITCODE -ne 0) { throw ("Fallo la subida de las reglas (curl " + $LASTEXITCODE + ").") }
  Write-Host "Reglas subidas. Comprobando..." -ForegroundColor Cyan
  # 5) Comprobar: lo que habia que cerrar, cerrado; y todo lo demas, igual que antes
  $mal = @(); $seguidas = 0
  for ($i = 0; $i -lt 6; $i++) {
    Start-Sleep -Seconds 2
    $abiertos = @(); foreach ($r in $cerrar) { $c = Cod $r; if ($c -ne "403" -and $c -ne "404") { $abiertos += ($r + " (" + $c + ")") } }
    $mal = @(); foreach ($r in $sanas) { $c = Cod $r; if ($c -ne $antes[$r]) { $mal += ($r + " (antes " + $antes[$r] + ", ahora " + $c + ")") } }
    if ($mal.Count) { $seguidas++ } else { $seguidas = 0 }
    if ($seguidas -ge 2 -or (-not $mal.Count -and -not $abiertos.Count)) { break }
  }
  if ($mal.Count) { throw ("Con las reglas nuevas cambio la respuesta de: " + ($mal -join ", ") + ".") }
  if ($abiertos.Count) { Write-Host ("OJO: las reglas se subieron y el sistema responde igual que antes, pero esto sigue abriendo sin usuario: " + ($abiertos -join ", ") + ".") -ForegroundColor Yellow }
  else { Write-Host "Cerrado: la calculadora suelta y el archivo de intentos ya no abren sin usuario. El ingreso y el sistema responden igual que antes." -ForegroundColor Green }
  # 6) Que lo confirme una persona, entrando con su usuario
  Start-Process ($sitio + "calculadora/?v=" + [DateTimeOffset]::Now.ToUnixTimeSeconds())
  Write-Host "Se abrio la calculadora en el navegador: entra con tu usuario como siempre y fijate que abre." -ForegroundColor Cyan
  do { $resp = ("" + (Read-Host "Abre bien? Escribi S y Enter. Si NO abre, escribi N y Enter (vuelve todo atras)")).Trim().ToUpper() } until ($resp -match "^[SN]")
  if ($resp -match "^N") { throw "Dijiste que la calculadora no abre." }
  if ($abiertos.Count) { Write-Host "LISTO: las reglas quedaron subidas, pero falta que cierren. Avisame." -ForegroundColor Yellow }
  else { Write-Host "LISTO: quedo cerrado." -ForegroundColor Green }
} catch {
  $msg = $_.Exception.Message
  if ($tocado) {
    $src = $viejo
    if (-not $habia) { $src = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".bin"); $tmps += $src; Set-Content -Path $src -Value "# (sin reglas)" -Encoding ascii }
    curl.exe -s -m 60 --user $cred -T $src $ftp
    $ev = $LASTEXITCODE
    Start-Sleep -Seconds 3
    $dif = @(); foreach ($r in $sanas) { $c = Cod $r; if ($c -ne $antes[$r]) { Start-Sleep -Seconds 3; $c = Cod $r }; if ($c -ne $antes[$r]) { $dif += ($r + " (antes " + $antes[$r] + ", ahora " + $c + ")") } }
    if ($dif.Count) { Write-Host ("ATENCION: el sistema NO responde como antes: " + ($dif -join ", ") + ". Avisame ya.") -ForegroundColor Red }
    elseif ($ev -eq 0) { Write-Host "Se volvio atras: las reglas quedaron como estaban y el sistema responde igual que antes." -ForegroundColor Yellow }
    else { Write-Host ("No se pudo escribir en el servidor (curl " + $ev + "), pero el sistema responde igual que antes.") -ForegroundColor Yellow }
  }
  Write-Host ("ERROR: " + $msg) -ForegroundColor Red
} finally {
  foreach ($t in $tmps) { Remove-Item $t -ErrorAction SilentlyContinue }
} }
'''


def fallar(msg):
    sys.exit('bloque-htaccess: ' + msg)


def git(*args, texto=True):
    return subprocess.check_output(('git',) + args, cwd=REPO, text=texto, stderr=subprocess.DEVNULL)


def main():
    if len(sys.argv) != 3:
        fallar('uso: bloque-htaccess.py <commit> <salida.ps1>')
    commit, salida = sys.argv[1:3]
    try:
        sha = git('rev-parse', '--verify', commit + '^{commit}').strip()
    except subprocess.CalledProcessError:
        fallar('no existe el commit %r' % commit)
    if not git('branch', '-r', '--contains', sha).strip():
        fallar('el commit %s no esta en GitHub: hacer git push antes de armar el bloque' % sha[:7])
    try:
        datos = git('show', '%s:%s' % (sha, RUTA), texto=False)
    except subprocess.CalledProcessError:
        fallar('%s no existe en el commit %s' % (RUTA, sha[:7]))
    if MARCA.encode() not in datos:
        fallar('las reglas no traen la marca %r' % MARCA)
    bloque = PLANTILLA
    for clave, valor in (('@@CORTO@@', sha[:7]), ('@@SHA@@', sha), ('@@GITHUB@@', GITHUB), ('@@RUTA@@', RUTA),
                         ('@@BYTES@@', str(len(datos))), ('@@MARCA@@', MARCA)):
        bloque = bloque.replace(clave, valor)
    if '@@' in bloque:
        fallar('quedo un dato sin completar')
    renglones = bloque.split('\n')
    if renglones[-1] != '' or any(not r.strip() for r in renglones[:-1]):
        fallar('el bloque quedo con un renglon vacio')
    if any(ord(c) > 126 or (ord(c) < 32 and c != '\n') for c in bloque):
        fallar('el bloque quedo con un caracter que no es ASCII, o con una tabulacion')
    with open(salida, 'w', encoding='ascii', newline='\n') as f:
        f.write(bloque)
    print('%s: reglas de %d bytes del commit %s, %d renglones' % (salida, len(datos), sha[:7], len(renglones) - 1))


if __name__ == '__main__':
    main()
