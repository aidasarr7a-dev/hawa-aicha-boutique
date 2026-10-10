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

<div class="admin-head">
  <div>
    <h1 class="page-title">Clients <span class="compteur-titre">(<?= count($clients) ?>)</span></h1>
    <p class="admin-sous">Retrouvez vos clientes, leurs coordonnées et leur historique d'achat.</p>
  </div>
  <div class="admin-head-actions">
    <a href="export_commandes.php" class="btn btn-outline-rose"><i class="fa-solid fa-file-excel me-1"></i>Exporter les commandes (Excel)</a>
  </div>
</div>

<div class="admin-filtres">
  <form method="get" class="admin-recherche" role="search">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Nom, téléphone ou e-mail…" aria-label="Rechercher un client">
    <button class="btn btn-rose btn-sm">Rechercher</button>
  </form>
</div>

<div class="panel panel-table">
<?php if ($clients): ?>
  <div class="table-responsive">
    <table class="table table-rose align-middle">
      <thead><tr><th>Client</th><th>Contact</th><th>Adresse</th><th>Compte</th><th class="text-center">Commandes</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($clients as $c): ?>
        <tr>
          <td style="min-width:160px"><span class="cellule-principale"><?= e($c['nom_complet']) ?></span><div class="small text-muted">depuis le <?= e(date('d/m/Y', strtotime($c['date_creation']))) ?></div></td>
          <td class="small"><a href="tel:<?= e($c['telephone']) ?>"><?= e($c['telephone']) ?></a><?= $c['email'] ? '<br><a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>' : '' ?></td>
          <td class="small" style="min-width:140px"><?= e(trim(($c['adresse'] ?? '') . ($c['ville'] ? ', ' . $c['ville'] : ''), ', ') ?: '—') ?></td>
          <td class="text-nowrap"><?= $c['mot_de_passe_hash'] ? '<span class="badge rounded-pill text-bg-success">Compte client</span>' : '<span class="badge rounded-pill text-bg-light border">Sans compte</span>' ?></td>
          <td class="text-center"><span class="fw-medium"><?= (int)$c['nb_commandes'] ?></span><?= $c['derniere_commande'] ? '<div class="small text-muted text-nowrap">dernière : ' . e(date('d/m/Y', strtotime($c['derniere_commande']))) . '</div>' : '' ?></td>
          <td class="text-nowrap fw-medium"><?= fcfa($c['total_commandes']) ?></td>
          <td class="text-end"><?php if ($c['nb_commandes']): ?><a class="btn btn-outline-rose btn-sm text-nowrap" href="commandes.php?q=<?= e(rawurlencode($c['telephone'])) ?>">Ses commandes</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="admin-vide"><i class="fa-solid fa-users"></i>Aucun client<?= $q !== '' ? ' ne correspond à « ' . e($q) . ' »' : ' pour le moment' ?>.</div>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
