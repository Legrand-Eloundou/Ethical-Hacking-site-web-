<?php
$pageTitle = 'Mon profil';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$user = currentUser();
$pdo  = getPDO();

$errors = [];
$success = '';

// ── Mise à jour profil ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $pseudo  = trim($_POST['pseudo']  ?? '');
    $email   = trim($_POST['email']   ?? '');
    $pwd     = $_POST['new_password'] ?? '';
    $pwdConf = $_POST['confirm_password'] ?? '';

    if (strlen($pseudo) < 2) $errors[] = 'Pseudo trop court.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';

    // Unicité email
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $check->execute([$email, $user['id']]);
    if ($check->fetch()) $errors[] = 'Cet email est déjà utilisé.';

    $avatarFile = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $up = uploadImage($_FILES['avatar'], 'avatar');
        if (!$up) $errors[] = 'Avatar invalide.';
        else {
            if ($avatarFile && file_exists(UPLOAD_DIR . $avatarFile)) unlink(UPLOAD_DIR . $avatarFile);
            $avatarFile = $up;
        }
    }

    if (empty($errors)) {
        if ($pwd) {
            if (strlen($pwd) < 8) { $errors[] = 'Mot de passe trop court.'; }
            elseif ($pwd !== $pwdConf) { $errors[] = 'Mots de passe différents.'; }
            else {
                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                $pdo->prepare('UPDATE users SET pseudo=?, email=?, password=?, avatar=? WHERE id=?')
                    ->execute([$pseudo, $email, $hash, $avatarFile, $user['id']]);
            }
        }
        if (empty($errors)) {
            $pdo->prepare('UPDATE users SET pseudo=?, email=?, avatar=? WHERE id=?')
                ->execute([$pseudo, $email, $avatarFile, $user['id']]);
            flash('success', 'Profil mis à jour.');
            redirect(BASE_URL . '/profil.php');
        }
    }
}

// Recharge user
$user = currentUser();

// Stats
$nbAnnonces = (int)$pdo->prepare('SELECT COUNT(*) FROM annonces WHERE user_id = ?')
                        ->execute([$user['id']]) ? 0 : 0;
$s = $pdo->prepare('SELECT COUNT(*) FROM annonces WHERE user_id = ?');
$s->execute([$user['id']]);
$nbAnnonces = (int)$s->fetchColumn();

$s2 = $pdo->prepare('SELECT COUNT(*) FROM favoris WHERE user_id = ?');
$s2->execute([$user['id']]);
$nbFavoris = (int)$s2->fetchColumn();

