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

<h1 class="page-title mb-4">Catégories</h1>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel">
      <h2 class="h5 mb-3">Nouvelle catégorie</h2>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <div class="mb-3"><label class="form-label" for="nom">Nom *</label><input class="form-control" id="nom" name="nom" required maxlength="100"></div>
        <div class="mb-3"><label class="form-label">Icône</label><?= icone_select('fa-gem') ?></div>
        <button class="btn btn-rose w-100"><i class="fa-solid fa-plus me-1"></i>Ajouter</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="panel">
      <?php if ($cats): ?>
      <div class="table-responsive">
        <table class="table table-rose align-middle mb-0">
          <thead><tr><th>Catégorie</th><th>Icône</th><th>Produits</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($cats as $c): $fid = 'f' . (int)$c['id']; ?>
            <tr>
              <td><input form="<?= $fid ?>" class="form-control form-control-sm" name="nom" value="<?= e($c['nom']) ?>" required maxlength="100"></td>
              <td><span class="me-2" style="color:var(--rose)"><i class="fa-solid <?= e($c['icone']) ?>"></i></span></td>
              <td><?= (int)$c['nb'] ?></td>
              <td class="text-end text-nowrap">
                <form id="<?= $fid ?>" method="post" class="d-inline-flex gap-1 align-items-center">
                  <?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <?= icone_select($c['icone']) ?>
                  <button class="btn btn-outline-rose btn-sm" title="Enregistrer"><i class="fa-solid fa-check"></i></button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ? Ses produits seront conservés sans catégorie.')">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm" title="Supprimer"><i class="fa-solid fa-trash-can"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">Aucune catégorie pour le moment.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
