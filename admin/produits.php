<?php
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $pdo = db();

    if ($action === 'delete' && $id > 0) {
        exiger_principal('produits.php', 'Seul l\'administrateur principal peut supprimer un produit.');
        $st = $pdo->prepare('SELECT image FROM produits WHERE id = ?');
        $st->execute([$id]);
        $img = $st->fetchColumn();
        $pdo->prepare('DELETE FROM produits WHERE id = ?')->execute([$id]);
        delete_image($img ?: null);
        flash('success', 'Produit supprimé.');
    } elseif ($action === 'toggle' && $id > 0) {
        $pdo->prepare('UPDATE produits SET actif = 1 - actif WHERE id = ?')->execute([$id]);
        flash('success', 'Visibilité du produit mise à jour.');
    } elseif ($action === 'stock' && $id > 0) {
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $pdo->prepare('UPDATE produits SET stock = ? WHERE id = ?')->execute([$stock, $id]);
        flash('success', 'Stock mis à jour.');
    }
    redirect('produits.php' . (!empty($_POST['q']) ? '?q=' . rawurlencode($_POST['q']) : ''));
}

$adminPage = 'produits';
$titre = 'Produits';
require __DIR__ . '/_header.php';

$q = trim($_GET['q'] ?? '');
$filtreStock = $_GET['stock'] ?? '';
$sql = 'SELECT p.*, c.nom AS categorie, c.icone FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id WHERE 1 = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND p.nom LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($filtreStock === 'epuise') {
    $sql .= ' AND p.stock <= 0';
} elseif ($filtreStock === 'faible') {
    $sql .= ' AND p.stock <= ?';
    $params[] = SEUIL_STOCK;
} elseif ($filtreStock === 'masques') {
    $sql .= ' AND p.actif = 0';
} else {
    $filtreStock = '';
}
$sql .= $filtreStock === 'faible' ? ' ORDER BY p.stock, p.nom' : ' ORDER BY p.id DESC';
$st = db()->prepare($sql);
$st->execute($params);
$produits = $st->fetchAll();
?>

<div class="admin-head">
  <div>
    <h1 class="page-title">Produits <span class="compteur-titre">(<?= count($produits) ?>)</span></h1>
    <p class="admin-sous">Gérez le catalogue, les prix, le stock et la visibilité sur le site.</p>
  </div>
  <div class="admin-head-actions">
    <a href="produit_form.php" class="btn btn-rose"><i class="fa-solid fa-plus me-1"></i>Ajouter un produit</a>
  </div>
</div>

<div class="admin-filtres">
  <form method="get" class="admin-recherche" role="search">
    <?php if ($filtreStock !== ''): ?><input type="hidden" name="stock" value="<?= e($filtreStock) ?>"><?php endif; ?>
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Rechercher un produit…" aria-label="Rechercher un produit">
    <button class="btn btn-rose btn-sm">Rechercher</button>
  </form>
  <div class="chips">
    <?php foreach (['' => 'Tous', 'faible' => 'À réapprovisionner (≤ ' . SEUIL_STOCK . ')', 'epuise' => 'Épuisés', 'masques' => 'Masqués'] as $k => $label): ?>
      <a class="chip <?= $filtreStock === $k ? 'active' : '' ?>" href="produits.php?<?= e(http_build_query(array_filter(['stock' => $k, 'q' => $q]))) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel panel-table">
<?php if ($produits): ?>
  <div class="table-responsive">
    <table class="table table-rose align-middle table-cartes">
      <thead><tr><th>Produit</th><th>Prix</th><th>Stock</th><th>État</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($produits as $p): $img = product_image($p['image']); ?>
        <tr class="<?= $p['actif'] ? '' : 'opacity-50' ?>">
          <td class="cel-titre">
            <div class="d-flex align-items-center gap-3">
              <?php if ($img): ?><img class="admin-thumb" src="<?= e($img) ?>" alt=""><?php else: ?><span class="admin-thumb"><i class="fa-solid <?= e($p['icone'] ?? 'fa-gem') ?>"></i></span><?php endif; ?>
              <div class="min-w-0"><span class="cellule-principale"><?= e($p['nom']) ?></span><div class="small text-muted"><?= e($p['categorie'] ?? 'Sans catégorie') ?><?= $p['nouveau'] ? ' · <span style="color:var(--rose)">Nouveau</span>' : '' ?></div></div>
            </div>
          </td>
          <td class="text-nowrap fw-medium" data-label="Prix"><?= fcfa($p['prix']) ?></td>
          <td data-label="Stock">
            <div class="stock-cellule">
              <form method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="stock"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
                <input type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>" class="form-control form-control-sm" aria-label="Stock de <?= e($p['nom']) ?>">
                <button class="btn btn-outline-rose btn-sm btn-ic" title="Enregistrer le stock"><i class="fa-solid fa-check"></i></button>
              </form>
              <?php [$sl, $sc] = stock_info((int)$p['stock']); ?><span class="stock-badge stock-<?= e($sc) ?> m-0"><?= e($sl) ?></span>
            </div>
          </td>
          <td data-label="État">
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
              <button class="badge rounded-pill border-0 text-bg-<?= $p['actif'] ? 'success' : 'secondary' ?>" title="Cliquer pour changer"><i class="fa-solid <?= $p['actif'] ? 'fa-eye' : 'fa-eye-slash' ?> me-1"></i><?= $p['actif'] ? 'Visible' : 'Masqué' ?></button>
            </form>
          </td>
          <td class="text-end cel-actions">
            <div class="admin-actions">
              <a class="btn btn-outline-rose btn-sm btn-ic" href="produit_form.php?id=<?= (int)$p['id'] ?>" title="Modifier"><i class="fa-solid fa-pen"></i></a>
              <?php if (est_principal()): ?>
              <form method="post" data-confirm-titre="Supprimer ce produit ?" data-confirm="« <?= e($p['nom']) ?> » sera retiré définitivement du catalogue, avec sa photo." data-confirm-ok="Supprimer">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
                <button class="btn btn-outline-danger btn-sm btn-ic" title="Supprimer"><i class="fa-solid fa-trash-can"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="admin-vide"><i class="fa-solid fa-box-open"></i>Aucun produit. <a href="produit_form.php">Ajoutez le premier !</a></div>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
