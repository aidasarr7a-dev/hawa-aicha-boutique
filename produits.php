<?php
$page = 'produits';
$titre = 'Nos produits';
require __DIR__ . '/includes/header.php';

$categories = get_categories();
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$q = trim($_GET['q'] ?? '');
$prixMin = (int)preg_replace('/\D/', '', (string)($_GET['min'] ?? ''));
$prixMax = (int)preg_replace('/\D/', '', (string)($_GET['max'] ?? ''));
$dispo = !empty($_GET['dispo']);

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
if ($prixMin > 0) {
    $sql .= ' AND p.prix >= ?';
    $params[] = $prixMin;
}
if ($prixMax > 0) {
    $sql .= ' AND p.prix <= ?';
    $params[] = $prixMax;
}
if ($dispo) {
    $sql .= ' AND p.stock > 0';
}
$sql .= ' ORDER BY (p.stock > 0) DESC, p.nouveau DESC, p.id DESC';
$st = db()->prepare($sql);
$st->execute($params);
$produits = $st->fetchAll();

/** Lien du catalogue en conservant les filtres actuels (sauf ceux remplacés). */
function lien_catalogue(array $changer): string
{
    global $catId, $q, $prixMin, $prixMax, $dispo;
    $p = array_merge(['cat' => $catId ?: null, 'q' => $q ?: null, 'min' => $prixMin ?: null, 'max' => $prixMax ?: null, 'dispo' => $dispo ? 1 : null], $changer);
    $qs = http_build_query(array_filter($p, fn($v) => $v !== null && $v !== ''));
    return base_url('produits.php' . ($qs !== '' ? '?' . $qs : ''));
}
$filtresActifs = $q !== '' || $prixMin || $prixMax || $dispo;
?>

<h1 class="page-title mb-1">Nos produits</h1>
<p class="text-muted mb-4">Prix en FCFA · en gros comme au détail · stock mis à jour régulièrement</p>

<div class="filter-bar mb-4">
  <form method="get" class="row g-2 align-items-end mb-3">
    <?php if ($catId): ?><input type="hidden" name="cat" value="<?= $catId ?>"><?php endif; ?>
    <div class="col-12 col-md-5">
      <label class="form-label small mb-1" for="q">Rechercher</label>
      <input type="search" id="q" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Nom du produit…">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1" for="min">Prix min.</label>
      <input type="number" id="min" name="min" min="0" step="500" value="<?= $prixMin ?: '' ?>" class="form-control" placeholder="0">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1" for="max">Prix max.</label>
      <input type="number" id="max" name="max" min="0" step="500" value="<?= $prixMax ?: '' ?>" class="form-control" placeholder="Illimité">
    </div>
    <div class="col-7 col-md-2">
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="dispo" name="dispo" value="1" <?= $dispo ? 'checked' : '' ?>>
        <label class="form-check-label small" for="dispo">Disponibles uniquement</label>
      </div>
    </div>
    <div class="col-5 col-md-1 d-grid"><button class="btn btn-rose" aria-label="Filtrer"><i class="fa-solid fa-magnifying-glass"></i></button></div>
  </form>
  <div class="chips">
    <a class="chip <?= $catId === 0 ? 'active' : '' ?>" href="<?= e(lien_catalogue(['cat' => null])) ?>">Tout</a>
    <?php foreach ($categories as $c): ?>
      <a class="chip <?= $catId === (int)$c['id'] ? 'active' : '' ?>" href="<?= e(lien_catalogue(['cat' => (int)$c['id']])) ?>"><?= e($c['nom']) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($filtresActifs): ?>
  <p class="small text-muted mb-3"><?= count($produits) ?> produit(s) trouvé(s) · <a href="<?= e(lien_catalogue(['q' => null, 'min' => null, 'max' => null, 'dispo' => null])) ?>">Effacer les filtres</a></p>
<?php endif; ?>

<?php if ($produits): ?>
  <div class="row g-3">
    <?php foreach ($produits as $p) { product_card($p); } ?>
  </div>
<?php else: ?>
  <div class="panel empty-state">
    <i class="fa-solid fa-magnifying-glass"></i>
    <p class="mt-3 mb-3 text-muted">Aucun produit ne correspond à votre recherche.</p>
    <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Voir tous les produits</a>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
