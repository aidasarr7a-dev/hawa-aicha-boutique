<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
// Visible par le client qui vient de commander, le client connecté propriétaire ou l'administrateur
$st = db()->prepare('SELECT * FROM commandes WHERE id = ?');
$st->execute([$id]);
$cmd = $st->fetch();
if (!$cmd || !peut_voir_commande($cmd)) {
    redirect(base_url('index.php'));
}
$items = db()->prepare('SELECT * FROM commande_items WHERE commande_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();
$sp = db()->prepare('SELECT * FROM paiements WHERE commande_id = ?');
$sp->execute([$id]);
$paiement = $sp->fetch() ?: ['mode_paiement' => $cmd['paiement'], 'statut_paiement' => 'en_attente', 'date_declaration' => null];
$client = client_connecte();

$lignes = [];
foreach ($items as $i) {
    $lignes[] = '• ' . $i['nom_produit'] . ' × ' . $i['quantite'] . ' = ' . fcfa($i['prix'] * $i['quantite']);
}
$message = "Bonjour Hawa Aïcha Boutique, je viens de passer la commande {$cmd['numero_commande']}.\n"
         . "Nom : {$cmd['nom_complet']}\n"
         . 'Livraison : ' . adresse_commande($cmd) . " ({$cmd['mode_livraison']})\n"
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
    <div class="small text-muted mt-2"><?= e($cmd['mode_livraison']) ?> · <?= e(adresse_commande($cmd)) ?></div>
  </div>

  <div class="text-start mb-4">
    <?php bloc_payer($cmd, $paiement); ?>
  </div>

  <div class="d-flex flex-wrap justify-content-center gap-2">
    <a class="btn btn-rose btn-lg px-4" target="_blank" href="<?= e(base_url('recu.php?id=' . (int)$cmd['id'])) ?>">
      <i class="fa-solid fa-file-invoice me-2"></i>Ma facture
    </a>
    <a class="btn btn-wa btn-lg px-4" target="_blank" rel="noopener" href="<?= e(wa_link($message)) ?>">
      <i class="fa-brands fa-whatsapp me-2"></i>Envoyer sur WhatsApp
    </a>
  </div>
  <?php if (!$client): ?>
    <div class="alert-compte mt-4 text-start"><i class="fa-solid fa-user-plus me-2"></i><strong>Suivez votre commande et retrouvez vos factures :</strong> <a href="<?= e(base_url('compte.php?onglet=inscription')) ?>">créez votre compte</a> avec le même numéro de téléphone.</div>
  <?php else: ?>
    <div class="mt-3"><a href="<?= e(base_url('mon-compte.php?commande=' . (int)$cmd['id'])) ?>">Suivre cette commande dans mon compte</a></div>
  <?php endif; ?>
  <div class="mt-3"><a href="<?= e(base_url('produits.php')) ?>">Continuer mes achats</a></div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
