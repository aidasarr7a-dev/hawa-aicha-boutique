<?php
require_once __DIR__ . '/includes/functions.php';

/* ---------- Actions (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);
    $retour = $_POST['retour'] ?? '';
    // Redirection uniquement vers une page locale du site
    if (!preg_match('/^[a-z_]+\.php(\?[\w=&%.\-]*)?$/i', $retour)) {
        $retour = 'panier.php';
    }

    if ($action === 'add' && $id > 0) {
        $st = db()->prepare('SELECT id, nom, stock FROM produits WHERE id = ? AND actif = 1');
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p || (int)$p['stock'] <= 0) {
            flash('danger', 'Ce produit n\'est plus disponible.');
        } else {
            $qty = max(1, (int)($_POST['qty'] ?? 1));
            $actuel = $_SESSION['panier'][$id] ?? 0;
            $nouveau = min($actuel + $qty, (int)$p['stock']);
            $_SESSION['panier'][$id] = $nouveau;
            $msg = '<i class="fa-solid fa-circle-check me-1"></i> <strong>' . e($p['nom']) . '</strong> ajouté au panier. '
                 . '<a href="' . e(base_url('panier.php')) . '" class="alert-link">Voir le panier</a>';
            if ($actuel + $qty > (int)$p['stock']) {
                $msg .= '<br><small>Quantité limitée au stock disponible (' . (int)$p['stock'] . ').</small>';
            }
            flash('success', $msg);
        }
    } elseif ($action === 'update' && $id > 0 && isset($_SESSION['panier'][$id])) {
        $qty = (int)($_POST['qty'] ?? 1);
        if ($qty <= 0) {
            unset($_SESSION['panier'][$id]);
        } else {
            $_SESSION['panier'][$id] = $qty; // ajusté au stock dans cart_items()
        }
    } elseif ($action === 'remove' && $id > 0) {
        unset($_SESSION['panier'][$id]);
        flash('success', 'Produit retiré du panier.');
    } elseif ($action === 'clear') {
        $_SESSION['panier'] = [];
    }
    redirect(base_url($retour));
}

$page = 'panier';
$titre = 'Mon panier';
require __DIR__ . '/includes/header.php';

[$lignes, $total] = cart_items();
?>

<h1 class="page-title mb-2">Mon panier</h1>
<div class="steps mb-4"><span class="on">1. Panier</span><span>2. Mes informations</span><span>3. Confirmation</span></div>

<?php if (!$lignes): ?>
  <div class="panel empty-state">
    <i class="fa-solid fa-bag-shopping"></i>
    <p class="mt-3 mb-3 text-muted">Votre panier est vide pour le moment.</p>
    <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Découvrir les produits</a>
  </div>
<?php else: ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="panel">
        <?php foreach ($lignes as $l): $p = $l['p']; $img = product_image($p['image']); ?>
          <div class="cart-line">
            <a class="cart-thumb" href="<?= e(base_url('produit.php?id=' . (int)$p['id'])) ?>">
              <?php if ($img): ?><img src="<?= e($img) ?>" alt=""><?php else: ?><i class="fa-solid <?= e($p['icone'] ?? 'fa-gem') ?>"></i><?php endif; ?>
            </a>
            <div class="flex-grow-1">
              <a class="fw-medium text-reset" href="<?= e(base_url('produit.php?id=' . (int)$p['id'])) ?>"><?= e($p['nom']) ?></a>
              <div class="text-muted small"><?= fcfa($p['prix']) ?> l'unité</div>
              <form method="post" class="d-flex align-items-center gap-2 mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button type="button" class="btn btn-outline-rose btn-sm rounded-circle" data-qty-step="-1" data-target="q<?= (int)$p['id'] ?>" aria-label="Moins"><i class="fa-solid fa-minus"></i></button>
                <input id="q<?= (int)$p['id'] ?>" type="number" name="qty" value="<?= $l['qty'] ?>" min="1" max="<?= (int)$p['stock'] ?>" class="form-control form-control-sm qty-input" data-autosubmit="1" onchange="this.form.submit()">
                <button type="button" class="btn btn-outline-rose btn-sm rounded-circle" data-qty-step="1" data-target="q<?= (int)$p['id'] ?>" aria-label="Plus"><i class="fa-solid fa-plus"></i></button>
              </form>
            </div>
            <div class="text-end">
              <div class="fw-semibold"><?= fcfa($l['sous_total']) ?></div>
              <form method="post" class="mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="btn btn-link btn-sm text-danger p-0" aria-label="Retirer"><i class="fa-solid fa-trash-can me-1"></i>Retirer</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <a href="<?= e(base_url('produits.php')) ?>" class="d-inline-block mt-3"><i class="fa-solid fa-arrow-left me-1"></i> Continuer mes achats</a>
    </div>

    <div class="col-lg-4">
      <div class="panel">
        <h2 class="h5 mb-3">Récapitulatif</h2>
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Articles</span><span><?= cart_count() ?></span></div>
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Livraison</span><span class="small text-muted">selon votre zone</span></div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3"><span class="fw-medium">Total</span><span class="total-line"><?= fcfa($total) ?></span></div>
        <a href="<?= e(base_url('commander.php')) ?>" class="btn btn-rose w-100 btn-lg">Commander <i class="fa-solid fa-arrow-right ms-1"></i></a>
        <form method="post" class="mt-2 text-center">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="clear">
          <button class="btn btn-link btn-sm text-muted">Vider le panier</button>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
