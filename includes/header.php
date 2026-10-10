<?php
require_once __DIR__ . '/functions.php';
$user = currentUser();

// Compte messages non lus
$unread = 0;
if ($user) {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM messages m
        JOIN discussions d ON d.id = m.discussion_id
        WHERE (d.buyer_id = ? OR d.seller_id = ?)
          AND m.sender_id != ?
          AND m.lu = 0
    ');
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    $unread = (int)$stmt->fetchColumn();
}

// Catégories pour le nav (optionnel)
$cats = getPDO()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
  <meta name="description" content="<?= SITE_NAME ?> — <?= SITE_TAGLINE ?>">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Icônes -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Nos styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  <!-- BASE_URL pour JS -->
  <script>window.BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>

<nav class="the_legacy_house-nav">
  <div class="nav-inner">
  <a href="<?= BASE_URL ?>/index.php" class="nav-brand">
  <img src="<?= BASE_URL ?>/uploads/logo.png" alt="Logo" style="height:34px; width:auto; vertical-align:middle; margin-right:8px;">
  The Legacy House
</a>
    <div class="nav-search-wrap">
      <i class="bi bi-search search-icon"></i>
      <form method="GET" action="<?= BASE_URL ?>/index.php">
        <input type="search" name="q"
               placeholder="Rechercher une annonce…"
               value="<?= h($_GET['q'] ?? '') ?>">
      </form>
    </div>

    <a href="<?= BASE_URL ?>/contact.php" class="nav-contact-link">
      <i class="bi bi-envelope"></i> Contact
    </a>

    <div class="nav-links">
      <?php if ($user): ?>
        <a href="<?= BASE_URL ?>/annonce/create.php" class="btn-publish btn">
          <i class="bi bi-plus-lg"></i> Déposer
        </a>
        <a href="<?= BASE_URL ?>/favoris.php">
          <i class="bi bi-heart"></i> Favoris
        </a>
        <a href="<?= BASE_URL ?>/messagerie.php">
          <i class="bi bi-chat"></i>
          <?php if ($unread > 0): ?>
            <span class="badge-notif"><?= $unread ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/profil.php">
          <i class="bi bi-person-circle"></i>
          <?= h($user['pseudo']) ?>
        </a>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="<?= BASE_URL ?>/admin/index.php" style="color:var(--gold-400)">
            <i class="bi bi-shield-check"></i> Admin
          </a>
          <a href="<?= BASE_URL ?>/admin/message.php" style="color:var(--gold-400)">
            <i class="bi bi-envelope-exclamation"></i> Messages
          </a>
        <?php endif; ?>
        <form method="POST" action="<?= BASE_URL ?>/logout.php" style="display:inline">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf'] ?? '') ?>">
          <button type="submit"><i class="bi bi-box-arrow-right"></i> Sortir</button>
        </form>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php"><i class="bi bi-person"></i> Connexion</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn-publish btn">S'inscrire</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main>