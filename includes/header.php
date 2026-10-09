<?php
require_once __DIR__ . '/functions.php';
$page  = $page  ?? '';
$titre = $titre ?? SITE_NAME;
$fullTitle = $titre === SITE_NAME ? SITE_NAME . ' — ' . SITE_SLOGAN : $titre . ' — ' . SITE_NAME;
$nbPanier = cart_count();
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="Hawa Aïcha Boutique : parfums, beauté, sacs, voiles et jalabés. Des produits de qualité à prix accessibles, en gros comme au détail, à commander depuis votre téléphone.">
  <meta name="theme-color" content="#C5628C">
  <link rel="icon" href="<?= e(base_url('assets/img/logo.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-md site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(base_url('index.php')) ?>">
      <img src="<?= e(logo_url()) ?>" alt="Logo <?= e(SITE_NAME) ?>" height="42">
      <span class="brand-text">Hawa Aïcha</span>
    </a>
    <div class="d-flex align-items-center gap-2 order-md-last">
      <a class="btn btn-cart position-relative d-md-none" href="<?= e(base_url('panier.php')) ?>" aria-label="Panier">
        <i class="fa-solid fa-bag-shopping"></i>
        <?php if ($nbPanier): ?><span class="cart-badge"><?= $nbPanier ?></span><?php endif; ?>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Menu">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav mx-auto mb-2 mb-md-0 gap-md-2">
        <li class="nav-item"><a class="nav-link <?= $page === 'accueil' ? 'active' : '' ?>" href="<?= e(base_url('index.php')) ?>">Accueil</a></li>
        <li class="nav-item"><a class="nav-link <?= $page === 'produits' ? 'active' : '' ?>" href="<?= e(base_url('produits.php')) ?>">Produits</a></li>
        <li class="nav-item"><a class="nav-link <?= $page === 'apropos' ? 'active' : '' ?>" href="<?= e(base_url('apropos.php')) ?>">À propos</a></li>
        <li class="nav-item"><a class="nav-link <?= $page === 'contact' ? 'active' : '' ?>" href="<?= e(base_url('contact.php')) ?>">Contact</a></li>
        <li class="nav-item"><a class="nav-link d-md-none <?= $page === 'panier' ? 'active' : '' ?>" href="<?= e(base_url('panier.php')) ?>">Panier</a></li>
      </ul>
      <a class="btn btn-cart position-relative d-none d-md-inline-flex align-items-center gap-2 <?= $page === 'panier' ? 'active' : '' ?>" href="<?= e(base_url('panier.php')) ?>">
        <i class="fa-solid fa-bag-shopping"></i> Panier
        <?php if ($nbPanier): ?><span class="cart-badge"><?= $nbPanier ?></span><?php endif; ?>
      </a>
    </div>
  </div>
</nav>
<main class="container py-4">
  <?= flash_render() ?>
