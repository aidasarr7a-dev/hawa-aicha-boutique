</main>

<footer class="site-footer mt-5">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="d-flex align-items-center gap-2 mb-2">
          <img src="<?= e(logo_url()) ?>" alt="" height="40">
          <span class="brand-text">Hawa Aïcha</span>
        </div>
        <p class="fst-italic mb-2" style="color:var(--rose-dark)"><?= e(SITE_SLOGAN) ?></p>
        <p class="mb-0 text-muted small">Beauté, parfumerie, mode et accessoires pour femmes, hommes et familles. Des produits de qualité à prix accessibles, en gros comme au détail.</p>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="footer-title">Navigation</h6>
        <ul class="list-unstyled mb-0">
          <li><a href="<?= e(base_url('index.php')) ?>">Accueil</a></li>
          <li><a href="<?= e(base_url('produits.php')) ?>">Produits</a></li>
          <li><a href="<?= e(base_url('apropos.php')) ?>">À propos</a></li>
          <li><a href="<?= e(base_url('panier.php')) ?>">Panier</a></li>
          <li><a href="<?= e(base_url('contact.php')) ?>">Contact</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-3">
        <h6 class="footer-title">Nous trouver</h6>
        <ul class="list-unstyled small text-muted mb-0">
          <li><i class="fa-solid fa-location-dot me-1" style="color:var(--rose)"></i> <?= info_boutique(BOUTIQUE_ADRESSE) ?></li>
          <li><i class="fa-solid fa-map me-1" style="color:var(--rose)"></i> <a href="<?= e(carte_lien()) ?>" target="_blank" rel="noopener">Voir sur la carte</a></li>
          <li><i class="fa-solid fa-phone me-1" style="color:var(--rose)"></i> <?= info_boutique(CONTACT_TELEPHONE) ?></li>
        </ul>
        <div class="d-flex flex-wrap gap-1 mt-3">
          <?php foreach (MODES_PAIEMENT as $mode): [$ico, $cls] = info_paiement($mode); ?>
            <span class="pastille-paiement <?= e($cls) ?>"><i class="<?= e($ico) ?>"></i> <?= e($mode) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-md-3">
        <h6 class="footer-title">Suivez-nous</h6>
        <div class="d-flex gap-2 mb-3">
          <a class="social" href="<?= e(TIKTOK_URL) ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
          <a class="social" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
        <div class="small text-muted">TikTok : <?= e(TIKTOK_HANDLE) ?></div>
      </div>
    </div>
    <hr class="my-4">
    <div class="small text-muted d-flex flex-wrap justify-content-between gap-2">
      <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. Tous droits réservés.</span>
      <a href="<?= e(base_url('admin/login.php')) ?>" class="text-muted">Espace administrateur</a>
    </div>
  </div>
</footer>

<a class="wa-float" href="<?= e(wa_link('Bonjour Hawa Aïcha Boutique, j\'aimerais avoir des informations.')) ?>" target="_blank" rel="noopener" aria-label="Discuter sur WhatsApp">
  <i class="fa-brands fa-whatsapp"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
