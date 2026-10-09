<?php
require_once __DIR__ . '/_auth.php';

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $actuel = (string)($_POST['actuel'] ?? '');
    $nouveau = (string)($_POST['nouveau'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    $st = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $st->execute([$_SESSION['admin_id']]);
    $admin = $st->fetch();

    if (!$admin || !password_verify($actuel, $admin['password_hash'])) $erreurs[] = 'Le mot de passe actuel est incorrect.';
    if (strlen($nouveau) < 8)  $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    if ($nouveau !== $confirm) $erreurs[] = 'La confirmation ne correspond pas.';

    if (!$erreurs) {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
        flash('success', 'Mot de passe modifié avec succès.');
        redirect('index.php');
    }
}

$adminPage = 'mdp';
$titre = 'Mot de passe';
require __DIR__ . '/_header.php';
?>

<h1 class="page-title mb-4">Changer le mot de passe</h1>

<?php if ($erreurs): ?>
  <div class="alert alert-danger rounded-4"><ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="panel" style="max-width:480px">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label" for="actuel">Mot de passe actuel</label><input class="form-control" id="actuel" name="actuel" type="password" required autocomplete="current-password"></div>
  <div class="mb-3"><label class="form-label" for="nouveau">Nouveau mot de passe (8 caractères min.)</label><input class="form-control" id="nouveau" name="nouveau" type="password" required minlength="8" autocomplete="new-password"></div>
  <div class="mb-4"><label class="form-label" for="confirm">Confirmer le nouveau mot de passe</label><input class="form-control" id="confirm" name="confirm" type="password" required autocomplete="new-password"></div>
  <button class="btn btn-rose px-4">Enregistrer</button>
</form>

<?php require __DIR__ . '/_footer.php'; ?>
