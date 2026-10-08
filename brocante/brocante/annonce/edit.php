<?php
$pageTitle = 'Modifier l\'annonce';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = currentUser();
$pdo  = getPDO();

$id = (int)($_GET['id'] ?? 0);
$a  = $pdo->prepare('SELECT * FROM annonces WHERE id = ?');
$a->execute([$id]);
$annonce = $a->fetch();

if (!$annonce || ($annonce['user_id'] !== $user['id'] && !isAdmin())) {
    redirect(BASE_URL . '/index.php');
}

$cats   = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre      = trim($_POST['titre']       ?? '');
    $prix       = (float)str_replace(',', '.', $_POST['prix'] ?? '0');
    $etat       = $_POST['etat']             ?? '';
    $desc       = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;

    if (strlen($titre) < 3) $errors[] = 'Titre trop court.';
    if ($prix < 0)           $errors[] = 'Prix invalide.';
    if (!array_key_exists($etat, ETAT_LABELS)) $errors[] = 'État invalide.';
    if (strlen($desc) < 10)  $errors[] = 'Description trop courte.';

    $imageFile = $annonce['image'];
    if (!empty($_FILES['image']['name'])) {
        $up = uploadImage($_FILES['image'], 'ann');
        if (!$up) $errors[] = 'Image invalide.';
        else {
            // Supprime l'ancienne
            if ($imageFile && file_exists(UPLOAD_DIR . $imageFile)) unlink(UPLOAD_DIR . $imageFile);
            $imageFile = $up;
        }
    }

    if (empty($errors)) {
        $upd = $pdo->prepare('UPDATE annonces SET titre=?, description=?, prix=?, etat=?, image=?, category_id=? WHERE id=?');
        $upd->execute([$titre, $desc, $prix, $etat, $imageFile, $categoryId, $id]);
        flash('success', 'Annonce mise à jour.');
        redirect(BASE_URL . '/annonce/detail.php?id=' . $id);
    }
    $annonce = array_merge($annonce, compact('titre', 'prix', 'etat', 'description'));
    $annonce['category_id'] = $categoryId;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap" style="max-width:700px">
  <div class="form-card fade-up" style="max-width:100%;margin-top:1rem">
    <h1 class="form-title">Modifier l'annonce</h1>

    <?php foreach ($errors as $e): ?>
      <div class="flash flash-error"><?= h($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Titre *</label>
        <input type="text" name="titre" class="form-control"
               value="<?= h($annonce['titre']) ?>" required>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label>Prix (€) *</label>
          <input type="number" name="prix" class="form-control" step="0.01" min="0"
                 value="<?= h($annonce['prix']) ?>">
        </div>
        <div class="form-group">
          <label>État *</label>
          <select name="etat" class="form-control">
            <?php foreach (ETAT_LABELS as $k => $v): ?>
              <option value="<?= $k ?>" <?= $annonce['etat'] === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Catégorie</label>
        <select name="category_id" class="form-control">
          <option value="">— Sans catégorie —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (int)$annonce['category_id'] === $c['id'] ? 'selected' : '' ?>>
              <?= h($c['icon']) ?> <?= h($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Description *</label>
        <textarea name="description" class="form-control" rows="5"><?= h($annonce['description']) ?></textarea>
      </div>

      <div class="form-group">
        <label>Changer la photo</label>
        <?php if ($annonce['image']): ?>
          <div style="margin-bottom:.7rem">
            <img src="<?= BASE_URL ?>/uploads/<?= h($annonce['image']) ?>"
                 style="max-height:160px;border-radius:var(--radius-md);object-fit:cover">
          </div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/*">
      </div>

      <div style="display:flex;gap:1rem;margin-top:1.5rem">
        <button type="submit" class="btn btn-gold"><i class="bi bi-check-lg"></i> Enregistrer</button>
        <a href="<?= BASE_URL ?>/annonce/detail.php?id=<?= $id ?>" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
