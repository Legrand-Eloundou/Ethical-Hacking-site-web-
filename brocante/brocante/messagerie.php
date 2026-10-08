<?php
$pageTitle = 'Messagerie';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$user = currentUser();
$pdo  = getPDO();

// ── Démarrer une discussion depuis une annonce ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start') {
    $annonceId = (int)($_POST['annonce_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM annonces WHERE id = ?');
    $stmt->execute([$annonceId]);
    $ann = $stmt->fetch();

    if ($ann && $ann['user_id'] !== $user['id']) {
        // Cherche discussion existante
        $check = $pdo->prepare('SELECT id FROM discussions WHERE annonce_id = ? AND buyer_id = ?');
        $check->execute([$annonceId, $user['id']]);
        $disc = $check->fetch();
        if (!$disc) {
            $ins = $pdo->prepare('INSERT INTO discussions (annonce_id, buyer_id, seller_id) VALUES (?, ?, ?)');
            $ins->execute([$annonceId, $user['id'], $ann['user_id']]);
            $discId = (int)$pdo->lastInsertId();
        } else {
            $discId = $disc['id'];
        }
        redirect(BASE_URL . '/messagerie.php?d=' . $discId);
    }
    redirect(BASE_URL . '/messagerie.php');
}

// ── Envoyer un message ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $discId  = (int)($_POST['discussion_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    // Vérifie appartenance
    $d = $pdo->prepare('SELECT * FROM discussions WHERE id = ? AND (buyer_id = ? OR seller_id = ?)');
    $d->execute([$discId, $user['id'], $user['id']]);
    $disc = $d->fetch();

    if ($disc && $contenu !== '') {
        $ins = $pdo->prepare('INSERT INTO messages (discussion_id, sender_id, contenu) VALUES (?, ?, ?)');
        $ins->execute([$discId, $user['id'], $contenu]);
    }
    redirect(BASE_URL . '/messagerie.php?d=' . $discId);
}