// Mes annonces
$myAnn = $pdo->prepare('SELECT a.*, c.icon AS cat_icon FROM annonces a LEFT JOIN categories c ON c.id=a.category_id WHERE a.user_id=? ORDER BY a.created_at DESC');
$myAnn->execute([$user['id']]);
$annonces = $myAnn->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-wrap">
  <?php foreach (['success','error'] as $k): ?>
    <?php if ($m = getFlash($k)): ?>
      <div class="flash flash-<?= $k ?>"><?= h($m) ?></div>
    <?php endif; ?>
  <?php endforeach; ?>
  <?php foreach ($errors as $e): ?>
    <div class="flash flash-error"><?= h($e) ?></div>
  <?php endforeach; ?>

  <!-- En-tête profil -->
  <div class="profile-header fade-up">
    <div class="profile-avatar-wrap">
      <?php if ($user['avatar']): ?>
        <img src="<?= BASE_URL ?>/uploads/<?= h($user['avatar']) ?>" alt="avatar">
      <?php else: ?>
        <?= mb_strtoupper(mb_substr($user['pseudo'], 0, 1)) ?>
      <?php endif; ?>
    </div>
    <div style="flex:1">
      <div class="profile-name"><?= h($user['pseudo']) ?></div>
      <div style="color:var(--text-300);font-size:.88rem"><?= h($user['email']) ?></div>
      <div class="profile-stats">
        <div class="stat-item"><div class="stat-val"><?= $nbAnnonces ?></div><div class="stat-lbl">Annonces</div></div>
        <div class="stat-item"><div class="stat-val"><?= $nbFavoris ?></div><div class="stat-lbl">Favoris</div></div>
        <div class="stat-item">
          <div class="stat-val" style="color:<?= $user['role']==='admin' ? 'var(--gold-400)' : 'var(--text-100)' ?>">
            <?= $user['role'] === 'admin' ? '👑 Admin' : 'Membre' ?>
          </div>
          <div class="stat-lbl">Rôle</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Onglets -->
  <ul class="nav nav-tabs" style="border-color:var(--bg-600);margin-bottom:2rem" id="profileTabs">
    <li class="nav-item">
      <button class="nav-link active" style="color:var(--text-200);background:none;border-color:transparent"
              data-bs-toggle="tab" data-bs-target="#tab-annonces">
        <i class="bi bi-grid"></i> Mes annonces
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" style="color:var(--text-200);background:none;border-color:transparent"
              data-bs-toggle="tab" data-bs-target="#tab-edit">
        <i class="bi bi-pencil"></i> Modifier le profil
      </button>
    </li>
  </ul>

  <div class="tab-content">
    <!-- MES ANNONCES -->
    <div class="tab-pane fade show active" id="tab-annonces">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem">
        <h2 class="section-title" style="margin:0">Mes annonces (<?= $nbAnnonces ?>)</h2>
        <a href="<?= BASE_URL ?>/annonce/create.php" class="btn btn-gold btn-sm">
          <i class="bi bi-plus"></i> Déposer
        </a>
      </div>

      <?php if (empty($annonces)): ?>
        <div style="text-align:center;padding:3rem;color:var(--text-300)">
          <div style="font-size:2.5rem;margin-bottom:.8rem">📭</div>
          <p>Vous n'avez pas encore d'annonces.</p>
          <a href="<?= BASE_URL ?>/annonce/create.php" class="btn btn-gold" style="margin-top:1rem">
            Déposer ma première annonce
          </a>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.6rem">
          <?php foreach ($annonces as $a): ?>
            <div style="background:var(--bg-800);border:1px solid var(--bg-600);border-radius:var(--radius-md);
                        padding:1rem 1.2rem;display:flex;align-items:center;gap:1.2rem">
              <div style="width:60px;height:60px;border-radius:var(--radius-sm);overflow:hidden;background:var(--bg-700);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                <?php if ($a['image']): ?>
                  <img src="<?= BASE_URL ?>/uploads/<?= h($a['image']) ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?= h($a['cat_icon'] ?? '📦') ?>
                <?php endif; ?>
              </div>
              <div style="flex:1;min-width:0">
                <a href="<?= BASE_URL ?>/annonce/detail.php?id=<?= $a['id'] ?>"
                   style="font-weight:600;color:var(--text-100);text-decoration:none">
                  <?= h($a['titre']) ?>
                </a>
                <div style="font-size:.8rem;color:var(--text-300);margin-top:.15rem">
                  <?= formatPrix((float)$a['prix']) ?>
                  · <?= h(ETAT_LABELS[$a['etat']] ?? $a['etat']) ?>
                  · <?= timeAgo($a['created_at']) ?>
                  · <i class="bi bi-eye"></i> <?= $a['views'] ?>
                </div>
              </div>
              <div style="display:flex;gap:.5rem;flex-shrink:0">
                <a href="<?= BASE_URL ?>/annonce/edit.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" action="<?= BASE_URL ?>/annonce/delete.php" style="display:inline">
                  <input type="hidden" name="id" value="<?= $a['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm"
                          onclick="return confirm('Supprimer ?')">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- MODIFIER PROFIL -->
    <div class="tab-pane fade" id="tab-edit">
      <div class="form-card" style="max-width:520px;margin:0">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update">
          <div class="form-group">
            <label>Pseudo</label>
            <input type="text" name="pseudo" class="form-control"
                   value="<?= h($user['pseudo']) ?>" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= h($user['email']) ?>" required>
          </div>
          <div class="form-group">
            <label>Photo de profil</label>
            <input type="file" name="avatar" class="form-control" accept="image/*">
          </div>
          <hr style="border-color:var(--bg-600);margin:1.2rem 0">
          <p style="color:var(--text-300);font-size:.85rem;margin-bottom:1rem">
            Laissez vide pour ne pas changer le mot de passe.
          </p>
          <div class="form-group">
            <label>Nouveau mot de passe</label>
            <input type="password" name="new_password" class="form-control" minlength="8">
          </div>
          <div class="form-group">
            <label>Confirmer</label>
            <input type="password" name="confirm_password" class="form-control" minlength="8">
          </div>
          <button type="submit" class="btn btn-gold" style="margin-top:1rem">
            <i class="bi bi-check-lg"></i> Enregistrer les modifications
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
.nav-tabs .nav-link.active {
  color: var(--gold-400) !important;
  border-color: var(--bg-600) var(--bg-600) var(--bg-900) !important;
  background: var(--bg-800) !important;
}
.nav-tabs .nav-link:hover { color: var(--text-100) !important; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
