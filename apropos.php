<?php
$page = 'apropos';
$titre = 'À propos';
require __DIR__ . '/includes/header.php';

$categories = get_categories();
?>

<section class="hero mb-5">
  <span class="eyebrow"><i class="fa-solid fa-heart me-1"></i> Notre boutique</span>
  <h1 class="mt-3 mb-3">À propos de <span>Hawa Aïcha</span></h1>
  <p class="lead text-muted mb-0" style="max-width:720px"><?= e(SITE_SLOGAN) ?></p>
</section>

<div class="row g-4 mb-5">
  <div class="col-12">
    <div class="panel h-100">
      <h2 class="section-title mb-3">Qui sommes-nous ?</h2>
      <p>Hawa Aïcha Boutique vend des produits de <strong>beauté</strong>, de <strong>parfumerie</strong>, de <strong>mode</strong> et des <strong>accessoires</strong> pour les femmes, les hommes et les familles.</p>
      <p>Notre idée est simple : permettre à chacun de se faire plaisir et de prendre soin de soi avec des articles de qualité, sans se ruiner. Les arrivages changent régulièrement, ce qui permet de découvrir souvent de nouveaux produits.</p>
      <p class="mb-0">Vous pouvez commander directement sur ce site, nous écrire sur WhatsApp ou passer nous voir à la boutique.</p>
    </div>
  </div>
</div>

<section class="mb-5">
  <h2 class="section-title mb-4">Nos engagements</h2>
  <div class="row g-3">
    <div class="col-6 col-lg-3"><div class="why-card"><i class="fa-solid fa-star"></i><h5>Qualité</h5><p class="text-muted small mb-0">Des articles choisis avec soin pour leur qualité.</p></div></div>
    <div class="col-6 col-lg-3"><div class="why-card"><i class="fa-solid fa-tags"></i><h5>Prix accessibles</h5><p class="text-muted small mb-0">Des prix justes, affichés clairement en FCFA.</p></div></div>
    <div class="col-6 col-lg-3"><div class="why-card"><i class="fa-solid fa-boxes-stacked"></i><h5>Gros et détail</h5><p class="text-muted small mb-0">À l’unité pour vous, en quantité pour les revendeuses et revendeurs. Contactez-nous pour les commandes en gros.</p></div></div>
    <div class="col-6 col-lg-3"><div class="why-card"><i class="fa-solid fa-rotate"></i><h5>Nouveautés</h5><p class="text-muted small mb-0">Des arrivages réguliers à découvrir sur le site et sur TikTok.</p></div></div>
  </div>
</section>

<?php if ($categories): ?>
<section class="mb-5">
  <h2 class="section-title mb-4">Nos univers</h2>
  <div class="chips flex-wrap">
    <?php foreach ($categories as $c): ?>
      <a class="chip" href="<?= e(base_url('produits.php?cat=' . (int)$c['id'])) ?>"><i class="fa-solid <?= e($c['icone']) ?> me-1"></i><?= e($c['nom']) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="cta-band text-center">
  <h2 class="mb-2">Commande en gros ou question ?</h2>
  <p class="mb-4 opacity-75">Écrivez-nous, nous vous répondons rapidement.</p>
  <div class="d-flex flex-wrap gap-2 justify-content-center">
    <a class="btn btn-light btn-lg px-4" href="<?= e(base_url('contact.php')) ?>"><i class="fa-solid fa-envelope me-1"></i> Nous écrire</a>
    <a class="btn btn-light btn-lg px-4" href="<?= e(base_url('produits.php')) ?>">Voir les produits</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