// ── Charge discussions de l'utilisateur ────────────────────────
$discs = $pdo->prepare('
    SELECT d.*,
           a.titre AS ann_titre, a.image AS ann_image,
           buyer.pseudo  AS buyer_pseudo,
           seller.pseudo AS seller_pseudo,
           (SELECT contenu FROM messages WHERE discussion_id = d.id ORDER BY sent_at DESC LIMIT 1) AS last_msg,
           (SELECT sent_at FROM messages WHERE discussion_id = d.id ORDER BY sent_at DESC LIMIT 1) AS last_at,
           (SELECT COUNT(*) FROM messages WHERE discussion_id = d.id AND sender_id != ? AND lu = 0) AS unread_count
    FROM discussions d
    JOIN annonces a  ON a.id  = d.annonce_id
    JOIN users buyer  ON buyer.id  = d.buyer_id
    JOIN users seller ON seller.id = d.seller_id
    WHERE d.buyer_id = ? OR d.seller_id = ?
    ORDER BY last_at DESC
');
$discs->execute([$user['id'], $user['id'], $user['id']]);
$discussions = $discs->fetchAll();

// ── Discussion active ───────────────────────────────────────────
$activeDiscId = (int)($_GET['d'] ?? ($discussions[0]['id'] ?? 0));
$activeDisc   = null;
$messages     = [];

if ($activeDiscId) {
    $d = $pdo->prepare('SELECT * FROM discussions WHERE id = ? AND (buyer_id = ? OR seller_id = ?)');
    $d->execute([$activeDiscId, $user['id'], $user['id']]);
    $activeDisc = $d->fetch();

    if ($activeDisc) {
        // Marque lus
        $pdo->prepare('UPDATE messages SET lu = 1 WHERE discussion_id = ? AND sender_id != ?')
            ->execute([$activeDiscId, $user['id']]);

        $msgs = $pdo->prepare('
            SELECT m.*, u.pseudo FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.discussion_id = ?
            ORDER BY m.sent_at ASC
        ');
        $msgs->execute([$activeDiscId]);
        $messages = $msgs->fetchAll();

        // Infos annonce de la discussion
        $annStmt = $pdo->prepare('SELECT a.*, u.pseudo AS seller_pseudo FROM annonces a JOIN users u ON u.id=a.user_id WHERE a.id=?');
        $annStmt->execute([$activeDisc['annonce_id']]);
        $activeAnn = $annStmt->fetch();
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-wrap">
  <h1 class="section-title fade-up">
    <i class="bi bi-chat-dots" style="color:var(--gold-400)"></i> Messagerie
  </h1>

  <?php if (empty($discussions)): ?>
    <div style="text-align:center;padding:4rem;color:var(--text-300)">
      <div style="font-size:3rem;margin-bottom:1rem">💬</div>
      <p>Aucune discussion pour l'instant.<br>Contactez un vendeur depuis une annonce.</p>
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-gold" style="margin-top:1rem">
        Parcourir les annonces
      </a>
    </div>
  <?php else: ?>
    <div class="chat-layout">
      <!-- Sidebar discussions -->
      <div class="chat-sidebar">
        <?php foreach ($discussions as $d): ?>
          <?php
            $interlocuteur = $d['buyer_id'] == $user['id'] ? $d['seller_pseudo'] : $d['buyer_pseudo'];
            $isActive = $d['id'] == $activeDiscId;
          ?>
          <a href="messagerie.php?d=<?= $d['id'] ?>"
             class="discussion-item <?= $isActive ? 'active' : '' ?>">
            <div class="avatar" style="width:38px;height:38px;font-size:1rem;flex-shrink:0">
              <?= mb_strtoupper(mb_substr($interlocuteur, 0, 1)) ?>
            </div>
            <div style="min-width:0;flex:1">
              <div style="display:flex;justify-content:space-between;align-items:center">
                <span class="d-title"><?= h($interlocuteur) ?></span>
                <?php if ($d['unread_count'] > 0): ?>
                  <span class="badge-notif"><?= $d['unread_count'] ?></span>
                <?php endif; ?>
              </div>
              <div class="d-preview" style="color:var(--text-300);font-size:.78rem">
                📎 <?= h(mb_strimwidth($d['ann_titre'], 0, 30, '…')) ?>
              </div>
              <?php if ($d['last_msg']): ?>
                <div class="d-preview"><?= h(mb_strimwidth($d['last_msg'], 0, 35, '…')) ?></div>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Zone chat -->
      <?php if ($activeDisc && isset($activeAnn)): ?>
        <div class="chat-main">
          <!-- Header -->
          <div class="chat-header">
            <div class="avatar" style="width:36px;height:36px;font-size:.9rem">
              <?php
                $interlocuteur = $activeDisc['buyer_id'] == $user['id']
                    ? $activeAnn['seller_pseudo']
                    : ($discussions[array_search($activeDiscId, array_column($discussions, 'id'))]['buyer_pseudo'] ?? '?');
                echo mb_strtoupper(mb_substr($interlocuteur, 0, 1));
              ?>
            </div>
            <div>
              <div><?= h($interlocuteur) ?></div>
              <div style="font-size:.78rem;color:var(--text-300)">
                À propos de :
                <a href="<?= BASE_URL ?>/annonce/detail.php?id=<?= $activeAnn['id'] ?>"
                   style="color:var(--gold-400)">
                  <?= h(mb_strimwidth($activeAnn['titre'], 0, 40, '…')) ?>
                </a>
              </div>
            </div>
            <div style="margin-left:auto;font-size:.85rem;font-weight:700;color:var(--gold-400)">
              <?= formatPrix((float)$activeAnn['prix']) ?>
            </div>
          </div>

          <!-- Messages -->
          <div class="chat-messages" id="chatMessages">
            <?php if (empty($messages)): ?>
              <div style="text-align:center;color:var(--text-300);padding:2rem;font-size:.88rem">
                Démarrez la conversation !
              </div>
            <?php else: ?>
              <?php foreach ($messages as $m): ?>
                <?php $mine = $m['sender_id'] == $user['id']; ?>
                <div class="msg-row <?= $mine ? 'mine' : '' ?>">
                  <?php if (!$mine): ?>
                    <div class="avatar" style="width:30px;height:30px;font-size:.75rem;flex-shrink:0">
                      <?= mb_strtoupper(mb_substr($m['pseudo'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                  <div class="msg-bubble"><?= nl2br(h($m['contenu'])) ?></div>
                  <div class="msg-time"><?= (new DateTime($m['sent_at']))->format('H:i') ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Formulaire envoi -->
          <form method="POST" class="chat-form">
            <input type="hidden" name="action" value="send">
            <input type="hidden" name="discussion_id" value="<?= $activeDiscId ?>">
            <input type="text" name="contenu" placeholder="Votre message…" required autocomplete="off" id="msgInput">
            <button type="submit" class="btn btn-gold btn-sm" style="border-radius:50px">
              <i class="bi bi-send"></i>
            </button>
          </form>
        </div>
      <?php else: ?>
        <div style="display:flex;align-items:center;justify-content:center;
                    background:var(--bg-800);border:1px solid var(--bg-600);
                    border-radius:var(--radius-lg);color:var(--text-300)">
          Sélectionnez une discussion
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<script>
// Scroll bas automatique
const chatMsgs = document.getElementById('chatMessages');
if (chatMsgs) chatMsgs.scrollTop = chatMsgs.scrollHeight;
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
