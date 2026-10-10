<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

// Les détails techniques des erreurs ne sont affichés qu'en local (XAMPP), jamais aux visiteurs
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ['127.0.0.1', '::1'], true)) {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}

// Statuts de commande : [libellé, couleur Bootstrap]
const STATUTS = [
    'nouvelle'       => ['Nouvelle',                'warning'],
    'en_preparation' => ['En préparation',          'info'],
    'prete'          => ['Prête à livrer / remettre', 'primary'],
    'livree'         => ['Livrée / remise',         'success'],
    'annulee'        => ['Annulée',                 'secondary'],
];

// Statuts de paiement (indépendants du statut de commande)
const STATUTS_PAIEMENT = [
    'en_attente' => ['Paiement en attente', 'warning'],
    'paye'       => ['Payé',                'success'],
    'echec'      => ['Échec / annulé',      'danger'],
    'rembourse'  => ['Remboursé',           'secondary'],
];

const ICONES_CATEGORIES = [
    'fa-spray-can-sparkles' => 'Parfum',
    'fa-heart'              => 'Beauté / soins',
    'fa-bag-shopping'       => 'Sacs',
    'fa-shoe-prints'        => 'Chaussures',
    'fa-shirt'              => 'Vêtements',
    'fa-house'              => 'Maison',
    'fa-gem'                => 'Bijoux / accessoires',
    'fa-tag'                => 'Divers',
];

/* ---------- Utilitaires ---------- */

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function fcfa($n): string
{
    return number_format((int)$n, 0, ',', "\u{202F}") . ' FCFA';
}

function base_url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function logo_url(): string
{
    return file_exists(__DIR__ . '/../assets/img/logo.png')
        ? base_url('assets/img/logo.png')
        : base_url('assets/img/logo.svg');
}

/**
 * Lien WhatsApp. Tant que le numéro n'est pas renseigné dans config.php,
 * WhatsApp s'ouvre avec le message et le client choisit le destinataire.
 */
function wa_link(string $message = ''): string
{
    $url = 'https://wa.me/' . WHATSAPP_NUMBER;
    return $message !== '' ? $url . '?text=' . rawurlencode($message) : $url;
}

/** Affiche une coordonnée de config.php, ou « à compléter » si elle est vide. */
function info_boutique(string $valeur, string $siVide = 'À compléter'): string
{
    return trim($valeur) !== '' ? e($valeur) : '<span class="a-completer">' . e($siVide) . '</span>';
}

function badge_statut(string $statut): string
{
    [$label, $cls] = STATUTS[$statut] ?? [$statut, 'secondary'];
    return '<span class="badge text-bg-' . e($cls) . ' rounded-pill">' . e($label) . '</span>';
}

function badge_paiement(?string $statut): string
{
    $statut = $statut ?? 'en_attente';
    [$label, $cls] = STATUTS_PAIEMENT[$statut] ?? [$statut, 'secondary'];
    return '<span class="badge text-bg-' . e($cls) . ' rounded-pill">' . e($label) . '</span>';
}

/** Carte de la boutique (OpenStreetMap intégré, sans clé API) et lien d'itinéraire Google Maps. */
function carte_embed_url(): string
{
    $lat = (float)BOUTIQUE_LAT;
    $lon = (float)BOUTIQUE_LON;
    $bbox = sprintf('%.5f,%.5f,%.5f,%.5f', $lon - 0.006, $lat - 0.003, $lon + 0.006, $lat + 0.003);
    return 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox)
         . '&layer=mapnik&marker=' . rawurlencode(sprintf('%.5f,%.5f', $lat, $lon));
}

function carte_lien(): string
{
    return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode(sprintf('%.5f,%.5f', BOUTIQUE_LAT, BOUTIQUE_LON));
}

