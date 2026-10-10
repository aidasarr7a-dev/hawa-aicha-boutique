<?php
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'lu' && $id > 0) {
        db()->prepare('UPDATE messages_contact SET lu = 1 - lu WHERE id = ?')->execute([$id]);
    } elseif ($action === 'supprimer' && $id > 0) {
        db()->prepare('DELETE FROM messages_contact WHERE id = ?')->execute([$id]);
        flash('success', 'Message supprimé.');
    }
    redirect('messages.php' . (($_POST['filtre'] ?? '') === 'non_lus' ? '?filtre=non_lus' : ''));
}

$adminPage = 'messages';
$titre = 'Messages';
require __DIR__ . '/_header.php';

$filtre = ($_GET['filtre'] ?? '') === 'non_lus' ? 'non_lus' : '';
$messages = db()->query('SELECT * FROM messages_contact' . ($filtre ? ' WHERE lu = 0' : '') . ' ORDER BY id DESC LIMIT 300')->fetchAll();
?>

<div class="admin-head">
  <div>
    <h1 class="page-title">Messages reçus</h1>
    <p class="admin-sous">Messages envoyés depuis le formulaire de la page Contact.</p>
  </div>
</div>

<div class="admin-filtres">
  <div class="chips">
    <a class="chip <?= $filtre === '' ? 'active' : '' ?>" href="messages.php">Tous</a>
    <a class="chip <?= $filtre === 'non_lus' ? 'active' : '' ?>" href="messages.php?filtre=non_lus">Non lus (<?= $nbMessagesNonLus ?>)</a>
  </div>
</div>

<?php if ($messages): ?>
  <div class="d-grid gap-3">
    <?php foreach ($messages as $m): ?>
      <article class="panel message-admin <?= $m['lu'] ? '' : 'non-lu' ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
          <div class="min-w-0">
            <span class="cellule-principale"><?= e($m['nom']) ?></span><?= $m['lu'] ? '' : ' <span class="badge rounded-pill text-bg-danger ms-1">Nouveau</span>' ?>
            <div class="small text-muted mt-1">
              <i class="fa-regular fa-clock me-1"></i><?= e(date('d/m/Y à H:i', strtotime($m['date_creation']))) ?>
              <?php if ($m['telephone']): ?><span class="mx-2">·</span><i class="fa-solid fa-phone me-1"></i><a href="tel:<?= e($m['telephone']) ?>"><?= e($m['telephone']) ?></a><?php endif; ?>
              <?php if ($m['email']): ?><span class="mx-2">·</span><i class="fa-regular fa-envelope me-1"></i><a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re : ' . ($m['sujet'] ?: 'votre message')) ?>"><?= e($m['email']) ?></a><?php endif; ?>
            </div>
          </div>
          <div class="admin-actions">
            <?php if ($m['telephone']): $tel = preg_replace('/\D/', '', $m['telephone']); if (strlen($tel) === 9) $tel = '221' . $tel; ?>
              <a class="btn btn-wa btn-sm btn-ic" target="_blank" rel="noopener" href="https://wa.me/<?= e($tel) ?>" title="Répondre sur WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
            <?php endif; ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="lu"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="filtre" value="<?= e($filtre) ?>">
              <button class="btn btn-outline-rose btn-sm btn-ic" title="<?= $m['lu'] ? 'Marquer non lu' : 'Marquer lu' ?>"><i class="fa-solid <?= $m['lu'] ? 'fa-envelope' : 'fa-envelope-open' ?>"></i></button></form>
            <form method="post" onsubmit="return confirm('Supprimer ce message ?')"><?= csrf_field() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button class="btn btn-outline-danger btn-sm btn-ic" title="Supprimer"><i class="fa-solid fa-trash-can"></i></button></form>
          </div>
        </div>
        <div class="message-texte">
          <?php if ($m['sujet']): ?><div class="fw-semibold mb-1"><?= e($m['sujet']) ?></div><?php endif; ?>
          <p class="mb-0"><?= nl2br(e($m['message'])) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="panel"><div class="admin-vide"><i class="fa-solid fa-envelope-open"></i>Aucun message<?= $filtre ? ' non lu' : '' ?>.</div></div>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
