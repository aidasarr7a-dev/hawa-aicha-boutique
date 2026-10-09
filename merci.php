<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
// Seul le client qui vient de commander peut voir cette page
if ($id <= 0 || ($_SESSION['derniere_commande'] ?? 0) !== $id) {
    redirect(base_url('index.php'));
}

$st = db()->prepare('SELECT * FROM commandes WHERE id = ?');
$st->execute([$id]);
$cmd = $st->fetch();
if (!$cmd) {
    redirect(base_url('index.php'));
}
$items = db()->prepare('SELECT * FROM commande_items WHERE commande_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();
$sp = db()->prepare('SELECT statut_paiement FROM paiements WHERE commande_id = ?');
$sp->execute([$id]);
$statutPaiement = $sp->fetchColumn() ?: 'en_attente';

$lignes = [];
foreach ($items as $i) {
    $lignes[] = '• ' . $i['nom_produit'] . ' × ' . $i['quantite'] . ' = ' . fcfa($i['prix'] * $i['quantite']);
}
$message = "Bonjour Hawa Aïcha Boutique, je viens de passer la commande {$cmd['numero_commande']}.\n"
         . "Nom : {$cmd['nom_complet']}\n"
         . 'Livraison : ' . ($cmd['adresse_livraison'] ? $cmd['adresse_livraison'] . ', ' : '') . $cmd['ville'] . " ({$cmd['mode_livraison']})\n"
         . implode("\n", $lignes) . "\n"
         . 'Total : ' . fcfa($cmd['total']) . "\n"
         . "Paiement : {$cmd['paiement']}";

$page = 'panier';
$titre = 'Commande confirmée';
require __DIR__ . '/includes/header.php';
?>

<div class="steps mb-4"><span>1. Panier</span><span>2. Mes informations</span><span class="on">3. Confirmation</span></div>

<div class="panel text-center mx-auto" style="max-width:640px">
  <div class="mb-3" style="font-size:3.5rem;color:var(--rose)"><i class="fa-solid fa-circle-check"></i></div>
  <h1 class="page-title">Merci <?= e(strtok($cmd['nom_complet'], ' ')) ?> !</h1>
  <p class="text-muted">Votre commande <strong><?= e($cmd['numero_commande']) ?></strong> a bien été enregistrée. Nous vous contactons très vite pour confirmer la livraison.</p>

  <div class="text-start border rounded-4 p-3 my-4" style="border-color:#f0d3e1!important">
    <?php foreach ($items as $i): ?>
      <div class="d-flex justify-content-between small mb-1">
        <span><?= e($i['nom_produit']) ?> × <?= (int)$i['quantite'] ?></span>
        <span><?= fcfa($i['prix'] * $i['quantite']) ?></span>
      </div>
    <?php endforeach; ?>
    <hr>
    <div class="d-flex justify-content-between"><strong>Total</strong><strong class="text-nowrap total-line fs-6"><?= fcfa($cmd['total']) ?></strong></div>
    <div class="small text-muted mt-2"><?= e($cmd['mode_livraison']) ?> · <?= e($cmd['adresse_livraison'] ? $cmd['adresse_livraison'] . ', ' : '') ?><?= e($cmd['ville']) ?></div>
  </div>

  <div class="text-start pay-info mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <strong><i class="fa-solid fa-wallet me-1"></i> Paiement : <?= e($cmd['paiement']) ?></strong>
      <?= badge_paiement($statutPaiement) ?>
    </div>
    <p class="small mb-0"><?= instructions_paiement($cmd['paiement'], (int)$cmd['total'], $cmd['numero_commande']) ?></p>
  </div>

  <div class="d-flex flex-wrap justify-content-center gap-2">
    <a class="btn btn-outline-rose btn-lg px-4" target="_blank" href="<?= e(base_url('recu.php?id=' . (int)$cmd['id'])) ?>">
      <i class="fa-solid fa-print me-2"></i>Mon reçu
    </a>
    <a class="btn btn-wa btn-lg px-4" target="_blank" rel="noopener" href="<?= e(wa_link($message)) ?>">
      <i class="fa-brands fa-whatsapp me-2"></i>Envoyer sur WhatsApp
    </a>
  </div>
  <div class="mt-3"><a href="<?= e(base_url('produits.php')) ?>">Continuer mes achats</a></div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
