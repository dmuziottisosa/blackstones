<?php
// Prepara la foto de un trabajo para la página /trabajos/.
// De la foto original (PNG o JPG, como la sube el dueño) saca cuatro copias WebP cuadradas:
// de 1440, 1080 y 720 px de lado, y una miniatura de 160 px.
// (1080 es la que piden los celulares; 720, la computadora con tres columnas.)
//
// El estándar (estandar.md): la foto llega cuadrada, 1:1, y de 1440 px de lado o más. El dueño,
// 24/11/2026: "pasé las imágenes en 1:1". Si no es cuadrada o es chica, esto no arma nada y avisa.
//
// Uso:   php 04-operations/trabajos/foto.php <foto original> <nombre> [recortar|agrandar]
// Ej.:   php 04-operations/trabajos/foto.php "/tmp/1934 GRIS MARA.png" gris-mara-1
//        "recortar": si la foto no es cuadrada, la corta al centro y sigue. Solo si el dueño lo pidió:
//        lo que queda afuera no se ve más.
//        "agrandar": si la foto mide menos de 1440 px, la agranda y sigue. Es para la foto que llegó
//        por el chat, que la achica a 1024 px, cuando no hay original: las copias de 1440 y de 1080
//        salen menos nítidas. Anotar en README.md que falta la original, y rehacerla cuando llegue.
// Deja:  site/public_html/trabajos/f/gris-mara-1-1440.webp, -1080.webp, -720.webp y -160.webp,
//        y gris-mara-1-og.jpg (1200 x 630): la imagen que muestra WhatsApp cuando se manda el link.
//
// El nombre es el material y un número de orden. NO lleva el N° de presupuesto: el archivo es público.
if ($argc < 3 || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $argv[2]) || ($argc > 3 && !in_array($argv[3], ['recortar', 'agrandar'], true))) { fwrite(STDERR, "Uso: php foto.php <foto original> <nombre-en-minusculas-con-guiones> [recortar]\n"); exit(1); }
$bytes = @file_get_contents($argv[1]);
$info = $bytes === false ? false : @getimagesizefromstring($bytes);
if (!$info) { fwrite(STDERR, "No se pudo leer la foto.\n"); exit(1); }
$src = imagecreatefromstring($bytes);
$w = $info[0]; $h = $info[1];
const LADO_MINIMO = 1440;
// cuadrada: hasta un 1% de diferencia entre el ancho y el alto se empareja sola, cortando esos
// pocos píxeles al centro; con más diferencia, no es una foto cuadrada
if ($w !== $h) {
    if (abs($w - $h) > 0.01 * max($w, $h) && ($argv[3] ?? '') !== 'recortar') {
        fwrite(STDERR, "NO SE ARMÓ NADA: la foto mide {$w} x {$h} y el estándar es cuadrada, 1:1. Pedirle al dueño la cuadrada; si dice que se recorte, agregar \"recortar\" al final.\n"); exit(1);
    }
    $lado = min($w, $h);
    $cuad = imagecreatetruecolor($lado, $lado);
    imagefill($cuad, 0, 0, imagecolorallocate($cuad, 255, 255, 255));
    imagecopy($cuad, $src, 0, 0, (int)(($w - $lado) / 2), (int)(($h - $lado) / 2), $lado, $lado);
    echo (abs($w - $h) > 0.01 * max($w, $h) ? 'Recortada al centro' : 'Emparejada'), ": de {$w} x {$h} a {$lado} x {$lado}\n";
    $src = $cuad; $w = $h = $lado;
}
$agrandar = ($argv[3] ?? '') === 'agrandar';
if (min($w, $h) < LADO_MINIMO) {
    if (!$agrandar) { fwrite(STDERR, "NO SE ARMÓ NADA: la foto mide {$w} x {$h} y hace falta de " . LADO_MINIMO . " px de lado o más (las del dueño vienen de 2048). Pedirle la original; si no la hay, agregar \"agrandar\" al final.\n"); exit(1); }
    echo "Agrandada: la foto mide {$w} x {$h}; las copias más grandes salen menos nítidas. Falta la original.\n";
}
$dir = __DIR__ . '/../../site/public_html/trabajos/f';
if (!is_dir($dir)) mkdir($dir, 0755, true);
foreach ([1440, 1080, 720, 160] as $lado) {
    $e = $agrandar ? $lado / max($w, $h) : min(1, $lado / max($w, $h));
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
