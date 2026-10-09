<?php
require_once __DIR__ . '/includes/functions.php';

$client = client_connecte();
$val = ['nom' => $client['nom_complet'] ?? '', 'telephone' => $client['telephone'] ?? '', 'email' => $client['email'] ?? '', 'sujet' => '', 'message' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($val as $k => $_) {
        $val[$k] = trim((string)($_POST[$k] ?? ''));
    }
    // Champ piège invisible : rempli uniquement par les robots
    if (($_POST['site_web'] ?? '') !== '') {
        redirect(base_url('contact.php'));
    }
    if (mb_strlen($val['nom']) < 2 || mb_strlen($val['nom']) > 150)                 $erreurs[] = 'Veuillez indiquer votre nom.';
    if ($val['telephone'] === '' && $val['email'] === '')                             $erreurs[] = 'Indiquez un téléphone ou un e-mail pour que nous puissions vous répondre.';
    if ($val['email'] !== '' && !filter_var($val['email'], FILTER_VALIDATE_EMAIL))    $erreurs[] = 'L\'adresse e-mail n\'est pas valide.';
    if (mb_strlen($val['telephone']) > 30 || mb_strlen($val['sujet']) > 150)          $erreurs[] = 'Un des champs est trop long.';
    if (mb_strlen($val['message']) < 5 || mb_strlen($val['message']) > 3000)          $erreurs[] = 'Veuillez écrire votre message (3000 caractères maximum).';

    if (!$erreurs) {
        db()->prepare('INSERT INTO messages_contact (nom, telephone, email, sujet, message) VALUES (?, ?, ?, ?, ?)')
            ->execute([$val['nom'], $val['telephone'] ?: null, $val['email'] ?: null, $val['sujet'] ?: null, $val['message']]);
        flash('success', '<i class="fa-solid fa-circle-check me-1"></i> Merci ! Votre message a bien été envoyé à la boutique. Nous vous répondons rapidement.');
        redirect(base_url('contact.php'));
    }
}

$page = 'contact';
$titre = 'Contact';
require __DIR__ . '/includes/header.php';
?>

<div class="text-center mb-4">
  <span class="eyebrow-simple"><i class="fa-solid fa-envelope-open-text me-1"></i> Contact</span>
  <h1 class="page-title mt-2 mb-1">Contactez-nous</h1>
  <p class="text-muted mb-0">Une question sur un produit, un prix, une commande en gros ? Écrivez-nous.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <a class="contact-card" href="tel:<?= e(normaliser_telephone(CONTACT_TELEPHONE)) ?>">
      <span class="ic"><i class="fa-solid fa-phone"></i></span>
      <span><strong class="d-block">Téléphone</strong><span class="text-muted small text-nowrap"><?= info_boutique(CONTACT_TELEPHONE) ?></span></span>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a class="contact-card" href="mailto:<?= e(CONTACT_EMAIL) ?>">
      <span class="ic"><i class="fa-solid fa-envelope"></i></span>
      <span class="text-break"><strong class="d-block">E-mail</strong><span class="text-muted small"><?= info_boutique(CONTACT_EMAIL) ?></span></span>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a class="contact-card" href="<?= e(wa_link('Bonjour Hawa Aïcha Boutique, j\'aimerais avoir des informations.')) ?>" target="_blank" rel="noopener">
      <span class="ic wa"><i class="fa-brands fa-whatsapp"></i></span>
      <span><strong class="d-block">WhatsApp</strong><span class="text-muted small">Discutons</span></span>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="contact-card">
      <span class="ic"><i class="fa-solid fa-hashtag"></i></span>
      <span><strong class="d-block">Réseaux</strong>
        <span class="d-flex gap-2 mt-1">
          <a class="social social-sm" href="<?= e(TIKTOK_URL) ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
          <a class="social social-sm" href="<?= e(SNAPCHAT_URL) ?>" target="_blank" rel="noopener" aria-label="Snapchat"><i class="fa-brands fa-snapchat"></i></a>
        </span>
      </span>
    </div>
  </div>
</div>

<div class="row g-4 mb-5">
  <div class="col-lg-7">
    <div class="panel">
      <h2 class="h5 mb-3">Envoyer un message</h2>
      <?php if ($erreurs): ?>
        <div class="alert alert-danger rounded-4"><ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <form method="post" class="row g-3" data-envoi-unique>
        <?= csrf_field() ?>
        <div class="d-none" aria-hidden="true"><label>Site web<input name="site_web" tabindex="-1" autocomplete="off"></label></div>
        <div class="col-sm-6"><label class="form-label" for="c-nom">Votre nom *</label><input class="form-control" id="c-nom" name="nom" value="<?= e($val['nom']) ?>" required maxlength="150" autocomplete="name"></div>
        <div class="col-sm-6"><label class="form-label" for="c-tel">Téléphone</label><input class="form-control" id="c-tel" name="telephone" type="tel" value="<?= e($val['telephone']) ?>" maxlength="30" autocomplete="tel"></div>
        <div class="col-sm-6"><label class="form-label" for="c-email">E-mail</label><input class="form-control" id="c-email" name="email" type="email" value="<?= e($val['email']) ?>" maxlength="150" autocomplete="email"></div>
        <div class="col-sm-6"><label class="form-label" for="c-sujet">Sujet</label><input class="form-control" id="c-sujet" name="sujet" value="<?= e($val['sujet']) ?>" maxlength="150" placeholder="Ex : commande en gros"></div>
        <div class="col-12"><label class="form-label" for="c-message">Votre message *</label><textarea class="form-control" id="c-message" name="message" rows="5" required maxlength="3000" placeholder="Disponibilité, taille, couleur, livraison…"><?= e($val['message']) ?></textarea></div>
        <div class="col-12"><button class="btn btn-rose btn-lg px-4" data-texte-envoi="Envoi…"><i class="fa-solid fa-paper-plane me-2"></i>Envoyer le message</button></div>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel h-100">
      <h2 class="h5 mb-3">Bon à savoir</h2>
      <ul class="list-unstyled mb-0">
        <li class="d-flex gap-3 mb-3"><span class="mini-ic"><i class="fa-solid fa-truck-fast"></i></span><span><strong>Livraison</strong><br><span class="text-muted small">À domicile (frais selon la zone) ou retrait à la boutique.</span></span></li>
        <li class="d-flex gap-3 mb-3"><span class="mini-ic"><i class="fa-solid fa-wallet"></i></span><span><strong>Paiement</strong><br>
          <span class="d-flex flex-wrap gap-2 mt-1"><?php foreach (MODES_PAIEMENT as $m): ?><span class="pastille-logo"><?= logo_paiement($m) ?> <?= e($m) ?></span><?php endforeach; ?></span></span></li>
        <li class="d-flex gap-3"><span class="mini-ic"><i class="fa-solid fa-boxes-stacked"></i></span><span><strong>Gros et détail</strong><br><span class="text-muted small">Revendeuses : demandez nos conditions pour les commandes en quantité.</span></span></li>
      </ul>
    </div>
  </div>
</div>

<section id="localisation">
  <?php bloc_localisation(); ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
