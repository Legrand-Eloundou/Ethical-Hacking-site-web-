<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = currentUser();
$pdo  = getPDO();

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM annonces WHERE id = ?');
$stmt->execute([$id]);
$a = $stmt->fetch();

if (!$a || ($a['user_id'] !== $user['id'] && !isAdmin())) {
    redirect(BASE_URL . '/index.php');
}

// Supprime l'image
if ($a['image'] && file_exists(UPLOAD_DIR . $a['image'])) {
    unlink(UPLOAD_DIR . $a['image']);
}

$pdo->prepare('DELETE FROM annonces WHERE id = ?')->execute([$id]);
flash('success', 'Annonce supprimée.');
redirect(BASE_URL . '/profil.php');
