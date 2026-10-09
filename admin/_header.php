<?php
require_once __DIR__ . '/_auth.php';
$adminPage = $adminPage ?? '';
$titre = $titre ?? 'Administration';
$liens = [
    'dashboard'  => ['index.php',      'fa-chart-pie',   'Tableau de bord'],
    'produits'   => ['produits.php',   'fa-box-open',    'Produits'],
    'categories' => ['categories.php', 'fa-layer-group', 'Catégories'],
    'commandes'  => ['commandes.php',  'fa-receipt',     'Commandes'],
    'paiements'  => ['paiements.php',  'fa-wallet',      'Paiements'],
    'clients'    => ['clients.php',    'fa-users',       'Clients'],
    'messages'   => ['messages.php',   'fa-envelope',    'Messages'],
];
$nbMessagesNonLus = (int)db()->query('SELECT COUNT(*) FROM messages_contact WHERE lu = 0')->fetchColumn();
$nbPaiementsDeclares = (int)db()->query("SELECT COUNT(*) FROM paiements WHERE statut_paiement = 'en_attente' AND date_declaration IS NOT NULL")->fetchColumn();
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($titre) ?> — Administration <?= e(SITE_NAME) ?></title>
  <link rel="icon" href="<?= e(base_url('assets/img/logo.svg')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-xl admin-nav sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <img src="<?= e(logo_url()) ?>" alt="" height="36"><span class="brand-text fs-5">Administration</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#amenu" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <div class="collapse navbar-collapse" id="amenu">
      <ul class="navbar-nav me-auto gap-xl-1">
        <?php foreach ($liens as $k => [$href, $ico, $txt]): ?>
          <?php $pastille = ['messages' => $nbMessagesNonLus, 'paiements' => $nbPaiementsDeclares][$k] ?? 0; ?>
          <li class="nav-item"><a class="nav-link <?= $adminPage === $k ? 'active' : '' ?>" href="<?= $href ?>"><i class="fa-solid <?= $ico ?> me-1"></i><?= $txt ?><?php if ($pastille): ?> <span class="badge rounded-pill text-bg-danger"><?= $pastille ?></span><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-rose btn-sm" href="<?= e(base_url('index.php')) ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Voir le site</a>
        <a class="btn btn-outline-rose btn-sm" href="motdepasse.php"><i class="fa-solid fa-key me-1"></i>Mot de passe</a>
        <form method="post" action="logout.php" class="d-inline"><?= csrf_field() ?><button class="btn btn-rose btn-sm"><i class="fa-solid fa-right-from-bracket me-1"></i>Déconnexion</button></form>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4">
  <?= flash_render() ?>
