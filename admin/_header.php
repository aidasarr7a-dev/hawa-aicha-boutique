<?php
require_once __DIR__ . '/_auth.php';
$adminPage = $adminPage ?? '';
$titre = $titre ?? 'Administration';
// Rubriques du menu latéral, regroupées par thème : clé => [lien, icône, intitulé]
$sections = [
    'Boutique' => [
        'dashboard'  => ['index.php',      'fa-chart-pie',   'Tableau de bord'],
        'produits'   => ['produits.php',   'fa-box-open',    'Produits'],
        'categories' => ['categories.php', 'fa-layer-group', 'Catégories'],
    ],
    'Ventes' => [
        'commandes'  => ['commandes.php',  'fa-receipt',     'Commandes'],
        'paiements'  => ['paiements.php',  'fa-wallet',      'Paiements'],
        'clients'    => ['clients.php',    'fa-users',       'Clients'],
    ],
    'Échanges' => [
        'messages'   => ['messages.php',   'fa-envelope',    'Messages'],
    ],
    'Mon compte' => [
        'profil'     => ['profil.php',     'fa-user-gear',   'Mon profil'],
    ],
];
// Gestion des comptes : rubrique visible uniquement pour l'administrateur principal (la page vérifie aussi le rôle)
if (est_principal()) {
    $sections['Mon compte']['admins'] = ['administrateurs.php', 'fa-users-gear', 'Administrateurs'];
}
$nbMessagesNonLus = (int)db()->query('SELECT COUNT(*) FROM messages_contact WHERE lu = 0')->fetchColumn();
$nbPaiementsDeclares = (int)db()->query("SELECT COUNT(*) FROM paiements WHERE statut_paiement = 'en_attente' AND date_declaration IS NOT NULL")->fetchColumn();
$pastilles = ['messages' => $nbMessagesNonLus, 'paiements' => $nbPaiementsDeclares];
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
  <link href="<?= e(base_url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-sidebar offcanvas-lg offcanvas-start" id="asidebar" tabindex="-1" aria-label="Menu de l'administration">
    <div class="admin-brand">
      <a class="d-flex align-items-center gap-2" href="index.php">
        <img src="<?= e(logo_url()) ?>" alt="" height="38">
        <span><span class="brand-text d-block">Hawa Aïcha</span><small>Administration</small></span>
      </a>
      <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#asidebar" aria-label="Fermer le menu"></button>
    </div>
    <nav class="admin-menu">
      <?php foreach ($sections as $section => $liens): ?>
        <div class="admin-menu-titre"><?= e($section) ?></div>
        <ul class="list-unstyled mb-0">
          <?php foreach ($liens as $k => [$href, $ico, $txt]): $pastille = $pastilles[$k] ?? 0; ?>
            <li><a class="admin-menu-lien <?= $adminPage === $k ? 'active' : '' ?>" href="<?= $href ?>"<?= $adminPage === $k ? ' aria-current="page"' : '' ?>>
              <i class="fa-solid <?= $ico ?>"></i><span><?= $txt ?></span><?php if ($pastille): ?><span class="admin-pastille"><?= $pastille ?></span><?php endif; ?>
            </a></li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-pied">
      <a class="admin-connecte" href="profil.php" title="Mon profil">
        <span class="admin-connecte-avatar"><?= e(mb_strtoupper(mb_substr($_SESSION['admin_nom'] ?? 'A', 0, 1))) ?></span>
        <span class="min-w-0"><strong class="d-block text-truncate"><?= e($_SESSION['admin_nom'] ?? '') ?></strong><small><?= e(ROLES_ADMIN[$adminConnecte['role']][0] ?? '') ?></small></span>
      </a>
      <a class="admin-menu-lien" href="<?= e(base_url('index.php')) ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>Voir le site</span></a>
      <form method="post" action="logout.php"><?= csrf_field() ?><button class="admin-menu-lien admin-deconnexion"><i class="fa-solid fa-right-from-bracket"></i><span>Déconnexion</span></button></form>
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar d-lg-none">
      <button class="admin-burger" type="button" data-bs-toggle="offcanvas" data-bs-target="#asidebar" aria-controls="asidebar" aria-label="Ouvrir le menu"><i class="fa-solid fa-bars"></i></button>
      <a class="d-flex align-items-center gap-2" href="index.php"><img src="<?= e(logo_url()) ?>" alt="" height="30"><span class="brand-text">Administration</span></a>
      <?php if ($nbMessagesNonLus + $nbPaiementsDeclares): ?><span class="admin-pastille ms-auto" title="Éléments à traiter"><?= $nbMessagesNonLus + $nbPaiementsDeclares ?></span><?php endif; ?>
    </header>
    <main class="admin-content">
      <?php
      // Notifications « Toast » : messages flash de la session + erreurs de validation de la page ($erreurs)
      $toasts = $_SESSION['flash'] ?? [];
      unset($_SESSION['flash']);
      foreach (($erreurs ?? []) as $er) {
          $toasts[] = ['danger', e($er)];
      }
      $typesToast = [
          'success' => ['fa-circle-check',         'Succès'],
          'danger'  => ['fa-circle-exclamation',   'Erreur'],
          'warning' => ['fa-triangle-exclamation', 'Attention'],
          'info'    => ['fa-circle-info',          'Information'],
      ];
      ?>
      <div class="toast-container admin-toasts" id="admin-toasts" aria-live="polite" aria-atomic="false">
        <?php foreach ($toasts as [$type, $msg]): [$ico, $libelle] = $typesToast[$type] ?? $typesToast['info']; ?>
          <div class="toast admin-toast toast-<?= e($type) ?>" role="<?= $type === 'danger' ? 'alert' : 'status' ?>" data-bs-delay="<?= $type === 'danger' ? 8000 : 5000 ?>">
            <div class="admin-toast-corps">
              <span class="admin-toast-ic"><i class="fa-solid <?= $ico ?>"></i></span>
              <div class="flex-grow-1 min-w-0"><strong class="d-block"><?= $libelle ?></strong><div class="admin-toast-msg"><?= $msg ?></div></div>
              <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fermer"></button>
            </div>
            <span class="admin-toast-temps"></span>
          </div>
        <?php endforeach; ?>
      </div>
