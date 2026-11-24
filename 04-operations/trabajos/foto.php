<?php
// Prepara la foto de un trabajo para la página /trabajos/.
// De la foto original (PNG o JPG, como la sube el dueño) saca dos copias WebP cuadradas o
// del formato que traiga: de 1440, 1080 y 720 px de lado mayor, y una miniatura de 160 px.
// (1080 es la que piden los celulares; 720, la computadora con tres columnas.)
//
// Uso:   php 04-operations/trabajos/foto.php <foto original> <nombre>
// Ej.:   php 04-operations/trabajos/foto.php "/tmp/1934 GRIS MARA.png" gris-mara-1
// Deja:  site/public_html/trabajos/f/gris-mara-1-1440.webp, -1080.webp, -720.webp y -160.webp,
//        y gris-mara-1-og.jpg (1200 x 630): la imagen que muestra WhatsApp cuando se manda el link.
//
// El nombre es el material y un número de orden. NO lleva el N° de presupuesto: el archivo es público.
if ($argc < 3 || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $argv[2])) { fwrite(STDERR, "Uso: php foto.php <foto original> <nombre-en-minusculas-con-guiones>\n"); exit(1); }
$bytes = @file_get_contents($argv[1]);
$info = $bytes === false ? false : @getimagesizefromstring($bytes);
if (!$info) { fwrite(STDERR, "No se pudo leer la foto.\n"); exit(1); }
$src = imagecreatefromstring($bytes);
$w = $info[0]; $h = $info[1];
$dir = __DIR__ . '/../../site/public_html/trabajos/f';
if (!is_dir($dir)) mkdir($dir, 0755, true);
foreach ([1440, 1080, 720, 160] as $lado) {
    $e = min(1, $lado / max($w, $h));
    $nw = (int)round($w * $e); $nh = (int)round($h * $e);
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $f = "$dir/{$argv[2]}-$lado.webp";
    imagewebp($dst, $f, 80);
    echo basename($f), "  {$nw}x{$nh}  ", round(filesize($f) / 1024), " KB\n";
}
// La vista previa del link: una franja apaisada de 1200 x 630, tomada un poco por debajo del
// centro de la foto, que es donde suele estar la mesada. En JPG, que es lo que WhatsApp muestra seguro.
$fw = $w; $fh = (int)round($w * 630 / 1200);
if ($fh > $h) { $fh = $h; $fw = (int)round($h * 1200 / 630); }
$fx = (int)(($w - $fw) / 2); $fy = (int)max(0, min($h - $fh, $h * 0.55 - $fh / 2));
$dst = imagecreatetruecolor(1200, 630);
imagecopyresampled($dst, $src, 0, 0, $fx, $fy, 1200, 630, $fw, $fh);
imageinterlace($dst, true);
$f = "$dir/{$argv[2]}-og.jpg";
imagejpeg($dst, $f, 82);
echo basename($f), "  1200x630  ", round(filesize($f) / 1024), " KB\n";
