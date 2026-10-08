<?php
$pageTitle = 'Connexion';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password'] ?? '';

    $stmt = getPDO()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($pwd, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['csrf']    = bin2hex(random_bytes(16));
        $next = $_GET['next'] ?? BASE_URL . '/index.php';
        redirect($next);
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-wrap" style="max-width:520px">
  <div class="form-card fade-up" style="margin-top:2rem">
    <h1 class="form-title">Bon retour 👋</h1>
    <p class="form-subtitle">Connectez-vous pour accéder à votre compte.</p>

    <?php if ($error): ?>
      <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" class="form-control"
               value="<?= h($_POST['email'] ?? '') ?>" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" class="form-control"
               required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-gold btn-block" style="margin-top:1rem">
        <i class="bi bi-box-arrow-in-right"></i> Se connecter
      </button>
    </form>

    <p style="text-align:center;margin-top:1.5rem;color:var(--text-300);font-size:.88rem">
      Pas encore de compte ?
      <a href="<?= BASE_URL ?>/register.php" style="color:var(--gold-400)">Créer un compte</a>
    </p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
