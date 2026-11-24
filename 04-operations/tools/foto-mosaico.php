<?php
// Prepara una foto para el mosaico "Lo último que salió del taller" de la landing.
// De la foto que sube el dueño saca dos archivos cuadrados en site/public_html/ig_section/:
//   N.webp    1080 x 1080, para cuando la foto va grande en el mosaico
//   N-c.webp   540 x 540,  para cuando va chica (así el mosaico pesa poco)
// Si la foto no es cuadrada, la recorta: al centro, o de donde se le diga. Antes de elegir
// conviene mirar los tres cortes: en las fotos más altas que anchas el logo suele estar bien
// abajo, y un corte al centro lo deja por la mitad.
//
// Uso:  php 04-operations/tools/foto-mosaico.php <foto original> <número> [corte]
// Ej.:  php 04-operations/tools/foto-mosaico.php /tmp/foto.webp 11
//       php 04-operations/tools/foto-mosaico.php /tmp/foto.webp 12 abajo
//       php 04-operations/tools/foto-mosaico.php /tmp/foto.webp 11 290,638,1150
// corte: arriba, centro (lo normal) o abajo; o "x,y,lado" para un cuadrado puesto a mano
// (desde qué píxel de la izquierda, desde cuál de arriba y de cuánto de lado).
// Cómo se suma al mosaico después: 04-operations/instagram-en-la-landing.md
$corte = $argv[3] ?? 'centro';
if ($argc < 3 || !preg_match('/^[1-9][0-9]*$/', $argv[2]) || !preg_match('/^(arriba|centro|abajo|\d+,\d+,\d+)$/', $corte)) { fwrite(STDERR, "Uso: php foto-mosaico.php <foto original> <número> [arriba|centro|abajo|x,y,lado]\n"); exit(1); }
$bytes = @file_get_contents($argv[1]);
$src = $bytes === false ? false : @imagecreatefromstring($bytes);
if (!$src) { fwrite(STDERR, "No se pudo leer la foto.\n"); exit(1); }
$w = imagesx($src); $h = imagesy($src); $l = min($w, $h);
$x = (int)(($w - $l) / 2); $y = (int)(($h - $l) / 2);
if ($corte === 'arriba') { $y = 0; } elseif ($corte === 'abajo') { $y = $h - $l; }
elseif ($corte !== 'centro') {
    [$x, $y, $l] = array_map('intval', explode(',', $corte));
    if ($x + $l > $w || $y + $l > $h) { fwrite(STDERR, "NO SE ARMÓ NADA: ese corte se sale de la foto, que mide {$w} x {$h}.\n"); exit(1); }
}
if ($l < 1080) { fwrite(STDERR, "NO SE ARMÓ NADA: el cuadrado queda de {$l} px (la foto mide {$w} x {$h}) y hace falta de 1080 o más.\n"); exit(1); }
$dir = __DIR__ . '/../../site/public_html/ig_section';
foreach ([['', 1080, 82], ['-c', 540, 80]] as [$cola, $lado, $calidad]) {
    $f = "$dir/{$argv[2]}$cola.webp";
    if (file_exists($f)) { fwrite(STDERR, "NO SE ARMÓ NADA: ya existe ig_section/{$argv[2]}$cola.webp. Usar el número que sigue.\n"); exit(1); }
}
foreach ([['', 1080, 82], ['-c', 540, 80]] as [$cola, $lado, $calidad]) {
    $dst = imagecreatetruecolor($lado, $lado);
    imagecopyresampled($dst, $src, 0, 0, $x, $y, $lado, $lado, $l, $l);
    $f = "$dir/{$argv[2]}$cola.webp";
    imagewebp($dst, $f, $calidad);
    echo basename($f), "  {$lado}x{$lado}  ", round(filesize($f) / 1024), " KB\n";
}
if ($w !== $h || $l !== $w) echo "Recortada ($corte): de {$w} x {$h} a {$l} x {$l}\n";
