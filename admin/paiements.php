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

<div class="admin-head">
  <div>
    <h1 class="page-title">Paiements</h1>
    <p class="admin-sous">Un paiement n'est « Payé » qu'après votre vérification réelle de la réception de l'argent.</p>
  </div>
</div>

<div class="row g-3 g-lg-4 mb-4">
  <?php
  $iconesPaiement = ['en_attente' => ['fa-hourglass-half', 'ic-alerte'], 'paye' => ['fa-circle-check', 'ic-ok'], 'echec' => ['fa-circle-xmark', ''], 'rembourse' => ['fa-rotate-left', '']];
  foreach (STATUTS_PAIEMENT as $k => [$label, $cls]): $t = $totaux[$k] ?? ['nb' => 0, 'montant' => 0]; [$ico, $icCls] = $iconesPaiement[$k] ?? ['fa-wallet', '']; ?>
    <div class="col-6 col-xl-3">
      <a class="stat-card" href="<?= e(lien_filtre($k, $mode)) ?>">
        <span class="stat-ic <?= $icCls ?>"><i class="fa-solid <?= $ico ?>"></i></span>
        <span class="stat-label"><?= e($label) ?></span>
        <span class="num num-montant"><?= fcfa($t['montant']) ?></span>
        <span class="stat-sous"><?= (int)$t['nb'] ?> paiement(s)</span>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="admin-filtres">
  <div class="filtre-groupe">
    <span class="filtre-titre">Statut</span>
    <a class="chip <?= $filtre === 'tous' ? 'active' : '' ?>" href="<?= e(lien_filtre('tous', $mode)) ?>">Tous</a>
    <?php foreach (STATUTS_PAIEMENT as $k => [$label]): ?>
      <a class="chip <?= $filtre === $k ? 'active' : '' ?>" href="<?= e(lien_filtre($k, $mode)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="filtre-groupe">
    <span class="filtre-titre">Mode</span>
    <a class="chip <?= $mode === '' ? 'active' : '' ?>" href="<?= e(lien_filtre($filtre, '')) ?>">Tous les modes</a>
    <?php foreach (MODES_PAIEMENT as $m): ?>
      <a class="chip <?= $mode === $m ? 'active' : '' ?>" href="<?= e(lien_filtre($filtre, $m)) ?>"><?= e($m) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel panel-table">
<?php if ($paiements): ?>
  <div class="table-responsive">
    <table class="table table-rose align-middle table-cartes">
      <thead><tr><th>Commande</th><th>Client</th><th>Montant</th><th>Statut</th><th>Référence</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($paiements as $p): ?>
        <tr>
          <td class="cel-titre"><span class="text-nowrap cellule-principale"><?= e($p['numero_commande']) ?></span><div class="mt-1"><?= badge_statut($p['statut_commande']) ?></div></td>
          <td data-label="Client"><?= e($p['nom_complet']) ?><div class="small text-muted text-nowrap"><?= e($p['telephone']) ?></div></td>
          <td class="text-nowrap fw-medium" data-label="Montant"><?= fcfa($p['montant']) ?><div class="small text-muted fw-normal"><?= e($p['mode_paiement']) ?></div></td>
          <td data-label="Statut"><?= badge_paiement($p['statut_paiement']) ?><div class="small text-muted text-nowrap mt-1"><?= $p['date_confirmation'] ? 'Confirmé le ' . e(date('d/m/Y H:i', strtotime($p['date_confirmation']))) : 'Non confirmé' ?></div></td>
          <td class="small cel-reference" data-label="Référence"><?= e($p['reference_transaction'] ?? '—') ?><?php if (!empty($p['date_declaration']) && $p['statut_paiement'] === 'en_attente'): ?><div class="mt-1"><span class="badge rounded-pill text-bg-info">Déclaré payé</span> <?= e($p['reference_client']) ?></div><?php endif; ?></td>
          <td class="text-end cel-actions"><a class="btn <?= $p['statut_paiement'] === 'en_attente' ? 'btn-rose' : 'btn-outline-rose' ?> btn-sm text-nowrap" href="commande.php?id=<?= (int)$p['commande_id'] ?>#paiement"><?= $p['statut_paiement'] === 'en_attente' ? 'Vérifier' : 'Détails' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="admin-vide"><i class="fa-solid fa-wallet"></i>Aucun paiement dans cette catégorie.</div>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
