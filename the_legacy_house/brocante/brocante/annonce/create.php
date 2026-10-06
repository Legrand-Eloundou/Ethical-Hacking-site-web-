<?php
$pageTitle = 'Déposer une annonce';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = currentUser();
$pdo  = getPDO();

$cats   = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];
$vals   = ['titre' => '', 'prix' => '', 'etat' => 'bon_etat', 'description' => '', 'category_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre      = trim($_POST['titre']       ?? '');
    $prix       = (float)str_replace(',', '.', $_POST['prix'] ?? '0');
    $etat       = $_POST['etat']             ?? '';
    $desc       = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;

    if (strlen($titre) < 3)  $errors[] = 'Le titre doit faire au moins 3 caractères.';
    if ($prix < 0)            $errors[] = 'Le prix ne peut pas être négatif.';
    if (!array_key_exists($etat, ETAT_LABELS)) $errors[] = 'État invalide.';
    if (strlen($desc) < 10)  $errors[] = 'La description doit faire au moins 10 caractères.';

    $imageFile = null;
    if (!empty($_FILES['image']['name'])) {
        $imageFile = uploadImage($_FILES['image'], 'ann');
        if (!$imageFile) $errors[] = 'Image invalide (jpg, png, webp, gif — max 5 Mo).';
    }

    if (empty($errors)) {
        $ins = $pdo->prepare('INSERT INTO annonces (user_id, category_id, titre, description, prix, etat, image)
                              VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->execute([$user['id'], $categoryId, $titre, $desc, $prix, $etat, $imageFile]);
        $newId = (int)$pdo->lastInsertId();
        flash('success', 'Annonce publiée avec succès !');
        redirect(BASE_URL . '/annonce/detail.php?id=' . $newId);
    }
    $vals = compact('titre', 'prix', 'etat', 'description', 'category_id');
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap" style="max-width:700px">
  <div class="form-card fade-up" style="max-width:100%;margin-top:1rem">
    <h1 class="form-title">Déposer une annonce</h1>
    <p class="form-subtitle">Décrivez votre objet avec soin pour attirer les acheteurs.</p>

    <?php foreach ($errors as $e): ?>
      <div class="flash flash-error"><?= h($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Titre de l'annonce *</label>
        <input type="text" name="titre" class="form-control"
               value="<?= h($vals['titre']) ?>" required maxlength="200"
               placeholder="Ex : Vélo de ville Decathlon, quasi neuf">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label>Prix (€) *</label>
          <input type="number" name="prix" class="form-control" step="0.01" min="0"
                 value="<?= h($vals['prix']) ?>" placeholder="0 pour gratuit">
        </div>
        <div class="form-group">
          <label>État *</label>
          <select name="etat" class="form-control">
            <?php foreach (ETAT_LABELS as $k => $v): ?>
              <option value="<?= $k ?>" <?= $vals['etat'] === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Catégorie</label>
        <select name="category_id" class="form-control">
          <option value="">— Choisir une catégorie —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (int)$vals['category_id'] === $c['id'] ? 'selected' : '' ?>>
              <?= h($c['icon']) ?> <?= h($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Description *</label>
        <textarea name="description" class="form-control" rows="5"
                  placeholder="Décrivez l'état, les dimensions, les défauts éventuels…"><?= h($vals['description']) ?></textarea>
      </div>

      <div class="form-group">
        <label>Photo</label>
        <input type="file" name="image" class="form-control" accept="image/*"
               id="imageInput" onchange="previewImg(this)">
        <div id="imgPreview" style="margin-top:.7rem;display:none">
          <img id="imgPreviewEl" style="max-height:200px;border-radius:var(--radius-md);object-fit:cover">
        </div>
        <small style="color:var(--text-300);font-size:.78rem">JPG, PNG, WEBP, GIF — max 5 Mo</small>
      </div>

      <div style="display:flex;gap:1rem;margin-top:1.5rem">
        <button type="submit" class="btn btn-gold">
          <i class="bi bi-send"></i> Publier l'annonce
        </button>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>

<script>
function previewImg(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('imgPreviewEl').src = e.target.result;
    document.getElementById('imgPreview').style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
