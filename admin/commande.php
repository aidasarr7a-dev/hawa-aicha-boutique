<?php
require_once __DIR__ . '/_auth.php';

$pdo = db();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

function charger_commande(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM commandes WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function charger_paiement(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT pa.*, a.nom AS confirme_par_nom FROM paiements pa LEFT JOIN admins a ON a.id = pa.confirme_par WHERE pa.commande_id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

$cmd = charger_commande($pdo, $id);
if (!$cmd) {
    flash('danger', 'Commande introuvable.');
    redirect('commandes.php');
}

$it = $pdo->prepare('SELECT * FROM commande_items WHERE commande_id = ?');
$it->execute([$id]);
$items = $it->fetchAll();
$sousTotal = array_sum(array_map(fn($i) => $i['prix'] * $i['quantite'], $items));
$paiement = charger_paiement($pdo, $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $retour = 'commande.php?id=' . $id;

    if ($action === 'statut') {
        $nouveau = $_POST['statut'] ?? '';
        if (!isset(STATUTS[$nouveau]) || $nouveau === $cmd['statut']) {
            redirect($retour);
        }
        if ($cmd['statut'] === 'annulee') {
            flash('warning', 'Une commande annulée ne peut plus être modifiée (le stock a déjà été remis).');
            redirect($retour);
        }
        if ($nouveau === 'annulee' && $cmd['statut'] === 'livree') {
            flash('warning', 'Une commande livrée ne peut pas être annulée : les articles ont quitté la boutique.');
            redirect($retour);
        }
        try {
            $pdo->beginTransaction();
            // La condition « statut <> annulee » garantit que le stock n'est remis qu'une seule fois
            $up = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ? AND statut <> 'annulee'");
            $up->execute([$nouveau, $id]);
            if ($up->rowCount() !== 1) {
                throw new RuntimeException('Commande déjà annulée.');
            }
            $msg = 'Statut de la commande mis à jour.';
            if ($nouveau === 'annulee') {
                $rs = $pdo->prepare('UPDATE produits SET stock = stock + ? WHERE id = ?');
                foreach ($items as $i) {
                    if ($i['produit_id']) {
                        $rs->execute([$i['quantite'], $i['produit_id']]);
                    }
                }
                $msg = 'Commande annulée · articles remis en stock.';
                if ($paiement && $paiement['statut_paiement'] === 'en_attente') {
                    $pdo->prepare("UPDATE paiements SET statut_paiement = 'echec', note = 'Commande annulée' WHERE id = ?")->execute([$paiement['id']]);
                    $msg .= ' Le paiement en attente est passé à « Échec / annulé ».';
                } elseif ($paiement && $paiement['statut_paiement'] === 'paye') {
                    $msg .= ' ⚠ Cette commande était payée : pensez à rembourser la cliente puis à marquer le paiement « Remboursé ».';
                }
            }
            $pdo->commit();
            flash('success', $msg);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', 'Impossible de modifier le statut.');
        }
    } elseif ($action === 'frais') {
        $frais = max(0, (int)preg_replace('/\D/', '', (string)($_POST['frais_livraison'] ?? '0')));
        if (in_array($cmd['statut'], ['annulee', 'livree'], true) || ($paiement && $paiement['statut_paiement'] !== 'en_attente')) {
            flash('warning', 'Les frais ne peuvent être modifiés que pour une commande en cours dont le paiement est en attente.');
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE commandes SET frais_livraison = ?, total = ? WHERE id = ?')->execute([$frais, $sousTotal + $frais, $id]);
            $pdo->prepare('UPDATE paiements SET montant = ? WHERE commande_id = ?')->execute([$sousTotal + $frais, $id]);
            $pdo->commit();
            flash('success', 'Frais de livraison enregistrés · nouveau total : ' . fcfa($sousTotal + $frais) . '.');
        }
    } elseif (str_starts_with($action, 'paiement_') && $paiement) {
        $actuel = $paiement['statut_paiement'];
        if ($action === 'paiement_confirmer' && $actuel === 'en_attente') {
            $mode = $_POST['mode_paiement'] ?? $paiement['mode_paiement'];
            $ref = trim((string)($_POST['reference'] ?? ''));
            $datePaiement = DateTime::createFromFormat('Y-m-d\TH:i', (string)($_POST['date_paiement'] ?? '')) ?: new DateTime();
            if (!in_array($mode, MODES_PAIEMENT, true)) {
                flash('danger', 'Mode de paiement invalide.');
            } elseif (mb_strlen($ref) > 100) {
                flash('danger', 'La référence est trop longue (100 caractères maximum).');
            } elseif ($datePaiement > new DateTime('+5 minutes')) {
                flash('danger', 'La date du paiement ne peut pas être dans le futur.');
            } elseif ($cmd['statut'] === 'annulee') {
                flash('warning', 'Cette commande est annulée.');
            } else {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE paiements SET statut_paiement = 'paye', mode_paiement = ?, montant = ?, reference_transaction = ?,
                                      date_paiement = ?, date_confirmation = NOW(), confirme_par = ?
                                WHERE id = ? AND statut_paiement = 'en_attente'")
                    ->execute([$mode, (int)$cmd['total'], $ref ?: null, $datePaiement->format('Y-m-d H:i:s'), (int)$_SESSION['admin_id'], $paiement['id']]);
                $pdo->prepare('UPDATE commandes SET paiement = ? WHERE id = ?')->execute([$mode, $id]);
                $pdo->commit();
                flash('success', 'Paiement confirmé : ' . fcfa($cmd['total']) . ' reçus par ' . e($mode) . '.');
            }
        } elseif ($action === 'paiement_echec' && $actuel === 'en_attente') {
            $pdo->prepare("UPDATE paiements SET statut_paiement = 'echec', note = ? WHERE id = ?")
                ->execute([mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255) ?: null, $paiement['id']]);
            flash('success', 'Paiement marqué « Échec / annulé ».');
        } elseif ($action === 'paiement_reouvrir' && $actuel === 'echec' && $cmd['statut'] !== 'annulee') {
            $pdo->prepare("UPDATE paiements SET statut_paiement = 'en_attente', note = NULL WHERE id = ?")->execute([$paiement['id']]);
            flash('success', 'Paiement remis « en attente ».');
        } elseif ($action === 'paiement_rembourser' && $actuel === 'paye') {
            $pdo->prepare("UPDATE paiements SET statut_paiement = 'rembourse', note = ? WHERE id = ?")
                ->execute([mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255) ?: 'Remboursement effectué', $paiement['id']]);
            flash('success', 'Paiement marqué « Remboursé ».');
        } else {
            flash('warning', 'Cette action n\'est pas possible pour le statut de paiement actuel.');
        }
    } elseif ($action === 'delete') {
        if ($cmd['statut'] !== 'annulee') {
            flash('warning', 'Seule une commande annulée peut être supprimée (les commandes livrées sont conservées pour l\'historique des ventes).');
        } else {
            $pdo->prepare('DELETE FROM commandes WHERE id = ?')->execute([$id]);
            flash('success', 'Commande ' . e($cmd['numero_commande']) . ' supprimée.');
            redirect('commandes.php');
        }
    }
    redirect($retour);
}

