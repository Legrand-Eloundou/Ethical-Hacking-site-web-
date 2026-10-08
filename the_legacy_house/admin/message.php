<?php
$pageTitle = 'Messages reçus';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$messages = getPDO()->query('SELECT * FROM messages_contact ORDER BY date_envoi DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-wrap">
  <h1 class="form-title">📬 Messages de contact</h1>

  <table class="table">
    <thead>
      <tr><th>Nom</th><th>Email</th><th>Sujet</th><th>Message</th><th>Date</th></tr>
    </thead>
    <tbody>
      <?php foreach ($messages as $m): ?>
        <tr>
          <td><?= h($m['nom']) ?></td>
          <td><?= h($m['email']) ?></td>
          <td><?= h($m['sujet']) ?></td>
          <!-- Volontairement non échappé : XSS stocké de démonstration -->
          <td><?= $m['message'] ?></td>
          <td><?= h($m['date_envoi']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>