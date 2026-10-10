<?php
require_once __DIR__ . '/includes/functions.php';

$client = client_connecte();
$val = ['nom' => $client['nom_complet'] ?? '', 'telephone' => $client['telephone'] ?? '', 'message' => ''];
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
    if (mb_strlen($val['nom']) < 2 || mb_strlen($val['nom']) > 150)                          $erreurs[] = 'Veuillez indiquer votre nom.';
    if (strlen(preg_replace('/\D/', '', $val['telephone'])) < 6 || mb_strlen($val['telephone']) > 30) $erreurs[] = 'Indiquez un numéro de téléphone valide pour que nous puissions vous répondre.';
    if (mb_strlen($val['message']) < 5 || mb_strlen($val['message']) > 3000)                   $erreurs[] = 'Veuillez écrire votre message (3000 caractères maximum).';

    if (!$erreurs) {
        // E-mail et sujet ne sont plus demandés : l'e-mail du compte client est conservé s'il est connecté.
        db()->prepare('INSERT INTO messages_contact (nom, telephone, email, sujet, message) VALUES (?, ?, ?, ?, ?)')
            ->execute([$val['nom'], $val['telephone'], $client['email'] ?? null, null, $val['message']]);
        flash('success', '<i class="fa-solid fa-circle-check me-1"></i> Merci ! Votre message a bien été envoyé à la boutique. Nous vous répondons rapidement.');
        redirect(base_url('contact.php'));
    }
}

$page = 'contact';
$titre = 'Contact';
require __DIR__ . '/includes/header.php';
$telLien = normaliser_telephone(CONTACT_TELEPHONE);
?>

<div class="text-center mb-4 mb-lg-5">
  <span class="eyebrow-simple"><i class="fa-solid fa-envelope-open-text me-1"></i> Contact</span>
  <h1 class="page-title mt-2 mb-2">Contactez-nous</h1>
  <p class="text-muted mb-0">Une question sur un produit, un prix ou une commande ? Nous sommes à votre écoute.</p>
</div>

<div class="row g-4 mb-5 justify-content-center">
  <div class="col-lg-5 col-xl-4">
    <div class="panel contact-joindre h-100">
      <h2 class="h5 mb-4">Nous joindre</h2>

      <a class="contact-wa" href="<?= e(wa_link('Bonjour Hawa Aïcha Boutique, j\'aimerais avoir des informations.')) ?>" target="_blank" rel="noopener">
        <i class="fa-brands fa-whatsapp"></i>
        <span><strong class="d-block">Écrire sur WhatsApp</strong><small>Réponse rapide</small></span>
      </a>

      <div class="contact-tel">
        <span class="ic"><i class="fa-solid fa-phone"></i></span>
        <span class="flex-grow-1"><small class="d-block text-muted">Téléphone</small><strong class="text-nowrap"><?= info_boutique(CONTACT_TELEPHONE) ?></strong></span>
        <a class="btn btn-outline-rose btn-sm px-3" href="tel:<?= e($telLien) ?>"><i class="fa-solid fa-phone me-1"></i>Appeler</a>
      </div>
    </div>
  </div>

  <div class="col-lg-7 col-xl-6">
    <div class="panel contact-formulaire">
      <h2 class="h5 mb-1">Envoyer un message</h2>
      <p class="text-muted small mb-4">Nous vous rappelons ou vous répondons au numéro indiqué.</p>
      <?php if ($erreurs): ?>
        <div class="alert alert-danger rounded-4"><ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <form method="post" class="row g-3" data-envoi-unique>
        <?= csrf_field() ?>
        <div class="d-none" aria-hidden="true"><label>Site web<input name="site_web" tabindex="-1" autocomplete="off"></label></div>
        <div class="col-sm-6"><label class="form-label" for="c-nom">Votre nom</label><input class="form-control" id="c-nom" name="nom" value="<?= e($val['nom']) ?>" required maxlength="150" autocomplete="name"></div>
        <div class="col-sm-6"><label class="form-label" for="c-tel">Téléphone</label><input class="form-control" id="c-tel" name="telephone" type="tel" value="<?= e($val['telephone']) ?>" required maxlength="30" autocomplete="tel"></div>
        <div class="col-12"><label class="form-label" for="c-message">Votre message</label><textarea class="form-control" id="c-message" name="message" rows="5" required maxlength="3000" placeholder="Disponibilité, taille, couleur, livraison…"><?= e($val['message']) ?></textarea></div>
        <div class="col-12"><button class="btn btn-rose btn-lg px-4" data-texte-envoi="Envoi…"><i class="fa-solid fa-paper-plane me-2"></i>Envoyer le message</button></div>
      </form>
    </div>
  </div>
</div>

<section id="localisation">
  <?php bloc_localisation(); ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
