<?php
require_once __DIR__ . '/_auth.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $icone = $_POST['icone'] ?? 'fa-gem';
    if (!isset(ICONES_CATEGORIES[$icone])) {
        $icone = 'fa-gem';
    }

    if ($action === 'add') {
        if ($nom === '' || mb_strlen($nom) > 100) {
            flash('danger', 'Le nom de la catégorie est obligatoire.');
        } else {
            $pdo->prepare('INSERT INTO categories (nom, icone) VALUES (?, ?)')->execute([$nom, $icone]);
            flash('success', 'Catégorie ajoutée.');
        }
    } elseif ($action === 'edit' && $id > 0) {
        if ($nom === '' || mb_strlen($nom) > 100) {
            flash('danger', 'Le nom de la catégorie est obligatoire.');
        } else {
            $pdo->prepare('UPDATE categories SET nom = ?, icone = ? WHERE id = ?')->execute([$nom, $icone, $id]);
            flash('success', 'Catégorie modifiée.');
        }
    } elseif ($action === 'delete' && $id > 0) {
        exiger_principal('categories.php', 'Seul l\'administrateur principal peut supprimer une catégorie.');
        $pdo->prepare('UPDATE produits SET categorie_id = NULL WHERE categorie_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash('success', 'Catégorie supprimée. Ses produits sont conservés (sans catégorie).');
    }
    redirect('categories.php');
}

$adminPage = 'categories';
$titre = 'Catégories';
require __DIR__ . '/_header.php';

$cats = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM produits p WHERE p.categorie_id = c.id) AS nb FROM categories c ORDER BY c.nom')->fetchAll();

function icone_select(string $courant): string
{
    $h = '<select name="icone" class="form-select form-select-sm">';
    foreach (ICONES_CATEGORIES as $k => $label) {
        $h .= '<option value="' . e($k) . '"' . ($k === $courant ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $h . '</select>';
}
?>

<div class="admin-head">
  <div>
    <h1 class="page-title">Catégories <span class="compteur-titre">(<?= count($cats) ?>)</span></h1>
    <p class="admin-sous">Organisez les univers de la boutique. Modifiez un nom ou une icône puis enregistrez la ligne.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel">
      <h2 class="h5 mb-4">Nouvelle catégorie</h2>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <div class="mb-3"><label class="form-label" for="nom">Nom</label><input class="form-control" id="nom" name="nom" required maxlength="100"></div>
        <div class="mb-4"><label class="form-label">Icône</label><?= icone_select('fa-gem') ?></div>
        <button class="btn btn-rose w-100"><i class="fa-solid fa-plus me-1"></i>Ajouter</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="panel panel-table">
      <?php if ($cats): ?>
      <div class="table-responsive">
        <table class="table table-rose align-middle">
          <thead><tr><th style="width:52px"></th><th>Catégorie</th><th class="text-center">Produits</th><th class="text-end">Icône et actions</th></tr></thead>
          <tbody>
          <?php foreach ($cats as $c): $fid = 'f' . (int)$c['id']; ?>
            <tr>
              <td><span class="stat-ic m-0" style="width:36px;height:36px;font-size:.9rem"><i class="fa-solid <?= e($c['icone']) ?>"></i></span></td>
              <td style="min-width:180px"><input form="<?= $fid ?>" class="form-control form-control-sm" name="nom" value="<?= e($c['nom']) ?>" required maxlength="100" aria-label="Nom de la catégorie"></td>
              <td class="text-center fw-medium"><?= (int)$c['nb'] ?></td>
              <td class="text-end">
                <div class="admin-actions">
                  <form id="<?= $fid ?>" method="post">
                    <?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <?= icone_select($c['icone']) ?>
                    <button class="btn btn-outline-rose btn-sm btn-ic" title="Enregistrer"><i class="fa-solid fa-check"></i></button>
                  </form>
                  <?php if (est_principal()): ?>
                  <form method="post" data-confirm-titre="Supprimer la catégorie ?" data-confirm="Ses produits seront conservés, sans catégorie. Cette action est définitive." data-confirm-ok="Supprimer">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-outline-danger btn-sm btn-ic" title="Supprimer"><i class="fa-solid fa-trash-can"></i></button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="admin-vide"><i class="fa-solid fa-layer-group"></i>Aucune catégorie pour le moment.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
