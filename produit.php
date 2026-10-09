<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare(
    "SELECT p.*, c.nom AS categorie, c.icone
       FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
      WHERE p.id = ? AND p.actif = 1"
);
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    http_response_code(404);
}

$page = 'produits';
$titre = $p ? $p['nom'] : 'Produit introuvable';
require __DIR__ . '/includes/header.php';

if (!$p): ?>
  <div class="panel empty-state">
    <i class="fa-solid fa-circle-question"></i>
    <p class="mt-3 mb-3">Ce produit n'existe pas ou n'est plus disponible.</p>
    <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Retour aux produits</a>
  </div>
<?php
    require __DIR__ . '/includes/footer.php';
    exit;
endif;

[$label, $cls] = stock_info((int)$p['stock']);
$img = product_image($p['image']);

$similaires = [];
if ($p['categorie_id']) {
    $s = db()->prepare(
        "SELECT p.*, c.nom AS categorie, c.icone
           FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
          WHERE p.actif = 1 AND p.categorie_id = ? AND p.id <> ?
          ORDER BY p.id DESC LIMIT 4"
    );
    $s->execute([$p['categorie_id'], $p['id']]);
    $similaires = $s->fetchAll();
}
?>

<nav aria-label="Fil d'Ariane" class="mb-3 small">
  <a href="<?= e(base_url('produits.php')) ?>">Produits</a>
  <?php if ($p['categorie']): ?> / <a href="<?= e(base_url('produits.php?cat=' . (int)$p['categorie_id'])) ?>"><?= e($p['categorie']) ?></a><?php endif; ?>
  / <span class="text-muted"><?= e($p['nom']) ?></span>
</nav>

<div class="row g-4">
  <div class="col-md-6">
    <div class="detail-img">
      <?php if ($img): ?>
        <img src="<?= e($img) ?>" alt="<?= e($p['nom']) ?>">
      <?php else: ?>
        <span class="thumb-placeholder"><i class="fa-solid <?= e($p['icone'] ?? 'fa-gem') ?>"></i></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <?php if ($p['categorie']): ?><div class="product-cat mb-1"><?= e($p['categorie']) ?></div><?php endif; ?>
    <h1 class="page-title"><?= e($p['nom']) ?> <?php if ($p['nouveau']): ?><span class="badge rounded-pill bg-danger-subtle text-danger-emphasis fs-6 align-middle" style="background:var(--rose)!important;color:#fff!important">Nouveau</span><?php endif; ?></h1>
    <div class="total-line mb-2"><?= fcfa($p['prix']) ?></div>
    <span class="stock-badge stock-<?= e($cls) ?> mb-3 d-inline-block"><?= e($label) ?><?= (int)$p['stock'] > 0 && (int)$p['stock'] <= 3 ? ' (' . (int)$p['stock'] . ')' : '' ?></span>

    <?php if (trim((string)$p['description']) !== ''): ?>
      <p class="text-muted"><?= nl2br(e($p['description'])) ?></p>
    <?php endif; ?>

    <?php if ((int)$p['stock'] > 0): ?>
      <form method="post" action="<?= e(base_url('panier.php')) ?>" class="mt-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <input type="hidden" name="retour" value="produit.php?id=<?= (int)$p['id'] ?>">
        <div class="d-flex align-items-center gap-2 mb-3">
          <button type="button" class="btn btn-outline-rose rounded-circle" data-qty-step="-1" data-target="qty" aria-label="Moins"><i class="fa-solid fa-minus"></i></button>
          <input id="qty" type="number" name="qty" value="1" min="1" max="<?= (int)$p['stock'] ?>" class="form-control qty-input">
          <button type="button" class="btn btn-outline-rose rounded-circle" data-qty-step="1" data-target="qty" aria-label="Plus"><i class="fa-solid fa-plus"></i></button>
        </div>
        <button class="btn btn-rose btn-lg px-4"><i class="fa-solid fa-bag-shopping me-2"></i>Ajouter au panier</button>
      </form>
    <?php else: ?>
      <a class="btn btn-wa btn-lg mt-3" target="_blank" rel="noopener"
         href="<?= e(wa_link('Bonjour, le produit « ' . $p['nom'] . ' » m\'intéresse. Sera-t-il bientôt disponible ?')) ?>">
        <i class="fa-brands fa-whatsapp me-1"></i> Me prévenir sur WhatsApp
      </a>
    <?php endif; ?>

    <a class="d-inline-block mt-3 small" target="_blank" rel="noopener"
       href="<?= e(wa_link('Bonjour, j\'ai une question sur « ' . $p['nom'] . ' ».')) ?>">
      <i class="fa-brands fa-whatsapp"></i> Poser une question sur ce produit
    </a>
  </div>
</div>

<?php if ($similaires): ?>
  <section class="mt-5">
    <h2 class="section-title mb-4">Vous aimerez aussi</h2>
    <div class="row g-3"><?php foreach ($similaires as $s) { product_card($s); } ?></div>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
