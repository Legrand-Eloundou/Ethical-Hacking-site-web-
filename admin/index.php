<?php
$pageTitle = 'Administration';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();
$me  = currentUser();

// ── Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if ($targetId && $targetId !== $me['id']) {
        switch ($action) {
            case 'promote':
                $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$targetId]);
                flash('success', 'Utilisateur promu admin.');
                break;
            case 'demote':
                $pdo->prepare("UPDATE users SET role = 'member' WHERE id = ?")->execute([$targetId]);
                flash('success', 'Rôle retiré.');
                break;
            case 'toggle':
                $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$targetId]);
                flash('success', 'Statut modifié.');
                break;
            case 'delete':
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
                flash('success', 'Utilisateur supprimé.');
                break;
        }
    }
    redirect(BASE_URL . '/admin/index.php');
}

// ── Filtre recherche ──────────────────────────────────────────
$q     = trim($_GET['q'] ?? '');
$where = $q ? 'WHERE pseudo LIKE ? OR email LIKE ?' : '';
$params = $q ? ["%$q%", "%$q%"] : [];

$stmt = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM annonces WHERE user_id = u.id) AS nb_annonces
    FROM users u
    $where
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// ── Stats globales ────────────────────────────────────────────
$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM users)    AS total_users,
      (SELECT COUNT(*) FROM annonces) AS total_annonces,
      (SELECT COUNT(*) FROM messages) AS total_messages,
      (SELECT COUNT(*) FROM favoris)  AS total_favoris
