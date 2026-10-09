<?php
$page = 'produits';
$titre = 'Nos produits';
require __DIR__ . '/includes/header.php';

$categories = db()->query(
    'SELECT c.*, (SELECT COUNT(*) FROM produits p WHERE p.categorie_id = c.id AND p.actif = 1) AS nb
       FROM categories c HAVING nb > 0 ORDER BY c.nom'
)->fetchAll();
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$sql = "SELECT p.*, c.nom AS categorie, c.icone
          FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
         WHERE p.actif = 1";
$params = [];
if ($catId > 0) {
    $sql .= ' AND p.categorie_id = ?';
    $params[] = $catId;
}
$sql .= ' ORDER BY (p.stock > 0) DESC, p.nouveau DESC, p.id DESC';
$st = db()->prepare($sql);
$st->execute($params);
$produits = $st->fetchAll();

$catActive = null;
foreach ($categories as $c) {
    if ((int)$c['id'] === $catId) $catActive = $c;
}
?>

<div class="entete-page mb-4">
  <h1 class="page-title mb-1"><?= $catActive ? e($catActive['nom']) : 'Nos produits' ?></h1>
  <p class="text-muted mb-0">Prix en FCFA · en gros comme au détail</p>
</div>

<div class="chips chips-categories mb-4">
  <a class="chip <?= $catId === 0 ? 'active' : '' ?>" href="<?= e(base_url('produits.php')) ?>"><i class="fa-solid fa-border-all me-1"></i>Tout</a>
  <?php foreach ($categories as $c): ?>
    <a class="chip <?= $catId === (int)$c['id'] ? 'active' : '' ?>" href="<?= e(base_url('produits.php?cat=' . (int)$c['id'])) ?>"><i class="fa-solid <?= e($c['icone']) ?> me-1"></i><?= e($c['nom']) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($produits): ?>
  <div class="row g-3">
    <?php foreach ($produits as $p) { product_card($p); } ?>
  </div>
<?php else: ?>
  <div class="panel empty-state">
    <i class="fa-solid fa-box-open"></i>
    <p class="mt-3 mb-3 text-muted">Aucun produit dans cette catégorie pour le moment.</p>
    <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Voir tous les produits</a>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
