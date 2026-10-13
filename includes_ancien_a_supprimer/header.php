<?php
require_once __DIR__ . '/functions.php';
$page  = $page  ?? '';
$titre = $titre ?? SITE_NAME;
$fullTitle = $titre === SITE_NAME ? SITE_NAME . ' — ' . SITE_SLOGAN : $titre . ' — ' . SITE_NAME;
$nbPanier = cart_count();
$clientNav = client_connecte();
$liensNav = [
    'accueil'  => ['index.php',    'Accueil'],
    'produits' => ['produits.php', 'Produits'],
    'apropos'  => ['apropos.php',  'À propos'],
    'contact'  => ['contact.php',  'Contact'],
];
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="Hawa Aïcha Boutique : parfums, beauté, sacs, bijoux, voiles, jalabés et décoration. Des produits de qualité à prix accessibles, en gros comme au détail.">
  <meta name="theme-color" content="#F4B6C2">
  <link rel="icon" href="<?= e(base_url('assets/img/logo.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="page-<?= e($page ?: 'autre') ?>">
<nav class="navbar navbar-expand-lg site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(base_url('index.php')) ?>">
      <img class="logo-site" src="<?= e(logo_url()) ?>" alt="Logo <?= e(SITE_NAME) ?>" width="52" height="52">
      <span class="lh-1"><span class="brand-text d-block">Hawa Aïcha</span><span class="brand-sous">Boutique</span></span>
    </a>
    <div class="d-flex align-items-center gap-2 order-lg-last">
      <a class="btn-icone" href="<?= e(base_url($clientNav ? 'mon-compte.php' : 'compte.php')) ?>" aria-label="<?= $clientNav ? 'Mon compte' : 'Se connecter' ?>" title="<?= $clientNav ? 'Mon compte' : 'Se connecter' ?>">
        <i class="fa-regular fa-user"></i><span class="d-none d-xl-inline ms-1"><?= $clientNav ? e(strtok($clientNav['nom_complet'], ' ')) : 'Se connecter' ?></span>
      </a>
      <a class="btn-icone btn-panier position-relative <?= $page === 'panier' ? 'active' : '' ?>" href="<?= e(base_url('panier.php')) ?>" aria-label="Panier" title="Panier">
        <i class="fa-solid fa-bag-shopping"></i><span class="d-none d-xl-inline ms-1">Panier</span>
        <?php if ($nbPanier): ?><span class="cart-badge"><?= $nbPanier ?></span><?php endif; ?>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Menu">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-2">
        <?php foreach ($liensNav as $k => [$href, $label]): ?>
          <li class="nav-item"><a class="nav-link <?= $page === $k ? 'active' : '' ?>" href="<?= e(base_url($href)) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
        <li class="nav-item d-lg-none"><a class="nav-link <?= $page === 'compte' ? 'active' : '' ?>" href="<?= e(base_url($clientNav ? 'mon-compte.php' : 'compte.php')) ?>"><?= $clientNav ? 'Mon compte' : 'Se connecter / Créer un compte' ?></a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4">
  <?php
  // Notifications « Toast » : messages flash de la session + erreurs de validation de la page ($erreurs)
  $toasts = $_SESSION['flash'] ?? [];
  unset($_SESSION['flash']);
  foreach ((isset($erreurs) && is_array($erreurs) ? $erreurs : []) as $er) {
      $toasts[] = ['danger', e($er)];
  }
  $typesToast = [
      'success' => ['fa-circle-check',         'C’est fait !'],
      'danger'  => ['fa-circle-exclamation',   'Oups…'],
      'warning' => ['fa-triangle-exclamation', 'Attention'],
      'info'    => ['fa-circle-info',          'Information'],
  ];
  ?>
  <div class="toast-container site-toasts" id="site-toasts" aria-live="polite" aria-atomic="false">
    <?php foreach ($toasts as [$type, $msg]): [$ico, $libelle] = $typesToast[$type] ?? $typesToast['info']; ?>
      <div class="toast site-toast toast-<?= e($type) ?>" role="<?= $type === 'danger' ? 'alert' : 'status' ?>" data-bs-delay="<?= $type === 'danger' ? 8000 : 5000 ?>">
        <div class="site-toast-corps">
          <span class="site-toast-ic"><i class="fa-solid <?= $ico ?>"></i></span>
          <div class="flex-grow-1" style="min-width:0"><strong class="d-block"><?= $libelle ?></strong><div class="site-toast-msg"><?= $msg ?></div></div>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fermer"></button>
        </div>
        <span class="site-toast-temps"></span>
      </div>
    <?php endforeach; ?>
  </div>
