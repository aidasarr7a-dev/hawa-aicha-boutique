<?php
$adminPage = 'dashboard';
$titre = 'Tableau de bord';
require __DIR__ . '/_header.php';

$pdo = db();
$nbProduits  = (int)$pdo->query('SELECT COUNT(*) FROM produits')->fetchColumn();
$nbRupture   = (int)$pdo->query('SELECT COUNT(*) FROM produits WHERE stock <= 0')->fetchColumn();
$nbNouvelles = (int)$pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'nouvelle'")->fetchColumn();
$ca          = (int)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut <> 'annulee'")->fetchColumn();

$dernieres = $pdo->query('SELECT * FROM commandes ORDER BY id DESC LIMIT 8')->fetchAll();
$faibles = $pdo->query('SELECT id, nom, stock FROM produits WHERE actif = 1 AND stock <= 3 ORDER BY stock, nom LIMIT 8')->fetchAll();
?>

<h1 class="page-title mb-4">Tableau de bord</h1>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-muted small">Produits</div><div class="num"><?= $nbProduits ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-muted small">En rupture</div><div class="num"><?= $nbRupture ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-muted small">Nouvelles commandes</div><div class="num"><?= $nbNouvelles ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-muted small">Chiffre d'affaires</div><div class="num fs-4"><?= fcfa($ca) ?></div></div></div>
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
          <thead><tr><th>N°</th><th>Client</th><th>Total</th><th>Statut</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($dernieres as $c): [$sl, $sc] = STATUTS[$c['statut']] ?? [$c['statut'], 'secondary']; ?>
            <tr>
              <td>#<?= (int)$c['id'] ?></td>
              <td><?= e($c['prenom'] . ' ' . $c['nom']) ?><div class="small text-muted"><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></div></td>
              <td class="text-nowrap"><?= fcfa($c['total']) ?></td>
              <td><span class="badge text-bg-<?= e($sc) ?> rounded-pill"><?= e($sl) ?></span></td>
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
      <h2 class="h5 mb-3">Stock faible ou épuisé</h2>
      <?php if ($faibles): ?>
        <?php foreach ($faibles as $f): ?>
          <a href="produit_form.php?id=<?= (int)$f['id'] ?>" class="d-flex justify-content-between align-items-center text-reset py-2 border-bottom">
            <span><?= e($f['nom']) ?></span>
            <span class="badge rounded-pill <?= (int)$f['stock'] <= 0 ? 'text-bg-secondary' : 'text-bg-warning' ?>"><?= (int)$f['stock'] ?></span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-muted mb-0">Tout est bien approvisionné 🎉</p>
      <?php endif; ?>
      <a href="produit_form.php" class="btn btn-rose w-100 mt-3"><i class="fa-solid fa-plus me-1"></i>Ajouter un produit</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
