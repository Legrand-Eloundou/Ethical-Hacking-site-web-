<?php
$pageTitle = 'Inscription';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$errors = [];
$vals   = ['pseudo' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $pwd    = $_POST['password']    ?? '';
    $pwd2   = $_POST['password2']   ?? '';

    if (strlen($pseudo) < 2) $errors[] = 'Le pseudo doit faire au moins 2 caractères.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse e-mail invalide.';
    if (strlen($pwd) < 8) $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
    if ($pwd !== $pwd2)   $errors[] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Cet e-mail est déjà utilisé.';
        } else {
            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare('INSERT INTO users (pseudo, email, password) VALUES (?, ?, ?)');
            $ins->execute([$pseudo, $email, $hash]);
            $id = (int)$pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['csrf']    = bin2hex(random_bytes(16));
            flash('success', 'Bienvenue sur The_legacy_house, ' . $pseudo . ' !');
            redirect(BASE_URL . '/index.php');
        }
    }
    $vals = compact('pseudo', 'email');
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-wrap" style="max-width:520px">
  <div class="form-card fade-up" style="margin-top:2rem">
    <h1 class="form-title">Créer un compte</h1>
    <p class="form-subtitle">Rejoignez la communauté The_legacy_house.</p>

    <?php foreach ($errors as $e): ?>
      <div class="flash flash-error"><?= h($e) ?></div>
    <?php endforeach; ?>

    <form method="POST">
      <div class="form-group">
        <label for="pseudo">Pseudo</label>
        <input type="text" id="pseudo" name="pseudo" class="form-control"
               value="<?= h($vals['pseudo']) ?>" required minlength="2" maxlength="60">
      </div>
      <div class="form-group">
        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" class="form-control"
               value="<?= h($vals['email']) ?>" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" class="form-control"
               required minlength="8" autocomplete="new-password">
        <small style="color:var(--text-300);font-size:.78rem">Minimum 8 caractères</small>
      </div>
      <div class="form-group">
        <label for="password2">Confirmer le mot de passe</label>
        <input type="password" id="password2" name="password2" class="form-control"
               required minlength="8" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-gold btn-block" style="margin-top:1rem">
        <i class="bi bi-person-plus"></i> Créer mon compte
      </button>
    </form>

    <p style="text-align:center;margin-top:1.5rem;color:var(--text-300);font-size:.88rem">
      Déjà un compte ?
      <a href="<?= BASE_URL ?>/login.php" style="color:var(--gold-400)">Se connecter</a>
    </p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