$adminPage = 'commandes';
$titre = 'Commande ' . $cmd['numero_commande'];
require __DIR__ . '/_header.php';

$tel = preg_replace('/\D/', '', $cmd['telephone']);
if (strlen($tel) === 9) {
    $tel = '221' . $tel; // numéro sénégalais saisi sans indicatif
}
$waMsg = "Bonjour " . strtok($cmd['nom_complet'], ' ') . ", c'est Hawa Aïcha Boutique. Nous avons bien reçu votre commande {$cmd['numero_commande']} d'un montant de " . fcfa($cmd['total']) . '.';
$statutPaiement = $paiement['statut_paiement'] ?? 'en_attente';
$modifiable = !in_array($cmd['statut'], ['annulee', 'livree'], true) && $statutPaiement === 'en_attente';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="page-title mb-0">Commande <?= e($cmd['numero_commande']) ?></h1>
  <div class="d-flex gap-2">
    <a href="<?= e(base_url('recu.php?id=' . $id)) ?>" target="_blank" class="btn btn-rose btn-sm"><i class="fa-solid fa-print me-1"></i>Reçu imprimable</a>
    <a href="commandes.php" class="btn btn-outline-rose btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Retour</a>
  </div>
</div>
<div class="d-flex flex-wrap gap-2 mb-4">
  <span class="small text-muted">Commande :</span> <?= badge_statut($cmd['statut']) ?>
  <span class="small text-muted ms-2">Paiement :</span> <?= badge_paiement($statutPaiement) ?>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel mb-4">
      <h2 class="h5 mb-3">Articles</h2>
      <div class="table-responsive">
        <table class="table table-rose align-middle mb-0">
          <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Qté</th><th class="text-end">Sous-total</th></tr></thead>
          <tbody>
          <?php foreach ($items as $i): ?>
            <tr><td><?= e($i['nom_produit']) ?></td><td class="text-nowrap"><?= fcfa($i['prix']) ?></td><td><?= (int)$i['quantite'] ?></td><td class="text-end text-nowrap"><?= fcfa($i['prix'] * $i['quantite']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="3" class="text-end text-muted">Sous-total articles</td><td class="text-end text-nowrap"><?= fcfa($sousTotal) ?></td></tr>
            <tr><td colspan="3" class="text-end text-muted">Frais de livraison</td><td class="text-end text-nowrap"><?= fcfa($cmd['frais_livraison']) ?></td></tr>
            <tr><td colspan="3" class="text-end fw-semibold">Total</td><td class="text-end total-line text-nowrap"><?= fcfa($cmd['total']) ?></td></tr>
          </tfoot>
        </table>
      </div>
      <?php if ($modifiable): ?>
        <form method="post" class="d-flex flex-wrap align-items-end gap-2 mt-3">
          <?= csrf_field() ?><input type="hidden" name="action" value="frais"><input type="hidden" name="id" value="<?= (int)$id ?>">
          <div><label class="form-label small mb-1" for="frais_livraison">Frais de livraison (FCFA)</label>
            <input class="form-control form-control-sm" style="max-width:160px" type="number" min="0" step="1" id="frais_livraison" name="frais_livraison" value="<?= (int)$cmd['frais_livraison'] ?>"></div>
          <button class="btn btn-outline-rose btn-sm">Enregistrer les frais</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="panel mb-4">
      <h2 class="h5 mb-3">Statut de la commande</h2>
      <?php if ($cmd['statut'] === 'annulee'): ?>
        <p class="text-muted mb-0">Cette commande est annulée : son stock a été remis à jour.</p>
      <?php else: ?>
        <form method="post" class="d-flex flex-wrap gap-2">
          <?= csrf_field() ?><input type="hidden" name="action" value="statut"><input type="hidden" name="id" value="<?= (int)$id ?>">
          <select name="statut" class="form-select" style="max-width:260px">
            <?php foreach (STATUTS as $k => [$label]): if ($k === 'annulee' && $cmd['statut'] === 'livree') continue; ?>
              <option value="<?= e($k) ?>" <?= $cmd['statut'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-rose" onclick="return this.form.statut.value !== 'annulee' || confirm('Annuler cette commande ? Les articles seront remis en stock.')">Mettre à jour</button>
        </form>
      <?php endif; ?>
      <?php if ($cmd['statut'] === 'annulee'): ?>
        <form method="post" class="mt-3" onsubmit="return confirm('Supprimer définitivement cette commande annulée ?')">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$id ?>">
          <button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash-can me-1"></i>Supprimer la commande</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="panel" id="paiement">
      <h2 class="h5 mb-3">Paiement</h2>
      <?php if (!$paiement): ?>
        <p class="text-muted mb-0">Aucun paiement enregistré pour cette commande.</p>
      <?php else: ?>
        <dl class="row small mb-3">
          <dt class="col-5 text-muted fw-normal">Mode</dt><dd class="col-7"><?= e($paiement['mode_paiement']) ?></dd>
          <dt class="col-5 text-muted fw-normal">Montant</dt><dd class="col-7"><?= fcfa($paiement['montant']) ?></dd>
          <dt class="col-5 text-muted fw-normal">Statut</dt><dd class="col-7"><?= badge_paiement($statutPaiement) ?></dd>
          <?php if (!empty($paiement['date_declaration'])): ?><dt class="col-5 text-muted fw-normal">Déclaré par la cliente</dt><dd class="col-7"><span class="badge rounded-pill text-bg-info">le <?= e(date('d/m/Y à H:i', strtotime($paiement['date_declaration']))) ?></span><br>Réf. indiquée : <strong><?= e($paiement['reference_client']) ?></strong></dd><?php endif; ?>
          <?php if ($paiement['reference_transaction']): ?><dt class="col-5 text-muted fw-normal">Référence</dt><dd class="col-7"><?= e($paiement['reference_transaction']) ?></dd><?php endif; ?>
          <?php if ($paiement['date_paiement']): ?><dt class="col-5 text-muted fw-normal">Payé le</dt><dd class="col-7"><?= e(date('d/m/Y à H:i', strtotime($paiement['date_paiement']))) ?></dd><?php endif; ?>
          <?php if ($paiement['date_confirmation']): ?><dt class="col-5 text-muted fw-normal">Confirmé le</dt><dd class="col-7"><?= e(date('d/m/Y à H:i', strtotime($paiement['date_confirmation']))) ?><?= $paiement['confirme_par_nom'] ? ' par ' . e($paiement['confirme_par_nom']) : '' ?></dd><?php endif; ?>
          <?php if ($paiement['note']): ?><dt class="col-5 text-muted fw-normal">Note</dt><dd class="col-7"><?= e($paiement['note']) ?></dd><?php endif; ?>
        </dl>

        <?php if ($statutPaiement === 'en_attente' && $cmd['statut'] !== 'annulee'): ?>
          <div class="alert alert-warning rounded-4 small py-2">Ne confirmez qu'après avoir <strong>vérifié la réception réelle</strong> de l'argent (message Wave / Orange Money reçu, ou espèces remises).</div>
          <form method="post" class="row g-2 mb-3" onsubmit="return confirm('Confirmer que <?= e(fcfa($cmd['total'])) ?> ont bien été reçus ?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="paiement_confirmer"><input type="hidden" name="id" value="<?= (int)$id ?>">
            <div class="col-sm-6"><label class="form-label small mb-1" for="mode_paiement">Mode utilisé</label>
              <select class="form-select form-select-sm" id="mode_paiement" name="mode_paiement">
                <?php foreach (MODES_PAIEMENT as $m): ?><option <?= $m === $paiement['mode_paiement'] ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-sm-6"><label class="form-label small mb-1" for="date_paiement">Date du paiement</label>
              <input class="form-control form-control-sm" type="datetime-local" id="date_paiement" name="date_paiement" value="<?= e(date('Y-m-d\TH:i')) ?>" max="<?= e(date('Y-m-d\TH:i', time() + 300)) ?>"></div>
            <div class="col-12"><label class="form-label small mb-1" for="reference">Référence de la transaction (facultatif)</label>
              <input class="form-control form-control-sm" id="reference" name="reference" maxlength="100" value="<?= e($paiement['reference_client'] ?? '') ?>" placeholder="Ex : identifiant du message Wave / Orange Money"></div>
            <div class="col-12"><button class="btn btn-rose btn-sm"><i class="fa-solid fa-circle-check me-1"></i>Confirmer le paiement reçu</button></div>
          </form>
          <form method="post" class="d-flex flex-wrap gap-2" onsubmit="return confirm('Marquer ce paiement comme échoué / annulé ?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="paiement_echec"><input type="hidden" name="id" value="<?= (int)$id ?>">
            <input class="form-control form-control-sm" style="max-width:260px" name="note" maxlength="255" placeholder="Motif (facultatif)">
            <button class="btn btn-outline-danger btn-sm">Échec / annulé</button>
          </form>
        <?php elseif ($statutPaiement === 'echec' && $cmd['statut'] !== 'annulee'): ?>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="paiement_reouvrir"><input type="hidden" name="id" value="<?= (int)$id ?>">
            <button class="btn btn-outline-rose btn-sm">Remettre « en attente »</button>
          </form>
        <?php elseif ($statutPaiement === 'paye'): ?>
          <form method="post" class="d-flex flex-wrap gap-2" onsubmit="return confirm('Confirmer que l\'argent a RÉELLEMENT été rendu à la cliente ?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="paiement_rembourser"><input type="hidden" name="id" value="<?= (int)$id ?>">
            <input class="form-control form-control-sm" style="max-width:260px" name="note" maxlength="255" placeholder="Détail du remboursement (facultatif)">
            <button class="btn btn-outline-secondary btn-sm">Marquer « Remboursé »</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel">
      <h2 class="h5 mb-3">Client</h2>
      <dl class="row mb-0">
        <dt class="col-5 text-muted fw-normal">Nom</dt><dd class="col-7"><?= e($cmd['nom_complet']) ?></dd>
        <dt class="col-5 text-muted fw-normal">Téléphone</dt><dd class="col-7"><a href="tel:<?= e($cmd['telephone']) ?>"><?= e($cmd['telephone']) ?></a></dd>
        <dt class="col-5 text-muted fw-normal">Adresse de livraison</dt><dd class="col-7"><?= e($cmd['adresse_livraison'] ?: '—') ?></dd>
        <dt class="col-5 text-muted fw-normal">Ville / commune</dt><dd class="col-7"><?= e($cmd['ville']) ?></dd>
        <dt class="col-5 text-muted fw-normal">Livraison</dt><dd class="col-7"><?= e($cmd['mode_livraison']) ?></dd>
        <dt class="col-5 text-muted fw-normal">Date</dt><dd class="col-7"><?= e(date('d/m/Y à H:i', strtotime($cmd['created_at']))) ?></dd>
        <?php if ($cmd['remarque']): ?><dt class="col-5 text-muted fw-normal">Instructions</dt><dd class="col-7"><?= nl2br(e($cmd['remarque'])) ?></dd><?php endif; ?>
      </dl>
      <?php if ($tel !== ''): ?>
        <a class="btn btn-wa w-100 mt-3" target="_blank" rel="noopener" href="https://wa.me/<?= e($tel) ?>?text=<?= rawurlencode($waMsg) ?>"><i class="fa-brands fa-whatsapp me-1"></i>Contacter le client</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
