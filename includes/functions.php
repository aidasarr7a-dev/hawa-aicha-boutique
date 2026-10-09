<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

const STATUTS = [
    'nouvelle'  => ['Nouvelle',  'warning'],
    'confirmee' => ['Confirmée', 'info'],
    'livree'    => ['Livrée',    'success'],
    'annulee'   => ['Annulée',   'secondary'],
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

function wa_link(string $message = ''): string
{
    $url = 'https://wa.me/' . WHATSAPP_NUMBER;
    return $message !== '' ? $url . '?text=' . rawurlencode($message) : $url;
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
    if ($stock <= 3) {
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
