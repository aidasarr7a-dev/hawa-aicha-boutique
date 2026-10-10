</main>

<footer class="site-footer">
  <div class="footer-haut">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
      <p class="footer-slogan mb-0"><?= e(SITE_SLOGAN) ?></p>
      <div class="d-flex gap-2">
        <a class="social" href="<?= e(TIKTOK_URL) ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
        <a class="social" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        <a class="social" href="<?= e(SNAPCHAT_URL) ?>" target="_blank" rel="noopener" aria-label="Snapchat"><i class="fa-brands fa-snapchat"></i></a>
      </div>
    </div>
  </div>

  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <img src="<?= e(logo_url()) ?>" alt="" height="46">
          <span class="lh-1"><span class="brand-text d-block">Hawa Aïcha</span><span class="brand-sous">Boutique</span></span>
        </div>
        <p class="small mb-0 footer-texte">Beauté, parfumerie, mode, accessoires et décoration pour les femmes, les hommes et toute la famille. Des produits de qualité à prix accessibles, en gros comme au détail.</p>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <h6 class="footer-title">La boutique</h6>
        <ul class="list-unstyled mb-0">
          <li><a href="<?= e(base_url('index.php')) ?>">Accueil</a></li>
          <li><a href="<?= e(base_url('produits.php')) ?>">Produits</a></li>
          <li><a href="<?= e(base_url('commander.php')) ?>">Commande</a></li>
          <li><a href="<?= e(base_url('apropos.php')) ?>">À propos</a></li>
          <li><a href="<?= e(base_url('contact.php')) ?>">Contact</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <h6 class="footer-title">Mon espace</h6>
        <ul class="list-unstyled mb-0">
          <?php if (client_connecte()): ?>
            <li><a href="<?= e(base_url('mon-compte.php')) ?>">Mes commandes</a></li>
            <li><a href="<?= e(base_url('mon-compte.php?onglet=infos')) ?>">Mes informations</a></li>
          <?php else: ?>
            <li><a href="<?= e(base_url('compte.php')) ?>">Se connecter</a></li>
            <li><a href="<?= e(base_url('compte.php?onglet=inscription')) ?>">Créer un compte</a></li>
          <?php endif; ?>
          <li><a href="<?= e(base_url('panier.php')) ?>">Mon panier</a></li>
        </ul>
      </div>
      <div class="col-md-4 col-lg-4">
        <h6 class="footer-title">Nous contacter</h6>
        <ul class="list-unstyled footer-contact mb-0">
          <li><i class="fa-solid fa-location-dot"></i> <a href="<?= e(carte_lien()) ?>" target="_blank" rel="noopener"><?= e(BOUTIQUE_ADRESSE) ?></a></li>
          <li><i class="fa-solid fa-phone"></i> <a href="tel:<?= e(normaliser_telephone(CONTACT_TELEPHONE)) ?>"><?= info_boutique(CONTACT_TELEPHONE) ?></a></li>
          <li><i class="fa-solid fa-envelope"></i> <a class="text-break" href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= info_boutique(CONTACT_EMAIL) ?></a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="footer-bas">
    <div class="container d-flex flex-wrap justify-content-between gap-2 small">
      <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. Tous droits réservés.</span>
      <a href="<?= e(base_url('admin/login.php')) ?>"><i class="fa-solid fa-lock me-1"></i>Espace administrateur</a>
    </div>
  </div>
</footer>

<?php if (($page ?? '') !== 'accueil'): ?>
<a class="wa-float" href="<?= e(wa_link('Bonjour Hawa Aïcha Boutique, j\'aimerais avoir des informations.')) ?>" target="_blank" rel="noopener" aria-label="Discuter sur WhatsApp">
  <i class="fa-brands fa-whatsapp"></i>
</a>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
