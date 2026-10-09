<?php
$page = 'contact';
$titre = 'Contact';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title mb-1">Contactez-nous</h1>
<p class="text-muted mb-4">Une question sur un produit, un prix, une commande en gros ou la disponibilité ? Nous répondons rapidement.</p>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <a class="contact-card" href="<?= e(wa_link('Bonjour Hawa Aïcha Boutique, j\'aimerais avoir des informations.')) ?>" target="_blank" rel="noopener">
      <span class="ic wa"><i class="fa-brands fa-whatsapp"></i></span>
      <span><strong class="d-block">WhatsApp</strong><span class="text-muted small">Écrivez-nous pour commander ou poser une question</span></span>
    </a>
  </div>
  <div class="col-sm-6 col-lg-3">
    <?php if (CONTACT_TELEPHONE !== ''): ?>
      <a class="contact-card" href="tel:<?= e(preg_replace('/[^\d+]/', '', CONTACT_TELEPHONE)) ?>">
    <?php else: ?>
      <div class="contact-card">
    <?php endif; ?>
      <span class="ic"><i class="fa-solid fa-phone"></i></span>
      <span><strong class="d-block">Téléphone</strong><span class="text-muted small"><?= info_boutique(CONTACT_TELEPHONE) ?></span></span>
    <?= CONTACT_TELEPHONE !== '' ? '</a>' : '</div>' ?>
  </div>
  <div class="col-sm-6 col-lg-3">
    <?php if (CONTACT_EMAIL !== ''): ?>
      <a class="contact-card" href="mailto:<?= e(CONTACT_EMAIL) ?>">
    <?php else: ?>
      <div class="contact-card">
    <?php endif; ?>
      <span class="ic"><i class="fa-solid fa-envelope"></i></span>
      <span><strong class="d-block">E-mail</strong><span class="text-muted small"><?= info_boutique(CONTACT_EMAIL, 'Bientôt disponible') ?></span></span>
    <?= CONTACT_EMAIL !== '' ? '</a>' : '</div>' ?>
  </div>
  <div class="col-sm-6 col-lg-3">
    <a class="contact-card" href="<?= e(TIKTOK_URL) ?>" target="_blank" rel="noopener">
      <span class="ic"><i class="fa-brands fa-tiktok"></i></span>
      <span><strong class="d-block">TikTok <?= e(TIKTOK_HANDLE) ?></strong><span class="text-muted small">Nouveautés et arrivages en vidéo</span></span>
    </a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel">
      <h2 class="h5 mb-3">Formulaire de contact</h2>
      <form id="wa-form" data-number="<?= e(WHATSAPP_NUMBER) ?>" class="row g-3">
        <div class="col-sm-6"><label class="form-label" for="c-nom">Votre nom</label><input class="form-control" id="c-nom" name="nom" autocomplete="name"></div>
        <div class="col-sm-6"><label class="form-label" for="c-produit">Produit concerné</label><input class="form-control" id="c-produit" name="produit" placeholder="Ex : sac à main"></div>
        <div class="col-12"><label class="form-label" for="c-message">Votre message *</label><textarea class="form-control" id="c-message" name="message" rows="4" required placeholder="Disponibilité, taille, couleur, livraison, commande en gros…"></textarea></div>
        <div class="col-12">
          <button class="btn btn-wa btn-lg"><i class="fa-brands fa-whatsapp me-2"></i>Envoyer via WhatsApp</button>
          <div class="small text-muted mt-2">Le message s'ouvre dans WhatsApp, prêt à être envoyé à la boutique.</div>
        </div>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel h-100">
      <h2 class="h5 mb-3">Bon à savoir</h2>
      <ul class="list-unstyled mb-0">
        <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-location-dot mt-1" style="color:var(--rose)"></i><span><strong>Adresse de la boutique</strong><br><span class="text-muted small"><?= info_boutique(BOUTIQUE_ADRESSE) ?></span></span></li>
        <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-truck-fast mt-1" style="color:var(--rose)"></i><span><strong>Zone de livraison</strong><br><span class="text-muted small"><?= info_boutique(ZONE_LIVRAISON, 'À préciser avec la boutique') ?> · retrait possible en boutique. Les frais dépendent de votre zone.</span></span></li>
        <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-wallet mt-1" style="color:var(--rose)"></i><span><strong>Paiement</strong><br><span class="text-muted small">Wave, Orange Money ou espèces (à la livraison ou au retrait).</span></span></li>
        <li class="d-flex gap-3"><i class="fa-solid fa-rotate mt-1" style="color:var(--rose)"></i><span><strong>Nouveautés</strong><br><span class="text-muted small">Arrivages réguliers : suivez-nous sur TikTok.</span></span></li>
      </ul>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
