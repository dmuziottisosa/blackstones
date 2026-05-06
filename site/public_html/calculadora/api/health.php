<?php
// ============================================================
// health.php — Health check público (sin auth) para uptime monitoring
// ============================================================
// GET /api/health.php
// Responde 200 OK con status del sistema o 503 si algo crítico falla.
//
// Verifica:
//   - bs-data/ es escribible
//   - clientes/ existe y es escribible
//   - Último cron (si hay log) no es muy viejo
//   - Free disk space razonable
//
// Diseñado para uptime monitors (UptimeRobot, etc) — pinger leve.
// NO devuelve info sensible. NO requiere auth.
// ============================================================

require_once __DIR__ . '/_config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$checks = [];
$failed = false;

// Check 1: bs-data writable
$ok = is_dir(BS_DATA_DIR) && is_writable(BS_DATA_DIR);
$checks['bs_data_writable'] = $ok;
if (!$ok) $failed = true;

// Check 2: clientes writable
$ok = is_dir(BS_CLIENTES_DIR) && is_writable(BS_CLIENTES_DIR);
$checks['clientes_writable'] = $ok;
if (!$ok) $failed = true;

// Check 3: registro writable
$ok = is_dir(BS_REGISTRO_DIR) && is_writable(BS_REGISTRO_DIR);
$checks['registro_writable'] = $ok;
if (!$ok) $failed = true;

// Check 4: cron log existe y es reciente (último run <36h, da margen al cron diario)
$cron_log_age_hours = null;
if (file_exists(BS_REGISTRO_CRON_LOG)) {
    $cron_log_age_hours = round((time() - filemtime(BS_REGISTRO_CRON_LOG)) / 3600, 1);
    $checks['cron_recent'] = $cron_log_age_hours < 36;
    if ($cron_log_age_hours >= 36) $failed = true;
} else {
    // Sin log: cron quizás no corrió todavía. No marcamos failed pero sí lo notamos.
    $checks['cron_recent'] = null;
}

// Check 5: free disk space
$free_bytes = @disk_free_space(BS_DATA_DIR);
$free_mb = $free_bytes !== false ? round($free_bytes / (1024 * 1024)) : null;
$checks['free_disk_mb'] = $free_mb;
if ($free_mb !== null && $free_mb < 100) $failed = true;

http_response_code($failed ? 503 : 200);
echo json_encode([
    'ok' => !$failed,
    'checks' => $checks,
    'cron_log_age_hours' => $cron_log_age_hours,
    'timestamp' => date('c'),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
