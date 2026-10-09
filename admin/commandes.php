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

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="page-title mb-0">Commandes</h1>
  <a href="export_commandes.php" class="btn btn-outline-rose btn-sm"><i class="fa-solid fa-file-excel me-1"></i>Exporter (Excel)</a>
</div>

<form method="get" class="mb-3"><div class="input-group" style="max-width:480px">
  <?php if (isset(STATUTS[$filtre])): ?><input type="hidden" name="statut" value="<?= e($filtre) ?>"><?php endif; ?>
  <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="N° de commande, téléphone ou nom…">
  <button class="btn btn-rose" aria-label="Rechercher"><i class="fa-solid fa-magnifying-glass"></i></button>
</div></form>

<div class="chips mb-3">
  <a class="chip <?= $filtre === '' ? 'active' : '' ?>" href="commandes.php<?= $q !== '' ? '?q=' . rawurlencode($q) : '' ?>">Toutes</a>
  <?php foreach (STATUTS as $k => [$label]): ?>
    <a class="chip <?= $filtre === $k ? 'active' : '' ?>" href="commandes.php?statut=<?= e($k) ?><?= e($qs) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<div class="panel">
<?php if ($commandes): ?>
  <div class="table-responsive">
    <table class="table table-rose align-middle mb-0">
      <thead><tr><th>N°</th><th>Client</th><th>Téléphone</th><th>Livraison</th><th>Total</th><th>Commande</th><th>Paiement</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($commandes as $c): ?>
        <tr>
          <td class="text-nowrap fw-medium"><?= e($c['numero_commande']) ?></td>
          <td><?= e($c['nom_complet']) ?><div class="small text-muted"><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></div></td>
          <td class="text-nowrap"><a href="tel:<?= e($c['telephone']) ?>"><?= e($c['telephone']) ?></a></td>
          <td><?= e($c['adresse_livraison'] ? $c['adresse_livraison'] . ', ' : '') ?><?= e($c['ville']) ?><div class="small text-muted"><?= e($c['mode_livraison']) ?></div></td>
          <td class="text-nowrap fw-medium"><?= fcfa($c['total']) ?><div class="small text-muted"><?= e($c['paiement']) ?></div></td>
          <td><?= badge_statut($c['statut']) ?></td>
          <td><?= badge_paiement($c['statut_paiement']) ?></td>
          <td class="text-end"><a class="btn btn-outline-rose btn-sm" href="commande.php?id=<?= (int)$c['id'] ?>">Détails</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="text-muted text-center py-4 mb-0">Aucune commande<?= $q !== '' ? ' ne correspond à « ' . e($q) . ' »' : '' ?>.</p>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
