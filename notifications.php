<?php
session_start();
require_once 'includes/config.php';

// ── Auth check ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ── Mark all as read ──────────────────────────────────────────
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
    ->execute([$user_id]);

// ── Fetch notifications ───────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$unread_count = count(array_filter($notifications, fn($n) => !$n['is_read']));
$total        = count($notifications);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — Taghazout Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <style>
        /* ── Page hero (same as index) ── */
        .page-hero {
            position: relative;
            height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #0ea5e9 100%);
        }
        .page-hero-bg {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 60% 40%, rgba(14,165,233,0.18) 0%, transparent 70%);
        }
        .page-hero-content { position: relative; z-index: 2; color: #fff; }
        .page-hero-content .eyebrow {
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #7dd3fc;
            margin-bottom: .75rem;
        }
        .page-hero-content h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700;
            margin: 0 0 .6rem;
        }
        .page-hero-content p {
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            color: rgba(255,255,255,.7);
        }

        /* ── Notifications wrapper ── */
        .notif-wrapper {
            max-width: 780px;
            margin: 2.5rem auto 5rem;
            padding: 0 1.25rem;
        }

        /* ── Top bar ── */
        .notif-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: .75rem;
        }
        .notif-count {
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            color: var(--text-light, #94a3b8);
        }
        .notif-count strong { color: var(--text-primary, #f1f5f9); }
        .btn-clear-all {
            font-family: 'DM Sans', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            color: #ef4444;
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: 50px;
            padding: .35rem 1rem;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }
        .btn-clear-all:hover {
            background: rgba(239,68,68,.18);
        }

        /* ── Notification card ── */
        .notif-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }
        .notif-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            background: var(--card-bg, #1e293b);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            transition: transform .18s, box-shadow .18s, opacity .3s;
            position: relative;
            animation: slideIn .35s ease both;
        }
        .notif-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0,0,0,.22);
        }
        .notif-card.unread {
            border-left: 3px solid #0ea5e9;
            background: linear-gradient(90deg, rgba(14,165,233,.06) 0%, var(--card-bg, #1e293b) 60%);
        }

        /* ── Icon ── */
        .notif-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            background: rgba(14,165,233,.12);
        }
        .notif-icon.payment  { background: rgba(16,185,129,.12); }
        .notif-icon.cancel   { background: rgba(239,68,68,.12); }
        .notif-icon.info     { background: rgba(245,158,11,.12); }

        /* ── Content ── */
        .notif-body { flex: 1; min-width: 0; }
        .notif-title {
            font-family: 'Syne', sans-serif;
            font-size: .95rem;
            font-weight: 600;
            color: var(--text-primary, #f1f5f9);
            margin: 0 0 .25rem;
        }
        .notif-msg {
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem;
            color: var(--text-light, #94a3b8);
            margin: 0 0 .4rem;
            line-height: 1.5;
        }
        .notif-time {
            font-family: 'DM Sans', sans-serif;
            font-size: .75rem;
            color: var(--text-light, #64748b);
        }

        /* ── Unread dot ── */
        .notif-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0ea5e9;
            flex-shrink: 0;
            margin-top: .35rem;
        }

        /* ── Delete btn ── */
        .btn-delete-notif {
            position: absolute;
            top: .75rem;
            right: .75rem;
            background: none;
            border: none;
            color: var(--text-light, #64748b);
            font-size: 1rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity .2s, color .2s;
            padding: .2rem .4rem;
            border-radius: 6px;
        }
        .notif-card:hover .btn-delete-notif { opacity: 1; }
        .btn-delete-notif:hover { color: #ef4444; background: rgba(239,68,68,.1); }

        /* ── Empty state ── */
        .notif-empty {
            text-align: center;
            padding: 5rem 2rem;
            color: var(--text-light, #94a3b8);
        }
        .notif-empty .emoji { font-size: 3.5rem; display: block; margin-bottom: 1rem; }
        .notif-empty h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            color: var(--text-primary, #f1f5f9);
            margin-bottom: .5rem;
        }

        /* ── Animations ── */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .notif-card { animation-fill-mode: both; }
        <?php for ($i = 0; $i < 20; $i++): ?>
        .notif-card:nth-child(<?= $i + 1 ?>) { animation-delay: <?= $i * 0.04 ?>s; }
        <?php endfor; ?>
    </style>
</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
                <video autoplay muted loop playsinline class="hero-video">
                    <source src="../assets/videos/notifications.mp4" type="video/mp4">
                </video>
    <div class="page-hero-bg"></div>
    <div class="page-hero-content">
        <p class="eyebrow">🔔 Taghazout Platform</p>
        <h1>Mes Notifications</h1>
        <p>Restez informé de vos réservations et activités</p>
    </div>
</section>

<!-- NOTIFICATIONS -->
<div class="notif-wrapper">

    <!-- Top bar -->
    <div class="notif-topbar">
        <p class="notif-count">
            <strong><?= $total ?></strong> notification<?= $total > 1 ? 's' : '' ?>
        </p>
        <?php if ($total > 0): ?>
            <button class="btn-clear-all" onclick="clearAll()">🗑️ Tout supprimer</button>
        <?php endif; ?>
    </div>

    <!-- List -->
    <div class="notif-list" id="notif-list">
        <?php if (empty($notifications)): ?>
            <div class="notif-empty">
                <span class="emoji">🔔</span>
                <h3>Aucune notification</h3>
                <p>Vous êtes à jour !</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif):
                // Detect icon type from title
                $icon = '🔔';
                $icon_class = '';
                $title_lower = strtolower($notif['title']);
                if (str_contains($title_lower, 'paiement')) {
                    $icon = '💳'; $icon_class = 'payment';
                } elseif (str_contains($title_lower, 'annul')) {
                    $icon = '❌'; $icon_class = 'cancel';
                } elseif (str_contains($title_lower, 'confirm')) {
                    $icon = '✅'; $icon_class = '';
                } elseif (str_contains($title_lower, 'bienvenu') || str_contains($title_lower, 'info')) {
                    $icon = 'ℹ️'; $icon_class = 'info';
                }

                // Format time
                $date = new DateTime($notif['created_at']);
                $now  = new DateTime();
                $diff = $now->diff($date);
                if ($diff->days === 0 && $diff->h === 0)      $time_str = "Il y a " . $diff->i . " min";
                elseif ($diff->days === 0)                     $time_str = "Il y a " . $diff->h . "h";
                elseif ($diff->days === 1)                     $time_str = "Hier à " . $date->format('H:i');
                else                                           $time_str = $date->format('d/m/Y à H:i');
            ?>
            <div class="notif-card <?= !$notif['is_read'] ? 'unread' : '' ?>"
                 id="notif-<?= $notif['id'] ?>">

                <div class="notif-icon <?= $icon_class ?>"><?= $icon ?></div>

                <div class="notif-body">
                    <p class="notif-title"><?= htmlspecialchars($notif['title']) ?></p>
                    <?php if (!empty($notif['message'])): ?>
                        <p class="notif-msg"><?= htmlspecialchars($notif['message']) ?></p>
                    <?php endif; ?>
                    <span class="notif-time">🕐 <?= $time_str ?></span>
                </div>

                <?php if (!$notif['is_read']): ?>
                    <div class="notif-dot"></div>
                <?php endif; ?>

                <button
                    class="btn-delete-notif"
                    title="Supprimer"
                    onclick="deleteNotif(<?= $notif['id'] ?>)"
                >✕</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>