<?php
// ============================================================
// admin/messages/messages.php
// ============================================================
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php"); exit;
}

$adminId = (int)$_SESSION['user_id'];

// ── Delete conversation ────────────────────────────────────
if (isset($_GET['delete_sender']) && is_numeric($_GET['delete_sender'])) {
    $sid = (int)$_GET['delete_sender'];
    $pdo->prepare("
        DELETE FROM messages
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
    ")->execute([$sid, $adminId, $adminId, $sid]);
    header("Location: messages.php"); exit;
}

// ── Mark all read ──────────────────────────────────────────
if (isset($_GET['mark_all_read'])) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ?")
        ->execute([$adminId]);
    header("Location: messages.php"); exit;
}

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// ── Build search SQL ───────────────────────────────────────
$searchSQL    = '';
$searchParams = [];
if ($search !== '') {
    $searchSQL    = "AND (u.name LIKE ? OR u.email LIKE ?)";
    $s            = "%$search%";
    $searchParams = [$s, $s];
}

// ── Conversations groupées par user ───────────────────────
$stmt = $pdo->prepare("
    SELECT
        u.id            AS user_id,
        u.name          AS user_name,
        u.email         AS user_email,
        COUNT(m.id)     AS total_messages,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count,
        MAX(m.created_at) AS last_activity,
        (
            SELECT mm.message FROM messages mm
            WHERE (mm.sender_id = u.id   AND mm.receiver_id = ?)
               OR (mm.sender_id = ?      AND mm.receiver_id = u.id)
            ORDER BY mm.created_at DESC LIMIT 1
        ) AS last_message,
        (
            SELECT mm.sender_id FROM messages mm
            WHERE (mm.sender_id = u.id   AND mm.receiver_id = ?)
               OR (mm.sender_id = ?      AND mm.receiver_id = u.id)
            ORDER BY mm.created_at DESC LIMIT 1
        ) AS last_sender_id
    FROM messages m
    JOIN users u ON (
        (m.sender_id = u.id   AND m.receiver_id = ?)
        OR
        (m.sender_id = ?      AND m.receiver_id = u.id)
    )
    WHERE u.role != 'admin'
    $searchSQL
    GROUP BY u.id, u.name, u.email
    ORDER BY last_activity DESC
");

$allParams = [
    $adminId,                          // SUM is_read
    $adminId, $adminId,               // last_message subquery
    $adminId, $adminId,               // last_sender_id subquery
    $adminId, $adminId,               // JOIN
    ...$searchParams
];
$stmt->execute($allParams);
$conversations = $stmt->fetchAll();

// ── Filter ─────────────────────────────────────────────────
if ($filter === 'unread') {
    $conversations = array_filter($conversations, fn($c) => $c['unread_count'] > 0);
} elseif ($filter === 'replied') {
    // Conversations li آخر message جاء من admin
    $conversations = array_filter($conversations, fn($c) => (int)$c['last_sender_id'] === $adminId);
}
$conversations = array_values($conversations);

// ── Stats ──────────────────────────────────────────────────
$total  = count($conversations);

$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$unreadStmt->execute([$adminId]);
$unread = (int)$unreadStmt->fetchColumn();

$repliedStmt = $pdo->prepare("SELECT COUNT(DISTINCT receiver_id) FROM messages WHERE sender_id = ?");
$repliedStmt->execute([$adminId]);
$replied = (int)$repliedStmt->fetchColumn();

$allUsers = $pdo->query("SELECT id, name, email FROM users WHERE role != 'admin' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
        <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        :root {
            --wa-green: #25d366;
            --wa-green-dark: #128c7e;
            --wa-bg: #efeae2;
            --wa-bubble-in: #ffffff;
            --wa-bubble-out: #d9fdd3;
            --wa-text-primary: #111b21;
            --wa-text-secondary: #667781;
        }
        .msg-wrap {
            background: var(--wa-bg); border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 32px rgba(0,0,0,.12); min-height: 600px;
            display: flex; flex-direction: column;
        }
        .wa-header {
            background: var(--wa-green-dark); padding: 14px 20px;
            display: flex; align-items: center; gap: 12px;
        }
        .wa-header-icon { width:40px;height:40px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem; }
        .wa-header-title { color:#fff;font-weight:700;font-size:1rem; }
        .wa-header-sub { color:rgba(255,255,255,.7);font-size:.78rem; }
        .wa-header-actions { margin-left:auto;display:flex;gap:8px; }
        .wa-header-btn {
            background:rgba(255,255,255,.15);border:none;border-radius:50px;
            color:#fff;font-size:.8rem;font-weight:600;padding:6px 14px;cursor:pointer;
            display:flex;align-items:center;gap:6px;transition:background .18s;
            font-family:inherit;text-decoration:none;
        }
        .wa-header-btn:hover { background:rgba(255,255,255,.25); }
        .wa-header-btn.new-msg { background:var(--wa-green); }
        .wa-stats {
            background:rgba(0,0,0,.04);border-bottom:1px solid rgba(0,0,0,.08);
            display:flex;padding:10px 16px;gap:16px;
        }
        .wa-stat { display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--wa-text-secondary); }
        .wa-stat strong { color:var(--wa-text-primary);font-weight:700; }
        .wa-stat-dot { width:8px;height:8px;border-radius:50%; }
        .wa-toolbar {
            background:#f0f2f5;padding:10px 12px;border-bottom:1px solid rgba(0,0,0,.08);
            display:flex;gap:8px;align-items:center;flex-wrap:wrap;
        }
        .wa-search {
            flex:1;min-width:160px;display:flex;align-items:center;gap:8px;
            background:#fff;border-radius:50px;padding:7px 14px;
            border:1.5px solid transparent;transition:border-color .18s;
        }
        .wa-search:focus-within { border-color:var(--wa-green); }
        .wa-search input { border:none;background:transparent;outline:none;font-family:inherit;font-size:.85rem;width:100%; }
        .wa-filter-tabs { display:flex;gap:4px;flex-wrap:wrap; }
        .wa-tab {
            padding:5px 12px;border-radius:50px;font-size:.78rem;font-weight:600;
            border:1.5px solid #ddd;background:#fff;color:var(--wa-text-secondary);
            cursor:pointer;text-decoration:none;transition:all .15s;
        }
        .wa-tab:hover { border-color:var(--wa-green);color:var(--wa-green); }
        .wa-tab.active { background:var(--wa-green);border-color:var(--wa-green);color:#fff; }
        .wa-tab.active-red { background:#ef4444;border-color:#ef4444;color:#fff; }
        .wa-conv-list { flex:1;overflow-y:auto; }
        .wa-conv-item {
            display:flex;align-items:center;gap:12px;padding:12px 16px;
            border-bottom:1px solid rgba(0,0,0,.05);background:#fff;
            cursor:pointer;transition:background .12s;text-decoration:none;position:relative;
        }
        .wa-conv-item:hover { background:#f5f6f6; }
        .wa-avatar {
            width:48px;height:48px;border-radius:50%;
            background:linear-gradient(135deg,var(--wa-green-dark),var(--wa-green));
            display:flex;align-items:center;justify-content:center;
            font-weight:700;color:#fff;font-size:1.1rem;flex-shrink:0;
        }
        .wa-conv-content { flex:1;min-width:0; }
        .wa-conv-top { display:flex;align-items:center;justify-content:space-between;margin-bottom:3px; }
        .wa-conv-name { font-weight:600;font-size:.9rem;color:var(--wa-text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .wa-conv-time { font-size:.72rem;color:var(--wa-text-secondary);white-space:nowrap;flex-shrink:0; }
        .wa-conv-time.green { color:var(--wa-green);font-weight:600; }
        .wa-conv-bottom { display:flex;align-items:center;justify-content:space-between;gap:4px; }
        .wa-conv-preview { font-size:.82rem;color:var(--wa-text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1; }
        .wa-conv-preview.unread-text { color:var(--wa-text-primary);font-weight:500; }
        .wa-unread-badge {
            background:var(--wa-green);color:#fff;font-size:.68rem;font-weight:700;
            min-width:18px;height:18px;border-radius:50px;
            display:flex;align-items:center;justify-content:center;padding:0 5px;flex-shrink:0;
        }
        .wa-replied-badge { font-size:.7rem;padding:2px 7px;border-radius:50px;font-weight:600;background:rgba(37,211,102,.12);color:var(--wa-green-dark); }
        .wa-msg-count { font-size:.7rem;color:var(--wa-text-secondary);background:rgba(0,0,0,.06);padding:2px 7px;border-radius:50px;flex-shrink:0; }
        .wa-delete-btn {
            position:absolute;right:12px;top:50%;transform:translateY(-50%);
            opacity:0;transition:opacity .15s;background:rgba(239,68,68,.1);
            border:none;border-radius:50%;width:30px;height:30px;
            display:flex;align-items:center;justify-content:center;
            cursor:pointer;font-size:.85rem;color:#ef4444;text-decoration:none;
        }
        .wa-conv-item:hover .wa-delete-btn { opacity:1; }
        .wa-empty { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 2rem;color:var(--wa-text-secondary);text-align:center; }
        .wa-empty-icon { font-size:3rem;margin-bottom:1rem;opacity:.5; }

        /* Modal */
        .wa-modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center; }
        .wa-modal-overlay.open { display:flex; }
        .wa-modal { background:#fff;border-radius:16px;width:90%;max-width:480px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.2); }
        .wa-modal-header { background:var(--wa-green-dark);color:#fff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between; }
        .wa-modal-header h3 { margin:0;font-size:1rem;font-weight:700; }
        .wa-modal-close { background:rgba(255,255,255,.2);border:none;border-radius:50%;width:28px;height:28px;color:#fff;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center; }
        .wa-modal-body { padding:20px;display:flex;flex-direction:column;gap:12px; }
        .wa-modal-body select,.wa-modal-body input,.wa-modal-body textarea {
            width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:10px;
            font-family:inherit;font-size:.9rem;outline:none;transition:border-color .18s;
            box-sizing:border-box;background:#fff;
        }
        .wa-modal-body select:focus,.wa-modal-body input:focus,.wa-modal-body textarea:focus { border-color:var(--wa-green); }
        .wa-modal-body textarea { min-height:100px;resize:vertical; }
        .wa-modal-body label { font-size:.82rem;font-weight:600;color:var(--wa-text-secondary); }
        .wa-send-btn { background:var(--wa-green);color:#fff;border:none;border-radius:10px;padding:12px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;transition:background .18s; }
        .wa-send-btn:hover { background:#1ebe5d; }
        .wa-alert-success { background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.25);color:var(--wa-green-dark);padding:10px 16px;font-size:.85rem;font-weight:600; }
    </style>
</head>
<body>
<div class="admin-shell">
<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

<main class="admin-main">
<div class="admin-topbar">
    <div class="topbar-left">
        <div>
            <div class="topbar-title">Messages</div>
            <div class="topbar-breadcrumb">Home <span>/</span> Messages</div>
        </div>
    </div>
    <div class="topbar-right">
        <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
    </div>
</div>

<div class="admin-body">
    <?php if (isset($_GET['sent'])): ?>
    <div class="wa-alert-success" style="border-radius:10px;margin-bottom:1rem;">✅ Message envoyé !</div>
    <?php endif; ?>

    <div class="msg-wrap">
        <div class="wa-header">
            <div class="wa-header-icon">💬</div>
            <div>
                <div class="wa-header-title">Conversations</div>
                <div class="wa-header-sub"><?= $total ?> conversations · <?= $unread ?> non lus</div>
            </div>
            <div class="wa-header-actions">
                <?php if ($unread > 0): ?>
                <a href="messages.php?mark_all_read=1" class="wa-header-btn"
                   onclick="return confirm('Marquer tous comme lus ?')">✅ Tout lire</a>
                <?php endif; ?>
                <button class="wa-header-btn new-msg" onclick="openNewMsg()">✏️ Nouveau</button>
            </div>
        </div>

        <div class="wa-stats">
            <div class="wa-stat">
                <span class="wa-stat-dot" style="background:#667781;"></span>
                <strong><?= $total ?></strong> Conversations
            </div>
            <div class="wa-stat">
                <span class="wa-stat-dot" style="background:#ef4444;"></span>
                <strong><?= $unread ?></strong> Non lus
            </div>
            <div class="wa-stat">
                <span class="wa-stat-dot" style="background:var(--wa-green);"></span>
                <strong><?= $replied ?></strong> Répondus
            </div>
        </div>

        <div class="wa-toolbar">
            <form method="GET" style="display:contents;">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <div class="wa-search">
                    <span style="color:#aaa;">🔍</span>
                    <input type="text" name="search"
                           placeholder="Rechercher…"
                           value="<?= htmlspecialchars($search) ?>">
                    <?php if ($search): ?>
                    <a href="messages.php?filter=<?= $filter ?>" style="color:#aaa;text-decoration:none;">✕</a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="wa-tab active" style="border:none;">Chercher</button>
            </form>
            <div class="wa-filter-tabs">
                <a href="messages.php?filter=all<?= $search ? '&search='.urlencode($search) : '' ?>"
                   class="wa-tab <?= $filter==='all' ? 'active' : '' ?>">Tous</a>
                <a href="messages.php?filter=unread<?= $search ? '&search='.urlencode($search) : '' ?>"
                   class="wa-tab <?= $filter==='unread' ? 'active-red' : '' ?>">🔔 Non lus (<?= $unread ?>)</a>
                <a href="messages.php?filter=replied<?= $search ? '&search='.urlencode($search) : '' ?>"
                   class="wa-tab <?= $filter==='replied' ? 'active' : '' ?>">✅ Répondus</a>
            </div>
        </div>

        <div class="wa-conv-list">
            <?php if (empty($conversations)): ?>
            <div class="wa-empty">
                <div class="wa-empty-icon">📭</div>
                <p style="font-weight:600;">Aucun message</p>
                <p>Essayez de changer les filtres</p>
            </div>
            <?php else: ?>
            <?php foreach ($conversations as $conv):
                $hasUnread  = $conv['unread_count'] > 0;
                $lastIsAdmin = (int)$conv['last_sender_id'] === $adminId;
            ?>
            <a href="reply-message.php?sender_id=<?= (int)$conv['user_id'] ?>"
               class="wa-conv-item">
                <div class="wa-avatar">
                    <?= strtoupper(substr($conv['user_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="wa-conv-content">
                    <div class="wa-conv-top">
                        <div class="wa-conv-name" style="<?= $hasUnread ? 'font-weight:700;' : '' ?>">
                            <?= htmlspecialchars($conv['user_name'] ?? 'Inconnu') ?>
                            <span style="font-size:.72rem;color:#aaa;font-weight:400;margin-left:4px;">
                                <?= htmlspecialchars($conv['user_email'] ?? '') ?>
                            </span>
                        </div>
                        <div class="wa-conv-time <?= $hasUnread ? 'green' : '' ?>">
                            <?= date('H:i', strtotime($conv['last_activity'])) ?>
                        </div>
                    </div>
                    <div class="wa-conv-bottom">
                        <div class="wa-conv-preview <?= $hasUnread ? 'unread-text' : '' ?>">
                            <?php if ($lastIsAdmin): ?>
                            <span style="color:var(--wa-green-dark);font-size:.75rem;">Vous: </span>
                            <?php endif; ?>
                            <?= htmlspecialchars(mb_strimwidth($conv['last_message'] ?? '', 0, 55, '…')) ?>
                        </div>
                        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                            <span class="wa-msg-count"><?= $conv['total_messages'] ?></span>
                            <?php if ($hasUnread): ?>
                                <span class="wa-unread-badge"><?= $conv['unread_count'] ?></span>
                            <?php elseif ($lastIsAdmin): ?>
                                <span class="wa-replied-badge">✅ Répondu</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a href="#" class="wa-delete-btn"
                   onclick="event.preventDefault();event.stopPropagation();
                            if(confirm('Supprimer toute la conversation ?'))
                                window.location='messages.php?delete_sender=<?= (int)$conv['user_id'] ?>';"
                   title="Supprimer">🗑</a>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
</div>

<!-- Modal Nouveau Message -->
<div class="wa-modal-overlay" id="newMsgModal">
    <div class="wa-modal">
        <div class="wa-modal-header">
            <h3>✏️ Nouveau message</h3>
            <button class="wa-modal-close" onclick="closeNewMsg()">✕</button>
        </div>
        <form method="POST" action="send-message.php" class="wa-modal-body">
            <label>Destinataire</label>
            <select name="receiver_id" required>
                <option value="">Choisir un utilisateur…</option>
                <?php foreach ($allUsers as $u): ?>
                <option value="<?= $u['id'] ?>">
                    <?= htmlspecialchars($u['name']) ?> — <?= htmlspecialchars($u['email']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <label>Sujet (optionnel)</label>
            <input type="text" name="subject" placeholder="Objet du message…">
            <label>Message</label>
            <textarea name="message" placeholder="Écrivez votre message…" required></textarea>
            <button type="submit" class="wa-send-btn">📨 Envoyer</button>
        </form>
    </div>
</div>

<script>
function openNewMsg()  { document.getElementById('newMsgModal').classList.add('open'); }
function closeNewMsg() { document.getElementById('newMsgModal').classList.remove('open'); }
document.getElementById('newMsgModal').addEventListener('click', function(e) {
    if (e.target === this) closeNewMsg();
});
</script>
</body>
</html>