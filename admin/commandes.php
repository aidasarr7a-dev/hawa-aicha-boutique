<?php
$adminPage = 'commandes';
$titre = 'Commandes';
require __DIR__ . '/_header.php';

$filtre = $_GET['statut'] ?? '';
$sql = 'SELECT * FROM commandes';
$params = [];
if (isset(STATUTS[$filtre])) {
    $sql .= ' WHERE statut = ?';
    $params[] = $filtre;
}
$sql .= ' ORDER BY id DESC LIMIT 200';
$st = db()->prepare($sql);
$st->execute($params);
$commandes = $st->fetchAll();
?>

<h1 class="page-title mb-3">Commandes</h1>

<div class="chips mb-3">
  <a class="chip <?= $filtre === '' ? 'active' : '' ?>" href="commandes.php">Toutes</a>
  <?php foreach (STATUTS as $k => [$label]): ?>
    <a class="chip <?= $filtre === $k ? 'active' : '' ?>" href="commandes.php?statut=<?= e($k) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<div class="panel">
<?php if ($commandes): ?>
  <div class="table-responsive">
    <table class="table table-rose align-middle mb-0">
      <thead><tr><th>N°</th><th>Client</th><th>Téléphone</th><th>Livraison</th><th>Paiement</th><th>Total</th><th>Statut</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($commandes as $c): [$sl, $sc] = STATUTS[$c['statut']] ?? [$c['statut'], 'secondary']; ?>
        <tr>
          <td>#<?= (int)$c['id'] ?></td>
          <td><?= e($c['prenom'] . ' ' . $c['nom']) ?><div class="small text-muted"><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></div></td>
          <td class="text-nowrap"><a href="tel:<?= e($c['telephone']) ?>"><?= e($c['telephone']) ?></a></td>
          <td><?= e($c['quartier']) ?>, <?= e($c['ville']) ?></td>
          <td><?= e($c['paiement']) ?></td>
          <td class="text-nowrap fw-medium"><?= fcfa($c['total']) ?></td>
          <td><span class="badge text-bg-<?= e($sc) ?> rounded-pill"><?= e($sl) ?></span></td>
          <td class="text-end"><a class="btn btn-outline-rose btn-sm" href="commande.php?id=<?= (int)$c['id'] ?>">Détails</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="text-muted text-center py-4 mb-0">Aucune commande.</p>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
