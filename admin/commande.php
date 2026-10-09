<?php
require_once __DIR__ . '/_auth.php';

$pdo = db();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM commandes WHERE id = ?');
$st->execute([$id]);
$cmd = $st->fetch();
if (!$cmd) {
    flash('danger', 'Commande introuvable.');
    redirect('commandes.php');
}

$it = $pdo->prepare('SELECT * FROM commande_items WHERE commande_id = ?');
$it->execute([$id]);
$items = $it->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'statut') {
        $nouveau = $_POST['statut'] ?? '';
        if (!isset(STATUTS[$nouveau]) || $nouveau === $cmd['statut']) {
            redirect('commande.php?id=' . $id);
        }
        if ($cmd['statut'] === 'annulee') {
            flash('warning', 'Une commande annulée ne peut plus être modifiée (le stock a déjà été remis).');
            redirect('commande.php?id=' . $id);
        }
        try {
            $pdo->beginTransaction();
            if ($nouveau === 'annulee') {
                // Remet les articles en stock
                $rs = $pdo->prepare('UPDATE produits SET stock = stock + ? WHERE id = ?');
                foreach ($items as $i) {
                    if ($i['produit_id']) {
                        $rs->execute([$i['quantite'], $i['produit_id']]);
                    }
                }
            }
            $pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?')->execute([$nouveau, $id]);
            $pdo->commit();
            flash('success', 'Statut mis à jour' . ($nouveau === 'annulee' ? ' · stock remis à jour.' : '.'));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', 'Impossible de modifier le statut.');
        }
    } elseif ($action === 'delete') {
        if ($cmd['statut'] !== 'annulee' && $cmd['statut'] !== 'livree') {
            flash('warning', 'Annulez ou livrez la commande avant de la supprimer.');
        } else {
            $pdo->prepare('DELETE FROM commande_items WHERE commande_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM commandes WHERE id = ?')->execute([$id]);
            flash('success', 'Commande supprimée.');
            redirect('commandes.php');
        }
    }
    redirect('commande.php?id=' . $id);
}

$adminPage = 'commandes';
$titre = 'Commande n°' . $id;
require __DIR__ . '/_header.php';

[$sl, $sc] = STATUTS[$cmd['statut']] ?? [$cmd['statut'], 'secondary'];
$tel = preg_replace('/\D/', '', $cmd['telephone']);
$waMsg = "Bonjour {$cmd['prenom']}, c'est Hawa Aïcha Boutique. Nous avons bien reçu votre commande n°{$cmd['id']} d'un montant de " . fcfa($cmd['total']) . '.';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="page-title mb-0">Commande n°<?= (int)$cmd['id'] ?> <span class="badge text-bg-<?= e($sc) ?> rounded-pill fs-6 align-middle"><?= e($sl) ?></span></h1>
  <a href="commandes.php" class="btn btn-outline-rose btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Retour</a>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel mb-4">
      <h2 class="h5 mb-3">Articles</h2>
      <div class="table-responsive">
        <table class="table table-rose align-middle mb-0">
          <thead><tr><th>Produit</th><th>Prix</th><th>Qté</th><th class="text-end">Sous-total</th></tr></thead>
          <tbody>
          <?php foreach ($items as $i): ?>
            <tr><td><?= e($i['nom_produit']) ?></td><td class="text-nowrap"><?= fcfa($i['prix']) ?></td><td><?= (int)$i['quantite'] ?></td><td class="text-end text-nowrap"><?= fcfa($i['prix'] * $i['quantite']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><td colspan="3" class="text-end fw-semibold">Total</td><td class="text-end total-line text-nowrap"><?= fcfa($cmd['total']) ?></td></tr></tfoot>
        </table>
      </div>
    </div>

    <div class="panel">
      <h2 class="h5 mb-3">Changer le statut</h2>
      <?php if ($cmd['statut'] === 'annulee'): ?>
        <p class="text-muted mb-0">Cette commande est annulée : son stock a été remis à jour.</p>
      <?php else: ?>
        <form method="post" class="d-flex flex-wrap gap-2">
          <?= csrf_field() ?><input type="hidden" name="action" value="statut"><input type="hidden" name="id" value="<?= (int)$id ?>">
          <select name="statut" class="form-select" style="max-width:240px">
            <?php foreach (STATUTS as $k => [$label]): ?>
              <option value="<?= e($k) ?>" <?= $cmd['statut'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-rose" onclick="return this.form.statut.value !== 'annulee' || confirm('Annuler cette commande ? Les articles seront remis en stock.')">Mettre à jour</button>
        </form>
      <?php endif; ?>
      <?php if (in_array($cmd['statut'], ['annulee', 'livree'], true)): ?>
        <form method="post" class="mt-3" onsubmit="return confirm('Supprimer définitivement cette commande ?')">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$id ?>">
          <button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash-can me-1"></i>Supprimer la commande</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel">
      <h2 class="h5 mb-3">Client</h2>
      <dl class="row mb-0">
        <dt class="col-5 text-muted fw-normal">Nom</dt><dd class="col-7"><?= e($cmd['prenom'] . ' ' . $cmd['nom']) ?></dd>
        <dt class="col-5 text-muted fw-normal">Téléphone</dt><dd class="col-7"><a href="tel:<?= e($cmd['telephone']) ?>"><?= e($cmd['telephone']) ?></a></dd>
        <dt class="col-5 text-muted fw-normal">Ville</dt><dd class="col-7"><?= e($cmd['ville']) ?></dd>
        <dt class="col-5 text-muted fw-normal">Quartier</dt><dd class="col-7"><?= e($cmd['quartier']) ?></dd>
        <?php if ($cmd['adresse']): ?><dt class="col-5 text-muted fw-normal">Adresse</dt><dd class="col-7"><?= e($cmd['adresse']) ?></dd><?php endif; ?>
        <dt class="col-5 text-muted fw-normal">Paiement</dt><dd class="col-7"><?= e($cmd['paiement']) ?></dd>
        <dt class="col-5 text-muted fw-normal">Date</dt><dd class="col-7"><?= e(date('d/m/Y à H:i', strtotime($cmd['created_at']))) ?></dd>
        <?php if ($cmd['remarque']): ?><dt class="col-5 text-muted fw-normal">Remarque</dt><dd class="col-7"><?= nl2br(e($cmd['remarque'])) ?></dd><?php endif; ?>
      </dl>
      <?php if ($tel !== ''): ?>
        <a class="btn btn-wa w-100 mt-3" target="_blank" rel="noopener" href="https://wa.me/<?= e($tel) ?>?text=<?= rawurlencode($waMsg) ?>"><i class="fa-brands fa-whatsapp me-1"></i>Contacter le client</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
