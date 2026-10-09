<?php
require_once __DIR__ . '/includes/functions.php';
$page = 'accueil';
$titre = SITE_NAME;
require __DIR__ . '/includes/header.php';

$categories = get_categories();
$nouveautes = db()->query(
    "SELECT p.*, c.nom AS categorie, c.icone
       FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
      WHERE p.actif = 1
      ORDER BY p.nouveau DESC, p.id DESC
      LIMIT 8"
)->fetchAll();
?>

<section class="hero mb-5">
  <div class="row align-items-center g-4">
    <div class="col-lg-7">
      <span class="eyebrow"><i class="fa-solid fa-sparkles me-1"></i> Nouveaux arrivages réguliers</span>
      <h1 class="mt-3 mb-3">Des produits de <span>qualité</span> à prix accessibles</h1>
      <p class="lead text-muted mb-4">Parfums, beauté, sacs, chaussures, jalabés et bien plus : trouvez tout au même endroit et commandez en quelques clics depuis votre téléphone.</p>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose btn-lg px-4">Voir les produits</a>
        <a href="<?= e(wa_link('Bonjour Hawa Aïcha Boutique, j\'aimerais avoir des informations.')) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg px-4">
          <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
        </a>
      </div>
    </div>
    <div class="col-lg-5 d-none d-md-block">
      <div class="hero-art" aria-hidden="true">
        <div class="tile"><i class="fa-solid fa-spray-can-sparkles"></i></div>
        <div class="tile"><i class="fa-solid fa-bag-shopping"></i></div>
        <div class="tile"><i class="fa-solid fa-shirt"></i></div>
        <div class="tile"><i class="fa-solid fa-heart"></i></div>
      </div>
    </div>
  </div>
</section>

<?php if ($categories): ?>
<section class="mb-5">
  <h2 class="section-title">Nos catégories</h2>
  <p class="section-sub mb-4">Choisissez ce qui vous fait plaisir.</p>
  <div class="row g-3">
    <?php foreach ($categories as $c): ?>
      <div class="col-6 col-md-4 col-lg-2">
        <a class="cat-card" href="<?= e(base_url('produits.php?cat=' . (int)$c['id'])) ?>">
          <span class="cat-icon"><i class="fa-solid <?= e($c['icone']) ?>"></i></span>
          <span><?= e($c['nom']) ?></span>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="mb-5">
  <div class="d-flex justify-content-between align-items-end mb-4">
    <div>
      <h2 class="section-title">Nouveautés</h2>
      <p class="section-sub mb-0">Les derniers arrivages de la boutique.</p>
    </div>
    <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-outline-rose btn-sm">Tout voir <i class="fa-solid fa-arrow-right ms-1"></i></a>
  </div>
  <?php if ($nouveautes): ?>
    <div class="row g-3">
      <?php foreach ($nouveautes as $p) { product_card($p); } ?>
    </div>
  <?php else: ?>
    <div class="panel empty-state"><i class="fa-solid fa-box-open"></i><p class="mt-3 mb-0 text-muted">Les produits arrivent bientôt. Revenez vite !</p></div>
  <?php endif; ?>
</section>

<section class="mb-5">
  <h2 class="section-title mb-4">Pourquoi Hawa Aïcha ?</h2>
  <div class="row g-3">
    <div class="col-6 col-lg-3"><div class="why-card"><i class="fa-solid fa-star"></i><h5>Qualité</h5><p class="text-muted small mb-0">Des articles sélectionnés auprès de fournisseurs fiables.</p></div></div>
    <div class="col-6 col-lg-3"><div class="why-card"><i class="fa-solid fa-tags"></i><h5>Prix accessibles</h5><p class="text-muted small mb-0">Des prix cassés pour toute la famille, en FCFA.</p></div></div>
    <div class="col-6 col-lg-3"><div class="why-card"><i class="fa-solid fa-boxes-stacked"></i><h5>Variété</h5><p class="text-muted small mb-0">Plusieurs catégories de produits au même endroit.</p></div></div>
    <div class="col-6 col-lg-3"><div class="why-card"><i class="fa-solid fa-mobile-screen"></i><h5>Commande facile</h5><p class="text-muted small mb-0">Commandez depuis votre téléphone, payez avec Wave ou Orange Money.</p></div></div>
  </div>
</section>

<section class="cta-band text-center">
  <h2 class="mb-2">Une question sur un produit ?</h2>
  <p class="mb-4 opacity-75">Écrivez-nous sur WhatsApp ou suivez les nouveautés sur TikTok <?= e(TIKTOK_HANDLE) ?>.</p>
  <div class="d-flex flex-wrap gap-2 justify-content-center">
    <a class="btn btn-light btn-lg px-4" href="<?= e(wa_link('Bonjour Hawa Aïcha Boutique, j\'aimerais avoir des informations.')) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp me-1"></i> WhatsApp</a>
    <a class="btn btn-light btn-lg px-4" href="<?= e(TIKTOK_URL) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-tiktok me-1"></i> TikTok</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
