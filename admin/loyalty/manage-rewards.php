<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$success = $error = '';
$tab = $_GET['tab'] ?? 'rewards';

// ─── Handle POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Add / Edit reward ──────────────────────────────────
    if ($action === 'save_reward') {
        $rid         = (int)($_POST['reward_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type        = $_POST['type'] ?? 'discount';
        $points_cost = (int)($_POST['points_cost'] ?? 0);
        $value       = (float)($_POST['value'] ?? 0);
        $min_points  = (int)($_POST['min_points'] ?? 0);
        $max_uses    = $_POST['max_uses'] !== '' ? (int)$_POST['max_uses'] : null;
        $expires_days= $_POST['expires_days'] !== '' ? (int)$_POST['expires_days'] : null;
        $is_active   = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || $points_cost <= 0) {
            $error = "Le titre et le coût en points sont obligatoires.";
        } else {
            if ($rid > 0) {
                $pdo->prepare("
                    UPDATE loyalty_rewards
                    SET title=?, description=?, type=?, points_cost=?, value=?,
                        min_points=?, max_uses=?, expires_days=?, is_active=?, updated_at=NOW()
                    WHERE id=?
                ")->execute([$title, $description, $type, $points_cost, $value,
                              $min_points, $max_uses, $expires_days, $is_active, $rid]);
                $success = "✅ Récompense mise à jour.";
            } else {
                $pdo->prepare("
                    INSERT INTO loyalty_rewards
                        (title, description, type, points_cost, value, min_points, max_uses, expires_days, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ")->execute([$title, $description, $type, $points_cost, $value,
                              $min_points, $max_uses, $expires_days, $is_active]);
                $success = "✅ Récompense créée avec succès.";
            }
            $tab = 'rewards';
        }
    }

    // ── Toggle active ──────────────────────────────────────
    if ($action === 'toggle_reward') {
        $rid = (int)($_POST['reward_id'] ?? 0);
        $pdo->prepare("UPDATE loyalty_rewards SET is_active = NOT is_active WHERE id=?")->execute([$rid]);
        $success = "✅ Statut mis à jour.";
        $tab = 'rewards';
    }

    // ── Delete reward ──────────────────────────────────────
    if ($action === 'delete_reward') {
        $rid = (int)($_POST['reward_id'] ?? 0);
        $pdo->prepare("UPDATE loyalty_rewards SET is_active=0 WHERE id=?")->execute([$rid]);
        $success = "✅ Récompense désactivée.";
        $tab = 'rewards';
    }

    // ── Update redemption status ───────────────────────────
    if ($action === 'update_redemption') {
        $red_id = (int)($_POST['redemption_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if (in_array($status, ['active', 'used', 'expired'])) {
            $pdo->prepare("UPDATE loyalty_redemptions SET status=?, updated_at=NOW() WHERE id=?")
                ->execute([$status, $red_id]);
            $success = "✅ Statut de l'échange mis à jour.";
        }
        $tab = 'redemptions';
    }
}

// ─── Edit mode ─────────────────────────────────────────────
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM loyalty_rewards WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
    if ($editing) $tab = 'add';
}

