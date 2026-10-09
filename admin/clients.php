<?php
$adminPage = 'clients';
$titre = 'Clients';
require __DIR__ . '/_header.php';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT cl.*,
               COUNT(c.id) AS nb_commandes,
               COALESCE(SUM(CASE WHEN c.statut <> 'annulee' THEN c.total END), 0) AS total_commandes,
               MAX(c.created_at) AS derniere_commande
          FROM clients cl LEFT JOIN commandes c ON c.client_id = cl.id
         WHERE 1 = 1";
$params = [];
if ($q !== '') {
    $sql .= ' AND (cl.nom_complet LIKE ? OR cl.telephone LIKE ? OR cl.email LIKE ?)';
    array_push($params, '%' . $q . '%', '%' . preg_replace('/\D/', '', $q) . '%', '%' . $q . '%');
}
$sql .= ' GROUP BY cl.id ORDER BY MAX(c.created_at) IS NULL, MAX(c.created_at) DESC, cl.id DESC LIMIT 300';
$st = db()->prepare($sql);
$st->execute($params);
$clients = $st->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="page-title mb-0">Clients <small class="text-muted fs-6">(<?= count($clients) ?>)</small></h1>
  <a href="export_commandes.php" class="btn btn-outline-rose btn-sm"><i class="fa-solid fa-file-excel me-1"></i>Exporter les commandes (Excel)</a>
</div>

<form method="get" class="mb-3"><div class="input-group" style="max-width:460px">
  <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Nom, téléphone ou e-mail…">
  <button class="btn btn-rose" aria-label="Rechercher"><i class="fa-solid fa-magnifying-glass"></i></button>
</div></form>

<div class="panel">
<?php if ($clients): ?>
  <div class="table-responsive">
    <table class="table table-rose align-middle mb-0">
      <thead><tr><th>Client</th><th>Contact</th><th>Adresse</th><th>Compte</th><th>Commandes</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($clients as $c): ?>
        <tr>
          <td><strong><?= e($c['nom_complet']) ?></strong><div class="small text-muted">depuis le <?= e(date('d/m/Y', strtotime($c['date_creation']))) ?></div></td>
          <td class="small"><a href="tel:<?= e($c['telephone']) ?>"><?= e($c['telephone']) ?></a><?= $c['email'] ? '<br><a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>' : '' ?></td>
          <td class="small"><?= e(trim(($c['adresse'] ?? '') . ($c['ville'] ? ', ' . $c['ville'] : ''), ', ') ?: '—') ?></td>
          <td><?= $c['mot_de_passe_hash'] ? '<span class="badge rounded-pill text-bg-success">Compte client</span>' : '<span class="badge rounded-pill text-bg-light border">Sans compte</span>' ?></td>
          <td><?= (int)$c['nb_commandes'] ?><?= $c['derniere_commande'] ? '<div class="small text-muted">dernière : ' . e(date('d/m/Y', strtotime($c['derniere_commande']))) . '</div>' : '' ?></td>
          <td class="text-nowrap fw-medium"><?= fcfa($c['total_commandes']) ?></td>
          <td class="text-end"><?php if ($c['nb_commandes']): ?><a class="btn btn-outline-rose btn-sm text-nowrap" href="commandes.php?q=<?= e(rawurlencode($c['telephone'])) ?>">Ses commandes</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="text-muted text-center py-4 mb-0">Aucun client<?= $q !== '' ? ' ne correspond à « ' . e($q) . ' »' : ' pour le moment' ?>.</p>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
