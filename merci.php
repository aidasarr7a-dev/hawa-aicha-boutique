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

$lignes = [];
foreach ($items as $i) {
    $lignes[] = '• ' . $i['nom_produit'] . ' × ' . $i['quantite'] . ' = ' . fcfa($i['prix'] * $i['quantite']);
}
$message = "Bonjour Hawa Aïcha Boutique, je viens de passer la commande {$cmd['numero_commande']}.\n"
         . "Nom : {$cmd['prenom']} {$cmd['nom']}\n"
         . "Lieu : {$cmd['quartier']}, {$cmd['ville']}\n"
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
  <h1 class="page-title">Merci <?= e($cmd['prenom']) ?> !</h1>
  <p class="text-muted">Votre commande <strong><?= e($cmd['numero_commande']) ?></strong> a bien été enregistrée. Nous vous contactons très vite pour confirmer la livraison.</p>

  <div class="text-start border rounded-4 p-3 my-4" style="border-color:#f0d3e1!important">
    <?php foreach ($items as $i): ?>
      <div class="d-flex justify-content-between small mb-1">
        <span><?= e($i['nom_produit']) ?> × <?= (int)$i['quantite'] ?></span>
        <span><?= fcfa($i['prix'] * $i['quantite']) ?></span>
      </div>
    <?php endforeach; ?>
    <hr>
    <div class="d-flex justify-content-between"><strong>Total</strong><strong class="text-nowrap" style="color:var(--rose)"><?= fcfa($cmd['total']) ?></strong></div>
    <div class="small text-muted mt-2">Paiement : <?= e($cmd['paiement']) ?> · Livraison : <?= e($cmd['quartier']) ?>, <?= e($cmd['ville']) ?></div>
  </div>

  <p class="mb-3">Pour aller plus vite, envoyez-nous votre commande sur WhatsApp :</p>
  <a class="btn btn-wa btn-lg px-4" target="_blank" rel="noopener" href="<?= e(wa_link($message)) ?>">
    <i class="fa-brands fa-whatsapp me-2"></i>Envoyer sur WhatsApp
  </a>
  <div class="mt-3"><a href="<?= e(base_url('produits.php')) ?>">Continuer mes achats</a></div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
