<?php
// ============================================================
// mark-viewed.php — Marcar un mes como "visto" (oculta el banner)
// ============================================================
// POST /api/mark-viewed.php
// Body JSON: { "mes": "2026-05" }
//
// Crea (touch) bs-data/registro/viewed/{YYYY-MM}.flag
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
$mes = $body['mes'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $mes)) bs_error('Formato de mes inválido (YYYY-MM)');

$flag = BS_REGISTRO_VIEWED_DIR . '/' . $mes . '.flag';
if (!is_dir(BS_REGISTRO_VIEWED_DIR)) @mkdir(BS_REGISTRO_VIEWED_DIR, 0755, true);
@touch($flag);

bs_ok(['mes' => $mes]);
