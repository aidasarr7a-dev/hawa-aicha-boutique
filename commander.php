<?php
require_once __DIR__ . '/includes/functions.php';

$panierAvant = array_map('intval', cart_get());
[$lignes, $total] = cart_items();
// Vrai si un article a disparu ou si une quantité a été réduite au stock disponible
$panierAjuste = $panierAvant != array_map('intval', cart_get());
if (!$lignes && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulaire renvoyé après une commande réussie (double clic, retour arrière) : on réaffiche la confirmation
    if (!empty($_SESSION['derniere_commande'])) {
        redirect(base_url('merci.php?id=' . (int)$_SESSION['derniere_commande']));
    }
    flash('warning', 'Votre panier est vide.');
    redirect(base_url('panier.php'));
}
// Panier vide en accès direct (menu « Commande ») : la page affiche un message au lieu du formulaire

$erreurs = [];
$val = ['nom_complet' => '', 'telephone' => '', 'adresse_livraison' => '', 'ville' => '', 'remarque' => '',
        'paiement' => MODES_PAIEMENT[0], 'mode_livraison' => MODES_LIVRAISON[0]];

// Client connecté : formulaire prérempli avec ses informations
$client = client_connecte();
if ($client) {
    $val['nom_complet'] = $client['nom_complet'];
    $val['telephone'] = $client['telephone'];
    $val['adresse_livraison'] = (string)$client['adresse'];
}

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

    // Formulaire simplifié : nom complet, numéro, adresse de livraison et mode de paiement
    foreach (['nom_complet', 'telephone', 'adresse_livraison', 'paiement'] as $k) {
        $val[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $tel = preg_replace('/[^\d+]/', '', $val['telephone']);

    if (mb_strlen($val['nom_complet']) < 3 || mb_strlen($val['nom_complet']) > 200) $erreurs[] = 'Veuillez indiquer votre nom complet.';
    if (strlen(preg_replace('/\D/', '', $tel)) < 8)               $erreurs[] = 'Veuillez indiquer un numéro de téléphone / WhatsApp valide.';
    if (mb_strlen($val['adresse_livraison']) < 3)                      $erreurs[] = 'Veuillez indiquer votre adresse de livraison (quartier, ville, point de repère).';
    if (!in_array($val['paiement'], MODES_PAIEMENT, true))        $erreurs[] = 'Veuillez choisir un moyen de paiement.';
    // Un mode « en cours » est refusé côté serveur, même si le formulaire a été modifié
    elseif (in_array($val['paiement'], MODES_PAIEMENT_EN_COURS, true)) $erreurs[] = 'Le paiement par ' . $val['paiement'] . ' est en cours de mise en place. Merci de choisir un autre moyen de paiement.';
    foreach (['adresse_livraison' => [255, 'adresse de livraison']] as $champ => [$max, $libelle]) {
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
            if ($client) {
                // Client connecté : la commande est rattachée à son compte, son adresse habituelle est mémorisée
                $clientId = (int)$client['id'];
                if ($val['adresse_livraison'] !== '') {
                    $pdo->prepare('UPDATE clients SET adresse = ? WHERE id = ?')
                        ->execute([$val['adresse_livraison'], $clientId]);
                }
            } else {
                // Sans compte : fiche client par numéro de téléphone (un compte existant n'est jamais modifié)
                $pdo->prepare(
                    'INSERT INTO clients (nom_complet, telephone, adresse, ville) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id),
                         nom_complet = IF(mot_de_passe_hash IS NULL, VALUES(nom_complet), nom_complet),
                         adresse = IF(mot_de_passe_hash IS NULL, VALUES(adresse), adresse),
                         ville = IF(mot_de_passe_hash IS NULL, VALUES(ville), ville)'
                )->execute([$val['nom_complet'], $tel, $val['adresse_livraison'] ?: null, $val['ville']]);
                $clientId = (int)$pdo->lastInsertId();
            }

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

$page = 'commande';
$titre = 'Commande';
require __DIR__ . '/includes/header.php';
?>

<div class="entete-page mb-4">
  <h1 class="page-title mb-1">Commande</h1>
  <p class="text-muted mb-0">Indiquez vos coordonnées, choisissez votre paiement et validez : c'est tout.</p>
</div>

<?php if (!$lignes): ?>
  <div class="panel empty-state">
    <i class="fa-solid fa-bag-shopping"></i>
    <p class="mt-3 mb-1 fw-medium">Votre panier est vide.</p>
    <p class="text-muted mb-3">Ajoutez des produits au panier, puis revenez ici pour passer votre commande.</p>
    <a href="<?= e(base_url('produits.php')) ?>" class="btn btn-rose">Choisir mes produits</a>
  </div>
<?php else: ?>
<?php if (!$client): ?>
  <div class="alert-compte mb-4"><i class="fa-solid fa-user-circle me-2"></i>Vous avez un compte ? <a href="<?= e(base_url('connexion.php?retour=commander.php')) ?>">Connectez-vous</a> pour retrouver vos commandes et factures. <span class="text-muted">Sinon, commandez directement ci-dessous.</span></div>
<?php endif; ?>
<form method="post" class="row g-4" novalidate data-envoi-unique>
  <?= csrf_field() ?>
  <input type="hidden" name="jeton" value="<?= e($_SESSION['jeton_commande']) ?>">
  <div class="col-lg-7">
    <div class="panel">
      <h2 class="h5 mb-3"><span class="num-etape">1</span> Vos informations</h2>
      <div class="row g-3">
        <div class="col-12"><label class="form-label" for="nom_complet">Nom complet</label><input class="form-control" id="nom_complet" name="nom_complet" value="<?= e($val['nom_complet']) ?>" required maxlength="200" autocomplete="name" placeholder="Ex : Aminata Sow"></div>
        <div class="col-12"><label class="form-label" for="telephone">Numéro de téléphone</label><input class="form-control" id="telephone" name="telephone" type="tel" inputmode="tel" placeholder="Ex : 77 000 00 00" value="<?= e($val['telephone']) ?>" required autocomplete="tel"></div>
        <div class="col-12"><label class="form-label" for="adresse_livraison">Adresse de livraison</label><textarea class="form-control" id="adresse_livraison" name="adresse_livraison" rows="3" maxlength="255" required autocomplete="street-address" placeholder="Quartier, ville, point de repère (ex : Golf Sud, Guédiawaye, près de la pharmacie)"><?= e($val['adresse_livraison']) ?></textarea></div>
      </div>
    </div>

    <div class="panel mt-4">
      <h2 class="h5 mb-3"><span class="num-etape">2</span> Paiement</h2>
      <div class="row g-2 mb-3">
        <?php foreach (MODES_PAIEMENT as $mode): [, $cls, $desc] = info_paiement($mode); $enCours = in_array($mode, MODES_PAIEMENT_EN_COURS, true); ?>
          <div class="col-md-4">
            <label class="choix-carte choix-paiement choix-vertical <?= e($cls) ?><?= $enCours ? ' choix-en-cours' : '' ?>"<?= $enCours ? ' aria-disabled="true" title="Bientôt disponible"' : '' ?>>
              <input type="radio" name="paiement" value="<?= e($mode) ?>" <?= $enCours ? 'disabled' : ($val['paiement'] === $mode ? 'checked' : '') ?>>
              <?php if ($enCours): ?><span class="badge-en-cours">En cours</span><?php endif; ?>
              <?= logo_paiement($mode) ?>
              <span class="flex-grow-1"><strong class="d-block"><?= e($mode) ?></strong><span class="small text-muted"><?= $enCours ? 'Bientôt disponible' : e($desc) ?></span></span>
              <?php if (!$enCours): ?><i class="fa-solid fa-circle-check coche"></i><?php endif; ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>

      <?php foreach (array_diff(MODES_PAIEMENT, MODES_PAIEMENT_EN_COURS) as $mode): $numero =['Wave' => WAVE_NUMERO, 'Orange Money' => ORANGE_MONEY_NUMERO][$mode] ?? ''; ?>
        <div class="infos-paiement <?= e(info_paiement($mode)[1]) ?>" data-infos-paiement="<?= e($mode) ?>" <?= $val['paiement'] === $mode ? '' : 'hidden' ?>>
          <?php if ($numero !== ''): ?>
            <div class="small mb-2">Après avoir validé, envoyez <strong class="text-nowrap"><?= fcfa($total) ?></strong> par <strong><?= e($mode) ?></strong> au :</div>
            <div class="numero-paiement">
              <?= logo_paiement($mode) ?>
              <strong><?= e($numero) ?></strong>
              <button type="button" class="btn btn-sm btn-outline-rose ms-auto" data-copier="<?= e(preg_replace('/\D/', '', $numero)) ?>"><i class="fa-regular fa-copy"></i> Copier</button>
            </div>
            <div class="small text-muted mt-2">Un bouton « Payer avec <?= e($mode) ?> » vous sera proposé juste après la validation.</div>
          <?php else: ?>
            <div class="small"><i class="fa-solid fa-money-bill-wave me-1"></i> Vous payez <strong class="text-nowrap"><?= fcfa($total) ?></strong> en espèces à la livraison.</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel recap-commande">
      <h2 class="h5 mb-3">Votre commande</h2>
      <?php foreach ($lignes as $l): $img = product_image($l['p']['image']); ?>
        <div class="recap-ligne">
          <span class="recap-img"><?php if ($img): ?><img src="<?= e($img) ?>" alt=""><?php else: ?><i class="fa-solid <?= e($l['p']['icone'] ?? 'fa-gem') ?>"></i><?php endif; ?></span>
          <span class="flex-grow-1 small"><?= e($l['p']['nom']) ?><br><span class="text-muted">× <?= $l['qty'] ?></span></span>
          <span class="fw-medium text-nowrap small"><?= fcfa($l['sous_total']) ?></span>
        </div>
      <?php endforeach; ?>
      <hr>
      <div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-medium">Total</span><span class="total-line"><?= fcfa($total) ?></span></div>
      <p class="small text-muted mb-3">Frais de livraison communiqués selon votre zone.</p>
      <button class="btn btn-rose btn-lg w-100" id="bouton-commande" data-total="<?= e(fcfa($total)) ?>" data-texte-envoi="Enregistrement…"><i class="fa-solid fa-check me-2"></i><span>Valider ma commande</span></button>
      <p class="small text-muted text-center mt-2 mb-0"><i class="fa-solid fa-lock me-1"></i>Commande enregistrée en toute sécurité</p>
      <a href="<?= e(base_url('panier.php')) ?>" class="d-block text-center mt-3 small">Modifier mon panier</a>
    </div>
  </div>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
