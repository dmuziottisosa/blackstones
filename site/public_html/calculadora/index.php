<?php
// ============================================================
// index.php — Entry point de la calculadora
// Si auth válida → carga la calculadora.
// Si no → redirige al login.
// ============================================================

require_once __DIR__ . '/auth_check.php';

// Sin sesión va al login; un rol que no usa la calculadora va a su primera pantalla.
auth_exigir_pagina('calculadora');

// Auth OK → servir el HTML de la calculadora.
// Lo llamamos calc.html para no chocar con index.php
$calcPath = __DIR__ . '/calc.html';

if (!file_exists($calcPath)) {
    http_response_code(500);
    echo 'Error de configuración: falta calc.html';
    exit;
}

// Headers anti-cache para que cambios siempre se reflejen
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=UTF-8');

// calc.html es un archivo fijo: el menú y el nombre de quien entró se le ponen acá,
// para que sea el mismo menú por rol que en el resto del sistema.
$html = (string)file_get_contents($calcPath);
$html = preg_replace_callback('~<nav class="h-tabs">.*?</nav>~s', function () { return auth_nav_html('calculadora'); }, $html, 1) ?? $html;
$html = str_replace('<span>blackstones.com.ar</span>', auth_quien_html(), $html);
echo $html;
