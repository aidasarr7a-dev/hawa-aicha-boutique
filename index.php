<?php
require_once __DIR__ . '/includes/functions.php';
$page = 'accueil';
$titre = SITE_NAME;
require __DIR__ . '/includes/header.php';

// Catégories qui ont au moins un produit visible, illustrées par la photo d'un de leurs produits
$categories = db()->query(
    "SELECT c.*,
            (SELECT p.image FROM produits p WHERE p.categorie_id = c.id AND p.actif = 1 AND p.image IS NOT NULL ORDER BY p.nouveau DESC, p.id LIMIT 1) AS image,
            (SELECT COUNT(*) FROM produits p WHERE p.categorie_id = c.id AND p.actif = 1) AS nb
       FROM categories c
     HAVING nb > 0
      ORDER BY c.nom"
)->fetchAll();
$nouveautes = db()->query(
    "SELECT p.*, c.nom AS categorie, c.icone
       FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
      WHERE p.actif = 1
      ORDER BY (p.stock > 0) DESC, p.nouveau DESC, p.id DESC
      LIMIT 8"
)->fetchAll();
$nbProduits = (int)db()->query('SELECT COUNT(*) FROM produits WHERE actif = 1')->fetchColumn();
?>

<section class="hero mb-4">
  <div class="row align-items-center g-4">
    <div class="col-lg-6">
      <span class="eyebrow"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Nouveaux arrivages réguliers</span>
      <h1 class="mt-3 mb-3">L’élégance <span>au quotidien</span>, en gros comme au détail</h1>
      <p class="lead text-muted mb-4">Parfums, soins de beauté, sacs, bijoux, voiles, jalabés et décoration : des produits de qualité à prix accessibles pour toute la famille.</p>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose btn-lg px-4">Découvrir nos produits</a>
        <a href="<?= e(base_url(client_connecte() ? 'mon-compte.php' : 'compte.php?onglet=inscription')) ?>" class="btn btn-outline-rose btn-lg px-4"><i class="fa-regular fa-user me-1"></i> <?= client_connecte() ? 'Mon compte' : 'Créer mon compte' ?></a>
      </div>
    </div>
    <div class="col-lg-6">
      <?php
      // Composition de la bannière : une photo par univers (parfums, sacs, voiles, bijoux, chaussures).
      // Pour utiliser vos propres photos, déposez-les dans assets/img/accueil/ sous les noms
      // grand.jpg, haut.jpg, bas.jpg, rond.jpg et petit.jpg : elles remplacent alors les photos produits.
      $compoProduits = db()->query(
          "SELECT p.nom, p.image, c.nom AS categorie FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
            WHERE p.actif = 1 AND p.image IS NOT NULL ORDER BY p.nouveau DESC, p.id"
      )->fetchAll();
      $trouverPhoto = function (callable $filtre) use ($compoProduits): ?array {
          foreach ($compoProduits as $p) {
              if ($filtre($p)) return $p;
          }
          return null;
      };
      $emplacements = [
          'grand' => $trouverPhoto(fn($p) => $p['categorie'] === 'Mode et voiles'),
          'haut'  => $trouverPhoto(fn($p) => $p['nom'] === 'Parfum corporel') ?? $trouverPhoto(fn($p) => $p['categorie'] === 'Parfums'),
          'bas'   => $trouverPhoto(fn($p) => str_starts_with($p['nom'], 'Sac')) ?? $trouverPhoto(fn($p) => $p['categorie'] === 'Sacs et accessoires'),
          'rond'  => $trouverPhoto(fn($p) => str_starts_with($p['nom'], 'Bijoux')),
          'petit' => $trouverPhoto(fn($p) => $p['categorie'] === 'Chaussures'),
      ];
      $photoAccueil = function (string $cle) use ($emplacements): ?array {
          foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
              if (is_file(__DIR__ . "/assets/img/accueil/$cle.$ext")) {
                  return ['src' => base_url("assets/img/accueil/$cle.$ext"), 'alt' => SITE_NAME];
              }
          }
          $p = $emplacements[$cle];
          return $p ? ['src' => product_image($p['image']), 'alt' => $p['nom']] : null;
      };
      ?>
      <!-- Composition : les univers de la boutique (voiles, parfums, sacs, bijoux, chaussures) -->
      <div class="composition-accueil">
        <span class="compo-fond"></span>
        <span class="compo-cercle"></span>
        <?php foreach (['grand', 'haut', 'bas', 'rond', 'petit'] as $cle): $photo = $photoAccueil($cle); ?>
          <figure class="compo-photo compo-<?= $cle ?>">
            <?php if ($photo): ?>
              <img src="<?= e($photo['src']) ?>" alt="<?= e($photo['alt']) ?>" <?= $cle === 'grand' ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
            <?php else: ?>
              <span class="compo-vide"><i class="fa-regular fa-image"></i>Votre photo</span>
            <?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($categories): ?>
<section class="mb-5">
  <h2 class="section-title">Nos univers</h2>
  <p class="section-sub mb-4">Choisissez ce qui vous fait plaisir.</p>
  <div class="row g-3">
    <?php foreach ($categories as $c): ?>
      <div class="col-6 col-md-4 col-lg">
        <a class="cat-photo" href="<?= e(base_url('produits.php?cat=' . (int)$c['id'])) ?>">
          <?php if ($c['image']): ?><img src="<?= e(product_image($c['image'])) ?>" alt="" loading="lazy"><?php else: ?><span class="thumb-placeholder"><i class="fa-solid <?= e($c['icone']) ?>"></i></span><?php endif; ?>
          <span class="cat-legende"><strong><?= e($c['nom']) ?></strong><small><?= (int)$c['nb'] ?> produit<?= $c['nb'] > 1 ? 's' : '' ?></small></span>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="mb-5">
  <div class="d-flex justify-content-between align-items-end mb-4">
    <div>
      <h2 class="section-title">Produits vedettes</h2>
      <p class="section-sub mb-0">Nouveautés et coups de cœur de la boutique.</p>
    </div>
    <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-outline-rose btn-sm text-nowrap">Tout voir <i class="fa-solid fa-arrow-right ms-1"></i></a>
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
  <div class="panel presentation">
    <div class="row g-4 align-items-center">
      <div class="col-md-8">
        <h2 class="section-title">Bienvenue chez Hawa Aïcha</h2>
        <p class="text-muted mb-0">Hawa Aïcha Boutique propose des produits de beauté, de parfumerie, de mode et des accessoires pour les femmes, les hommes et toute la famille. Notre engagement : une sélection soignée, des prix accessibles et une vente en gros comme au détail. Les arrivages changent régulièrement, revenez souvent !</p>
      </div>
      <div class="col-md-4 text-md-end">
        <a href="<?= e(base_url('apropos.php')) ?>" class="btn btn-outline-rose px-4">Découvrir la boutique</a>
      </div>
    </div>
  </div>
</section>

<section class="mb-5" id="localisation">
  <?php bloc_localisation(false, false); ?>
</section>

<section class="cta-band text-center">
  <h2 class="mb-2">Suivez nos nouveautés</h2>
  <p class="mb-4 opacity-75">Nouveaux arrivages en vidéo sur TikTok et Snapchat.</p>
  <div class="d-flex flex-wrap gap-2 justify-content-center">
    <a class="btn btn-light btn-lg px-4" href="<?= e(TIKTOK_URL) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-tiktok me-1"></i> TikTok</a>
    <a class="btn btn-light btn-lg px-4" href="<?= e(SNAPCHAT_URL) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-snapchat me-1"></i> Snapchat</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
