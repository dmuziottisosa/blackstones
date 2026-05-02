<?php
// ============================================================
// auth_config.php — Configuración de autenticación
// BlackStones Marmolería · Calculadora interna
// ============================================================
//
// IMPORTANTE: Este archivo contiene el hash de la contraseña y
// el secret de firmado. NUNCA lo subas a un repositorio público.
//
// Hash bcrypt de la contraseña (no es la contraseña en texto plano,
// es un derivado irreversible). Si alguien lee este archivo, NO puede
// recuperar la contraseña original.
//
// Para generar uno nuevo:
//   php -r "echo password_hash('TU_NUEVA_CONTRASEÑA', PASSWORD_BCRYPT);"
// ============================================================

// Hash de la contraseña 'roma__blue'
const AUTH_PASSWORD_HASH = '$2y$10$JN2bPzF2Efe4diQaZf9Etu2p0PRIZc8.J/wkTfO38BUUE3pDSBx9y';

// Secret para firmar la cookie. Si lo cambiás, todas las cookies activas
// se invalidan (forzás re-login en todos los dispositivos).
// Para regenerarlo: php -r "echo bin2hex(random_bytes(32));"
const AUTH_SECRET = 'cf812bc9a466251112a766a86508169f714abf979ecf54c4d8edb1e1b05c04be';

// Duración de la cookie en segundos (60 días)
const AUTH_COOKIE_LIFETIME = 60 * 24 * 60 * 60;

// Nombre de la cookie
const AUTH_COOKIE_NAME = 'bs_auth';

// Rate limiting: máximos intentos fallidos por IP antes de bloquear
const AUTH_MAX_ATTEMPTS = 5;
// Ventana en segundos del bloqueo (15 min)
const AUTH_LOCKOUT_TIME = 15 * 60;
