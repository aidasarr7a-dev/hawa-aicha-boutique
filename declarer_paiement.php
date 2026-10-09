<?php
/**
 * Le client indique qu'il a payé par Wave / Orange Money et donne la référence de sa transaction.
 * Le paiement reste « en attente » : seule la boutique peut le confirmer après vérification.
 */
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('index.php'));
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$reference = trim((string)($_POST['reference'] ?? ''));

$st = db()->prepare('SELECT * FROM commandes WHERE id = ?');
$st->execute([$id]);
$cmd = $st->fetch();
if (!$cmd || !peut_voir_commande($cmd)) {
    redirect(base_url('index.php'));
}
$client = client_connecte();
$retour = $client && (int)$cmd['client_id'] === (int)$client['id']
    ? 'mon-compte.php?commande=' . $id
    : 'merci.php?id=' . $id;

if ($reference === '' || mb_strlen($reference) > 100) {
    flash('danger', 'Merci d\'indiquer la référence de votre transaction (100 caractères maximum).');
    redirect(base_url($retour));
}

$up = db()->prepare(
    "UPDATE paiements SET reference_client = ?, date_declaration = NOW()
      WHERE commande_id = ? AND statut_paiement = 'en_attente' AND date_declaration IS NULL
        AND mode_paiement IN ('Wave', 'Orange Money')"
);
$up->execute([$reference, $id]);

if ($up->rowCount() === 1 && $cmd['statut'] !== 'annulee') {
    flash('success', 'Merci ! Votre paiement est signalé à la boutique. Il sera confirmé après vérification.');
} else {
    flash('warning', 'Ce paiement ne peut pas (ou plus) être déclaré.');
}
redirect(base_url($retour));
