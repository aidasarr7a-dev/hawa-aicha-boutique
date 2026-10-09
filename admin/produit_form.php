<?php
require_once __DIR__ . '/_auth.php';

$pdo = db();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$produit = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM produits WHERE id = ?');
    $st->execute([$id]);
    $produit = $st->fetch();
    if (!$produit) {
        flash('danger', 'Produit introuvable.');
        redirect('produits.php');
    }
}

$val = $produit ?: ['nom' => '', 'categorie_id' => '', 'description' => '', 'prix' => '', 'stock' => 0, 'nouveau' => 1, 'actif' => 1, 'image' => null];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $val['nom']          = trim($_POST['nom'] ?? '');
    $val['categorie_id'] = (int)($_POST['categorie_id'] ?? 0) ?: null;
    $val['description']  = trim($_POST['description'] ?? '');
    $val['prix']         = (int)preg_replace('/\D/', '', (string)($_POST['prix'] ?? '0'));
    $val['stock']        = max(0, (int)($_POST['stock'] ?? 0));
    $val['nouveau']      = isset($_POST['nouveau']) ? 1 : 0;
    $val['actif']        = isset($_POST['actif']) ? 1 : 0;

    if ($val['nom'] === '' || mb_strlen($val['nom']) > 150) $erreurs[] = 'Le nom du produit est obligatoire (150 caractères max).';
    if ($val['prix'] <= 0) $erreurs[] = 'Le prix doit être supérieur à 0.';
    if ($val['categorie_id']) {
        $c = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
        $c->execute([$val['categorie_id']]);
        if (!$c->fetchColumn()) $val['categorie_id'] = null;
    }

    $nouvelleImage = upload_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], $errImg);
    if ($errImg) $erreurs[] = $errImg;

    if (!$erreurs) {
        $ancienneImage = $produit['image'] ?? null;
        $image = $ancienneImage;
        if ($nouvelleImage) {
            $image = $nouvelleImage;
        } elseif ($ancienneImage && isset($_POST['supprimer_image'])) {
            $image = null;
        }

        if ($produit) {
            $pdo->prepare('UPDATE produits SET categorie_id=?, nom=?, description=?, prix=?, stock=?, image=?, nouveau=?, actif=? WHERE id=?')
                ->execute([$val['categorie_id'], $val['nom'], $val['description'] ?: null, $val['prix'], $val['stock'], $image, $val['nouveau'], $val['actif'], $id]);
            if ($image !== $ancienneImage) delete_image($ancienneImage);
            flash('success', 'Produit modifié.');
        } else {
            $pdo->prepare('INSERT INTO produits (categorie_id, nom, description, prix, stock, image, nouveau, actif) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$val['categorie_id'], $val['nom'], $val['description'] ?: null, $val['prix'], $val['stock'], $image, $val['nouveau'], $val['actif']]);
            flash('success', 'Produit ajouté.');
        }
        redirect('produits.php');
    }
    if ($nouvelleImage) delete_image($nouvelleImage); // formulaire invalide : on ne garde pas le fichier
}

$categories = get_categories();
$adminPage = 'produits';
$titre = $produit ? 'Modifier le produit' : 'Ajouter un produit';
require __DIR__ . '/_header.php';
?>

<h1 class="page-title mb-4"><?= e($titre) ?></h1>

<?php if ($erreurs): ?>
  <div class="alert alert-danger rounded-4"><ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="panel" style="max-width:820px">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <div class="row g-3">
    <div class="col-md-8"><label class="form-label" for="nom">Nom du produit *</label><input class="form-control" id="nom" name="nom" value="<?= e($val['nom']) ?>" required maxlength="150"></div>
    <div class="col-md-4">
      <label class="form-label" for="categorie_id">Catégorie</label>
      <select class="form-select" id="categorie_id" name="categorie_id">
        <option value="">— Aucune —</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)$val['categorie_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4"><?= e($val['description']) ?></textarea></div>
    <div class="col-sm-6"><label class="form-label" for="prix">Prix (FCFA) *</label><input class="form-control" id="prix" name="prix" type="number" min="1" step="1" value="<?= e($val['prix']) ?>" required></div>
    <div class="col-sm-6"><label class="form-label" for="stock">Stock disponible</label><input class="form-control" id="stock" name="stock" type="number" min="0" step="1" value="<?= (int)$val['stock'] ?>"></div>
    <div class="col-12">
      <label class="form-label" for="image">Photo du produit</label>
      <?php if (!empty($val['image'])): ?>
        <div class="d-flex align-items-center gap-3 mb-2">
          <img class="admin-thumb" style="width:80px;height:80px" src="<?= e(product_image($val['image'])) ?>" alt="">
          <div class="form-check"><input class="form-check-input" type="checkbox" name="supprimer_image" id="supprimer_image"><label class="form-check-label" for="supprimer_image">Supprimer la photo actuelle</label></div>
        </div>
      <?php endif; ?>
      <input class="form-control" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
      <div class="form-text">JPG, PNG ou WebP · 3 Mo maximum. Photo carrée de préférence.</div>
    </div>
    <div class="col-12 d-flex flex-wrap gap-4">
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="nouveau" id="nouveau" <?= $val['nouveau'] ? 'checked' : '' ?>><label class="form-check-label" for="nouveau">Marquer comme « Nouveau »</label></div>
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="actif" id="actif" <?= $val['actif'] ? 'checked' : '' ?>><label class="form-check-label" for="actif">Visible sur le site</label></div>
    </div>
  </div>
  <div class="d-flex gap-2 mt-4">
    <button class="btn btn-rose px-4"><i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer</button>
    <a href="produits.php" class="btn btn-outline-rose">Annuler</a>
  </div>
</form>

<?php require __DIR__ . '/_footer.php'; ?>
