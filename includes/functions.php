<?php
require_once __DIR__ . '/db.php';

// Démarre la session PHP (gardée uniquement pour csrf/flash)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Helpers session (cookie "maison" non protégé — version vulnérable) ──

function isLoggedIn(): bool {
    return !empty($_COOKIE['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_COOKIE['user_id']]);
        $user = $stmt->fetch() ?: null;
        if (!$user) {
            setcookie('user_id', '', time() - 3600, '/');
        }
    }
    return $user;
}

function isAdmin(): bool {
    $u = currentUser();
    return $u && $u['role'] === 'admin';
}

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/' . $redirect . '?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// ── Upload image sécurisé ─────────────────────────────────────

function uploadImage(array $file, string $prefix = 'img'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_FILE_SIZE) return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) return null;

    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) return null;
    $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($imageInfo['mime'], $allowed_mimes, true)) return null;

    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;

    return $filename;
}

// ── Helpers génériques ────────────────────────────────────────

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function formatPrix(float $prix): string {
    if ($prix == 0) return 'Gratuit';
    return number_format($prix, 2, ',', ' ') . ' €';
}

function timeAgo(\DateTime|string $date): string {
    $d    = is_string($date) ? new \DateTime($date) : $date;
    $diff = (new \DateTime())->diff($d);
    if ($diff->y > 0)  return 'il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0)  return 'il y a ' . $diff->m . ' mois';
    if ($diff->d > 0)  return 'il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0)  return 'il y a ' . $diff->h . ' h';
    if ($diff->i > 0)  return 'il y a ' . $diff->i . ' min';
    return 'à l\'instant';
}

const ETAT_LABELS = [
    'neuf'        => 'Neuf',
    'bon_etat'    => 'Bon état',
    'correct'     => 'Correct',
    'pour_pieces' => 'Pour pièces',
];

const ETAT_COLORS = [
    'neuf'        => '#4ade80',
    'bon_etat'    => '#60a5fa',
    'correct'     => '#fb923c',
    'pour_pieces' => '#f87171',
];