/** Bloc « Nous trouver » : carte + adresse + boutons (accueil et page contact). */
function bloc_localisation(bool $avecWhatsapp = true): void
{
    ?>
    <div class="localisation">
      <div class="carte">
        <iframe src="<?= e(carte_embed_url()) ?>" title="Carte : <?= e(BOUTIQUE_ADRESSE) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
      </div>
      <div class="localisation-infos">
        <span class="eyebrow-simple"><i class="fa-solid fa-location-dot me-1"></i> Nous trouver</span>
        <h2 class="section-title mt-2">Venez nous rendre visite</h2>
        <ul class="list-unstyled mt-3 mb-4">
          <li class="d-flex gap-3 mb-3"><span class="mini-ic"><i class="fa-solid fa-store"></i></span><span><strong class="d-block">Adresse</strong><span class="text-muted"><?= info_boutique(BOUTIQUE_ADRESSE) ?></span></span></li>
          <li class="d-flex gap-3 mb-3"><span class="mini-ic"><i class="fa-solid fa-phone"></i></span><span><strong class="d-block">Téléphone</strong><span class="text-muted"><?= info_boutique(CONTACT_TELEPHONE) ?></span></span></li>
          <li class="d-flex gap-3"><span class="mini-ic"><i class="fa-solid fa-envelope"></i></span><span><strong class="d-block">E-mail</strong><span class="text-muted"><?= info_boutique(CONTACT_EMAIL) ?></span></span></li>
        </ul>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-rose px-4" href="<?= e(carte_lien()) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-diamond-turn-right me-1"></i> Itinéraire</a>
          <?php if ($avecWhatsapp): ?>
            <a class="btn btn-wa px-4" href="<?= e(wa_link('Bonjour Hawa Aïcha Boutique, je souhaite passer à la boutique.')) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp me-1"></i> WhatsApp</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
}

/** Logo d'un mode de paiement (images officielles Wave / Orange Money, icône pour les espèces). */
function logo_paiement(string $mode, string $classe = 'logo-pay'): string
{
    $logos = ['Wave' => 'assets/img/paiement/wave.png', 'Orange Money' => 'assets/img/paiement/orange-money-icone.svg'];
    if (isset($logos[$mode])) {
        return '<img class="' . e($classe) . '" src="' . e(base_url($logos[$mode])) . '" alt="' . e($mode) . '">';
    }
    return '<span class="' . e($classe) . ' logo-cash"><i class="fa-solid fa-money-bill-wave"></i></span>';
}

/** Bloc des trois modes de paiement (accueil). */
function bloc_paiements(): void
{
    $numeros = ['Wave' => WAVE_NUMERO, 'Orange Money' => ORANGE_MONEY_NUMERO];
    ?>
    <div class="row g-3">
      <?php foreach (MODES_PAIEMENT as $mode): [, $cls, $desc] = info_paiement($mode); ?>
        <div class="col-md-4">
          <div class="carte-paiement <?= e($cls) ?>">
            <?= logo_paiement($mode) ?>
            <div>
              <h3 class="h6 mb-1"><?= e($mode) ?></h3>
              <p class="small text-muted mb-0"><?= e($desc) ?><?php if (isset($numeros[$mode])): ?><br>Numéro : <strong class="text-nowrap"><?= info_boutique($numeros[$mode], 'communiqué après commande') ?></strong><?php endif; ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-shield-heart me-1" style="color:var(--rose)"></i> Chaque paiement est vérifié par la boutique avant confirmation : votre facture indique toujours le statut réel du paiement.</p>
    <?php
}

/** Présentation d'un mode de paiement : [icône Font Awesome, classe CSS, courte description]. */
function info_paiement(string $mode): array
{
    return [
        'Wave'         => ['fa-solid fa-water', 'pay-wave', 'Paiement mobile Wave'],
        'Orange Money' => ['fa-solid fa-mobile-screen-button', 'pay-om', 'Paiement mobile Orange Money'],
        'Espèces'      => ['fa-solid fa-money-bill-wave', 'pay-cash', 'À la livraison'],
    ][$mode] ?? ['fa-solid fa-wallet', '', ''];
}

/** Consignes de paiement affichées au client selon le mode choisi (HTML). */
function instructions_paiement(string $mode, int $montant, string $numeroCommande): string
{
    $comptes = ['Wave' => WAVE_NUMERO, 'Orange Money' => ORANGE_MONEY_NUMERO];
    if (isset($comptes[$mode])) {
        $numero = trim($comptes[$mode]) !== ''
            ? '<strong class="text-nowrap">' . e($comptes[$mode]) . '</strong>'
            : 'le numéro qui vous sera communiqué par la boutique';
        return 'Envoyez <strong class="text-nowrap">' . fcfa($montant) . '</strong> par <strong>' . e($mode) . '</strong> au ' . $numero
             . ', en indiquant la référence <strong>' . e($numeroCommande) . '</strong>. '
             . 'Votre paiement sera confirmé par la boutique après vérification.';
    }
    return 'Vous réglez <strong class="text-nowrap">' . fcfa($montant) . '</strong> en <strong>espèces</strong> à la livraison ou au retrait de votre commande. '
         . 'Le paiement est enregistré à la réception de l\'argent.';
}

