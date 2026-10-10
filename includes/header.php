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
  <meta name="theme-color" content="#C5628C">
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
      <img src="<?= e(logo_url()) ?>" alt="Logo <?= e(SITE_NAME) ?>" height="44">
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
  <?= flash_render() ?>
