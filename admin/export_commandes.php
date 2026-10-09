<?php
/**
 * Export de toutes les commandes au format CSV (ouvrable dans Excel).
 */
require_once __DIR__ . '/_auth.php';

$rows = db()->query(
    "SELECT c.numero_commande, c.created_at, c.nom_complet, c.telephone, c.adresse_livraison, c.ville, c.mode_livraison,
            (SELECT GROUP_CONCAT(CONCAT(i.nom_produit, ' x', i.quantite) SEPARATOR ' ; ') FROM commande_items i WHERE i.commande_id = c.id) AS articles,
            c.frais_livraison, c.total, c.statut, c.paiement, pa.statut_paiement, pa.reference_transaction, pa.reference_client, pa.date_confirmation
       FROM commandes c LEFT JOIN paiements pa ON pa.commande_id = c.id
      ORDER BY c.id DESC"
)->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="commandes-hawa-aicha-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM : accents corrects dans Excel
fputcsv($out, ['N° commande', 'Date', 'Client', 'Téléphone', 'Adresse de livraison', 'Ville', 'Livraison', 'Articles',
               'Frais de livraison', 'Total (FCFA)', 'Statut commande', 'Mode de paiement', 'Statut paiement',
               'Référence vérifiée', 'Référence déclarée par le client', 'Paiement confirmé le'], ';');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['numero_commande'], date('d/m/Y H:i', strtotime($r['created_at'])), $r['nom_complet'], $r['telephone'],
        $r['adresse_livraison'], $r['ville'], $r['mode_livraison'], $r['articles'], $r['frais_livraison'], $r['total'],
        STATUTS[$r['statut']][0] ?? $r['statut'], $r['paiement'], STATUTS_PAIEMENT[$r['statut_paiement'] ?? 'en_attente'][0] ?? '',
        $r['reference_transaction'], $r['reference_client'],
        $r['date_confirmation'] ? date('d/m/Y H:i', strtotime($r['date_confirmation'])) : '',
    ], ';');
}
fclose($out);
