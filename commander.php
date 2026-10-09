<?php
require_once __DIR__ . '/includes/functions.php';

$panierAvant = array_map('intval', cart_get());
[$lignes, $total] = cart_items();
// Vrai si un article a disparu ou si une quantité a été réduite au stock disponible
$panierAjuste = $panierAvant != array_map('intval', cart_get());
if (!$lignes) {
    // Formulaire renvoyé après une commande réussie (double clic, retour arrière) : on réaffiche la confirmation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['derniere_commande'])) {
        redirect(base_url('merci.php?id=' . (int)$_SESSION['derniere_commande']));
    }
    flash('warning', 'Votre panier est vide.');
    redirect(base_url('panier.php'));
}

$erreurs = [];
$val = ['nom_complet' => '', 'telephone' => '', 'adresse_livraison' => '', 'ville' => '', 'remarque' => '',
        'paiement' => MODES_PAIEMENT[0], 'mode_livraison' => MODES_LIVRAISON[0]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Jeton à usage unique : un double clic ou un renvoi du formulaire ne crée pas une deuxième commande
    $jeton = (string)($_POST['jeton'] ?? '');
    if ($jeton === '' || !hash_equals($_SESSION['jeton_commande'] ?? '', $jeton)) {
        if (!empty($_SESSION['derniere_commande'])) {
            redirect(base_url('merci.php?id=' . (int)$_SESSION['derniere_commande']));
        }
        flash('warning', 'Ce formulaire a déjà été envoyé. Merci de vérifier votre panier.');
        redirect(base_url('panier.php'));
    }
    unset($_SESSION['jeton_commande']);

    foreach ($val as $k => $_) {
        $val[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $tel = preg_replace('/[^\d+]/', '', $val['telephone']);

    $domicile = $val['mode_livraison'] === MODES_LIVRAISON[0];
    if (mb_strlen($val['nom_complet']) < 3 || mb_strlen($val['nom_complet']) > 200) $erreurs[] = 'Veuillez indiquer votre nom complet.';
    if (strlen(preg_replace('/\D/', '', $tel)) < 8)               $erreurs[] = 'Veuillez indiquer un numéro de téléphone / WhatsApp valide.';
    if ($val['ville'] === '')                                     $erreurs[] = 'Veuillez indiquer votre ville / commune.';
    if ($domicile && $val['adresse_livraison'] === '')               $erreurs[] = 'Veuillez indiquer votre adresse de livraison (quartier, rue, point de repère).';
    if (!in_array($val['paiement'], MODES_PAIEMENT, true))        $erreurs[] = 'Veuillez choisir un moyen de paiement.';
    if (!in_array($val['mode_livraison'], MODES_LIVRAISON, true)) $erreurs[] = 'Veuillez choisir un mode de livraison.';
    foreach (['adresse_livraison' => [255, 'adresse de livraison'], 'remarque' => [1000, 'instructions'], 'ville' => [100, 'ville / commune']] as $champ => [$max, $libelle]) {
        if (mb_strlen($val[$champ]) > $max) $erreurs[] = 'Le champ « ' . $libelle . ' » est trop long.';
    }

    if ($panierAjuste) {
        $erreurs[] = 'Le stock a changé : certaines quantités ont été ajustées au stock disponible. Vérifiez le récapitulatif ci-dessous puis confirmez à nouveau.';
    }

    if (!$erreurs) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $totalReel = 0;
            foreach ($lignes as $l) {
                // Décrémente le stock seulement s'il est suffisant
                $up = $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ? AND actif = 1 AND stock >= ?');
                $up->execute([$l['qty'], $l['p']['id'], $l['qty']]);
                if ($up->rowCount() !== 1) {
                    throw new RuntimeException('Le produit « ' . $l['p']['nom'] . ' » n\'est plus disponible en quantité suffisante.');
                }
                $totalReel += $l['qty'] * (int)$l['p']['prix'];
            }
            // Fiche client (une par numéro de téléphone, mise à jour à chaque commande)
            $pdo->prepare(
                'INSERT INTO clients (nom_complet, telephone, adresse, ville) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), nom_complet = VALUES(nom_complet),
                                         adresse = VALUES(adresse), ville = VALUES(ville)'
            )->execute([$val['nom_complet'], $tel, $val['adresse_livraison'] ?: null, $val['ville']]);
            $clientId = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare(
                'INSERT INTO commandes (client_id, nom_complet, telephone, ville, adresse_livraison, remarque,
                                        mode_livraison, frais_livraison, paiement, total, statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
            );
            $ins->execute([$clientId, $val['nom_complet'], $tel, $val['ville'],
                           $val['adresse_livraison'] ?: null, $val['remarque'] ?: null, $val['mode_livraison'],
                           $val['paiement'], $totalReel, 'nouvelle']);
            $cmdId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE commandes SET numero_commande = ? WHERE id = ?')->execute([numero_commande($cmdId), $cmdId]);

            $it = $pdo->prepare('INSERT INTO commande_items (commande_id, produit_id, nom_produit, prix, quantite) VALUES (?, ?, ?, ?, ?)');
            foreach ($lignes as $l) {
                $it->execute([$cmdId, $l['p']['id'], $l['p']['nom'], $l['p']['prix'], $l['qty']]);
            }

            // Le paiement reste « en attente » tant que la boutique ne l'a pas vérifié et confirmé
            $pdo->prepare("INSERT INTO paiements (commande_id, mode_paiement, montant, statut_paiement) VALUES (?, ?, ?, 'en_attente')")
                ->execute([$cmdId, $val['paiement'], $totalReel]);
            $pdo->commit();

            $_SESSION['panier'] = [];
            $_SESSION['derniere_commande'] = $cmdId;
            redirect(base_url('merci.php?id=' . $cmdId));
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erreurs[] = $ex->getMessage() . ' Merci de vérifier votre panier.';
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erreurs[] = 'Une erreur est survenue. Merci de réessayer ou de nous écrire sur WhatsApp.';
        }
        [$lignes, $total] = cart_items();
    }
}

