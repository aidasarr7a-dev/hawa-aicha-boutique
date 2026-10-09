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

<h1 class="page-title mb-4">Tableau de bord</h1>

<?php if ($manquants): ?>
  <div class="alert alert-light border rounded-4 small"><i class="fa-solid fa-circle-info me-1" style="color:var(--rose)"></i>
    Informations à compléter dans <code>config.php</code> : <?= e(implode(', ', $manquants)) ?>.</div>
<?php endif; ?>

<?php if ($nbPaiementsDeclares || $nbMessagesNonLus): ?>
  <div class="d-flex flex-wrap gap-2 mb-3">
    <?php if ($nbPaiementsDeclares): ?><a class="alerte-admin" href="paiements.php?statut=en_attente"><i class="fa-solid fa-hand-holding-dollar"></i> <?= $nbPaiementsDeclares ?> paiement(s) déclaré(s) par des clientes à vérifier</a><?php endif; ?>
    <?php if ($nbMessagesNonLus): ?><a class="alerte-admin" href="messages.php?filtre=non_lus"><i class="fa-solid fa-envelope"></i> <?= $nbMessagesNonLus ?> nouveau(x) message(s)</a><?php endif; ?>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-4 col-xl-2"><a class="stat-card d-block text-reset" href="produits.php"><div class="text-muted small">Produits</div><div class="num"><?= $nbProduits ?></div></a></div>
  <div class="col-6 col-lg-4 col-xl-2"><a class="stat-card d-block text-reset" href="commandes.php"><div class="text-muted small">Commandes</div><div class="num"><?= $nbCommandes ?></div></a></div>
  <div class="col-6 col-lg-4 col-xl-2"><a class="stat-card d-block text-reset" href="commandes.php?statut=nouvelle"><div class="text-muted small">Nouvelles commandes</div><div class="num"><?= $nbNouvelles ?></div></a></div>
  <div class="col-6 col-lg-4 col-xl-2"><a class="stat-card d-block text-reset" href="paiements.php?statut=en_attente"><div class="text-muted small">Paiements en attente</div><div class="num"><?= (int)$attente['nb'] ?></div><div class="small text-muted"><?= fcfa($attente['montant']) ?></div></a></div>
  <div class="col-6 col-lg-4 col-xl-2"><a class="stat-card d-block text-reset" href="produits.php?stock=epuise"><div class="text-muted small">Ruptures de stock</div><div class="num"><?= $nbRupture ?></div></a></div>
  <div class="col-6 col-lg-4 col-xl-2"><a class="stat-card d-block text-reset" href="paiements.php?statut=paye"><div class="text-muted small">Ventes payées</div><div class="num fs-5"><?= fcfa($ventesPayees) ?></div></a></div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Dernières commandes</h2>
        <a href="commandes.php" class="small">Tout voir</a>
      </div>
      <?php if ($dernieres): ?>
      <div class="table-responsive">
        <table class="table table-rose align-middle mb-0">
          <thead><tr><th>N°</th><th>Client</th><th>Total</th><th>Commande</th><th>Paiement</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($dernieres as $c): ?>
            <tr>
              <td class="text-nowrap"><?= e($c['numero_commande']) ?></td>
              <td><?= e($c['nom_complet']) ?><div class="small text-muted"><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></div></td>
              <td class="text-nowrap"><?= fcfa($c['total']) ?></td>
              <td><?= badge_statut($c['statut']) ?></td>
              <td><?= badge_paiement($c['statut_paiement']) ?></td>
              <td class="text-end"><a class="btn btn-outline-rose btn-sm" href="commande.php?id=<?= (int)$c['id'] ?>">Voir</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p class="text-muted mb-0">Aucune commande pour le moment.</p>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel">
      <h2 class="h5 mb-3">À réapprovisionner</h2>
      <?php if ($faibles): ?>
        <?php foreach ($faibles as $f): ?>
          <a href="produit_form.php?id=<?= (int)$f['id'] ?>" class="d-flex justify-content-between align-items-center text-reset py-2 border-bottom">
            <span><?= e($f['nom']) ?></span>
            <span class="badge rounded-pill <?= (int)$f['stock'] <= 0 ? 'text-bg-secondary' : 'text-bg-warning' ?>"><?= (int)$f['stock'] <= 0 ? 'Épuisé' : (int)$f['stock'] . ' restant(s)' ?></span>
          </a>
        <?php endforeach; ?>
        <a href="produits.php?stock=faible" class="d-block small mt-2">Voir tout le stock faible</a>
      <?php else: ?>
        <p class="text-muted mb-0">Tout est bien approvisionné 🎉</p>
      <?php endif; ?>
      <a href="produit_form.php" class="btn btn-rose w-100 mt-3"><i class="fa-solid fa-plus me-1"></i>Ajouter un produit</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
