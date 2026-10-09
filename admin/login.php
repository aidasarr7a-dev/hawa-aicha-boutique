<?php
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Petite temporisation contre les essais en rafale
    $_SESSION['essais'] = ($_SESSION['essais'] ?? 0) + 1;
    if ($_SESSION['essais'] > 5) {
        sleep(2);
    }
    $user = trim($_POST['username'] ?? '');
    $pass = (string)($_POST['password'] ?? '');

    $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $st->execute([$user]);
    $admin = $st->fetch();

    if ($admin && password_verify($pass, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_nom'] = $admin['username'];
        $_SESSION['essais'] = 0;
        redirect('index.php');
    }
    $erreur = 'Identifiant ou mot de passe incorrect.';
}
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Connexion — Administration <?= e(SITE_NAME) ?></title>
  <link rel="icon" href="<?= e(base_url('assets/img/logo.svg')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="container">
  <div class="login-box">
    <div class="text-center mb-4">
      <img src="<?= e(logo_url()) ?>" alt="" height="72">
      <h1 class="h3 mt-2 brand-text">Espace administrateur</h1>
      <p class="text-muted small"><?= e(SITE_NAME) ?></p>
    </div>
    <div class="panel">
      <?php if ($erreur): ?><div class="alert alert-danger rounded-4 py-2"><?= e($erreur) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label" for="username">Identifiant</label><input class="form-control" id="username" name="username" required autofocus autocomplete="username"></div>
        <div class="mb-4"><label class="form-label" for="password">Mot de passe</label><input class="form-control" id="password" name="password" type="password" required autocomplete="current-password"></div>
        <button class="btn btn-rose w-100 btn-lg">Se connecter</button>
      </form>
    </div>
    <div class="text-center mt-3"><a href="<?= e(base_url('index.php')) ?>" class="small">← Retour au site</a></div>
  </div>
</div>
</body>
</html>
