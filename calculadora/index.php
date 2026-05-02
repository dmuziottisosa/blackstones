<?php
// ============================================================
// index.php — Entry point de la calculadora
// Si auth válida → carga la calculadora.
// Si no → redirige al login.
// ============================================================

require_once __DIR__ . '/auth_check.php';

if (!auth_is_valid()) {
    header('Location: login.php');
    exit;
}

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

readfile($calcPath);
