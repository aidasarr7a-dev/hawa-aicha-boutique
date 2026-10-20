<?php
$adminPage = 'dashboard';
$titre = 'Tableau de bord';
require __DIR__ . '/_header.php';

$pdo = db();
$nbProduits   = (int)$pdo->query('SELECT COUNT(*) FROM produits WHERE actif = 1')->fetchColumn();
$nbRupture    = (int)$pdo->query('SELECT COUNT(*) FROM produits WHERE actif = 1 AND stock <= 0')->fetchColumn();
$nbCommandes  = (int)$pdo->query("SELECT COUNT(*) FROM commandes WHERE statut <> 'annulee'")->fetchColumn();
$nbNouvelles  = (int)$pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'nouvelle'")->fetchColumn();
$nbClients    = (int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
$attente      = $pdo->query("SELECT COUNT(*) AS nb, COALESCE(SUM(pa.montant), 0) AS montant
                               FROM paiements pa JOIN commandes c ON c.id = pa.commande_id
                              WHERE pa.statut_paiement = 'en_attente' AND c.statut <> 'annulee'")->fetch();
// Ventes réellement encaissées : uniquement les paiements confirmés « Payé »
$ventesPayees = (int)$pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut_paiement = 'paye'")->fetchColumn();
$montantCommandes = (int)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut <> 'annulee'")->fetchColumn();
$panierMoyen = $nbCommandes ? intdiv($montantCommandes, $nbCommandes) : 0;

// Courbe des ventes : montant des commandes (hors annulées) par jour sur les 30 derniers jours
$nbJours = 30;
$debut = new DateTimeImmutable('today -' . ($nbJours - 1) . ' days');
$st = $pdo->prepare("SELECT DATE(created_at) AS jour, COUNT(*) AS nb, SUM(total) AS montant
                       FROM commandes WHERE statut <> 'annulee' AND created_at >= ? GROUP BY DATE(created_at)");
$st->execute([$debut->format('Y-m-d')]);
$parJour = [];
foreach ($st->fetchAll() as $r) {
    $parJour[$r['jour']] = $r;
}
$jours = [];
for ($i = 0; $i < $nbJours; $i++) {
    $d = $debut->modify("+$i days");
    $r = $parJour[$d->format('Y-m-d')] ?? null;
    $jours[] = ['date' => $d, 'nb' => (int)($r['nb'] ?? 0), 'montant' => (int)($r['montant'] ?? 0)];
}
$totalPeriode = array_sum(array_column($jours, 'montant'));
$cmdPeriode   = array_sum(array_column($jours, 'nb'));
// Comparaison avec les 30 jours précédents
$st = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut <> 'annulee' AND created_at >= ? AND created_at < ?");
$st->execute([$debut->modify("-$nbJours days")->format('Y-m-d'), $debut->format('Y-m-d')]);
$totalAvant = (int)$st->fetchColumn();
$evolution = $totalAvant > 0 ? round(($totalPeriode - $totalAvant) / $totalAvant * 100) : null;

// Échelle du graphique : maximum arrondi à un palier lisible (1, 2 ou 5 × 10^n)
$maxJour = max(array_column($jours, 'montant')) ?: 1;
$puissance = 10 ** floor(log10($maxJour));
foreach ([1, 2, 5, 10] as $m) {
    if ($m * $puissance >= $maxJour) { $echelle = $m * $puissance; break; }
}
$moisCourts = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
$dateCourte = fn(DateTimeImmutable $d) => (int)$d->format('j') . ' ' . $moisCourts[(int)$d->format('n')];
$montantCourt = fn(int $n) => $n >= 1000 ? number_format($n / 1000, $n % 1000 ? 1 : 0, ',', '') . ' k' : (string)$n;

// Répartition des commandes par statut
$parStatut = $pdo->query('SELECT statut, COUNT(*) FROM commandes GROUP BY statut')->fetchAll(PDO::FETCH_KEY_PAIR);
$totalStatuts = array_sum($parStatut) ?: 1;

$dernieres = $pdo->query('SELECT c.*, pa.statut_paiement FROM commandes c LEFT JOIN paiements pa ON pa.commande_id = c.id ORDER BY c.id DESC LIMIT 6')->fetchAll();

$meilleures = $pdo->query("SELECT p.id, p.nom, p.image, p.prix, cat.icone, SUM(ci.quantite) AS vendus, SUM(ci.sous_total) AS montant
                             FROM commande_items ci
                             JOIN commandes c ON c.id = ci.commande_id AND c.statut <> 'annulee'
                             JOIN produits p ON p.id = ci.produit_id
                             LEFT JOIN categories cat ON cat.id = p.categorie_id
                            GROUP BY p.id ORDER BY vendus DESC, montant DESC LIMIT 5")->fetchAll();
$maxVendus = max(array_column($meilleures, 'vendus') ?: [1]);

$faibles = $pdo->prepare('SELECT p.id, p.nom, p.stock, p.image, c.icone FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
                           WHERE p.actif = 1 AND p.stock <= ? ORDER BY p.stock, p.nom LIMIT 5');
$faibles->execute([SEUIL_STOCK]);
$faibles = $faibles->fetchAll();

// Coordonnées encore à compléter dans config.php
$manquants = array_keys(array_filter([
    'e-mail de contact' => CONTACT_EMAIL,
    'zone de livraison' => ZONE_LIVRAISON,
    'numéro WhatsApp'   => WHATSAPP_NUMBER,
    'numéro Wave'       => WAVE_NUMERO,
    'numéro Orange Money' => ORANGE_MONEY_NUMERO,
], fn($v) => trim($v) === ''));

$heure = (int)date('G');
$prenom = trim($adminConnecte['prenom'] ?? '') ?: ($_SESSION['admin_nom'] ?? '');
$joursSemaine = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
$moisLongs = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$aujourdhui = $joursSemaine[(int)date('w')] . ' ' . date('j') . ' ' . $moisLongs[(int)date('n')] . ' ' . date('Y');

/** Miniature d'un produit : sa photo, ou l'icône de sa catégorie s'il n'en a pas. */
$miniature = function (array $p): string {
    $img = product_image($p['image'] ?? null);
    return $img
        ? '<img class="dash-vignette" src="' . e($img) . '" alt="" loading="lazy">'
        : '<span class="dash-vignette"><i class="fa-solid ' . e($p['icone'] ?? 'fa-gem') . '"></i></span>';
};
?>

<div class="admin-head dash-head">
  <div>
    <p class="dash-date"><i class="fa-regular fa-calendar me-1"></i><?= e(ucfirst($aujourdhui)) ?></p>
    <h1 class="page-title"><?= $heure < 18 ? 'Bonjour' : 'Bonsoir' ?><?= $prenom !== '' ? ', ' . e($prenom) : '' ?></h1>
    <p class="admin-sous">Voici l'activité de la boutique Hawa Aïcha.</p>
  </div>
  <div class="admin-head-actions">
    <a href="commandes.php" class="btn btn-outline-rose"><i class="fa-solid fa-receipt me-1"></i>Commandes</a>
    <a href="produit_form.php" class="btn btn-rose"><i class="fa-solid fa-plus me-1"></i>Ajouter un produit</a>
  </div>
</div>

<?php if ($nbPaiementsDeclares || $nbMessagesNonLus || $nbNouvelles): ?>
  <div class="dash-actions">
    <?php if ($nbNouvelles): ?><a class="dash-action" href="commandes.php?statut=nouvelle"><span class="dash-action-ic"><i class="fa-solid fa-bell"></i></span><span><strong><?= $nbNouvelles ?> nouvelle<?= $nbNouvelles > 1 ? 's' : '' ?> commande<?= $nbNouvelles > 1 ? 's' : '' ?></strong><small>à préparer</small></span><i class="fa-solid fa-chevron-right ms-auto"></i></a><?php endif; ?>
    <?php if ($nbPaiementsDeclares): ?><a class="dash-action" href="paiements.php?statut=en_attente"><span class="dash-action-ic ic-alerte"><i class="fa-solid fa-hand-holding-dollar"></i></span><span><strong><?= $nbPaiementsDeclares ?> paiement<?= $nbPaiementsDeclares > 1 ? 's' : '' ?> déclaré<?= $nbPaiementsDeclares > 1 ? 's' : '' ?></strong><small>à vérifier puis confirmer</small></span><i class="fa-solid fa-chevron-right ms-auto"></i></a><?php endif; ?>
    <?php if ($nbMessagesNonLus): ?><a class="dash-action" href="messages.php?filtre=non_lus"><span class="dash-action-ic ic-info"><i class="fa-solid fa-envelope"></i></span><span><strong><?= $nbMessagesNonLus ?> message<?= $nbMessagesNonLus > 1 ? 's' : '' ?> non lu<?= $nbMessagesNonLus > 1 ? 's' : '' ?></strong><small>formulaire de contact</small></span><i class="fa-solid fa-chevron-right ms-auto"></i></a><?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($manquants): ?>
  <div class="admin-info"><i class="fa-solid fa-circle-info"></i>
    <span>Informations à compléter dans <code>config.php</code> : <?= e(implode(', ', $manquants)) ?>.</span></div>
<?php endif; ?>

<!-- Indicateurs clés -->
<div class="dash-kpis">
  <a class="kpi" href="paiements.php?statut=paye">
    <span class="kpi-entete"><span class="kpi-ic ic-ok"><i class="fa-solid fa-sack-dollar"></i></span>Ventes encaissées</span>
    <span class="kpi-valeur"><?= fcfa($ventesPayees) ?></span>
    <span class="kpi-pied">Paiements confirmés « Payé »</span>
  </a>
  <a class="kpi" href="commandes.php">
    <span class="kpi-entete"><span class="kpi-ic"><i class="fa-solid fa-bag-shopping"></i></span>Commandes</span>
    <span class="kpi-valeur"><?= $nbCommandes ?></span>
    <span class="kpi-pied">Montant total <?= fcfa($montantCommandes) ?></span>
  </a>
  <a class="kpi" href="paiements.php?statut=en_attente">
    <span class="kpi-entete"><span class="kpi-ic ic-alerte"><i class="fa-solid fa-hourglass-half"></i></span>Paiements en attente</span>
    <span class="kpi-valeur"><?= fcfa($attente['montant']) ?></span>
    <span class="kpi-pied"><?= (int)$attente['nb'] ?> paiement<?= $attente['nb'] > 1 ? 's' : '' ?> à confirmer</span>
  </a>
  <a class="kpi" href="commandes.php">
    <span class="kpi-entete"><span class="kpi-ic ic-info"><i class="fa-solid fa-basket-shopping"></i></span>Panier moyen</span>
    <span class="kpi-valeur"><?= fcfa($panierMoyen) ?></span>
    <span class="kpi-pied">par commande</span>
  </a>
</div>

<div class="dash-mini">
  <a href="clients.php"><i class="fa-solid fa-users"></i><strong><?= $nbClients ?></strong> client<?= $nbClients > 1 ? 's' : '' ?> inscrit<?= $nbClients > 1 ? 's' : '' ?></a>
  <a href="produits.php"><i class="fa-solid fa-box-open"></i><strong><?= $nbProduits ?></strong> produit<?= $nbProduits > 1 ? 's' : '' ?> en ligne</a>
  <a href="produits.php?stock=epuise" class="<?= $nbRupture ? 'mini-alerte' : '' ?>"><i class="fa-solid fa-triangle-exclamation"></i><strong><?= $nbRupture ?></strong> en rupture de stock</a>
</div>

<div class="row g-4 mb-4">
  <!-- Graphique des ventes -->
  <div class="col-xl-8">
    <section class="panel h-100" aria-labelledby="titre-ventes">
      <div class="panel-entete align-items-start">
        <div>
          <h2 class="h5" id="titre-ventes">Ventes des 30 derniers jours</h2>
          <p class="dash-legende mb-0">Montant des commandes reçues par jour (hors annulées)</p>
        </div>
        <div class="dash-total-bloc">
          <div class="dash-total"><?= fcfa($totalPeriode) ?></div>
          <div class="dash-legende">
            <?= $cmdPeriode ?> commande<?= $cmdPeriode > 1 ? 's' : '' ?>
            <?php if ($evolution !== null): ?>
              · <span class="<?= $evolution >= 0 ? 'evol-hausse' : 'evol-baisse' ?>"><i class="fa-solid <?= $evolution >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?>"></i> <?= ($evolution > 0 ? '+' : '') . $evolution ?> %</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="graph-ventes" role="img" aria-label="Montant des commandes par jour du <?= e($dateCourte($jours[0]['date'])) ?> au <?= e($dateCourte(end($jours)['date'])) ?> : total <?= e(fcfa($totalPeriode)) ?>">
        <div class="graph-axe" aria-hidden="true">
          <?php foreach ([1, .5, 0] as $f): ?><span style="bottom: <?= $f * 100 ?>%"><?= $montantCourt((int)($echelle * $f)) ?></span><?php endforeach; ?>
        </div>
        <div class="graph-zone">
          <?php foreach ([1, .5, 0] as $f): ?><span class="graph-ligne" style="bottom: <?= $f * 100 ?>%"></span><?php endforeach; ?>
          <div class="graph-barres">
            <?php foreach ($jours as $i => $j): $h = $j['montant'] / $echelle * 100; ?>
              <div class="graph-col <?= $i >= $nbJours / 2 ? 'bulle-gauche' : '' ?>" tabindex="0">
                <span class="graph-barre<?= $j['montant'] ? '' : ' vide' ?>" style="height: <?= $j['montant'] ? max($h, 1.5) : 0 ?>%"></span>
                <span class="graph-bulle"><strong><?= e($dateCourte($j['date'])) ?></strong><?= fcfa($j['montant']) ?><small><?= $j['nb'] ?> commande<?= $j['nb'] > 1 ? 's' : '' ?></small></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="graph-dates" aria-hidden="true">
          <?php foreach ([0, 10, 20, $nbJours - 1] as $i): ?><span style="left: <?= ($i + .5) / $nbJours * 100 ?>%"><?= e($i === $nbJours - 1 ? "Aujourd'hui" : $dateCourte($jours[$i]['date'])) ?></span><?php endforeach; ?>
        </div>
      </div>
    </section>
  </div>

  <!-- Suivi des commandes par statut -->
  <div class="col-xl-4">
    <section class="panel h-100" aria-labelledby="titre-statuts">
      <div class="panel-entete">
        <h2 class="h5" id="titre-statuts">Suivi des commandes</h2>
        <a href="commandes.php">Tout voir</a>
      </div>
      <ul class="dash-statuts list-unstyled mb-0">
        <?php foreach (STATUTS as $k => [$libelle, $cls]): $n = (int)($parStatut[$k] ?? 0); ?>
          <li>
            <a href="commandes.php?statut=<?= e($k) ?>">
              <span class="d-flex justify-content-between gap-2"><span><span class="point point-<?= e($cls) ?>"></span><?= e($libelle) ?></span><strong><?= $n ?></strong></span>
              <span class="jauge"><span style="width: <?= round($n / $totalStatuts * 100) ?>%"></span></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Produits les plus vendus -->
  <div class="col-lg-6">
    <section class="panel h-100" aria-labelledby="titre-meilleures">
      <div class="panel-entete">
        <h2 class="h5" id="titre-meilleures">Meilleures ventes</h2>
        <a href="produits.php">Catalogue</a>
      </div>
      <?php if ($meilleures): ?>
        <ul class="dash-produits list-unstyled mb-0">
          <?php foreach ($meilleures as $p): ?>
            <li>
              <a href="produit_form.php?id=<?= (int)$p['id'] ?>">
                <?= $miniature($p) ?>
                <span class="flex-grow-1 min-w-0">
                  <span class="d-flex justify-content-between gap-2"><span class="text-truncate fw-medium"><?= e($p['nom']) ?></span><span class="text-nowrap small text-muted"><?= (int)$p['vendus'] ?> vendu<?= $p['vendus'] > 1 ? 's' : '' ?> · <?= fcfa($p['montant']) ?></span></span>
                  <span class="jauge"><span style="width: <?= round($p['vendus'] / $maxVendus * 100) ?>%"></span></span>
                </span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted mb-0">Aucune vente pour le moment.</p>
      <?php endif; ?>
    </section>
  </div>

  <!-- Stock faible -->
  <div class="col-lg-6">
    <section class="panel h-100" aria-labelledby="titre-stock">
      <div class="panel-entete">
        <h2 class="h5" id="titre-stock">À réapprovisionner</h2>
        <?php if ($faibles): ?><a href="produits.php?stock=faible">Tout voir</a><?php endif; ?>
      </div>
      <?php if ($faibles): ?>
        <ul class="dash-produits list-unstyled mb-0">
          <?php foreach ($faibles as $f): ?>
            <li>
              <a href="produit_form.php?id=<?= (int)$f['id'] ?>">
                <?= $miniature($f) ?>
                <span class="flex-grow-1 min-w-0 text-truncate fw-medium"><?= e($f['nom']) ?></span>
                <span class="badge rounded-pill text-nowrap <?= (int)$f['stock'] <= 0 ? 'text-bg-danger' : 'text-bg-warning' ?>"><?= (int)$f['stock'] <= 0 ? 'Épuisé' : (int)$f['stock'] . ' restant' . ((int)$f['stock'] > 1 ? 's' : '') ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted mb-0"><i class="fa-solid fa-circle-check text-success me-1"></i>Tout est bien approvisionné.</p>
      <?php endif; ?>
    </section>
  </div>
</div>

<div class="row g-4">
  <!-- Dernières commandes -->
  <div class="col-12">
    <section class="panel panel-table" aria-labelledby="titre-dernieres">
      <div class="panel-entete">
        <h2 class="h5" id="titre-dernieres">Dernières commandes</h2>
        <a href="commandes.php">Tout voir <i class="fa-solid fa-arrow-right ms-1"></i></a>
      </div>
      <?php if ($dernieres): ?>
      <div class="table-responsive">
        <table class="table table-rose align-middle table-cartes">
          <thead><tr><th>Cliente / client</th><th>N°</th><th>Total</th><th>Commande</th><th>Paiement</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($dernieres as $c): ?>
            <tr>
              <td class="cel-titre">
                <div class="d-flex align-items-center gap-2">
                  <span class="dash-avatar"><?= e(mb_strtoupper(mb_substr(trim($c['nom_complet']) ?: '?', 0, 1))) ?></span>
                  <span class="min-w-0"><span class="d-block text-nowrap fw-medium"><?= e($c['nom_complet']) ?></span><span class="small text-muted text-nowrap"><?= e(date('d/m/Y à H:i', strtotime($c['created_at']))) ?></span></span>
                </div>
              </td>
              <td class="text-nowrap cellule-principale" data-label="N°"><?= e($c['numero_commande']) ?></td>
              <td class="text-nowrap fw-medium" data-label="Total"><?= fcfa($c['total']) ?></td>
              <td data-label="Commande"><?= badge_statut($c['statut']) ?></td>
              <td data-label="Paiement"><?= badge_paiement($c['statut_paiement']) ?></td>
              <td class="text-end cel-actions"><a class="btn btn-outline-rose btn-sm" href="commande.php?id=<?= (int)$c['id'] ?>">Voir</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="admin-vide"><i class="fa-solid fa-receipt"></i>Aucune commande pour le moment.</div>
      <?php endif; ?>
    </section>
  </div>

</div>

<?php require __DIR__ . '/_footer.php'; ?>
