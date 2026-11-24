# === BlackStones - parche: link "Ver mas trabajos" en la pagina principal (index.html). No cambia nada mas ===
& { $tmp = $null; $ver = $null; try {
  Get-Content D:\blackstones\.env -ErrorAction SilentlyContinue | ForEach-Object { if ($_ -match '^\s*([^#=]+)=(.*)$') { Set-Item -Path "env:$($matches[1].Trim())" -Value $matches[2].Trim() } }
  if (-not $env:FTP_HOST -or -not $env:FTP_USER -or -not $env:FTP_REMOTE_BASE) { throw "No se pudo leer D:\blackstones\.env. No se cambio nada." }
  $cred = "${env:FTP_USER}:${env:FTP_PASS}"
  $destino = "ftp://${env:FTP_HOST}/${env:FTP_REMOTE_BASE}/index.html"
  $tmp = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".html")
  $ver = Join-Path $env:TEMP ("bs_" + [guid]::NewGuid().ToString().Substring(0,8) + ".bin")
  # 1) Bajar la pagina principal como esta hoy en el servidor
  curl.exe -s --user $cred -o $tmp $destino
  if ($LASTEXITCODE -ne 0 -or -not (Test-Path $tmp)) { throw ("No se pudo bajar la pagina principal del servidor (curl " + $LASTEXITCODE + "). No se cambio nada.") }
  $pre = (Get-Item $tmp).Length
  $html = [IO.File]::ReadAllText($tmp, [Text.Encoding]::UTF8)
  if ($pre -lt 150000 -or -not $html.TrimEnd().EndsWith("</html>")) { throw ("La pagina principal bajo incompleta (" + $pre + " bytes). No se cambio nada.") }
  if ($html.Contains('href="trabajos/"')) { Write-Host "La pagina principal ya tiene el link a Trabajos. No se cambio nada." -ForegroundColor Yellow; return }
  # 2) Los dos lugares que se tocan tienen que estar, y una sola vez cada uno
  $nl = [string][char]10
  if ($html.Contains([string][char]13 + [char]10)) { $nl = [string][char]13 + [char]10 }
  $regla = '.work-more{color:var(--accent-dark);font-weight:700;font-size:.88rem;text-decoration:none;border-bottom:1px solid var(--accent);white-space:nowrap}'
  $links = [regex]::Matches($html, '<a class="work-more" href="https://www\.instagram\.com/blackstones\.ar/" target="_blank" rel="noopener">[^<]*</a>')
  if ([regex]::Matches($html, [regex]::Escape($regla)).Count -ne 1 -or $links.Count -ne 1) { throw "La pagina principal del servidor no es la que se esperaba. No se cambio nada." }
  # 3) Sumar el link "Ver mas trabajos" al lado del de Instagram, y la regla de estilo que los acomoda
  $link = $links[0]
  $nuevo = '<div class="work-links">' + $nl + '      <a class="work-more" href="trabajos/">Ver m' + [char]225 + 's trabajos ' + [char]8594 + '</a>' + $nl + '      ' + $link.Value + $nl + '    </div>'
  $estilo = $nl + '.work-links{display:flex;flex-wrap:wrap;gap:.6rem 1.6rem}'
  $html = $html.Substring(0, $link.Index) + $nuevo + $html.Substring($link.Index + $link.Length)
  $html = $html.Replace($regla, $regla + $estilo)
  [IO.File]::WriteAllText($tmp, $html, (New-Object Text.UTF8Encoding $false))
  $suma = [Text.Encoding]::UTF8.GetByteCount($nuevo) - [Text.Encoding]::UTF8.GetByteCount($link.Value) + [Text.Encoding]::UTF8.GetByteCount($estilo)
  $post = (Get-Item $tmp).Length
  if ($post -ne $pre + $suma) { throw ("El archivo cambio de tamano de una forma que no se esperaba (de " + $pre + " a " + $post + " bytes). No se cambio nada.") }
  # 4) Subir la pagina principal con el link
  curl.exe -s --user $cred -T $tmp $destino
  if ($LASTEXITCODE -ne 0) { curl.exe -s --user $cred -T $tmp $destino }
  if ($LASTEXITCODE -ne 0) { throw ("Fallo la subida de la pagina principal (curl " + $LASTEXITCODE + "). Avisame ya: miro si quedo bien y, si hace falta, la vuelvo a subir entera.") }
  Write-Host ("Subida la pagina principal con el link (de " + $pre + " a " + $post + " bytes).") -ForegroundColor Green
  # 5) Pedirle la pagina al sitio para ver que ya muestra el link, y abrirla en la seccion Trabajos
  $t = [DateTimeOffset]::Now.ToUnixTimeSeconds()
  curl.exe -s -L -f -o $ver "https://blackstones.com.ar/?v=$t"
  if ($LASTEXITCODE -eq 0 -and (Test-Path $ver) -and (Select-String -Path $ver -Pattern 'href="trabajos/"' -SimpleMatch -Quiet)) { Write-Host "La pagina principal ya muestra el link." -ForegroundColor Green }
  else { Write-Host "OJO: se subio, pero la pagina todavia no muestra el link. Espera un minuto y actualizala." -ForegroundColor Yellow }
  Start-Process "https://blackstones.com.ar/?v=$t#trabajos"
} catch {
  Write-Host ("ERROR: " + $_.Exception.Message) -ForegroundColor Red
} finally {
  foreach ($f in @($tmp, $ver)) { if ($f) { Remove-Item $f -ErrorAction SilentlyContinue } }
} }
