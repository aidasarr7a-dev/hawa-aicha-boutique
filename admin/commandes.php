<?php
$adminPage = 'commandes';
$titre = 'Commandes';
require __DIR__ . '/_header.php';

$filtre = $_GET['statut'] ?? '';
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT c.*, pa.statut_paiement FROM commandes c LEFT JOIN paiements pa ON pa.commande_id = c.id WHERE 1 = 1';
$params = [];
if (isset(STATUTS[$filtre])) {
    $sql .= ' AND c.statut = ?';
    $params[] = $filtre;
}
if ($q !== '') {
    // Recherche par numéro de commande, téléphone ou nom
    $chiffres = preg_replace('/\D/', '', $q);
    $sql .= ' AND (c.numero_commande LIKE ? OR c.nom_complet LIKE ?' . ($chiffres !== '' ? ' OR c.telephone LIKE ? OR c.id = ?' : '') . ')';
    array_push($params, '%' . $q . '%', '%' . $q . '%');
    if ($chiffres !== '') {
        array_push($params, '%' . $chiffres . '%', (int)$chiffres);
    }
}
$sql .= ' ORDER BY c.id DESC LIMIT 200';
$st = db()->prepare($sql);
$st->execute($params);
$commandes = $st->fetchAll();
$qs = $q !== '' ? '&q=' . rawurlencode($q) : '';
?>

<div class="admin-head">
  <div>
    <h1 class="page-title">Commandes <span class="compteur-titre">(<?= count($commandes) ?>)</span></h1>
    <p class="admin-sous">Suivez les commandes, de leur réception à la livraison.</p>
  </div>
  <div class="admin-head-actions">
    <a href="export_commandes.php" class="btn btn-outline-rose"><i class="fa-solid fa-file-excel me-1"></i>Exporter (Excel)</a>
  </div>
</div>

<div class="admin-filtres">
  <form method="get" class="admin-recherche" role="search">
    <?php if (isset(STATUTS[$filtre])): ?><input type="hidden" name="statut" value="<?= e($filtre) ?>"><?php endif; ?>
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="N° de commande, téléphone ou nom…" aria-label="Rechercher une commande">
    <button class="btn btn-rose btn-sm">Rechercher</button>
  </form>
  <div class="chips">
    <a class="chip <?= $filtre === '' ? 'active' : '' ?>" href="commandes.php<?= $q !== '' ? '?q=' . rawurlencode($q) : '' ?>">Toutes</a>
    <?php foreach (STATUTS as $k => [$label]): ?>
      <a class="chip <?= $filtre === $k ? 'active' : '' ?>" href="commandes.php?statut=<?= e($k) ?><?= e($qs) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel panel-table">
<?php if ($commandes): ?>
  <div class="table-responsive">
    <table class="table table-rose align-middle">
      <thead><tr><th>N°</th><th>Client</th><th>Téléphone</th><th>Livraison</th><th>Total</th><th>Commande</th><th>Paiement</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($commandes as $c): ?>
        <tr>
          <td class="text-nowrap cellule-principale"><?= e($c['numero_commande']) ?></td>
          <td style="min-width:150px"><?= e($c['nom_complet']) ?><div class="small text-muted text-nowrap"><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></div></td>
          <td class="text-nowrap"><a href="tel:<?= e($c['telephone']) ?>"><?= e($c['telephone']) ?></a></td>
          <td style="min-width:170px"><?= e(adresse_commande($c)) ?><div class="small text-muted"><?= e($c['mode_livraison']) ?></div></td>
          <td class="text-nowrap fw-medium"><?= fcfa($c['total']) ?><div class="small text-muted fw-normal"><?= e($c['paiement']) ?></div></td>
          <td><?= badge_statut($c['statut']) ?></td>
          <td><?= badge_paiement($c['statut_paiement']) ?></td>
          <td class="text-end"><a class="btn btn-outline-rose btn-sm text-nowrap" href="commande.php?id=<?= (int)$c['id'] ?>">Détails</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="admin-vide"><i class="fa-solid fa-receipt"></i>Aucune commande<?= $q !== '' ? ' ne correspond à « ' . e($q) . ' »' : '' ?>.</div>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
