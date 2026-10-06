<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo  = getPDO();
$user = currentUser();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('
    SELECT a.*, u.pseudo, u.id AS seller_id, u.avatar, u.created_at AS seller_since,
           c.name AS cat_name, c.icon AS cat_icon
    FROM annonces a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN categories c ON c.id = a.category_id
    WHERE a.id = ?
');
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) { header('Location: ' . BASE_URL . '/index.php'); exit; }

// Incrémente vues (pas le proprio)
if (!$user || $user['id'] !== $a['seller_id']) {
    $pdo->prepare('UPDATE annonces SET views = views + 1 WHERE id = ?')->execute([$id]);
}

// Est en favori ?
$isFav = false;
if ($user) {
    $f = $pdo->prepare('SELECT 1 FROM favoris WHERE user_id = ? AND annonce_id = ?');
    $f->execute([$user['id'], $id]);
    $isFav = (bool)$f->fetchColumn();
}

// Discussion existante
$discussion = null;
if ($user && $user['id'] !== $a['seller_id']) {
    $d = $pdo->prepare('SELECT id FROM discussions WHERE annonce_id = ? AND buyer_id = ?');
    $d->execute([$id, $user['id']]);
    $discussion = $d->fetch();
}

$pageTitle = $a['titre'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap">
  <?php foreach (['success','error'] as $k): ?>
    <?php if ($m = getFlash($k)): ?>
      <div class="flash flash-<?= $k ?>"><?= h($m) ?></div>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- Breadcrumb -->
  <nav style="margin-bottom:1.5rem;font-size:.85rem;color:var(--text-300)">
    <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-300)">Accueil</a>
    <?php if ($a['cat_name']): ?>
      <span> › </span>
      <a href="<?= BASE_URL ?>/index.php?cat=<?= $a['category_id'] ?>" style="color:var(--text-300)"><?= h($a['cat_icon']) ?> <?= h($a['cat_name']) ?></a>
    <?php endif; ?>
    <span> › </span>
    <span style="color:var(--text-100)"><?= h(mb_strimwidth($a['titre'], 0, 40, '…')) ?></span>
  </nav>

  <div class="detail-layout fade-up">
    <!-- Image + infos gauche -->
    <div>
      <div class="detail-img">
        <?php if ($a['image']): ?>
          <img src="<?= BASE_URL ?>/uploads/<?= h($a['image']) ?>" alt="<?= h($a['titre']) ?>">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:6rem;color:var(--bg-500)">
            <?= h($a['cat_icon'] ?? '📦') ?>
          </div>
        <?php endif; ?>
      </div>

      <div style="margin-top:1.5rem">
        <h1 class="detail-titre" style="margin-bottom:.8rem"><?= h($a['titre']) ?></h1>
        <p class="detail-desc"><?= nl2br(h($a['description'])) ?></p>

        <!-- Stats -->
        <div style="margin-top:1.2rem;display:flex;gap:1rem;font-size:.82rem;color:var(--text-300)">
          <span><i class="bi bi-eye"></i> <?= $a['views'] ?> vue<?= $a['views'] > 1 ? 's' : '' ?></span>
          <span><i class="bi bi-clock"></i> <?= timeAgo($a['created_at']) ?></span>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="detail-sidebar">
      <div class="detail-prix"><?= formatPrix((float)$a['prix']) ?></div>

      <div class="detail-tags">
        <span class="tag tag-gold">
          <?= h($a['cat_icon'] ?? '📦') ?> <?= h($a['cat_name'] ?? 'Autre') ?>
        </span>
        <span class="tag" style="color:<?= ETAT_COLORS[$a['etat']] ?? '#fff' ?>">
          <?= h(ETAT_LABELS[$a['etat']] ?? $a['etat']) ?>
        </span>
      </div>

      <!-- Actions -->
      <?php if ($user && $user['id'] !== $a['seller_id']): ?>
        <?php if ($discussion): ?>
          <a href="<?= BASE_URL ?>/messagerie.php?d=<?= $discussion['id'] ?>" class="btn btn-gold btn-block">
            <i class="bi bi-chat-dots"></i> Voir la discussion
          </a>
        <?php else: ?>
          <form method="POST" action="<?= BASE_URL ?>/messagerie.php">
            <input type="hidden" name="annonce_id" value="<?= $id ?>">
            <input type="hidden" name="action" value="start">
            <button type="submit" class="btn btn-gold btn-block">
              <i class="bi bi-chat-dots"></i> Contacter le vendeur
            </button>
          </form>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/favoris.php">
          <input type="hidden" name="annonce_id" value="<?= $id ?>">
          <input type="hidden" name="action" value="<?= $isFav ? 'remove' : 'add' ?>">
          <button type="submit" class="btn btn-outline btn-block">
            <i class="bi bi-heart<?= $isFav ? '-fill' : '' ?>" style="color:var(--accent-red)"></i>
            <?= $isFav ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>
          </button>
        </form>

      <?php elseif ($user && $user['id'] === $a['seller_id']): ?>
        <a href="<?= BASE_URL ?>/annonce/edit.php?id=<?= $id ?>" class="btn btn-outline btn-block">
          <i class="bi bi-pencil"></i> Modifier
        </a>
        <form method="POST" action="<?= BASE_URL ?>/annonce/delete.php">
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="btn btn-danger btn-block"
                  onclick="return confirm('Supprimer cette annonce ?')">
            <i class="bi bi-trash"></i> Supprimer
          </button>
        </form>
      <?php elseif (!$user): ?>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-gold btn-block">
          <i class="bi bi-box-arrow-in-right"></i> Se connecter pour contacter
        </a>
      <?php endif; ?>

      <!-- Vendeur -->
      <div class="seller-block">
        <div class="avatar">
          <?php if ($a['avatar']): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= h($a['avatar']) ?>" alt="avatar">
          <?php else: ?>
            <?= mb_strtoupper(mb_substr($a['pseudo'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div>
          <div class="seller-name"><?= h($a['pseudo']) ?></div>
          <div class="seller-since">Membre depuis <?= (new DateTime($a['seller_since']))->format('M Y') ?></div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
