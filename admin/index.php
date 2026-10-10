<?php
$adminPage = 'dashboard';
$titre = 'Tableau de bord';
require __DIR__ . '/_header.php';

$pdo = db();
$nbProduits   = (int)$pdo->query('SELECT COUNT(*) FROM produits')->fetchColumn();
$nbRupture    = (int)$pdo->query('SELECT COUNT(*) FROM produits WHERE actif = 1 AND stock <= 0')->fetchColumn();
$nbCommandes  = (int)$pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn();
$nbNouvelles  = (int)$pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'nouvelle'")->fetchColumn();
$attente      = $pdo->query("SELECT COUNT(*) AS nb, COALESCE(SUM(pa.montant), 0) AS montant
                               FROM paiements pa JOIN commandes c ON c.id = pa.commande_id
                              WHERE pa.statut_paiement = 'en_attente' AND c.statut <> 'annulee'")->fetch();
// Ventes réellement encaissées : uniquement les paiements confirmés « Payé »
$ventesPayees = (int)$pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut_paiement = 'paye'")->fetchColumn();

$dernieres = $pdo->query('SELECT c.*, pa.statut_paiement FROM commandes c LEFT JOIN paiements pa ON pa.commande_id = c.id ORDER BY c.id DESC LIMIT 8')->fetchAll();
$faibles = $pdo->prepare('SELECT id, nom, stock FROM produits WHERE actif = 1 AND stock <= ? ORDER BY stock, nom LIMIT 8');
$faibles->execute([SEUIL_STOCK]);
$faibles = $faibles->fetchAll();

// Coordonnées encore à compléter dans config.php
$manquants = array_keys(array_filter([
    'e-mail de contact' => CONTACT_EMAIL,
    'zone de livraison' => ZONE_LIVRAISON,
    'numéro WhatsApp'   => WHATSAPP_NUMBER,
    'numéro Wave'       => WAVE_NUMERO,
    'numéro Orange Money' => ORANGE_MONEY_NUMERO,
], fn($v) => trim($v) === ''));
?>

<div class="admin-head">
  <div>
    <h1 class="page-title">Tableau de bord</h1>
    <p class="admin-sous">Vue d'ensemble de la boutique au <?= e(date('d/m/Y')) ?>.</p>
  </div>
  <div class="admin-head-actions">
    <a href="produit_form.php" class="btn btn-rose"><i class="fa-solid fa-plus me-1"></i>Ajouter un produit</a>
  </div>
</div>

<?php if ($nbPaiementsDeclares || $nbMessagesNonLus): ?>
  <div class="admin-alertes">
    <?php if ($nbPaiementsDeclares): ?><a class="alerte-admin" href="paiements.php?statut=en_attente"><i class="fa-solid fa-hand-holding-dollar"></i> <?= $nbPaiementsDeclares ?> paiement(s) déclaré(s) par des clientes à vérifier</a><?php endif; ?>
    <?php if ($nbMessagesNonLus): ?><a class="alerte-admin" href="messages.php?filtre=non_lus"><i class="fa-solid fa-envelope"></i> <?= $nbMessagesNonLus ?> nouveau(x) message(s)</a><?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($manquants): ?>
  <div class="admin-info"><i class="fa-solid fa-circle-info"></i>
    <span>Informations à compléter dans <code>config.php</code> : <?= e(implode(', ', $manquants)) ?>.</span></div>
<?php endif; ?>

<!-- Indicateurs principaux : ce qui demande une action en premier -->
<div class="row g-3 g-lg-4 mb-3 mb-lg-4">
  <div class="col-6 col-xl-3">
    <a class="stat-card" href="commandes.php?statut=nouvelle">
      <span class="stat-ic"><i class="fa-solid fa-bell"></i></span>
      <span class="stat-label">Nouvelles commandes</span>
      <span class="num"><?= $nbNouvelles ?></span>
      <span class="stat-sous">à préparer</span>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a class="stat-card" href="paiements.php?statut=en_attente">
      <span class="stat-ic ic-alerte"><i class="fa-solid fa-hourglass-half"></i></span>
      <span class="stat-label">Paiements en attente</span>
      <span class="num"><?= (int)$attente['nb'] ?></span>
      <span class="stat-sous"><?= fcfa($attente['montant']) ?></span>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a class="stat-card" href="paiements.php?statut=paye">
      <span class="stat-ic ic-ok"><i class="fa-solid fa-sack-dollar"></i></span>
      <span class="stat-label">Ventes payées</span>
      <span class="num num-montant"><?= fcfa($ventesPayees) ?></span>
      <span class="stat-sous">paiements confirmés</span>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a class="stat-card" href="produits.php?stock=epuise">
      <span class="stat-ic"><i class="fa-solid fa-box-open"></i></span>
      <span class="stat-label">Ruptures de stock</span>
      <span class="num"><?= $nbRupture ?></span>
      <span class="stat-sous">produit(s) épuisé(s)</span>
    </a>
  </div>
</div>

<!-- Indicateurs secondaires -->
<div class="row g-3 g-lg-4 mb-4 mb-lg-5">
  <div class="col-6 col-xl-3">
    <a class="stat-card stat-card-secondaire" href="commandes.php">
      <span class="stat-label">Commandes au total</span>
      <span class="num"><?= $nbCommandes ?></span>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a class="stat-card stat-card-secondaire" href="produits.php">
      <span class="stat-label">Produits au catalogue</span>
      <span class="num"><?= $nbProduits ?></span>
    </a>
  </div>
</div>

<div class="row g-4">
  <div class="col-xxl-8">
    <div class="panel panel-table">
      <div class="panel-entete">
        <h2 class="h5">Dernières commandes</h2>
        <a href="commandes.php">Tout voir <i class="fa-solid fa-arrow-right ms-1"></i></a>
      </div>
      <?php if ($dernieres): ?>
      <div class="table-responsive">
        <table class="table table-rose align-middle">
          <thead><tr><th>N°</th><th>Client</th><th>Total</th><th>Commande</th><th>Paiement</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($dernieres as $c): ?>
            <tr>
              <td class="text-nowrap cellule-principale"><?= e($c['numero_commande']) ?></td>
              <td><?= e($c['nom_complet']) ?><div class="small text-muted text-nowrap"><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></div></td>
              <td class="text-nowrap fw-medium"><?= fcfa($c['total']) ?></td>
              <td><?= badge_statut($c['statut']) ?></td>
              <td><?= badge_paiement($c['statut_paiement']) ?></td>
              <td class="text-end"><a class="btn btn-outline-rose btn-sm" href="commande.php?id=<?= (int)$c['id'] ?>">Voir</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="admin-vide"><i class="fa-solid fa-receipt"></i>Aucune commande pour le moment.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-xxl-4">
    <div class="panel">
      <div class="panel-entete">
        <h2 class="h5">À réapprovisionner</h2>
        <?php if ($faibles): ?><a href="produits.php?stock=faible">Tout voir</a><?php endif; ?>
      </div>
      <?php if ($faibles): ?>
        <div class="liste-admin">
          <?php foreach ($faibles as $f): ?>
            <a href="produit_form.php?id=<?= (int)$f['id'] ?>">
              <span><?= e($f['nom']) ?></span>
              <span class="badge rounded-pill text-nowrap <?= (int)$f['stock'] <= 0 ? 'text-bg-secondary' : 'text-bg-warning' ?>"><?= (int)$f['stock'] <= 0 ? 'Épuisé' : (int)$f['stock'] . ' restant(s)' ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0">Tout est bien approvisionné 🎉</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