$_SESSION['jeton_commande'] = bin2hex(random_bytes(16));

$page = 'panier';
$titre = 'Passer la commande';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title mb-2">Passer la commande</h1>
<div class="steps mb-4"><span>1. Panier</span><span class="on">2. Mes informations</span><span>3. Confirmation</span></div>

<?php if ($erreurs): ?>
  <div class="alert alert-danger rounded-4">
    <ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if (!$lignes): ?>
  <div class="panel empty-state"><p>Votre panier est vide.</p><a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Voir les produits</a></div>
<?php else: ?>
<form method="post" class="row g-4" novalidate data-envoi-unique>
  <?= csrf_field() ?>
  <input type="hidden" name="jeton" value="<?= e($_SESSION['jeton_commande']) ?>">
  <div class="col-lg-7">
    <div class="panel">
      <h2 class="h5 mb-3"><span class="num-etape">1</span> Vos coordonnées</h2>
      <div class="row g-3">
        <div class="col-12"><label class="form-label" for="nom_complet">Nom complet *</label><input class="form-control" id="nom_complet" name="nom_complet" value="<?= e($val['nom_complet']) ?>" required maxlength="200" autocomplete="name" placeholder="Ex : Aminata Sow"></div>
        <div class="col-12"><label class="form-label" for="telephone">Numéro de téléphone / WhatsApp *</label><input class="form-control" id="telephone" name="telephone" type="tel" inputmode="tel" placeholder="Ex : 77 000 00 00" value="<?= e($val['telephone']) ?>" required autocomplete="tel"></div>
      </div>
    </div>

    <div class="panel mt-4">
      <h2 class="h5 mb-3"><span class="num-etape">2</span> Livraison</h2>
      <div class="row g-2 mb-3">
        <?php foreach (MODES_LIVRAISON as $i => $mode): ?>
          <div class="col-sm-6">
            <label class="choix-carte">
              <input type="radio" name="mode_livraison" value="<?= e($mode) ?>" <?= $val['mode_livraison'] === $mode ? 'checked' : '' ?>>
              <span class="choix-icone"><i class="fa-solid <?= $i === 0 ? 'fa-truck-fast' : 'fa-store' ?>"></i></span>
              <span><strong class="d-block"><?= e($mode) ?></strong><span class="small text-muted"><?= $i === 0 ? 'Chez vous, frais selon la zone' : e(BOUTIQUE_ADRESSE) ?></span></span>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="row g-3">
        <div class="col-12" data-bloc-adresse><label class="form-label" for="adresse_livraison">Adresse de livraison *</label><textarea class="form-control" id="adresse_livraison" name="adresse_livraison" rows="2" maxlength="255" autocomplete="street-address" placeholder="Quartier, rue, point de repère (ex : Golf Sud, près de la pharmacie)"><?= e($val['adresse_livraison']) ?></textarea></div>
        <div class="col-12"><label class="form-label" for="ville">Ville / commune *</label><input class="form-control" id="ville" name="ville" value="<?= e($val['ville']) ?>" required maxlength="100" autocomplete="address-level2" placeholder="Ex : Guédiawaye"></div>
        <div class="col-12"><label class="form-label" for="remarque">Instructions (facultatif)</label><textarea class="form-control" id="remarque" name="remarque" rows="2" placeholder="Taille, couleur, heure de livraison…"><?= e($val['remarque']) ?></textarea></div>
      </div>
    </div>

    <div class="panel mt-4">
      <h2 class="h5 mb-3"><span class="num-etape">3</span> Mode de paiement</h2>
      <div class="row g-2">
        <?php foreach (MODES_PAIEMENT as $mode): [$ico, $cls, $desc] = info_paiement($mode); ?>
          <div class="col-sm-4">
            <label class="choix-carte choix-paiement <?= e($cls) ?>">
              <input type="radio" name="paiement" value="<?= e($mode) ?>" <?= $val['paiement'] === $mode ? 'checked' : '' ?>>
              <span class="choix-icone"><i class="<?= e($ico) ?>"></i></span>
              <span><strong class="d-block"><?= e($mode) ?></strong><span class="small text-muted"><?= e($desc) ?></span></span>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Votre paiement reste « en attente » jusqu'à sa vérification par la boutique.</p>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel">
      <h2 class="h5 mb-3">Votre commande</h2>
      <?php foreach ($lignes as $l): ?>
        <div class="d-flex justify-content-between gap-3 mb-2 small">
          <span><?= e($l['p']['nom']) ?> <span class="text-muted">× <?= $l['qty'] ?></span></span>
          <span class="fw-medium text-nowrap"><?= fcfa($l['sous_total']) ?></span>
        </div>
      <?php endforeach; ?>
      <hr>
      <div class="d-flex justify-content-between align-items-center mb-3"><span class="fw-medium">Total</span><span class="total-line"><?= fcfa($total) ?></span></div>
      <p class="small text-muted">Frais de livraison communiqués selon votre zone.</p>
      <button class="btn btn-rose btn-lg w-100" data-texte-envoi="Enregistrement…"><i class="fa-solid fa-check me-2"></i>Confirmer la commande</button>
      <a href="<?= e(base_url('panier.php')) ?>" class="d-block text-center mt-3 small">Modifier mon panier</a>
    </div>
  </div>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
