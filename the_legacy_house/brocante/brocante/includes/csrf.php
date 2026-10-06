<?php
// Injecte BASE_URL dans JS + génère token CSRF si absent
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
?>
<script>window.BASE_URL = '<?= BASE_URL ?>';</script>