/**
 * Panneau « Payer ma commande » : logo, montant, numéro à copier, accès à l'application,
 * puis formulaire « J'ai payé » où le client indique la référence de sa transaction.
 */
function bloc_payer(array $cmd, array $paiement): void
{
    $mode = $paiement['mode_paiement'];
    $statut = $paiement['statut_paiement'];
    $numero = ['Wave' => WAVE_NUMERO, 'Orange Money' => ORANGE_MONEY_NUMERO][$mode] ?? '';
    ?>
    <div class="payer <?= e(info_paiement($mode)[1]) ?>">
      <div class="d-flex align-items-center gap-3 mb-3">
        <?= logo_paiement($mode, 'logo-pay logo-pay-lg') ?>
        <div class="flex-grow-1">
          <div class="small text-muted">Paiement par</div>
          <strong class="fs-5"><?= e($mode) ?></strong>
        </div>
        <?= badge_paiement($statut) ?>
      </div>

      <?php if ($statut === 'en_attente' && $cmd['statut'] !== 'annulee' && $numero !== ''): ?>
        <div class="payer-etapes">
          <div><span>1</span> Montant à envoyer : <strong class="text-nowrap"><?= fcfa($cmd['total']) ?></strong></div>
          <div><span>2</span> Numéro <?= e($mode) ?> : <strong class="text-nowrap"><?= e($numero) ?></strong>
            <button type="button" class="btn btn-sm btn-outline-rose py-0 ms-1" data-copier="<?= e(preg_replace('/\D/', '', $numero)) ?>"><i class="fa-regular fa-copy"></i> Copier</button></div>
          <div><span>3</span> Référence à indiquer : <strong><?= e($cmd['numero_commande']) ?></strong></div>
        </div>
        <div class="actions-paiement my-3">
          <?php if ($mode === 'Orange Money'): ?>
            <a class="btn btn-pay-om btn-lg w-100" href="tel:%23144%23"><i class="fa-solid fa-phone me-2"></i>Payer avec Orange Money · composer #144#</a>
            <div class="qr-paiement" data-qr="tel:#144#" hidden>
              <div class="qr-code"></div>
              <small><strong>Vous êtes sur ordinateur ?</strong><br>Scannez ce code avec votre téléphone : il compose #144# pour payer avec Orange Money.</small>
            </div>
          <?php elseif ($mode === 'Wave' && WAVE_LIEN_PAIEMENT !== ''): ?>
            <a class="btn btn-pay-wave btn-lg w-100" href="<?= e(WAVE_LIEN_PAIEMENT) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-mobile-screen me-2"></i>Payer avec Wave</a>
          <?php else: ?>
            <a class="btn btn-pay-wave btn-lg w-100" href="https://www.wave.com/fr/" data-ouvrir-wave target="_blank" rel="noopener"><i class="fa-solid fa-mobile-screen me-2"></i>Payer avec Wave · ouvrir l'application</a>
            <small class="d-block text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i>Dans Wave, touchez « Envoyer », collez le numéro ci-dessus puis le montant.</small>
          <?php endif; ?>
        </div>
        <?php if (empty($paiement['date_declaration'])): ?>
          <form method="post" action="<?= e(base_url('declarer_paiement.php')) ?>" class="payer-declaration">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$cmd['id'] ?>">
            <label class="form-label small fw-semibold" for="ref-<?= (int)$cmd['id'] ?>">Vous avez payé ? Indiquez la référence de la transaction (reçue par SMS)</label>
            <div class="input-group">
              <input class="form-control" id="ref-<?= (int)$cmd['id'] ?>" name="reference" maxlength="100" required placeholder="Référence du SMS <?= e($mode) ?>">
              <button class="btn btn-rose">J'ai payé</button>
            </div>
          </form>
        <?php else: ?>
          <div class="alert alert-info rounded-4 small mb-0"><i class="fa-solid fa-hourglass-half me-1"></i> Paiement déclaré le <?= e(date('d/m/Y à H:i', strtotime($paiement['date_declaration']))) ?> (réf. <?= e($paiement['reference_client']) ?>). La boutique vérifie la réception puis confirme : votre facture passera à « Payé ».</div>
        <?php endif; ?>
      <?php elseif ($statut === 'en_attente' && $cmd['statut'] !== 'annulee'): ?>
        <div class="paiement-especes">
          <i class="fa-solid fa-circle-check"></i>
          <div><strong>Paiement en espèces enregistré</strong><br>
            <span class="small"><?= instructions_paiement($mode, (int)$cmd['total'], $cmd['numero_commande']) ?> Vous n'avez rien d'autre à faire maintenant.</span></div>
        </div>
      <?php elseif ($statut === 'paye'): ?>
        <p class="small mb-0 text-success"><i class="fa-solid fa-circle-check me-1"></i> Paiement reçu et confirmé par la boutique. Merci !</p>
      <?php endif; ?>
    </div>
    <?php
}

