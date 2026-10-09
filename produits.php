<?php
$page = 'produits';
$titre = 'Nos produits';
require __DIR__ . '/includes/header.php';

$categories = get_categories();
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$q = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, c.nom AS categorie, c.icone
          FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
         WHERE p.actif = 1";
$params = [];
if ($catId > 0) {
    $sql .= ' AND p.categorie_id = ?';
    $params[] = $catId;
}
if ($q !== '') {
    $sql .= ' AND (p.nom LIKE ? OR p.description LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like);
}
$sql .= ' ORDER BY (p.stock > 0) DESC, p.nouveau DESC, p.id DESC';
$st = db()->prepare($sql);
$st->execute($params);
$produits = $st->fetchAll();
?>

<h1 class="page-title mb-1">Nos produits</h1>
<p class="text-muted mb-4">Prix en FCFA · stock mis à jour régulièrement</p>

<div class="filter-bar mb-4">
  <form method="get" class="row g-2 align-items-center mb-3">
    <?php if ($catId): ?><input type="hidden" name="cat" value="<?= $catId ?>"><?php endif; ?>
    <div class="col">
      <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Rechercher un produit…">
    </div>
    <div class="col-auto"><button class="btn btn-rose px-3" aria-label="Rechercher"><i class="fa-solid fa-magnifying-glass"></i></button></div>
  </form>
  <div class="chips">
    <a class="chip <?= $catId === 0 ? 'active' : '' ?>" href="<?= e(base_url('produits.php' . ($q !== '' ? '?q=' . rawurlencode($q) : ''))) ?>">Tout</a>
    <?php foreach ($categories as $c): ?>
      <a class="chip <?= $catId === (int)$c['id'] ? 'active' : '' ?>"
         href="<?= e(base_url('produits.php?cat=' . (int)$c['id'] . ($q !== '' ? '&q=' . rawurlencode($q) : ''))) ?>"><?= e($c['nom']) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($produits): ?>
  <div class="row g-3">
    <?php foreach ($produits as $p) { product_card($p); } ?>
  </div>
<?php else: ?>
  <div class="panel empty-state">
    <i class="fa-solid fa-magnifying-glass"></i>
    <p class="mt-3 mb-3 text-muted">Aucun produit trouvé pour cette recherche.</p>
    <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Voir tous les produits</a>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
