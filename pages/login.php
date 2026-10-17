<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';

$error = '';
$return = isset($_GET['return']) ? (string)$_GET['return'] : (string)($_POST['return'] ?? '');
if ($return === '' || strpos($return, '/') !== 0 || strpos($return, '//') === 0) {
    $return = '../index.php';
}

if (!auth_is_enabled()) {
    header('Location: ' . $return);
    exit;
}

if (!empty($_SESSION['authenticated'])) {
    header('Location: ' . $return);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $user = (string)($_POST['user'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $configuredUser = env('AUTH_USER');
    $configuredHash = env('AUTH_PASSWORD_HASH');

    if ($configuredUser === '' || $configuredHash === '') {
        $error = 'La autenticación está activada, pero falta configurarla.';
    } elseif (hash_equals($configuredUser, $user) && password_verify($password, $configuredHash)) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['auth_user'] = $configuredUser;
        header('Location: ' . $return);
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso - Control Gasolina</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <link href="../css/main.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width: 30rem;">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h3 mb-4">Acceso</h1>
      <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="return" value="<?php echo e($return); ?>">
        <div class="mb-3">
          <label class="form-label" for="user">Usuario</label>
          <input class="form-control" id="user" name="user" required autocomplete="username">
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Contraseña</label>
          <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary" type="submit">Entrar</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>
