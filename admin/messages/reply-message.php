<?php
// ============================================================
// admin/messages/reply-message.php — FIXED
// ============================================================
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php"); exit;
}

$sender_id = (int)($_GET['sender_id'] ?? 0);
$single_id = (int)($_GET['id']        ?? 0);
$adminId   = (int)$_SESSION['user_id'];

// ── FIX 1: username ───────────────────────────────────────
$adminName = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';

if (!$sender_id && !$single_id) {
    header("Location: messages.php"); exit;
}

if (!$sender_id && $single_id) {
    $r = $pdo->prepare("SELECT sender_id FROM messages WHERE id = ? AND receiver_id = ?");
    $r->execute([$single_id, $adminId]);
    $row = $r->fetch();
    if (!$row) { header("Location: messages.php"); exit; }
    $sender_id = (int)$row['sender_id'];
}

$senderStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
$senderStmt->execute([$sender_id]);
$sender = $senderStmt->fetch();
if (!$sender) { header("Location: messages.php"); exit; }

$pdo->prepare("
    UPDATE messages SET is_read = 1
    WHERE sender_id = ? AND receiver_id = ?
")->execute([$sender_id, $adminId]);

$convStmt = $pdo->prepare("
    SELECT id, sender_id, receiver_id, message, subject, is_read, created_at
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$convStmt->execute([$sender_id, $adminId, $adminId, $sender_id]);
$conversation = $convStmt->fetchAll();

$counts = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN is_read = 0 AND receiver_id = ? THEN 1 ELSE 0 END) AS unread
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
");
$counts->execute([$adminId, $sender_id, $adminId, $adminId, $sender_id]);
$counts = $counts->fetch();

$sent = isset($_GET['sent']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversation — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        :root {
            --wa-green: #25d366;
            --wa-green-dark: #128c7e;
            --wa-bg-chat: #efeae2;
            --wa-bubble-in: #ffffff;
            --wa-bubble-out: #d9fdd3;
            --wa-text-primary: #111b21;
            --wa-text-secondary: #667781;
            --wa-tick: #53bdeb;
        }
        .chat-shell { display:flex; gap:1.4rem; align-items:flex-start; }
        .chat-window {
            flex:1; border-radius:16px; overflow:hidden;
            box-shadow:0 4px 32px rgba(0,0,0,.1);
            display:flex; flex-direction:column;
            min-height:600px; background:var(--wa-bg-chat);
        }
        .chat-header {
            background:var(--wa-green-dark); padding:12px 16px;
            display:flex; align-items:center; gap:12px;
        }
        .chat-back { color:rgba(255,255,255,.8); text-decoration:none; font-size:1.1rem; }
        .chat-back:hover { color:#fff; }
        .chat-header-avatar {
            width:40px; height:40px; border-radius:50%;
            background:rgba(255,255,255,.2);
            display:flex; align-items:center; justify-content:center;
            font-weight:700; color:#fff; font-size:1rem; flex-shrink:0;
        }
        .chat-header-info { flex:1; }
        .chat-header-name { color:#fff; font-weight:700; font-size:.95rem; }
        .chat-header-sub  { color:rgba(255,255,255,.65); font-size:.75rem; }
        .chat-header-actions { display:flex; gap:6px; }
        .chat-action-btn {
            background:rgba(255,255,255,.15); border:none; border-radius:50px;
            color:#fff; font-size:.75rem; font-weight:600;
            padding:5px 12px; cursor:pointer; text-decoration:none;
            transition:background .15s; font-family:inherit;
        }
        .chat-action-btn:hover { background:rgba(255,255,255,.25); }
        .chat-action-btn.danger { background:rgba(239,68,68,.3); }
        .chat-action-btn.danger:hover { background:rgba(239,68,68,.5); }
        .chat-messages {
            flex:1; padding:16px; overflow-y:auto;
            display:flex; flex-direction:column; gap:10px;
            background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .chat-date-divider { text-align:center; margin:6px 0; }
        .chat-date-divider span {
            background:rgba(255,255,255,.85); border-radius:50px;
            padding:3px 12px; font-size:.72rem; color:var(--wa-text-secondary);
        }
        .bubble-wrap { display:flex; gap:8px; max-width:72%; }
        .bubble-wrap.incoming { align-self:flex-start; }
        .bubble-wrap.outgoing { align-self:flex-end; flex-direction:row-reverse; }
        .bubble {
            border-radius:12px; padding:8px 12px 22px;
            position:relative; line-height:1.55; font-size:.88rem;
            min-width:80px; word-break:break-word;
            box-shadow:0 1px 2px rgba(0,0,0,.08);
        }
        .bubble.incoming { background:var(--wa-bubble-in); border-top-left-radius:2px; }
        .bubble.outgoing { background:var(--wa-bubble-out); border-top-right-radius:2px; }
        .bubble.incoming::before {
            content:''; position:absolute; top:0; left:-8px;
            border:8px solid transparent;
            border-top-color:var(--wa-bubble-in); border-right-color:var(--wa-bubble-in);
        }
        .bubble.outgoing::before {
            content:''; position:absolute; top:0; right:-8px;
            border:8px solid transparent;
            border-top-color:var(--wa-bubble-out); border-left-color:var(--wa-bubble-out);
        }
        .bubble-meta {
            position:absolute; bottom:4px; right:10px;
            font-size:.65rem; color:var(--wa-text-secondary);
            display:flex; align-items:center; gap:3px;
        }
        .bubble-meta .ticks { color:var(--wa-tick); }
        .bubble-subject {
            font-size:.75rem; font-weight:700; color:var(--wa-green-dark);
            background:rgba(18,140,126,.08); border-left:3px solid var(--wa-green-dark);
            padding:3px 7px; border-radius:4px; margin-bottom:6px;
        }
        .bubble-sender-label { font-size:.72rem; font-weight:700; color:var(--wa-green-dark); margin-bottom:3px; }
        .bubble-avatar-sm {
            width:30px; height:30px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:.75rem; flex-shrink:0; align-self:flex-end;
        }
        .bubble-avatar-sm.user  { background:linear-gradient(135deg,var(--wa-green-dark),var(--wa-green)); color:#fff; }
        .bubble-avatar-sm.admin { background:#f0f2f5; color:var(--wa-green-dark); }
        .chat-input-bar {
            background:#f0f2f5; padding:10px 12px;
            display:flex; align-items:flex-end; gap:8px;
            border-top:1px solid rgba(0,0,0,.06);
        }
        .chat-input-wrap {
            flex:1; background:#fff; border-radius:24px;
            padding:10px 16px; display:flex; align-items:flex-end;
        }
        .chat-input-wrap textarea {
            width:100%; border:none; background:transparent; outline:none;
            resize:none; font-family:inherit; font-size:.9rem;
            color:var(--wa-text-primary); line-height:1.5;
            max-height:120px; min-height:22px;
        }
        .chat-input-wrap textarea::placeholder { color:#aaa; }
        .chat-send-btn {
            width:46px; height:46px; border-radius:50%;
            background:var(--wa-green); border:none; color:#fff;
            font-size:1.1rem; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; transition:background .15s;
            box-shadow:0 2px 8px rgba(37,211,102,.4);
        }
        .chat-send-btn:hover { background:#1ebe5d; }
        .wa-alert-success {
            background:rgba(37,211,102,.1); border:1px solid rgba(37,211,102,.25);
            color:var(--wa-green-dark); padding:8px 14px; border-radius:8px;
            font-size:.82rem; font-weight:600; margin:8px 12px 0;
        }
        .chat-sidebar { width:260px; flex-shrink:0; display:flex; flex-direction:column; gap:12px; }
        .cs-card { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); }
        .cs-card-header { background:var(--wa-green-dark); padding:10px 14px; color:#fff; font-size:.82rem; font-weight:700; }
        .cs-card-body { padding:12px 14px; }
        .cs-row {
            display:flex; justify-content:space-between; align-items:center;
            padding:6px 0; border-bottom:1px solid #f0f0f0; font-size:.82rem;
        }
        .cs-row:last-child { border-bottom:none; }
        .cs-row span:first-child { color:var(--wa-text-secondary); }
        .cs-row span:last-child { font-weight:600; color:var(--wa-text-primary); }
        .cs-danger-btn {
            display:block; width:100%; background:rgba(239,68,68,.08);
            color:#ef4444; font-weight:700; font-size:.85rem;
            border:1.5px solid rgba(239,68,68,.2); border-radius:10px;
            padding:10px; text-align:center; text-decoration:none;
            cursor:pointer; transition:background .15s; font-family:inherit;
        }
        .cs-danger-btn:hover { background:rgba(239,68,68,.15); }
        @media(max-width:900px) {
            .chat-shell { flex-direction:column; }
            .chat-sidebar { width:100%; }
            .bubble-wrap { max-width:88%; }
        }
    </style>
</head>
<body>
<div class="admin-shell">

<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

<main class="admin-main">
<div class="admin-topbar">
    <div class="topbar-left">
        <div>
            <div class="topbar-title">Conversation</div>
            <div class="topbar-breadcrumb">
                Home <span>/</span>
                <a href="messages.php" style="color:var(--primary);text-decoration:none;">Messages</a>
                <span>/</span> <?= htmlspecialchars($sender['name']) ?>
            </div>
        </div>
    </div>
    <div class="topbar-right">
        <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
    </div>
</div>

<div class="admin-body">
<div class="chat-shell">

    <div class="chat-window">

        <div class="chat-header">
            <a href="messages.php" class="chat-back">←</a>
            <div class="chat-header-avatar">
                <?= strtoupper(substr($sender['name'], 0, 1)) ?>
            </div>
            <div class="chat-header-info">
                <div class="chat-header-name"><?= htmlspecialchars($sender['name']) ?></div>
                <div class="chat-header-sub" id="userStatus"><?= htmlspecialchars($sender['email']) ?></div>
            </div>
            <div class="chat-header-actions">
                <a href="../users/users.php?search=<?= urlencode($sender['email']) ?>" class="chat-action-btn">👤 Profil</a>
                <a href="messages.php?delete_sender=<?= $sender_id ?>"
                   class="chat-action-btn danger"
                   onclick="return confirm('Supprimer toute la conversation ?')">🗑 Supprimer</a>
            </div>
        </div>

        <?php if ($sent): ?>
        <div class="wa-alert-success">✅ Message envoyé !</div>
        <?php endif; ?>

        <div class="chat-messages" id="chatMessages">
            <?php
            $lastDate = '';
            foreach ($conversation as $m):
                $isAdmin  = (int)$m['sender_id'] === $adminId;
                $msgDate  = date('d/m/Y', strtotime($m['created_at']));
                if ($msgDate !== $lastDate):
                    $lastDate = $msgDate;
            ?>
            <div class="chat-date-divider"><span><?= $msgDate ?></span></div>
            <?php endif; ?>

            <div class="bubble-wrap <?= $isAdmin ? 'outgoing' : 'incoming' ?>"
                 data-msg-id="<?= (int)$m['id'] ?>">
                <?php if (!$isAdmin): ?>
                <div class="bubble-avatar-sm user">
                    <?= strtoupper(substr($sender['name'], 0, 1)) ?>
                </div>
                <?php endif; ?>

                <div class="bubble <?= $isAdmin ? 'outgoing' : 'incoming' ?>">
                    <?php if (!empty($m['subject'])): ?>
                    <div class="bubble-subject">📌 <?= htmlspecialchars($m['subject']) ?></div>
                    <?php endif; ?>
                    <div class="bubble-sender-label">
                        <?= $isAdmin ? htmlspecialchars($adminName).' 👑' : htmlspecialchars($sender['name']) ?>
                    </div>
                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                    <div class="bubble-meta">
                        <?= date('H:i', strtotime($m['created_at'])) ?>
                        <?php if ($isAdmin): ?>
                        <span class="ticks"><?= $m['is_read'] ? '✓✓' : '✓' ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isAdmin): ?>
                <div class="bubble-avatar-sm admin">👑</div>
                <?php endif; ?>
            </div>

            <?php endforeach; ?>
        </div>

        <!-- Reply: AJAX بدل form عادي -->
        <div class="chat-input-bar">
            <div class="chat-input-wrap">
                <textarea id="replyInput" rows="1"
                    placeholder="Écrire un message… (Ctrl+Enter pour envoyer)"></textarea>
            </div>
            <button class="chat-send-btn" id="sendBtn" onclick="adminSend()">➤</button>
        </div>

    </div>

    <div class="chat-sidebar">
        <div class="cs-card">
            <div class="cs-card-header">ℹ️ Infos conversation</div>
            <div class="cs-card-body">
                <div class="cs-row">
                    <span>Utilisateur</span>
                    <span><?= htmlspecialchars($sender['name']) ?></span>
                </div>
                <div class="cs-row">
                    <span>Email</span>
                    <span style="font-size:.75rem;"><?= htmlspecialchars($sender['email']) ?></span>
                </div>
                <div class="cs-row">
                    <span>Total messages</span>
                    <span id="totalCount"><?= $counts['total'] ?></span>
                </div>
                <div class="cs-row">
                    <span>Non lus</span>
                    <span id="unreadCount" style="color:<?= $counts['unread'] > 0 ? '#ef4444' : 'var(--wa-green)' ?>;">
                        <?= $counts['unread'] > 0 ? $counts['unread'].' 🔔' : '0 ✅' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="cs-card">
            <div class="cs-card-body">
                <a href="messages.php?delete_sender=<?= $sender_id ?>"
                   onclick="return confirm('Supprimer toute la conversation ?')"
                   class="cs-danger-btn">🗑 Supprimer la conversation</a>
            </div>
        </div>
    </div>

</div>
</div>
</main>
</div>

<script>
const ADMIN_ID  = <?= $adminId ?>;
const SENDER_ID = <?= $sender_id ?>;
let lastMsgId   = 0;
let seenIds     = new Set();

// ── Init lastMsgId ────────────────────────────────────────
document.querySelectorAll('[data-msg-id]').forEach(el => {
    const id = parseInt(el.dataset.msgId);
    if (!isNaN(id)) {
        seenIds.add(id);
        if (id > lastMsgId) lastMsgId = id;
    }
});

// ── Auto-resize textarea ──────────────────────────────────
const ta = document.getElementById('replyInput');
ta.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
ta.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') adminSend();
});

// ── Scroll to bottom ──────────────────────────────────────
function scrollBottom() {
    const el = document.getElementById('chatMessages');
    if (el) el.scrollTop = el.scrollHeight;
}
scrollBottom();

// ── Escape HTML ───────────────────────────────────────────
function esc(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Append bubble ─────────────────────────────────────────
function appendBubble(m) {
    const isAdminMsg = parseInt(m.sender_id) === ADMIN_ID;
    const container  = document.getElementById('chatMessages');
    const timeStr    = new Date(m.created_at.replace(' ','T'))
                        .toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});

    const wrap = document.createElement('div');
    wrap.className    = `bubble-wrap ${isAdminMsg ? 'outgoing' : 'incoming'}`;
    wrap.dataset.msgId = m.id;

    const avatarUser  = `<div class="bubble-avatar-sm user"><?= strtoupper(substr($sender['name'],0,1)) ?></div>`;
    const avatarAdmin = `<div class="bubble-avatar-sm admin">👑</div>`;
    const senderLabel = isAdminMsg
        ? `<?= htmlspecialchars($adminName) ?> 👑`
        : `<?= htmlspecialchars($sender['name']) ?>`;
    const ticks = isAdminMsg
        ? `<span class="ticks">${m.is_read ? '✓✓' : '✓'}</span>`
        : '';

    wrap.innerHTML = `
        ${!isAdminMsg ? avatarUser : ''}
        <div class="bubble ${isAdminMsg ? 'outgoing' : 'incoming'}">
            <div class="bubble-sender-label">${esc(senderLabel)}</div>
            ${esc(m.message).replace(/\n/g,'<br>')}
            <div class="bubble-meta">${timeStr} ${ticks}</div>
        </div>
        ${isAdminMsg ? avatarAdmin : ''}
    `;
    container.appendChild(wrap);
}

// ── Poll: messages جديدة + update ticks ──────────────────
setInterval(() => {
fetch(`../../chat/fetch-messages.php?with=${SENDER_ID}&last_id=0`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.messages) return;
            let added = false;
            data.messages.forEach(m => {
                const id = parseInt(m.id);
                if (!seenIds.has(id)) {
                    // message جديدة
                    seenIds.add(id);
                    appendBubble(m);
                    if (id > lastMsgId) lastMsgId = id;
                    added = true;
                } else if (parseInt(m.sender_id) === ADMIN_ID) {
                    // message قديمة ديال admin — update ticks فقط
                    const el = document.querySelector(`[data-msg-id="${id}"] .ticks`);
                    if (el) {
                        el.textContent = m.is_read == 1 ? '✓✓' : '✓';
                        el.style.color = m.is_read == 1 ? 'var(--wa-tick)' : 'var(--wa-text-secondary)';
                    }
                }
            });
            if (added) scrollBottom();
        })
        .catch(() => {});
}, 3000);

// ── FIX 3: Admin send AJAX ────────────────────────────────
function adminSend() {
    const txt = ta.value.trim();
    if (!txt) return;

    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    ta.value = '';
    ta.style.height = 'auto';

    fetch('../../chat/send-message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `receiver_id=${SENDER_ID}&message=${encodeURIComponent(txt)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.message) {
            const id = parseInt(data.message.id);
            if (!seenIds.has(id)) {
                seenIds.add(id);
                appendBubble(data.message);
                if (id > lastMsgId) lastMsgId = id;
                scrollBottom();
            }
        }
        btn.disabled = false;
        ta.focus();
    })
    .catch(() => { btn.disabled = false; });
}
</script>
</body>
</html>