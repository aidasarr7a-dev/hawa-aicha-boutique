<?php
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Limitation des essais : 5 échecs par adresse IP sur 15 minutes (enregistrés en base)
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
    $pdo = db();
    $pdo->exec('DELETE FROM tentatives_connexion WHERE date_tentative < NOW() - INTERVAL 1 DAY');
    $nb = $pdo->prepare('SELECT COUNT(*) FROM tentatives_connexion WHERE ip = ? AND date_tentative > NOW() - INTERVAL 15 MINUTE');
    $nb->execute([$ip]);

    if ((int)$nb->fetchColumn() >= 5) {
        $erreur = 'Trop de tentatives. Merci de patienter 15 minutes avant de réessayer.';
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = (string)($_POST['password'] ?? '');

        // Connexion avec l'identifiant ou l'adresse e-mail
        $st = $pdo->prepare('SELECT * FROM admins WHERE username = ? OR (email IS NOT NULL AND email = ?) LIMIT 1');
        $st->execute([$user, $user]);
        $admin = $st->fetch();

        $motDePasseOk = $admin && password_verify($pass, $admin['password_hash']);
        if ($motDePasseOk && !(int)$admin['actif']) {
            // Mot de passe correct mais compte désactivé par l'administrateur principal
            $erreur = 'Ce compte est désactivé. Contactez l\'administrateur principal de la boutique.';
        } elseif ($motDePasseOk) {
            $pdo->prepare('DELETE FROM tentatives_connexion WHERE ip = ?')->execute([$ip]);
            $pdo->prepare('UPDATE admins SET derniere_connexion = NOW() WHERE id = ?')->execute([$admin['id']]);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_nom'] = $admin['nom'] ?: $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            redirect('index.php');
        }
        if ($erreur === '') {
            $pdo->prepare('INSERT INTO tentatives_connexion (ip) VALUES (?)')->execute([$ip]);
            $erreur = 'Identifiant ou mot de passe incorrect.';
        }
    }
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
  <link href="<?= e(base_url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-login">
  <div class="login-box">
    <div class="text-center mb-4">
      <img src="<?= e(logo_url()) ?>" alt="" height="72">
      <h1 class="h3 mt-2 brand-text">Espace administrateur</h1>
      <p class="text-muted small"><?= e(SITE_NAME) ?></p>
    </div>
    <div class="panel">
      <?= flash_render() ?>
      <?php if ($erreur): ?><div class="alert alert-danger rounded-4 py-2"><?= e($erreur) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label" for="username">Identifiant ou e-mail</label><input class="form-control" id="username" name="username" required autofocus autocomplete="username"></div>
        <div class="mb-4"><label class="form-label" for="password">Mot de passe</label><input class="form-control" id="password" name="password" type="password" required autocomplete="current-password"></div>
        <button class="btn btn-rose w-100 btn-lg">Se connecter</button>
      </form>
    </div>
    <div class="text-center mt-3"><a href="<?= e(base_url('index.php')) ?>" class="small">← Retour au site</a></div>
  </div>
</div>
</body>
</html>