")->fetch();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap">
  <div style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem">
    <h1 class="section-title fade-up" style="margin:0;flex:1">
      <i class="bi bi-shield-check" style="color:var(--gold-400)"></i>
      Administration
    </h1>
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline btn-sm">
      <i class="bi bi-arrow-left"></i> Retour au site
    </a>
  </div>

  <?php foreach (['success','error'] as $k): ?>
    <?php if ($m = getFlash($k)): ?>
      <div class="flash flash-<?= $k ?>"><?= h($m) ?></div>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- Stats cards -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;margin-bottom:2.5rem">
    <?php
    $cards = [
      ['👤', 'Utilisateurs', $stats['total_users']],
      ['📋', 'Annonces',     $stats['total_annonces']],
      ['💬', 'Messages',     $stats['total_messages']],
      ['❤️', 'Favoris',      $stats['total_favoris']],
    ];
    foreach ($cards as [$icon, $label, $val]):
    ?>
    <div style="background:var(--bg-800);border:1px solid var(--bg-600);border-radius:var(--radius-md);
                padding:1.4rem;text-align:center" class="fade-up">
      <div style="font-size:2rem;margin-bottom:.4rem"><?= $icon ?></div>
      <div style="font-size:1.8rem;font-weight:700;color:var(--gold-400);font-family:'Playfair Display',serif">
        <?= number_format($val) ?>
      </div>
      <div style="font-size:.8rem;color:var(--text-300)"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Recherche utilisateurs -->
  <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.2rem">
    <h2 class="section-title" style="margin:0">Gestion des utilisateurs (<?= count($users) ?>)</h2>
    <form method="GET" style="display:flex;gap:.5rem">
      <div class="nav-search-wrap" style="display:flex;max-width:280px;position:relative">
        <i class="bi bi-search search-icon" style="left:.8rem"></i>
        <input type="search" name="q" class="form-control"
               placeholder="Chercher un utilisateur…"
               value="<?= h($q) ?>"
               style="padding-left:2.5rem;border-radius:50px">
      </div>
      <button type="submit" class="btn btn-outline btn-sm">OK</button>
    </form>
  </div>

  <!-- Table utilisateurs -->
  <div style="background:var(--bg-800);border:1px solid var(--bg-600);border-radius:var(--radius-lg);overflow:hidden">
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Pseudo</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Annonces</th>
            <th>Inscrit le</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td style="color:var(--text-300)">#<?= $u['id'] ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <div class="avatar" style="width:32px;height:32px;font-size:.85rem">
                    <?php if ($u['avatar']): ?>
                      <img src="<?= BASE_URL ?>/uploads/<?= h($u['avatar']) ?>" alt="">
                    <?php else: ?>
                      <?= mb_strtoupper(mb_substr($u['pseudo'], 0, 1)) ?>
                    <?php endif; ?>
                  </div>
                  <strong><?= h($u['pseudo']) ?></strong>
                  <?php if ($u['id'] === $me['id']): ?>
                    <span style="font-size:.72rem;color:var(--gold-400)">(vous)</span>
                  <?php endif; ?>
                </div>
              </td>
              <td style="color:var(--text-200)"><?= h($u['email']) ?></td>
              <td>
                <span class="role-badge <?= $u['role'] === 'admin' ? 'role-admin' : 'role-member' ?>">
                  <?= $u['role'] === 'admin' ? '👑 Admin' : 'Membre' ?>
                </span>
              </td>
              <td>
                <span style="color:<?= $u['is_active'] ? 'var(--accent-green)' : 'var(--accent-red)' ?>;
                             font-size:.82rem;font-weight:600">
                  <?= $u['is_active'] ? '● Actif' : '● Désactivé' ?>
                </span>
              </td>
              <td style="text-align:center"><?= $u['nb_annonces'] ?></td>
              <td style="color:var(--text-300);font-size:.82rem">
                <?= (new DateTime($u['created_at']))->format('d/m/Y') ?>
              </td>
              <td>
                <?php if ($u['id'] !== $me['id']): ?>
                  <div style="display:flex;gap:.35rem;flex-wrap:wrap">
                    <!-- Promouvoir / rétrograder -->
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <input type="hidden" name="action" value="<?= $u['role'] === 'admin' ? 'demote' : 'promote' ?>">
                      <button type="submit" class="btn btn-outline btn-sm"
                              title="<?= $u['role'] === 'admin' ? 'Rétrograder' : 'Promouvoir admin' ?>">
                        <i class="bi bi-<?= $u['role'] === 'admin' ? 'arrow-down-circle' : 'shield-plus' ?>"></i>
                      </button>
                    </form>

                    <!-- Activer / désactiver -->
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <input type="hidden" name="action" value="toggle">
                      <button type="submit" class="btn btn-outline btn-sm"
                              title="<?= $u['is_active'] ? 'Désactiver' : 'Activer' ?>">
                        <i class="bi bi-<?= $u['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                      </button>
                    </form>

                    <!-- Supprimer -->
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="btn btn-danger btn-sm"
                              title="Supprimer"
                              onclick="return confirm('Supprimer définitivement <?= h($u['pseudo']) ?> et toutes ses données ?')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                <?php else: ?>
                  <span style="color:var(--text-300);font-size:.8rem">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Section annonces (bonus admin) -->
  <div style="margin-top:3rem">
    <h2 class="section-title">
      <i class="bi bi-grid" style="color:var(--gold-400)"></i> Dernières annonces
    </h2>
    <?php
    $lastAnn = $pdo->query('
        SELECT a.*, u.pseudo FROM annonces a JOIN users u ON u.id=a.user_id
        ORDER BY a.created_at DESC LIMIT 10
    ')->fetchAll();
    ?>
    <div style="background:var(--bg-800);border:1px solid var(--bg-600);border-radius:var(--radius-lg);overflow:hidden">
      <table class="admin-table">
        <thead>
          <tr><th>Titre</th><th>Vendeur</th><th>Prix</th><th>État</th><th>Vues</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach ($lastAnn as $a): ?>
            <tr>
              <td>
                <a href="<?= BASE_URL ?>/annonce/detail.php?id=<?= $a['id'] ?>"
                   style="color:var(--text-100);text-decoration:none">
                  <?= h(mb_strimwidth($a['titre'], 0, 50, '…')) ?>
                </a>
              </td>
              <td style="color:var(--text-200)"><?= h($a['pseudo']) ?></td>
              <td style="color:var(--gold-400)"><?= formatPrix((float)$a['prix']) ?></td>
              <td><span style="font-size:.8rem"><?= h(ETAT_LABELS[$a['etat']] ?? '') ?></span></td>
              <td><?= $a['views'] ?></td>
              <td style="color:var(--text-300);font-size:.8rem"><?= timeAgo($a['created_at']) ?></td>
              <td>
                <form method="POST" action="<?= BASE_URL ?>/annonce/delete.php" style="display:inline">
                  <input type="hidden" name="id" value="<?= $a['id'] ?>">
                  <button class="btn btn-danger btn-sm"
                          onclick="return confirm('Supprimer cette annonce ?')">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
