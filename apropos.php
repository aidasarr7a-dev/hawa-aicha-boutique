<?php
$page = 'apropos';
$titre = 'À propos';
require __DIR__ . '/includes/header.php';

$categories = get_categories();
?>

<section class="hero mb-5">
  <span class="eyebrow"><i class="fa-solid fa-heart me-1"></i> Notre boutique</span>
  <h1 class="mt-3 mb-3">À propos de <span>Hawa Aïcha</span></h1>
  <p class="slogan-apropos mb-0"><span><?= e(SITE_SLOGAN) ?></span></p>
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

<section class="reseaux reseaux-pastel" aria-labelledby="titre-question">
  <div class="row g-4 g-lg-5 align-items-center">
    <div class="col-lg-5">
      <span class="eyebrow-simple"><i class="fa-solid fa-comments me-1"></i> À votre écoute</span>
      <h2 class="section-title mt-3" id="titre-question">Commande en gros ou question ?</h2>
      <p class="text-muted mb-0">Revendeuses, revendeurs ou clientes : écrivez-nous, nous vous répondons rapidement.</p>
    </div>
    <div class="col-lg-7">
      <div class="row g-3">
        <div class="col-sm-6">
          <a class="carte-reseau" href="<?= e(base_url('contact.php')) ?>">
            <span class="reseau-ic ic-pastel"><i class="fa-solid fa-envelope"></i></span>
            <span class="reseau-texte"><strong>Nous écrire</strong><small>Une question, une commande en gros</small></span>
            <span class="reseau-action">Contact <i class="fa-solid fa-arrow-right"></i></span>
          </a>
        </div>
        <div class="col-sm-6">
          <a class="carte-reseau" href="<?= e(base_url('produits.php')) ?>">
            <span class="reseau-ic ic-saumon"><i class="fa-solid fa-bag-shopping"></i></span>
            <span class="reseau-texte"><strong>Voir les produits</strong><small>Tout le catalogue en FCFA</small></span>
            <span class="reseau-action">Découvrir <i class="fa-solid fa-arrow-right"></i></span>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