/* ---------- Espace client ---------- */

/** Client connecté (ou null). */
function client_connecte(): ?array
{
    static $client = false;
    if ($client === false) {
        $client = null;
        if (!empty($_SESSION['client_id'])) {
            $st = db()->prepare('SELECT * FROM clients WHERE id = ? AND mot_de_passe_hash IS NOT NULL');
            $st->execute([(int)$_SESSION['client_id']]);
            $client = $st->fetch() ?: null;
            if (!$client) {
                unset($_SESSION['client_id']);
            }
        }
    }
    return $client;
}

function require_client(): array
{
    $client = client_connecte();
    if (!$client) {
        flash('info', 'Connectez-vous à votre espace client pour continuer.');
        redirect(base_url('compte.php?retour=' . rawurlencode(basename($_SERVER['SCRIPT_NAME']))));
    }
    return $client;
}

/** Le visiteur peut-il voir cette commande ? (admin, client propriétaire, ou client qui vient de commander) */
function peut_voir_commande(array $cmd): bool
{
    if (!empty($_SESSION['admin_id'])) {
        return true;
    }
    if ((int)($_SESSION['derniere_commande'] ?? 0) === (int)$cmd['id']) {
        return true;
    }
    $client = client_connecte();
    return $client && (int)$cmd['client_id'] === (int)$client['id'];
}

/** Numéro de téléphone normalisé : chiffres (et + initial), sans espaces. */
function normaliser_telephone(string $tel): string
{
    return preg_replace('/[^\d+]/', '', $tel);
}

/** Numéro de commande lisible et unique, basé sur l'identifiant : HA-2026-00012 */
function numero_commande(int $id, ?string $date = null): string
{
    return 'HA-' . date('Y', $date ? strtotime($date) : time()) . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
}

/* ---------- CSRF & messages flash ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Session expirée. Merci de recharger la page et de réessayer.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '';
    foreach ($_SESSION['flash'] as [$type, $msg]) {
        $html .= '<div class="alert alert-' . e($type) . ' alert-dismissible fade show rounded-4" role="alert">'
              . $msg
              . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/* ---------- Catalogue ---------- */

function get_categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
}

function stock_info(int $stock): array
{
    if ($stock <= 0) {
        return ['Épuisé', 'soldout'];
    }
    if ($stock <= SEUIL_STOCK) {
        return ['Dernières pièces', 'low'];
    }
    return ['En stock', 'ok'];
}

function product_image(?string $img): ?string
{
    return $img ? base_url('uploads/' . rawurlencode($img)) : null;
}

