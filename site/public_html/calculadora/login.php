<?php
require_once __DIR__ . '/auth_check.php';

// Si ya está autenticado, redirigir a la calculadora
if (auth_is_valid()) {
    header('Location: index.php');
    exit;
}

$error = '';
$lockoutSeconds = auth_check_lockout();

// Procesar form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockoutSeconds === 0) {
    $password = $_POST['password'] ?? '';

    if (auth_check_password($password)) {
        auth_reset_attempts();
        auth_login();
        header('Location: index.php');
        exit;
    } else {
        auth_record_failure();
        $error = 'Contraseña incorrecta.';
        $lockoutSeconds = auth_check_lockout();
    }
}

$lockoutMinutes = $lockoutSeconds > 0 ? ceil($lockoutSeconds / 60) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>BlackStones · Acceso</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{height:100%}
  body{
    font-family:'Manrope',sans-serif;
    background:radial-gradient(ellipse at top,#2A2522 0%,#1A1816 70%);
    color:#FAF8F4;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 24px;
    min-height:100vh;
  }
  .auth-shell{
    width:100%;
    max-width:380px;
    display:flex;
    flex-direction:column;
    align-items:center;
  }
  .auth-mark{
    width:96px;height:96px;
    display:block;
    object-fit:contain;
    margin-bottom:4px;
    filter:drop-shadow(0 6px 18px rgba(196,167,125,.32));
  }
  .auth-brand-name{
    font-family:'Fraunces',serif;
    font-size:1.45rem;
    font-weight:600;
    color:#FAF8F4;
    letter-spacing:.01em;
    text-align:center;
    margin-bottom:6px;
  }
  .auth-brand-name .heavy{font-weight:700}
  .auth-brand-name .thin{font-weight:300;color:#C4A77D}
  .auth-brand-sub{
    font-size:.65rem;
    color:#A89580;
    letter-spacing:.22em;
    text-transform:uppercase;
    font-weight:600;
    text-align:center;
    margin-bottom:32px;
  }
  .auth-card{
    background:#FAF8F4;
    color:#1A1816;
    border-radius:12px;
    padding:32px 36px 28px;
    width:100%;
    box-shadow:0 24px 60px rgba(0,0,0,.4);
    position:relative;
    overflow:hidden;
  }
  .auth-card::before{
    content:'';
    position:absolute;
    top:0;left:0;right:0;
    height:3px;
    background:linear-gradient(90deg,#C4A77D 0%,#A89580 50%,#C4A77D 100%);
  }
  label{
    display:block;
    font-size:.68rem;
    font-weight:600;
    color:#7A6649;
    text-transform:uppercase;
    letter-spacing:.1em;
    margin-bottom:8px;
  }
  input[type=password]{
    width:100%;
    font-family:'Manrope',sans-serif;
    font-size:1rem;
    font-weight:500;
    padding:12px 14px;
    border:1px solid #D4CFC6;
    border-radius:7px;
    background:#FFFFFF;
    color:#1A1816;
    outline:none;
    transition:border-color .15s,box-shadow .15s;
    letter-spacing:.04em;
  }
  input[type=password]:focus{
    border-color:#C4A77D;
    box-shadow:0 0 0 3px rgba(196,167,125,.18);
  }
  button{
    width:100%;
    margin-top:18px;
    font-family:'Manrope',sans-serif;
    font-size:.82rem;
    font-weight:600;
    padding:13px;
    border:none;
    border-radius:7px;
    background:#1A1816;
    color:#FAF8F4;
    cursor:pointer;
    transition:background .2s,transform .12s;
    letter-spacing:.05em;
  }
  button:hover:not(:disabled){background:#2A2522;transform:translateY(-1px)}
  button:disabled{opacity:.5;cursor:not-allowed}
  .err{
    margin-top:14px;
    padding:10px 12px;
    background:#FDF1F1;
    border-left:3px solid #C44747;
    border-radius:4px;
    font-size:.78rem;
    color:#7A2929;
  }
  .lock{
    margin-top:14px;
    padding:10px 12px;
    background:#FDF6E7;
    border-left:3px solid #C4A77D;
    border-radius:4px;
    font-size:.78rem;
    color:#7A6649;
  }
  .auth-foot{
    margin-top:20px;
    text-align:center;
    font-size:.66rem;
    color:#A89580;
    letter-spacing:.04em;
    font-style:italic;
  }
  @media(max-width:420px){
    .auth-mark{width:80px;height:80px;margin-bottom:2px}
    .auth-brand-name{font-size:1.25rem}
    .auth-brand-sub{margin-bottom:24px}
    .auth-card{padding:28px 22px 24px}
  }
</style>
</head>
<body>

<div class="auth-shell">
  <img src="../logo/logo_isotipo_crema.png" alt="BlackStones" class="auth-mark">
  <div class="auth-brand-name"><span class="heavy">Black</span><span class="thin">Stones</span></div>
  <div class="auth-brand-sub">Acceso interno</div>

  <form class="auth-card" method="POST" autocomplete="off">
    <label for="password">Contraseña</label>
    <input
      type="password"
      name="password"
      id="password"
      autofocus
      required
      autocomplete="current-password"
      <?= $lockoutSeconds > 0 ? 'disabled' : '' ?>
    >

    <button type="submit" <?= $lockoutSeconds > 0 ? 'disabled' : '' ?>>
      Ingresar
    </button>

    <?php if ($error && $lockoutSeconds === 0): ?>
      <div class="err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($lockoutSeconds > 0): ?>
      <div class="lock">
        Demasiados intentos fallidos. Probá de nuevo en <?= $lockoutMinutes ?> min.
      </div>
    <?php endif; ?>

    <p class="auth-foot">Sesión recordada por 60 días</p>
  </form>
</div>

</body>
</html>
