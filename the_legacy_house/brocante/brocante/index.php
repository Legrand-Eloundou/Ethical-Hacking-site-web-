<?php
$pageTitle = 'Accueil';
require_once __DIR__ . '/includes/functions.php';
$pdo  = getPDO();
$user = currentUser();

// Paramètres
$q      = trim($_GET['q']    ?? '');
$catId  = (int)($_GET['cat'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Construction requête
$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = '(a.titre LIKE ? OR a.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($catId > 0) {
    $where[]  = 'a.category_id = ?';
    $params[] = $catId;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM annonces a $whereSQL")->execute($params) ? 0 : 0;
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM annonces a $whereSQL");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT a.*, u.pseudo, c.name AS cat_name, c.icon AS cat_icon
        FROM annonces a
        JOIN users u ON u.id = a.user_id
        LEFT JOIN categories c ON c.id = a.category_id
        $whereSQL
        ORDER BY a.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$annonces = $stmt->fetchAll();

// Catégories
$cats = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// Favoris de l'utilisateur
$favIds = [];
if ($user) {
    $fav = $pdo->prepare('SELECT annonce_id FROM favoris WHERE user_id = ?');
    $fav->execute([$user['id']]);
    $favIds = array_column($fav->fetchAll(), 'annonce_id');
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<?php if (!$q && !$catId): ?>
<section class="hero fade-up">
  <h1>Le marché qui<br><em>a du goût.</em></h1>
  <p>Des milliers d'objets qui attendent un second souffle près de chez vous.</p>
  <form class="hero-search" method="GET" action="index.php">
    <input type="search" name="q" placeholder="Que cherchez-vous ? (ex : vélo, canapé, iPhone…)">
    <button type="submit">Chercher</button>
  </form>
</section>
<?php endif; ?>

<div class="page-wrap">

  <!-- Flash -->
  <?php foreach (['success','error','info'] as $k): ?>
    <?php if ($m = getFlash($k)): ?>
      <div class="flash flash-<?= $k ?>"><?= h($m) ?></div>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- Catégories chips -->
  <div class="cats-scroll fade-up-2">
    <a href="index.php<?= $q ? '?q='.urlencode($q) : '' ?>"
       class="cat-chip <?= !$catId ? 'active' : '' ?>">
      🏷️ Tout
    </a>
    <?php foreach ($cats as $cat): ?>
      <a href="index.php?<?= $q ? 'q='.urlencode($q).'&' : '' ?>cat=<?= $cat['id'] ?>"
         class="cat-chip <?= $catId == $cat['id'] ? 'active' : '' ?>">
        <?= h($cat['icon']) ?> <?= h($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Titre section -->
  <h2 class="section-title fade-up-3">
    <?php if ($q): ?>
      <i class="bi bi-search" style="color:var(--gold-400)"></i>
      Résultats pour « <?= h($q) ?> »
      <small style="font-size:.9rem;color:var(--text-300)">(<?= $total ?> annonce<?= $total > 1 ? 's' : '' ?>)</small>
    <?php else: ?>
      <i class="bi bi-clock-history" style="color:var(--gold-400)"></i>
      Dernières annonces
    <?php endif; ?>
  </h2>

  <!-- Grille annonces -->
  <?php if (empty($annonces)): ?>
    <div style="text-align:center;padding:4rem;color:var(--text-300)">
      <div style="font-size:3rem;margin-bottom:1rem">🔍</div>
      <p>Aucune annonce trouvée.</p>
      <?php if ($q): ?>
        <a href="index.php" class="btn btn-outline" style="margin-top:1rem">Voir toutes les annonces</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($annonces as $a): ?>
        <a href="annonce/detail.php?id=<?= $a['id'] ?>" class="annonce-card">
          <div class="card-img-wrap">
            <?php if ($a['image']): ?>
              <img src="<?= BASE_URL ?>/uploads/<?= h($a['image']) ?>"
                   alt="<?= h($a['titre']) ?>"
                   loading="lazy">
            <?php else: ?>
              <div class="card-no-img"><?= h($a['cat_icon'] ?? '📦') ?></div>
            <?php endif; ?>

            <!-- Badge état -->
            <span class="card-etat" style="color:<?= ETAT_COLORS[$a['etat']] ?? '#fff' ?>">
              <?= h(ETAT_LABELS[$a['etat']] ?? $a['etat']) ?>
            </span>

            <!-- Bouton favori -->
            <?php if ($user && $user['id'] !== $a['user_id']): ?>
              <button class="card-fav-btn <?= in_array($a['id'], $favIds) ? 'active' : '' ?>"
                      onclick="toggleFav(event, <?= $a['id'] ?>, this)"
                      title="Ajouter aux favoris">
                <i class="bi bi-heart<?= in_array($a['id'], $favIds) ? '-fill' : '' ?>"></i>
              </button>
            <?php endif; ?>
          </div>

          <div class="card-body">
            <div class="card-prix"><?= formatPrix((float)$a['prix']) ?></div>
            <div class="card-titre"><?= h($a['titre']) ?></div>
            <div class="card-meta">
              <?php if ($a['cat_name']): ?>
                <span><?= h($a['cat_icon']) ?> <?= h($a['cat_name']) ?></span>
                <span>·</span>
              <?php endif; ?>
              <span><?= timeAgo($a['created_at']) ?></span>
              <span>·</span>
              <span><i class="bi bi-eye"></i> <?= $a['views'] ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav style="margin-top:2.5rem;display:flex;justify-content:center;gap:.4rem">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <?php
            $params_pag = [];
            if ($q)     $params_pag['q']    = $q;
            if ($catId) $params_pag['cat']  = $catId;
            $params_pag['page'] = $i;
          ?>
          <a href="index.php?<?= http_build_query($params_pag) ?>"
             class="btn btn-sm <?= $i === $page ? 'btn-gold' : 'btn-outline' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