function product_card(array $p): void
{
    [$label, $cls] = stock_info((int)$p['stock']);
    $img = product_image($p['image'] ?? null);
    $icone = $p['icone'] ?? 'fa-gem';
    ?>
    <div class="col-6 col-md-4 col-lg-3">
      <article class="product-card h-100">
        <a href="<?= e(base_url('produit.php?id=' . (int)$p['id'])) ?>" class="product-thumb">
          <?php if ($img): ?>
            <img src="<?= e($img) ?>" alt="<?= e($p['nom']) ?>" loading="lazy">
          <?php else: ?>
            <span class="thumb-placeholder"><i class="fa-solid <?= e($icone) ?>"></i></span>
          <?php endif; ?>
          <?php if (!empty($p['nouveau'])): ?><span class="ribbon">Nouveau</span><?php endif; ?>
        </a>
        <div class="product-body">
          <?php if (!empty($p['categorie'])): ?><div class="product-cat"><?= e($p['categorie']) ?></div><?php endif; ?>
          <h3 class="product-name"><a href="<?= e(base_url('produit.php?id=' . (int)$p['id'])) ?>"><?= e($p['nom']) ?></a></h3>
          <div class="product-price"><?= fcfa($p['prix']) ?></div>
          <span class="stock-badge stock-<?= e($cls) ?>"><?= e($label) ?></span>
          <?php if ((int)$p['stock'] > 0): ?>
            <form method="post" action="<?= e(base_url('panier.php')) ?>" class="mt-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="retour" value="<?= e(basename($_SERVER['SCRIPT_NAME']) . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
              <button class="btn btn-rose btn-sm w-100"><i class="fa-solid fa-bag-shopping me-1"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a class="btn btn-outline-rose btn-sm w-100 mt-2"
               href="<?= e(wa_link('Bonjour, le produit « ' . $p['nom'] . ' » m\'intéresse. Sera-t-il bientôt disponible ?')) ?>"
               target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp me-1"></i> Me prévenir</a>
          <?php endif; ?>
        </div>
      </article>
    </div>
    <?php
}

/* ---------- Panier (session) ---------- */

function cart_get(): array
{
    return $_SESSION['panier'] ?? [];
}

function cart_count(): int
{
    return (int)array_sum(cart_get());
}

/**
 * Retourne [lignes, total]. Retire du panier les produits devenus indisponibles
 * et ajuste les quantités au stock réel.
 */
function cart_items(): array
{
    $cart = cart_get();
    if (!$cart) {
        return [[], 0];
    }
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = db()->prepare(
        "SELECT p.*, c.nom AS categorie, c.icone
           FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
          WHERE p.actif = 1 AND p.id IN ($in)"
    );
    $st->execute($ids);
    $rows = [];
    foreach ($st->fetchAll() as $r) {
        $rows[(int)$r['id']] = $r;
    }

    $lignes = [];
    $total  = 0;
    $newCart = [];
    foreach ($cart as $id => $qty) {
        $id = (int)$id;
        if (!isset($rows[$id]) || (int)$rows[$id]['stock'] <= 0) {
            continue;
        }
        $qty = max(1, min((int)$qty, (int)$rows[$id]['stock']));
        $newCart[$id] = $qty;
        $sous = $qty * (int)$rows[$id]['prix'];
        $total += $sous;
        $lignes[] = ['p' => $rows[$id], 'qty' => $qty, 'sous_total' => $sous];
    }
    $_SESSION['panier'] = $newCart;
    return [$lignes, $total];
}

/* ---------- Envoi d'image ---------- */

function upload_image(array $file, ?string &$erreur = null): ?string
{
    $erreur = null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $erreur = "L'envoi de l'image a échoué.";
        return null;
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        $erreur = "L'image dépasse 3 Mo.";
        return null;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) {
        $erreur = 'Format non accepté (JPG, PNG ou WebP uniquement).';
        return null;
    }
    $nom = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $nom)) {
        $erreur = "Impossible d'enregistrer l'image.";
        return null;
    }
    return $nom;
}

function delete_image(?string $nom): void
{
    if ($nom && preg_match('/^[a-f0-9]+\.(jpg|png|webp)$/', $nom)) {
        @unlink(__DIR__ . '/../uploads/' . $nom);
    }
}

/** Frise de suivi d'une commande : Nouvelle → En préparation → Prête → Livrée (ou Annulée). */
function suivi_commande(string $statut): string
{
    if ($statut === 'annulee') {
        return '<div class="alert alert-secondary rounded-4 small mb-0"><i class="fa-solid fa-ban me-1"></i> Cette commande a été annulée.</div>';
    }
    $etapes = ['nouvelle' => 'Reçue', 'en_preparation' => 'En préparation', 'prete' => 'Prête', 'livree' => 'Livrée / remise'];
    $rang = array_search($statut, array_keys($etapes), true);
    $html = '<ol class="suivi">';
    $i = 0;
    foreach ($etapes as $label) {
        $html .= '<li class="' . ($i <= $rang ? 'fait' : '') . '"><span></span>' . e($label) . '</li>';
        $i++;
    }
    return $html . '</ol>';
}

/** Adresse de livraison affichable : « adresse, ville » sans virgule superflue si la ville est vide. */
function adresse_commande(array $cmd): string
{
    return implode(', ', array_filter([trim((string)($cmd['adresse_livraison'] ?? '')), trim((string)($cmd['ville'] ?? ''))], 'strlen')) ?: '—';
}