// ─── Data ──────────────────────────────────────────────────
$rewards = $pdo->query("
    SELECT r.*,
           COUNT(rd.id)                                         AS total_redeemed,
           SUM(rd.status='active')                              AS active_count,
           SUM(rd.points_used)                                  AS total_pts_used
    FROM loyalty_rewards r
    LEFT JOIN loyalty_redemptions rd ON rd.reward_id = r.id
    GROUP BY r.id
    ORDER BY r.is_active DESC, r.created_at DESC
")->fetchAll();

$redemptions = $pdo->query("
    SELECT rd.*, u.name AS user_name, u.email AS user_email,
           rw.title AS reward_title, rw.type AS reward_type, rw.points_cost
    FROM loyalty_redemptions rd
    LEFT JOIN users u  ON rd.user_id  = u.id
    LEFT JOIN loyalty_rewards rw ON rd.reward_id = rw.id
    ORDER BY rd.created_at DESC
    LIMIT 60
")->fetchAll();

$stats = [
    'total_rewards'   => count($rewards),
    'active_rewards'  => count(array_filter($rewards, fn($r) => $r['is_active'])),
    'total_redeemed'  => $pdo->query("SELECT COUNT(*) FROM loyalty_redemptions")->fetchColumn(),
    'active_coupons'  => $pdo->query("SELECT COUNT(*) FROM loyalty_redemptions WHERE status='active'")->fetchColumn(),
    'pts_this_month'  => $pdo->query("SELECT COALESCE(SUM(points_used),0) FROM loyalty_redemptions WHERE MONTH(created_at)=MONTH(NOW())")->fetchColumn(),
];

$type_icons = [
    'discount'   => '💸',
    'free_night' => '🌙',
    'surf_lesson'=> '🏄',
    'gift'       => '🎁',
    'upgrade'    => '⬆️',
    'cashback'   => '💰',
    'other'      => '✨',
];
$type_labels = [
    'discount'   => 'Réduction',
    'free_night' => 'Nuit gratuite',
    'surf_lesson'=> 'Cours surf',
    'gift'       => 'Cadeau',
    'upgrade'    => 'Upgrade',
    'cashback'   => 'Cashback',
    'other'      => 'Autre',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Récompenses — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        :root {
            --rp:  #8b5cf6; --rp-lt: rgba(139,92,246,0.10);
            --rg:  #10b981; --rg-lt: rgba(16,185,129,0.10);
            --ro:  #f59e0b; --ro-lt: rgba(245,158,11,0.12);
            --rr:  #ef4444; --rr-lt: rgba(239,68,68,0.10);
            --rt:  #0ea5e9; --rt-lt: rgba(14,165,233,0.10);
        }

        /* TABS */
        .tab-bar {
            display: flex;
            gap: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 0.35rem;
            margin-bottom: 1.8rem;
            width: fit-content;
        }
        .tab-btn {
            padding: 0.55rem 1.2rem;
            border-radius: calc(var(--radius) - 4px);
            border: none;
            background: transparent;
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.18s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }
        .tab-btn:hover { color: var(--text); background: var(--bg); }
        .tab-btn.active {
            background: var(--rp);
            color: #fff;
            box-shadow: 0 2px 10px rgba(139,92,246,0.35);
        }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* REWARD CARDS GRID */
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 1.2rem;
        }
        .reward-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            transition: all 0.2s;
            position: relative;
        }
        .reward-card:hover {
            border-color: var(--rp);
            box-shadow: 0 8px 28px rgba(139,92,246,0.12);
            transform: translateY(-2px);
        }
        .reward-card.inactive { opacity: 0.55; }
        .reward-card-banner {
            height: 7px;
            background: linear-gradient(90deg, var(--rp), var(--rt));
        }
        .reward-card.inactive .reward-card-banner {
            background: var(--border);
        }
        .reward-card-body { padding: 1.2rem; }
        .reward-type-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.65rem;
            background: var(--rp-lt);
            color: var(--rp);
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 700;
            margin-bottom: 0.65rem;
        }
        .reward-title {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.35rem;
            line-height: 1.3;
        }
        .reward-desc {
            font-size: 0.8rem;
            color: var(--text-light);
            line-height: 1.5;
            margin-bottom: 0.9rem;
            min-height: 36px;
        }
        .reward-cost {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 0.85rem;
            background: var(--bg);
            border-radius: var(--radius-sm);
            margin-bottom: 0.9rem;
        }
        .reward-cost .pts {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--rp);
        }
        .reward-cost .pts-lbl {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        .reward-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.9rem;
        }
        .meta-chip {
            font-size: 0.72rem;
            padding: 0.18rem 0.55rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 50px;
            color: var(--text-light);
        }
        .reward-stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.5rem;
            padding-top: 0.85rem;
            border-top: 1px solid var(--border);
            margin-top: 0.5rem;
        }
        .rstat { text-align: center; }
        .rstat-val {
            font-family: 'Syne', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--text);
        }
        .rstat-lbl { font-size: 0.65rem; color: var(--text-light); }
        .reward-actions {
            display: flex;
            gap: 0.5rem;
            padding: 0.75rem 1.2rem;
            border-top: 1px solid var(--border);
            background: var(--bg);
        }
        .ra-btn {
            flex: 1;
            padding: 0.42rem;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--card-bg);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.14s;
            color: var(--text);
            text-align: center;
        }
        .ra-btn:hover { border-color: var(--rp); color: var(--rp); background: var(--rp-lt); }
        .ra-btn.danger:hover { border-color: var(--rr); color: var(--rr); background: var(--rr-lt); }
        .ra-btn.success:hover { border-color: var(--rg); color: var(--rg); background: var(--rg-lt); }
        .badge-active {
            position: absolute; top: 18px; right: 12px;
            font-size: 0.68rem; font-weight: 700;
            padding: 0.18rem 0.55rem;
            border-radius: 50px;
        }
        .badge-active.on  { background: var(--rg-lt); color: var(--rg); }
        .badge-active.off { background: var(--rr-lt); color: var(--rr); }

        /* ADD / EDIT FORM */
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            max-width: 760px;
        }
        .form-card-head {
            padding: 1.1rem 1.6rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--rp-lt), var(--rt-lt));
            display: flex; align-items: center; justify-content: space-between;
        }
        .form-card-head h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1rem; font-weight: 700; color: var(--text); margin: 0;
        }
        .form-card-body { padding: 1.6rem; }
        .fg label {
            display: block;
            font-size: 0.8rem; font-weight: 600; color: var(--text); margin-bottom: 0.4rem;
        }
        .fg input, .fg select, .fg textarea {
            width: 100%; padding: 0.65rem 0.9rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg); color: var(--text);
            font-size: 0.88rem; font-family: 'DM Sans', sans-serif;
            transition: border-color 0.18s; box-sizing: border-box;
        }
        .fg input:focus, .fg select:focus, .fg textarea:focus { outline: none; border-color: var(--rp); }
        .fg textarea { resize: vertical; min-height: 72px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        @media(max-width:640px) { .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; } }

        /* type selector */
        .type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 0.6rem;
        }
        .type-opt { display: none; }
        .type-lbl {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
            padding: 0.75rem 0.5rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-light);
            transition: all 0.15s;
            text-align: center;
            background: var(--bg);
        }
        .type-lbl:hover { border-color: var(--rp); color: var(--text); }
        .type-opt:checked + .type-lbl {
            border-color: var(--rp);
            background: var(--rp-lt);
            color: var(--rp);
        }
        .type-icon { font-size: 1.4rem; }

        /* toggle switch */
        .toggle-wrap {
            display: flex; align-items: center; gap: 0.8rem;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
        }
        .toggle-wrap label { margin: 0; font-size: 0.86rem; font-weight: 600; color: var(--text); }
        .toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; inset: 0;
            background: var(--border); border-radius: 50px; transition: 0.2s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute; width: 18px; height: 18px;
            left: 3px; bottom: 3px;
            background: white; border-radius: 50%; transition: 0.2s;
        }
        .toggle input:checked + .toggle-slider { background: var(--rg); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(20px); }

        /* redemptions table */
        .red-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
        .red-table th {
            padding: 0.7rem 1rem;
            background: var(--bg);
            border-bottom: 2px solid var(--border);
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--text-light); text-align: left;
        }
        .red-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .red-table tr:last-child td { border-bottom: none; }
        .red-table tr:hover td { background: var(--bg); }
        .user-cell { display: flex; align-items: center; gap: 0.6rem; }
        .u-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--rp-lt); color: var(--rp);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.78rem; flex-shrink: 0;
        }
        .u-name { font-weight: 600; color: var(--text); }
        .u-email { font-size: 0.71rem; color: var(--text-light); }
        .coupon-code {
            font-family: 'Courier New', monospace;
            font-size: 0.82rem; font-weight: 700;
            background: var(--bg);
            border: 1px solid var(--border);
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            color: var(--rp);
            letter-spacing: 0.05em;
        }
        .status-select {
            padding: 0.3rem 0.6rem;
            border: 1.5px solid var(--border);
            border-radius: 7px;
            background: var(--bg);
            font-size: 0.78rem; font-weight: 600;
            cursor: pointer;
            color: var(--text);
            transition: border-color 0.15s;
        }
        .status-select:focus { outline: none; border-color: var(--rp); }
        .pill { font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 50px; }
        .pill-active  { background: var(--rg-lt); color: var(--rg); }
        .pill-used    { background: var(--border); color: var(--text-light); }
        .pill-expired { background: var(--rr-lt); color: var(--rr); }

        /* misc */
        .alert { padding: 0.85rem 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; margin-bottom: 1.2rem; }
        .alert-success { background: var(--rg-lt); color: var(--rg); border: 1px solid rgba(16,185,129,.25); }
        .alert-error   { background: var(--rr-lt); color: var(--rr); border: 1px solid rgba(239,68,68,.25); }
        .stats-grid .stat-card { border-top: 3px solid transparent; }
        .stats-grid .stat-card.c-plum  { border-top-color: var(--rp); }
        .stats-grid .stat-card.c-green { border-top-color: var(--rg); }
        .stats-grid .stat-card.c-gold  { border-top-color: var(--ro); }
        .stats-grid .stat-card.c-teal  { border-top-color: var(--rt); }
        .save-btn {
            padding: 0.8rem 2rem;
            background: var(--rp); color: #fff;
            border: none; border-radius: var(--radius-sm);
            font-size: 0.95rem; font-weight: 700;
            font-family: 'Syne', sans-serif; cursor: pointer;
            transition: all 0.18s;
        }
        .save-btn:hover { opacity: 0.88; transform: translateY(-1px); }
        .empty-state {
            text-align: center; padding: 3.5rem 1rem; color: var(--text-light);
        }
        .empty-state .es-icon { font-size: 2.8rem; margin-bottom: 0.8rem; }
        .empty-state p { font-size: 0.88rem; }

        /* search bar */
        .table-toolbar {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid var(--border);
            display: flex; gap: 0.8rem; align-items: center; flex-wrap: wrap;
        }
        .search-input {
            flex: 1; min-width: 200px;
            padding: 0.55rem 0.9rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg); color: var(--text);
            font-size: 0.85rem; font-family: 'DM Sans', sans-serif;
        }
        .search-input:focus { outline: none; border-color: var(--rp); }
        .filter-select {
            padding: 0.55rem 0.9rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg); color: var(--text);
            font-size: 0.85rem; font-family: 'DM Sans', sans-serif; cursor: pointer;
        }
        .filter-select:focus { outline: none; border-color: var(--rp); }
    </style>
