<?php
$adminPage = 'paiements';
$titre = 'Paiements';
require __DIR__ . '/_header.php';

$pdo = db();
$filtre = $_GET['statut'] ?? 'en_attente';
if ($filtre !== 'tous' && !isset(STATUTS_PAIEMENT[$filtre])) {
    $filtre = 'en_attente';
}
$mode = $_GET['mode'] ?? '';

$sql = 'SELECT pa.*, c.numero_commande, c.nom_complet, c.telephone, c.statut AS statut_commande
          FROM paiements pa JOIN commandes c ON c.id = pa.commande_id WHERE 1 = 1';
$params = [];
if ($filtre !== 'tous') {
    $sql .= ' AND pa.statut_paiement = ?';
    $params[] = $filtre;
}
if (in_array($mode, MODES_PAIEMENT, true)) {
    $sql .= ' AND pa.mode_paiement = ?';
    $params[] = $mode;
}
$sql .= ' ORDER BY pa.id DESC LIMIT 300';
$st = $pdo->prepare($sql);
$st->execute($params);
$paiements = $st->fetchAll();

// Totaux par statut (commandes non annulées pour l'attente)
$totaux = $pdo->query(
    "SELECT pa.statut_paiement, COUNT(*) AS nb, COALESCE(SUM(pa.montant), 0) AS montant
       FROM paiements pa JOIN commandes c ON c.id = pa.commande_id
      GROUP BY pa.statut_paiement"
)->fetchAll(PDO::FETCH_UNIQUE);

function lien_filtre(string $statut, string $mode): string
{
    return 'paiements.php?statut=' . rawurlencode($statut) . ($mode !== '' ? '&mode=' . rawurlencode($mode) : '');
}
?>

<h1 class="page-title mb-1">Paiements</h1>
<p class="text-muted mb-4">Un paiement n'est « Payé » qu'après votre vérification réelle de la réception de l'argent.</p>

<div class="row g-3 mb-4">
  <?php foreach (STATUTS_PAIEMENT as $k => [$label, $cls]): $t = $totaux[$k] ?? ['nb' => 0, 'montant' => 0]; ?>
    <div class="col-6 col-lg-3">
      <a class="stat-card d-block text-reset" href="<?= e(lien_filtre($k, $mode)) ?>">
        <div class="text-muted small"><?= e($label) ?> · <?= (int)$t['nb'] ?></div>
        <div class="num fs-4"><?= fcfa($t['montant']) ?></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="chips mb-2">
  <a class="chip <?= $filtre === 'tous' ? 'active' : '' ?>" href="<?= e(lien_filtre('tous', $mode)) ?>">Tous</a>
  <?php foreach (STATUTS_PAIEMENT as $k => [$label]): ?>
    <a class="chip <?= $filtre === $k ? 'active' : '' ?>" href="<?= e(lien_filtre($k, $mode)) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
<div class="chips mb-3">
  <a class="chip <?= $mode === '' ? 'active' : '' ?>" href="<?= e(lien_filtre($filtre, '')) ?>">Tous les modes</a>
  <?php foreach (MODES_PAIEMENT as $m): ?>
    <a class="chip <?= $mode === $m ? 'active' : '' ?>" href="<?= e(lien_filtre($filtre, $m)) ?>"><?= e($m) ?></a>
  <?php endforeach; ?>
</div>

<div class="panel">
<?php if ($paiements): ?>
  <div class="table-responsive">
    <table class="table table-rose align-middle mb-0">
      <thead><tr><th>Commande</th><th>Client</th><th>Mode</th><th>Montant</th><th>Statut</th><th>Référence</th><th>Confirmé le</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($paiements as $p): ?>
        <tr>
          <td class="text-nowrap fw-medium"><?= e($p['numero_commande']) ?><div><?= badge_statut($p['statut_commande']) ?></div></td>
          <td><?= e($p['nom_complet']) ?><div class="small text-muted"><?= e($p['telephone']) ?></div></td>
          <td><?= e($p['mode_paiement']) ?></td>
          <td class="text-nowrap fw-medium"><?= fcfa($p['montant']) ?></td>
          <td><?= badge_paiement($p['statut_paiement']) ?></td>
          <td class="small"><?= e($p['reference_transaction'] ?? '—') ?><?php if (!empty($p['date_declaration']) && $p['statut_paiement'] === 'en_attente'): ?><div><span class="badge rounded-pill text-bg-info">Déclaré payé</span> <?= e($p['reference_client']) ?></div><?php endif; ?></td>
          <td class="small text-nowrap"><?= $p['date_confirmation'] ? e(date('d/m/Y H:i', strtotime($p['date_confirmation']))) : '—' ?></td>
          <td class="text-end"><a class="btn btn-outline-rose btn-sm text-nowrap" href="commande.php?id=<?= (int)$p['commande_id'] ?>#paiement"><?= $p['statut_paiement'] === 'en_attente' ? 'Vérifier' : 'Détails' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="text-muted text-center py-4 mb-0">Aucun paiement dans cette catégorie.</p>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
