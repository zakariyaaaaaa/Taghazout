<?php
// ============================================================
// chat/chat.php
// ============================================================
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); exit;
}

$me = (int)$_SESSION['user_id'];

// ── Admin info ────────────────────────────────────────────
$admin = $pdo->query("SELECT id, name FROM users WHERE role = 'admin' LIMIT 1")->fetch();
$adminId = $admin ? (int)$admin['id'] : 0;

// ── Selected partner ──────────────────────────────────────
$with = isset($_GET['with']) ? (int)$_GET['with'] : $adminId;

$partner = null;
if ($with) {
    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$with]);
    $partner = $stmt->fetch();

    // Mark messages من partner كـ read
    if ($partner) {
        $pdo->prepare("
            UPDATE messages SET is_read = 1
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
        ")->execute([$with, $me]);
    }
}

// ── Conversations ديالي ───────────────────────────────────
$convStmt = $pdo->prepare("
    SELECT DISTINCT
        u.id, u.name, u.role,
        (
            SELECT message FROM messages
            WHERE (sender_id = ? AND receiver_id = u.id)
               OR (sender_id = u.id AND receiver_id = ?)
            ORDER BY created_at DESC LIMIT 1
        ) AS last_message,
        (
            SELECT created_at FROM messages
            WHERE (sender_id = ? AND receiver_id = u.id)
               OR (sender_id = u.id AND receiver_id = ?)
            ORDER BY created_at DESC LIMIT 1
        ) AS last_time,
        (
            SELECT COUNT(*) FROM messages
            WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0
        ) AS unread_count
    FROM users u
    WHERE u.id != ?
    AND (
        EXISTS (SELECT 1 FROM messages WHERE sender_id = ? AND receiver_id = u.id)
        OR  EXISTS (SELECT 1 FROM messages WHERE sender_id = u.id AND receiver_id = ?)
    )
    ORDER BY last_time DESC
");
$convStmt->execute([$me,$me,$me,$me,$me,$me,$me,$me]);
$conversations = $convStmt->fetchAll();

// ── Total unread ──────────────────────────────────────────
$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$unreadStmt->execute([$me]);
$unreadTotal = (int)$unreadStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:#f0f2f5;}
.chat-shell{display:grid;grid-template-columns:320px 1fr;height:100vh;}

/* ── Sidebar ── */
.chat-sidebar{background:#fff;border-right:1.5px solid #e2e8f0;display:flex;flex-direction:column;overflow:hidden;}
.cs-header{padding:1rem 1.2rem;border-bottom:1.5px solid #e2e8f0;}
.cs-header h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:.7rem;}
.cs-search{display:flex;align-items:center;gap:.5rem;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:50px;padding:.4rem .9rem;}
.cs-search input{border:none;background:transparent;outline:none;font-family:inherit;font-size:.85rem;color:#1e293b;width:100%;}
.cs-search input::placeholder{color:#94a3b8;}
.conv-list{flex:1;overflow-y:auto;}
.conv-item{display:flex;align-items:center;gap:.85rem;padding:.85rem 1.1rem;cursor:pointer;transition:background .15s;text-decoration:none;border-left:3px solid transparent;}
.conv-item:hover{background:rgba(14,165,233,.05);}
.conv-item.active{background:rgba(14,165,233,.08);border-left-color:#0ea5e9;}
.conv-av{width:44px;height:44px;border-radius:50%;background:rgba(14,165,233,.15);color:#0ea5e9;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;flex-shrink:0;position:relative;}
.online-dot{position:absolute;bottom:1px;right:1px;width:11px;height:11px;border-radius:50%;background:#10b981;border:2px solid #fff;}
.conv-info{flex:1;min-width:0;}
.conv-name{font-weight:600;font-size:.88rem;color:#1e293b;display:flex;align-items:center;gap:.4rem;}
.admin-tag{font-size:.65rem;background:rgba(139,92,246,.12);color:#8b5cf6;padding:.1rem .4rem;border-radius:50px;font-weight:700;}
.conv-preview{font-size:.77rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:.15rem;}
.conv-preview.bold{color:#1e293b;font-weight:600;}
.conv-meta{display:flex;flex-direction:column;align-items:flex-end;gap:.3rem;flex-shrink:0;}
.conv-time{font-size:.7rem;color:#94a3b8;}
.unread-badge{background:#0ea5e9;color:#fff;font-size:.68rem;font-weight:700;border-radius:50px;padding:.1rem .45rem;min-width:18px;text-align:center;}
.support-btn{margin:.8rem 1.1rem;display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.7rem;border-radius:10px;background:#0ea5e9;color:#fff;font-weight:600;font-size:.85rem;text-decoration:none;transition:background .18s;}
.support-btn:hover{background:#0284c7;}

/* ── Main ── */
.chat-main{display:flex;flex-direction:column;overflow:hidden;}
.chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;gap:1rem;}
.chat-empty-icon{font-size:3.5rem;}
.chat-empty h3{font-family:'Syne',sans-serif;font-size:1.1rem;color:#1e293b;}

/* Chat header */
.chat-header{display:flex;align-items:center;gap:1rem;padding:.9rem 1.3rem;background:#fff;border-bottom:1.5px solid #e2e8f0;}
.ch-av{width:40px;height:40px;border-radius:50%;background:rgba(14,165,233,.15);color:#0ea5e9;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.95rem;position:relative;flex-shrink:0;}
.ch-name{font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem;color:#1e293b;}
.ch-status{font-size:.75rem;color:#10b981;font-weight:500;}
.ch-status.offline{color:#94a3b8;}

/* Messages */
.chat-messages{flex:1;overflow-y:auto;padding:1.2rem 1.4rem;display:flex;flex-direction:column;gap:.5rem;background:#f0f2f5;}
.msg-group{display:flex;flex-direction:column;gap:.2rem;}
.msg-group.mine{align-items:flex-end;}
.msg-group.theirs{align-items:flex-start;}
.msg-bubble{max-width:65%;padding:.65rem 1rem;border-radius:18px;font-size:.88rem;line-height:1.5;word-break:break-word;white-space:pre-wrap;}
.msg-group.mine   .msg-bubble{background:#0ea5e9;color:#fff;border-bottom-right-radius:5px;}
.msg-group.theirs .msg-bubble{background:#fff;color:#1e293b;border:1px solid #e2e8f0;border-bottom-left-radius:5px;box-shadow:0 1px 2px rgba(0,0,0,.06);}
.msg-time{font-size:.68rem;color:#94a3b8;margin:0 .3rem;}
.msg-date-sep{text-align:center;font-size:.73rem;color:#94a3b8;margin:.8rem 0;display:flex;align-items:center;gap:.5rem;}
.msg-date-sep::before,.msg-date-sep::after{content:'';flex:1;height:1px;background:#e2e8f0;}
.msg-subject{font-size:.72rem;font-weight:700;color:#8b5cf6;background:rgba(139,92,246,.08);border-left:3px solid #8b5cf6;padding:2px 7px;border-radius:4px;margin-bottom:4px;max-width:65%;}

/* Input */
.chat-input{padding:.9rem 1.2rem;background:#fff;border-top:1.5px solid #e2e8f0;display:flex;align-items:flex-end;gap:.7rem;}
.input-wrap{flex:1;background:#f0f2f5;border:1.5px solid #e2e8f0;border-radius:24px;display:flex;align-items:flex-end;padding:.45rem .45rem .45rem .9rem;transition:border-color .18s;}
.input-wrap:focus-within{border-color:#0ea5e9;}
.input-wrap textarea{flex:1;border:none;background:transparent;outline:none;font-family:inherit;font-size:.88rem;color:#1e293b;resize:none;max-height:120px;line-height:1.5;padding:.3rem 0;}
.input-wrap textarea::placeholder{color:#94a3b8;}
.send-btn{width:38px;height:38px;border-radius:50%;background:#0ea5e9;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .18s;flex-shrink:0;font-size:1rem;color:#fff;}
.send-btn:hover{background:#0284c7;}
.send-btn:disabled{background:#cbd5e1;cursor:not-allowed;}

@media(max-width:768px){
    .chat-shell{grid-template-columns:1fr;}
    .chat-sidebar{display:none;}
}
</style>
</head>
<body>
<div class="chat-shell">

<!-- ── SIDEBAR ── -->
<div class="chat-sidebar">
    <div class="cs-header">
        <h2>💬 Messages</h2>
        <div class="cs-search">
            <span style="color:#94a3b8;">🔍</span>
            <input type="text" id="searchInput" placeholder="Rechercher…">
        </div>
    </div>

    <div class="conv-list" id="convList">
        <?php if (empty($conversations)): ?>
        <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem;">
            Aucune conversation
        </div>
        <?php endif; ?>

        <?php foreach ($conversations as $c): ?>
        <a href="chat.php?with=<?= $c['id'] ?>"
           class="conv-item <?= $with == $c['id'] ? 'active' : '' ?>"
           data-name="<?= htmlspecialchars(strtolower($c['name'])) ?>">
            <div class="conv-av">
                <?= strtoupper(substr($c['name'],0,1)) ?>
                <span class="online-dot" id="dot-<?= $c['id'] ?>" style="display:none;"></span>
            </div>
            <div class="conv-info">
                <div class="conv-name">
                    <?= htmlspecialchars($c['name']) ?>
                    <?php if ($c['role'] === 'admin'): ?>
                    <span class="admin-tag">Support</span>
                    <?php endif; ?>
                </div>
                <div class="conv-preview <?= $c['unread_count'] > 0 ? 'bold' : '' ?>">
                    <?= htmlspecialchars(mb_strimwidth($c['last_message'] ?? '', 0, 38, '…')) ?>
                </div>
            </div>
            <div class="conv-meta">
                <?php if ($c['last_time']): ?>
                <span class="conv-time"><?= date('H:i', strtotime($c['last_time'])) ?></span>
                <?php endif; ?>
                <span class="unread-badge" id="badge-<?= $c['id'] ?>"
                      style="<?= $c['unread_count'] > 0 ? '' : 'display:none;' ?>">
                    <?= $c['unread_count'] ?>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($admin && $admin['id'] != $me): ?>
    <a href="chat.php?with=<?= $admin['id'] ?>" class="support-btn">
        🎧 Contacter le support
    </a>
    <?php endif; ?>
</div>

<!-- ── MAIN ── -->
<div class="chat-main">

<?php if (!$partner): ?>
<div class="chat-empty">
    <div class="chat-empty-icon">💬</div>
    <h3>Vos messages</h3>
    <p style="font-size:.88rem;">Sélectionnez une conversation ou contactez le support.</p>
    <?php if ($admin): ?>
    <a href="chat.php?with=<?= $admin['id'] ?>" style="margin-top:.5rem;padding:.6rem 1.4rem;background:#0ea5e9;color:#fff;border-radius:50px;font-weight:600;font-size:.85rem;text-decoration:none;">
        🎧 Contacter le support
    </a>
    <?php endif; ?>
</div>

<?php else: ?>

<!-- Header -->
<div class="chat-header">
    <div class="ch-av">
        <?= strtoupper(substr($partner['name'],0,1)) ?>
        <span class="online-dot" id="header-dot" style="display:none;"></span>
    </div>
    <div style="flex:1;">
        <div class="ch-name">
            <?= htmlspecialchars($partner['name']) ?>
            <?php if ($partner['role'] === 'admin'): ?>
            <span style="font-size:.68rem;background:rgba(139,92,246,.12);color:#8b5cf6;padding:.1rem .4rem;border-radius:50px;font-weight:700;margin-left:.4rem;">Support</span>
            <?php endif; ?>
        </div>
        <div class="ch-status offline" id="ch-status">Hors ligne</div>
    </div>
</div>

<!-- Messages -->
<div class="chat-messages" id="chatMessages">
    <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.82rem;" id="loadingMsg">
        Chargement…
    </div>
</div>

<!-- Input -->
<div class="chat-input">
    <div class="input-wrap">
        <textarea id="msgInput" placeholder="Écrire un message…" rows="1"></textarea>
    </div>
    <button class="send-btn" id="sendBtn" onclick="sendMessage()">➤</button>
</div>

<?php endif; ?>
</div>
</div>

<?php if ($partner): ?>
<script>
const ME   = <?= $me ?>;
const WITH = <?= $with ?>;
let lastId     = 0;
let renderedIds = new Set();
let isFirstLoad = true;

// ── Auto-resize textarea ──────────────────────────────────
const input = document.getElementById('msgInput');
input.addEventListener('input', () => {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 120) + 'px';
});
input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// ── Escape HTML ───────────────────────────────────────────
function esc(str) {
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

// ── Format time/date ──────────────────────────────────────
function fmtTime(dt) {
    const d = new Date(dt.replace(' ','T'));
    return d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
}
function fmtDate(dt) {
    const d = new Date(dt.replace(' ','T'));
    const today     = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);
    if (d.toDateString() === today.toDateString())     return "Aujourd'hui";
    if (d.toDateString() === yesterday.toDateString()) return "Hier";
    return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'long', year:'numeric' });
}

// ── Render messages ───────────────────────────────────────
function renderMessages(msgs, isFirst) {
    const container = document.getElementById('chatMessages');

    if (isFirst) {
        container.innerHTML = '';
        if (msgs.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.82rem;">Commencez la conversation 👋</div>';
            return;
        }
    }

    const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 80;
    let lastDate = '';

    msgs.forEach(m => {
        const msgKey = String(m.id);
        if (renderedIds.has(msgKey)) return;
        renderedIds.add(msgKey);

        // Date separator
        const dateStr = fmtDate(m.created_at);
        if (dateStr !== lastDate) {
            lastDate = dateStr;
            const sep = document.createElement('div');
            sep.className = 'msg-date-sep';
            sep.textContent = dateStr;
            container.appendChild(sep);
        }

        const isMine = parseInt(m.sender_id) === ME;
        const wrap   = document.createElement('div');
        wrap.className = `msg-group ${isMine ? 'mine' : 'theirs'}`;
        wrap.dataset.id = msgKey;

        const ticks = isMine
            ? (m.is_read == 1
                ? '<span style="font-size:.65rem;opacity:.8;"> ✓✓</span>'
                : '<span style="font-size:.65rem;opacity:.5;"> ✓</span>')
            : '';

        // Subject badge إلا كاين
        const subjectHtml = m.subject
            ? `<div class="msg-subject">📌 ${esc(m.subject)}</div>`
            : '';

        // إلا جاء من admin، زيد label
        const senderLabel = !isMine
            ? `<div style="font-size:.72rem;font-weight:700;color:#8b5cf6;margin-bottom:3px;">Support 👑</div>`
            : '';

        wrap.innerHTML = `
            ${subjectHtml}
            <div class="msg-bubble">
                ${senderLabel}
                ${esc(m.message).replace(/\n/g,'<br>')}
            </div>
            <span class="msg-time">${fmtTime(m.created_at)}${ticks}</span>
        `;
        container.appendChild(wrap);
    });

    if (isFirst || wasAtBottom) {
        container.scrollTop = container.scrollHeight;
    }
}

// ── Fetch messages ────────────────────────────────────────
function fetchMessages() {
    fetch(`fetch-messages.php?with=${WITH}&last_id=${lastId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const msgs = data.messages || [];

            if (msgs.length > 0 || isFirstLoad) {
                renderMessages(msgs, isFirstLoad);

                // Update lastId
                msgs.forEach(m => {
                    const id = parseInt(m.id);
                    if (!isNaN(id) && id > lastId) lastId = id;
                });

                isFirstLoad = false;
            }

            // Online status
            updateOnlineStatus(data.online);

            // Clear unread badge
            const badge = document.getElementById(`badge-${WITH}`);
            if (badge) badge.style.display = 'none';
        })
        .catch(err => console.error('fetch error:', err));
}

// ── Send message ──────────────────────────────────────────
function sendMessage() {
    const txt = input.value.trim();
    if (!txt) return;

    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    input.value  = '';
    input.style.height = 'auto';

    fetch('send-message.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    `receiver_id=${WITH}&message=${encodeURIComponent(txt)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // أضف message على طول بلا ما تستنى الـ poll
            renderMessages([data.message], false);
            if (data.message && data.message.id) {
                lastId = Math.max(lastId, parseInt(data.message.id));
            }
        }
        btn.disabled = false;
        input.focus();
    })
    .catch(() => { btn.disabled = false; });
}

// ── Online status ──────────────────────────────────────────
function updateOnlineStatus(isOnline) {
    const dot    = document.getElementById('header-dot');
    const status = document.getElementById('ch-status');
    const sdot   = document.getElementById(`dot-${WITH}`);

    if (isOnline) {
        if (dot)    dot.style.display    = 'block';
        if (sdot)   sdot.style.display   = 'block';
        if (status) { status.textContent = 'En ligne'; status.className = 'ch-status'; }
    } else {
        if (dot)    dot.style.display    = 'none';
        if (sdot)   sdot.style.display   = 'none';
        if (status) { status.textContent = 'Hors ligne'; status.className = 'ch-status offline'; }
    }
}

// ── Search sidebar ────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(item => {
        item.style.display = (item.dataset.name || '').includes(q) ? '' : 'none';
    });
});

// ── Start ─────────────────────────────────────────────────
fetchMessages();
setInterval(fetchMessages, 3000);
</script>
<?php endif; ?>

</body>
</html>