</head>
<body>
<div class="admin-shell">

<?php
$_SERVER['PHP_SELF'] = '/admin/loyalty/manage-rewards.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<main class="admin-main">

    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Gérer les récompenses</div>
                <div class="topbar-breadcrumb">
                    Home <span>/</span>
                    <a href="../loyalty.php">Loyalty</a>
                    <span>/</span> Récompenses
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
        </div>
    </div>

    <div class="admin-body">

        <div class="page-header">
            <div>
                <h1>🎁 Gérer les récompenses</h1>
                <p>Créez, modifiez et suivez les échanges de votre programme de fidélité.</p>
            </div>
            <div style="display:flex;gap:0.75rem;">
                <a href="../loyalty.php" style="padding:0.6rem 1.2rem;font-size:0.88rem;background:var(--bg);border:1.5px solid var(--border);border-radius:var(--radius-sm);color:var(--text);text-decoration:none;font-weight:600;">
                    ← Dashboard
                </a>
                <button onclick="switchTab('add')" style="padding:0.6rem 1.2rem;font-size:0.88rem;background:var(--rp);border:none;border-radius:var(--radius-sm);color:#fff;font-weight:700;cursor:pointer;font-family:'Syne',sans-serif;">
                    ➕ Nouvelle récompense
                </button>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid" style="margin-bottom:1.8rem;">
            <div class="stat-card c-plum">
                <div class="stat-icon" style="background:var(--rp-lt);color:var(--rp);">🎁</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['total_rewards'] ?></div>
                    <div class="stat-label">Récompenses total</div>
                </div>
            </div>
            <div class="stat-card c-green">
                <div class="stat-icon" style="background:var(--rg-lt);color:var(--rg);">✅</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['active_rewards'] ?></div>
                    <div class="stat-label">Actives</div>
                </div>
            </div>
            <div class="stat-card c-gold">
                <div class="stat-icon" style="background:var(--ro-lt);color:var(--ro);">🎟️</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['total_redeemed'] ?></div>
                    <div class="stat-label">Échanges total</div>
                </div>
            </div>
            <div class="stat-card c-teal">
                <div class="stat-icon" style="background:var(--rt-lt);color:var(--rt);">💎</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['pts_this_month']) ?></div>
                    <div class="stat-label">Points échangés ce mois</div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="tab-bar">
            <button class="tab-btn <?= $tab==='rewards'?'active':'' ?>" onclick="switchTab('rewards')">
                🎁 Récompenses <span style="font-size:0.7rem;opacity:0.8;">(<?= $stats['total_rewards'] ?>)</span>
            </button>
            <button class="tab-btn <?= $tab==='add'?'active':'' ?>" onclick="switchTab('add')">
                <?= $editing ? '✏️ Modifier' : '➕ Ajouter' ?>
            </button>
            <button class="tab-btn <?= $tab==='redemptions'?'active':'' ?>" onclick="switchTab('redemptions')">
                🎟️ Échanges <span style="font-size:0.7rem;opacity:0.8;">(<?= $stats['active_coupons'] ?> actifs)</span>
            </button>
        </div>

        <!-- ═══ TAB: REWARDS LIST ═══ -->
        <div class="tab-panel <?= $tab==='rewards'?'active':'' ?>" id="tab-rewards">

            <?php if (empty($rewards)): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="es-icon">🎁</div>
                    <p>Aucune récompense créée pour l'instant.</p>
                    <button onclick="switchTab('add')" style="margin-top:0.8rem;padding:0.65rem 1.4rem;background:var(--rp);color:#fff;border:none;border-radius:var(--radius-sm);font-weight:700;cursor:pointer;font-family:'Syne',sans-serif;">
                        Créer la première récompense
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="rewards-grid">
                <?php foreach ($rewards as $r):
                    $tIcon  = $type_icons[$r['type']] ?? '✨';
                    $tLabel = $type_labels[$r['type']] ?? $r['type'];
                ?>
                <div class="reward-card <?= !$r['is_active']?'inactive':'' ?>">
                    <div class="reward-card-banner"></div>
                    <span class="badge-active <?= $r['is_active']?'on':'off' ?>">
                        <?= $r['is_active']?'● Actif':'● Inactif' ?>
                    </span>
                    <div class="reward-card-body">
                        <div class="reward-type-chip"><?= $tIcon ?> <?= $tLabel ?></div>
                        <div class="reward-title"><?= htmlspecialchars($r['title']) ?></div>
                        <div class="reward-desc"><?= htmlspecialchars($r['description'] ?: '—') ?></div>
                        <div class="reward-cost">
                            <span style="font-size:1.3rem;">⭐</span>
                            <div>
                                <div class="pts"><?= number_format($r['points_cost']) ?></div>
                                <div class="pts-lbl">points requis</div>
                            </div>
                            <?php if ($r['value'] > 0): ?>
                            <div style="margin-left:auto;text-align:right;">
                                <div style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;color:var(--rg);">
                                    <?= $r['type']==='discount' ? $r['value'].'%' : number_format($r['value']).' MAD' ?>
                                </div>
                                <div style="font-size:0.7rem;color:var(--text-light);">valeur</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="reward-meta">
                            <?php if ($r['min_points'] > 0): ?>
                            <span class="meta-chip">📊 Min <?= number_format($r['min_points']) ?> pts</span>
                            <?php endif; ?>
                            <?php if ($r['max_uses']): ?>
                            <span class="meta-chip">🔢 Max <?= $r['max_uses'] ?> utilisations</span>
                            <?php endif; ?>
                            <?php if ($r['expires_days']): ?>
                            <span class="meta-chip">⏳ Expire dans <?= $r['expires_days'] ?> j</span>
                            <?php endif; ?>
                        </div>
                        <div class="reward-stats">
                            <div class="rstat">
                                <div class="rstat-val"><?= $r['total_redeemed'] ?></div>
                                <div class="rstat-lbl">Échangés</div>
                            </div>
                            <div class="rstat">
                                <div class="rstat-val"><?= $r['active_count'] ?></div>
                                <div class="rstat-lbl">Actifs</div>
                            </div>
                            <div class="rstat">
                                <div class="rstat-val"><?= number_format($r['total_pts_used'] ?? 0) ?></div>
                                <div class="rstat-lbl">Pts utilisés</div>
                            </div>
                        </div>
                    </div>
                    <div class="reward-actions">
                        <a href="?edit=<?= $r['id'] ?>" class="ra-btn">✏️ Modifier</a>
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="action" value="toggle_reward">
                            <input type="hidden" name="reward_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="ra-btn <?= $r['is_active']?'danger':'success' ?>" style="width:100%;">
                                <?= $r['is_active'] ? '⏸ Désactiver' : '▶ Activer' ?>
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Désactiver cette récompense ?')">
                            <input type="hidden" name="action" value="delete_reward">
                            <input type="hidden" name="reward_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="ra-btn danger">🗑️</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ═══ TAB: ADD / EDIT ═══ -->
        <div class="tab-panel <?= $tab==='add'?'active':'' ?>" id="tab-add">
            <div class="form-card">
                <div class="form-card-head">
                    <h3><?= $editing ? '✏️ Modifier la récompense' : '➕ Nouvelle récompense' ?></h3>
                    <?php if ($editing): ?>
                    <a href="manage-rewards.php" style="font-size:0.8rem;color:var(--text-light);">✕ Annuler la modification</a>
                    <?php endif; ?>
                </div>
                <div class="form-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_reward">
                        <?php if ($editing): ?>
                        <input type="hidden" name="reward_id" value="<?= $editing['id'] ?>">
                        <?php endif; ?>

                        <!-- Type selector -->
                        <div style="margin-bottom:1.4rem;">
                            <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text);margin-bottom:0.6rem;">Type de récompense *</label>
                            <div class="type-grid">
                                <?php foreach ($type_icons as $val => $icon):
                                    $checked = ($editing && $editing['type']===$val) || (!$editing && $val==='discount');
                                ?>
                                <input type="radio" name="type" id="type_<?= $val ?>" value="<?= $val ?>" class="type-opt" <?= $checked?'checked':'' ?>>
                                <label for="type_<?= $val ?>" class="type-lbl">
                                    <span class="type-icon"><?= $icon ?></span>
                                    <?= $type_labels[$val] ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Title & Description -->
                        <div class="fg" style="margin-bottom:1rem;">
                            <label>📝 Titre *</label>
                            <input type="text" name="title" placeholder="Ex: Réduction 15% sur la prochaine réservation" required
                                   value="<?= htmlspecialchars($editing['title'] ?? '') ?>">
                        </div>
                        <div class="fg" style="margin-bottom:1.2rem;">
                            <label>📄 Description <small style="font-weight:400;color:var(--text-light);">(optionnel)</small></label>
                            <textarea name="description" placeholder="Décrivez les conditions et avantages..."><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Points & Value -->
                        <div class="form-grid-2" style="margin-bottom:1.2rem;">
                            <div class="fg">
                                <label>⭐ Coût en points *</label>
                                <input type="number" name="points_cost" min="1" placeholder="Ex: 500" required
                                       value="<?= $editing['points_cost'] ?? '' ?>">
                            </div>
                            <div class="fg">
                                <label>💰 Valeur <small style="font-weight:400;color:var(--text-light);">(% ou MAD)</small></label>
                                <input type="number" name="value" step="0.01" min="0" placeholder="Ex: 15"
                                       value="<?= $editing['value'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Constraints -->
                        <div class="form-grid-3" style="margin-bottom:1.4rem;">
                            <div class="fg">
                                <label>📊 Points minimum requis</label>
                                <input type="number" name="min_points" min="0" placeholder="0 = sans minimum"
                                       value="<?= $editing['min_points'] ?? 0 ?>">
                            </div>
                            <div class="fg">
                                <label>🔢 Utilisations max</label>
                                <input type="number" name="max_uses" min="1" placeholder="Vide = illimité"
                                       value="<?= $editing['max_uses'] ?? '' ?>">
                            </div>
                            <div class="fg">
                                <label>⏳ Expiration (jours)</label>
                                <input type="number" name="expires_days" min="1" placeholder="Vide = pas d'expiration"
                                       value="<?= $editing['expires_days'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Active toggle -->
                        <div class="toggle-wrap" style="margin-bottom:1.4rem;">
                            <label class="toggle">
                                <input type="checkbox" name="is_active" id="isActive" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <label for="isActive">Récompense active (visible par les membres)</label>
                        </div>

                        <div style="display:flex;gap:0.85rem;align-items:center;">
                            <button type="submit" class="save-btn">
                                <?= $editing ? '💾 Mettre à jour' : '✨ Créer la récompense' ?>
                            </button>
                            <button type="button" onclick="switchTab('rewards')" style="padding:0.8rem 1.4rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--bg);cursor:pointer;font-weight:600;color:var(--text-light);">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: REDEMPTIONS ═══ -->
        <div class="tab-panel <?= $tab==='redemptions'?'active':'' ?>" id="tab-redemptions">
            <div class="card" style="padding:0;overflow:hidden;">
                <div class="table-toolbar">
                    <input type="text" class="search-input" id="redSearch" placeholder="🔍 Rechercher membre, récompense ou code...">
                    <select class="filter-select" id="redFilter">
                        <option value="">Tous les statuts</option>
                        <option value="active">Actifs</option>
                        <option value="used">Utilisés</option>
                        <option value="expired">Expirés</option>
                    </select>
                    <span style="font-size:0.8rem;color:var(--text-light);margin-left:auto;" id="redCount">
                        <?= count($redemptions) ?> échanges
                    </span>
                </div>
                <?php if (empty($redemptions)): ?>
                <div class="empty-state">
                    <div class="es-icon">🎟️</div>
                    <p>Aucun échange effectué pour l'instant.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="red-table" id="redTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Membre</th>
                                <th>Récompense</th>
                                <th>Code coupon</th>
                                <th>Points utilisés</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($redemptions as $rd): ?>
                        <tr data-search="<?= strtolower(htmlspecialchars($rd['user_name'].$rd['user_email'].$rd['reward_title'].($rd['coupon_code']??''))) ?>"
                            data-status="<?= $rd['status'] ?>">
                            <td style="color:var(--text-light);font-size:0.78rem;">#<?= $rd['id'] ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="u-avatar"><?= strtoupper(substr($rd['user_name']??'?',0,1)) ?></div>
                                    <div>
                                        <div class="u-name"><?= htmlspecialchars($rd['user_name']??'N/A') ?></div>
                                        <div class="u-email"><?= htmlspecialchars($rd['user_email']??'') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:var(--text);font-size:0.84rem;"><?= htmlspecialchars($rd['reward_title']??'—') ?></div>
                                <div style="font-size:0.72rem;color:var(--text-light);"><?= $type_labels[$rd['reward_type']??''] ?? '—' ?></div>
                            </td>
                            <td>
                                <?php if (!empty($rd['coupon_code'])): ?>
                                <span class="coupon-code"><?= htmlspecialchars($rd['coupon_code']) ?></span>
                                <?php else: ?>
                                <span style="color:var(--text-light);font-size:0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-family:'Syne',sans-serif;font-weight:800;color:var(--rp);">
                                    ⭐ <?= number_format($rd['points_used'] ?? $rd['points_cost'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <span class="pill pill-<?= $rd['status'] ?>">
                                    <?= $rd['status']==='active'?'● Actif':($rd['status']==='used'?'✓ Utilisé':'✕ Expiré') ?>
                                </span>
                            </td>
                            <td style="color:var(--text-light);font-size:0.78rem;white-space:nowrap;">
                                <?= date('d/m/Y H:i', strtotime($rd['created_at'])) ?>
                            </td>
                            <td>
                                <form method="POST" style="display:flex;gap:0.4rem;">
                                    <input type="hidden" name="action" value="update_redemption">
                                    <input type="hidden" name="redemption_id" value="<?= $rd['id'] ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="active"  <?= $rd['status']==='active' ?'selected':'' ?>>Actif</option>
                                        <option value="used"    <?= $rd['status']==='used'   ?'selected':'' ?>>Utilisé</option>
                                        <option value="expired" <?= $rd['status']==='expired'?'selected':'' ?>>Expiré</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>
</div>

<script>
// ─── Tab switching ────────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name)?.classList.add('active');
    document.querySelectorAll('.tab-btn').forEach(b => {
        if (b.getAttribute('onclick') === `switchTab('${name}')`) b.classList.add('active');
    });
}

// ─── Redemptions search + filter ─────────────────────────
function filterRedemptions() {
    const q = document.getElementById('redSearch').value.toLowerCase().trim();
    const f = document.getElementById('redFilter').value;
    let visible = 0;
    document.querySelectorAll('#redTable tbody tr').forEach(row => {
        const matchQ = !q || row.dataset.search.includes(q);
        const matchF = !f || row.dataset.status === f;
        const show = matchQ && matchF;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('redCount').textContent = visible + ' échange(s)';
}

document.getElementById('redSearch')?.addEventListener('input', filterRedemptions);
document.getElementById('redFilter')?.addEventListener('change', filterRedemptions);
</script>
</body>
</html>