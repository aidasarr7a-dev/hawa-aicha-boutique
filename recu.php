<?php
/**
 * Reçu de commande imprimable (et enregistrable en PDF depuis le navigateur).
 * Accessible à l'administrateur connecté, ou au client pour la commande qu'il vient de passer.
 */
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$estAdmin = !empty($_SESSION['admin_id']);
if ($id <= 0 || (!$estAdmin && ($_SESSION['derniere_commande'] ?? 0) !== $id)) {
    redirect(base_url('index.php'));
}

$st = db()->prepare('SELECT * FROM commandes WHERE id = ?');
$st->execute([$id]);
$cmd = $st->fetch();
if (!$cmd) {
    redirect(base_url($estAdmin ? 'admin/commandes.php' : 'index.php'));
}
$it = db()->prepare('SELECT * FROM commande_items WHERE commande_id = ?');
$it->execute([$id]);
$items = $it->fetchAll();
$pa = db()->prepare('SELECT * FROM paiements WHERE commande_id = ?');
$pa->execute([$id]);
$paiement = $pa->fetch() ?: ['statut_paiement' => 'en_attente', 'mode_paiement' => $cmd['paiement'], 'reference_transaction' => null, 'date_paiement' => null];

$sousTotal = array_sum(array_map(fn($i) => $i['prix'] * $i['quantite'], $items));
$estPaye = $paiement['statut_paiement'] === 'paye';
$mentionPaiement = [
    'paye'       => 'PAYÉ',
    'en_attente' => 'PAIEMENT EN ATTENTE — NON PAYÉ',
    'echec'      => 'PAIEMENT ÉCHOUÉ / ANNULÉ — NON PAYÉ',
    'rembourse'  => 'REMBOURSÉ',
][$paiement['statut_paiement']] ?? 'NON PAYÉ';
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Reçu <?= e($cmd['numero_commande']) ?> — <?= e(SITE_NAME) ?></title>
  <link rel="icon" href="<?= e(base_url('assets/img/logo.svg')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(base_url('assets/css/recu.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="actions">
  <button type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer / Enregistrer en PDF</button>
  <a href="<?= e($estAdmin ? base_url('admin/commande.php?id=' . $id) : base_url('index.php')) ?>">Retour</a>
</div>

<main class="recu">
  <header class="entete">
    <div class="marque">
      <img src="<?= e(logo_url()) ?>" alt="" height="56">
      <div>
        <div class="nom"><?= e(SITE_NAME) ?></div>
        <div class="petit"><?= e(SITE_SLOGAN) ?></div>
        <div class="petit"><?= info_boutique(BOUTIQUE_ADRESSE, 'Adresse à compléter') ?> · <?= info_boutique(CONTACT_TELEPHONE, 'Téléphone à compléter') ?></div>
      </div>
    </div>
    <div class="doc">
      <div class="titre">Reçu de commande</div>
      <div><strong><?= e($cmd['numero_commande']) ?></strong></div>
      <div class="petit">du <?= e(date('d/m/Y à H:i', strtotime($cmd['created_at']))) ?></div>
    </div>
  </header>

  <section class="blocs">
    <div>
      <h2>Client</h2>
      <p><strong><?= e($cmd['prenom'] . ' ' . $cmd['nom']) ?></strong><br>
         Tél. : <?= e($cmd['telephone']) ?><br>
         <?= e($cmd['quartier']) ?>, <?= e($cmd['ville']) ?><?= $cmd['adresse'] ? '<br>Repère : ' . e($cmd['adresse']) : '' ?></p>
    </div>
    <div>
      <h2>Livraison</h2>
      <p><?= e($cmd['mode_livraison']) ?><br>
         Statut de la commande : <?= e(STATUTS[$cmd['statut']][0] ?? $cmd['statut']) ?></p>
    </div>
  </section>

  <table>
    <thead><tr><th>Produit</th><th class="num">Prix unitaire</th><th class="num">Qté</th><th class="num">Sous-total</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr><td><?= e($i['nom_produit']) ?></td><td class="num"><?= fcfa($i['prix']) ?></td><td class="num"><?= (int)$i['quantite'] ?></td><td class="num"><?= fcfa($i['prix'] * $i['quantite']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="3" class="num">Sous-total articles</td><td class="num"><?= fcfa($sousTotal) ?></td></tr>
      <tr><td colspan="3" class="num">Frais de livraison</td><td class="num"><?= fcfa($cmd['frais_livraison']) ?></td></tr>
      <tr class="total"><td colspan="3" class="num">Total</td><td class="num"><?= fcfa($cmd['total']) ?></td></tr>
    </tfoot>
  </table>

  <section class="paiement <?= $estPaye ? 'ok' : 'attente' ?>">
    <div class="mention"><?= e($mentionPaiement) ?></div>
    <div>Mode de paiement : <strong><?= e($paiement['mode_paiement']) ?></strong></div>
    <?php if ($estPaye): ?>
      <div>Payé le <?= e(date('d/m/Y à H:i', strtotime($paiement['date_paiement']))) ?><?= $paiement['reference_transaction'] ? ' · Réf. ' . e($paiement['reference_transaction']) : '' ?></div>
    <?php elseif ($paiement['statut_paiement'] === 'en_attente' && $cmd['statut'] !== 'annulee'): ?>
      <div class="petit"><?= instructions_paiement($paiement['mode_paiement'], (int)$cmd['total'], $cmd['numero_commande']) ?></div>
    <?php endif; ?>
  </section>

  <footer class="pied">
    Ce document est un récapitulatif de commande. Il ne constitue pas une facture fiscale.<br>
    Merci pour votre confiance · <?= e(SITE_NAME) ?>
  </footer>
</main>
</body>
</html>
