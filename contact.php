<?php
$pageTitle = 'Contact';
require_once __DIR__ . '/includes/functions.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $sujet   = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($nom && $email && $sujet && $message) {
        $stmt = getPDO()->prepare(
            'INSERT INTO messages_contact (nom, email, sujet, message) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $email, $sujet, $message]);
        $success = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-wrap" style="max-width:520px">
  <div class="form-card fade-up" style="margin-top:2rem">
    <h1 class="form-title">Contactez-nous</h1>

    <?php if ($success): ?>
      <div class="flash flash-success">Message envoyé, merci !</div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="sujet">Sujet</label>
        <input type="text" id="sujet" name="sujet" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
      </div>
      <button type="submit" class="btn btn-gold btn-block" style="margin-top:1rem">Envoyer</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>