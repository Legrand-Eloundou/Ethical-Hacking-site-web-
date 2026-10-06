<?php
$pageTitle = 'Mes favoris';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$user = currentUser();
$pdo  = getPDO();

// Action add/remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $annonceId = (int)($_POST['annonce_id'] ?? 0);
    $action    = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pdo->prepare('INSERT IGNORE INTO favoris (user_id, annonce_id) VALUES (?, ?)')
            ->execute([$user['id'], $annonceId]);
    } elseif ($action === 'remove') {
        $pdo->prepare('DELETE FROM favoris WHERE user_id = ? AND annonce_id = ?')
            ->execute([$user['id'], $annonceId]);
    }

    // Retour XHR
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['ok' => true]);
        exit;
    }
    redirect(BASE_URL . '/favoris.php');
}

// Liste favoris
$stmt = $pdo->prepare('
    SELECT a.*, u.pseudo, c.name AS cat_name, c.icon AS cat_icon
    FROM favoris f
    JOIN annonces a ON a.id = f.annonce_id
    JOIN users u ON u.id = a.user_id
    LEFT JOIN categories c ON c.id = a.category_id
    WHERE f.user_id = ?
    ORDER BY f.added_at DESC
');
$stmt->execute([$user['id']]);
$favoris = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-wrap">
  <h1 class="section-title fade-up">
    <i class="bi bi-heart-fill" style="color:var(--accent-red)"></i>
    Mes favoris (<?= count($favoris) ?>)
  </h1>

  <?php if (empty($favoris)): ?>
    <div style="text-align:center;padding:4rem;color:var(--text-300)">
      <div style="font-size:3rem;margin-bottom:1rem">💔</div>
      <p>Vous n'avez pas encore de favoris.</p>
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-gold" style="margin-top:1rem">
        Explorer les annonces
      </a>
    </div>
  <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($favoris as $a): ?>
        <a href="<?= BASE_URL ?>/annonce/detail.php?id=<?= $a['id'] ?>" class="annonce-card">
          <div class="card-img-wrap">
            <?php if ($a['image']): ?>
              <img src="<?= BASE_URL ?>/uploads/<?= h($a['image']) ?>" alt="<?= h($a['titre']) ?>" loading="lazy">
            <?php else: ?>
              <div class="card-no-img"><?= h($a['cat_icon'] ?? '📦') ?></div>
            <?php endif; ?>
            <span class="card-etat" style="color:<?= ETAT_COLORS[$a['etat']] ?? '#fff' ?>">
              <?= h(ETAT_LABELS[$a['etat']] ?? '') ?>
            </span>
            <button class="card-fav-btn active"
                    onclick="toggleFav(event, <?= $a['id'] ?>, this)"
                    title="Retirer des favoris">
              <i class="bi bi-heart-fill"></i>
            </button>
          </div>
          <div class="card-body">
            <div class="card-prix"><?= formatPrix((float)$a['prix']) ?></div>
            <div class="card-titre"><?= h($a['titre']) ?></div>
            <div class="card-meta">
              <?= h($a['cat_icon'] ?? '') ?> <?= h($a['cat_name'] ?? 'Autre') ?>
              · <?= timeAgo($a['created_at']) ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
