<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$success = $error = '';
$tab = $_GET['tab'] ?? 'rewards';

// ─── Handle actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_reward') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type        = in_array($_POST['type'], ['discount','free_night','free_surf','coupon']) ? $_POST['type'] : 'discount';
        $points_cost = (int)($_POST['points_cost'] ?? 0);
        $value       = (float)($_POST['value'] ?? 0);
        $value_type  = in_array($_POST['value_type'], ['fixed','percent']) ? $_POST['value_type'] : 'percent';
        $stock       = $_POST['stock'] !== '' ? (int)$_POST['stock'] : null;
        $is_active   = isset($_POST['is_active']) ? 1 : 0;

        if ($title === '' || $points_cost <= 0 || $value <= 0) {
            $error = "Titre, points et valeur sont obligatoires.";
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE loyalty_rewards SET title=?, description=?, type=?, points_cost=?, value=?, value_type=?, stock=?, is_active=? WHERE id=?");
                $stmt->execute([$title, $description, $type, $points_cost, $value, $value_type, $stock, $is_active, $id]);
                $success = "✅ Récompense mise à jour avec succès.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO loyalty_rewards (title, description, type, points_cost, value, value_type, stock, is_active) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$title, $description, $type, $points_cost, $value, $value_type, $stock, $is_active]);
                $success = "✅ Récompense ajoutée avec succès.";
            }
        }
        $tab = 'rewards';
    }

    if ($action === 'delete_reward') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM loyalty_rewards WHERE id=?")->execute([$id]);
            $success = "🗑️ Récompense supprimée.";
        }
        $tab = 'rewards';
    }

    if ($action === 'toggle_reward') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE loyalty_rewards SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        header("Location: manage-rewards.php?tab=rewards");
        exit;
    }

    if ($action === 'update_redemption') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = in_array($_POST['status'], ['active','used','expired']) ? $_POST['status'] : 'active';
        if ($id > 0) {
            $used_at = $status === 'used' ? date('Y-m-d H:i:s') : null;
            $pdo->prepare("UPDATE loyalty_redemptions SET status=?, used_at=? WHERE id=?")->execute([$status, $used_at, $id]);
            $success = "✅ Statut mis à jour.";
        }
        $tab = 'redemptions';
    }
}

// ─── Data ─────────────────────────────────────────────────
$rewards = $pdo->query("SELECT * FROM loyalty_rewards ORDER BY points_cost ASC")->fetchAll();

$redemptions = $pdo->query("
    SELECT lr.*, u.name AS user_name, u.email AS user_email,
           rw.title AS reward_title, rw.type AS reward_type
    FROM loyalty_redemptions lr
    LEFT JOIN users u  ON lr.user_id  = u.id
    LEFT JOIN loyalty_rewards rw ON lr.reward_id = rw.id
    ORDER BY lr.created_at DESC
    LIMIT 50
")->fetchAll();

$stats = [
    'total_rewards'  => $pdo->query("SELECT COUNT(*) FROM loyalty_rewards")->fetchColumn(),
    'active_rewards' => $pdo->query("SELECT COUNT(*) FROM loyalty_rewards WHERE is_active=1")->fetchColumn(),
    'total_redeemed' => $pdo->query("SELECT COUNT(*) FROM loyalty_redemptions")->fetchColumn(),
    'active_coupons' => $pdo->query("SELECT COUNT(*) FROM loyalty_redemptions WHERE status='active'")->fetchColumn(),
    'used_coupons'   => $pdo->query("SELECT COUNT(*) FROM loyalty_redemptions WHERE status='used'")->fetchColumn(),
    'points_spent'   => $pdo->query("SELECT COALESCE(SUM(points_used),0) FROM loyalty_redemptions")->fetchColumn(),
];

$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt   = $pdo->prepare("SELECT * FROM loyalty_rewards WHERE id=?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
    $tab = 'add';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer Récompenses — Admin Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        :root {
            --rp:   #8b5cf6;
            --rp-lt:rgba(139,92,246,0.10);
            --rg:   #10b981;
            --rg-lt:rgba(16,185,129,0.10);
            --ro:   #f59e0b;
            --ro-lt:rgba(245,158,11,0.12);
            --rr:   #ef4444;
            --rr-lt:rgba(239,68,68,0.10);
            --rt:   #0ea5e9;
            --rt-lt:rgba(14,165,233,0.10);
        }

        /* stat cards — same .stats-grid as dashboard */
        .stats-grid .stat-card { border-top: 3px solid transparent; }
        .stats-grid .stat-card.c-plum   { border-top-color: var(--rp); }
        .stats-grid .stat-card.c-green  { border-top-color: var(--rg); }
        .stats-grid .stat-card.c-gold   { border-top-color: var(--ro); }
        .stats-grid .stat-card.c-teal   { border-top-color: var(--rt); }
        .stats-grid .stat-card.c-red    { border-top-color: var(--rr); }

        /* tabs */
        .tab-nav {
            display:flex; gap:0.3rem;
            border-bottom:2px solid var(--border);
            margin-bottom:1.5rem;
        }
        .tab-btn {
            display:inline-flex; align-items:center; gap:0.4rem;
            padding:0.65rem 1.25rem;
            font-size:0.88rem; font-weight:600; color:var(--text-light);
            border:none; background:none; cursor:pointer;
            border-bottom:2px solid transparent; margin-bottom:-2px;
            transition:all 0.18s; border-radius:8px 8px 0 0;
            text-decoration:none;
        }
        .tab-btn:hover { color:var(--text); background:var(--bg); }
        .tab-btn.active { color:var(--primary); border-bottom-color:var(--primary); background:var(--primary-light); }
        .tab-count {
            font-size:0.7rem; font-weight:700;
            background:var(--rr); color:#fff;
            padding:0.1rem 0.45rem; border-radius:50px;
        }

        /* rewards card grid */
        .rewards-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(265px,1fr));
            gap:1.2rem;
        }
        .reward-item {
            background:var(--card-bg); border:1px solid var(--border);
            border-radius:var(--radius); overflow:hidden;
            transition:transform 0.2s,box-shadow 0.2s;
        }
        .reward-item:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,0.07); }
        .reward-item.inactive { opacity:0.52; }

        .ri-top {
            padding:1rem 1.2rem 0.8rem;
            border-bottom:1px solid var(--border);
            display:flex; align-items:flex-start; gap:0.85rem;
        }
        .ri-icon {
            width:44px; height:44px; flex-shrink:0;
            border-radius:12px; display:flex;
            align-items:center; justify-content:center; font-size:1.3rem;
        }
        .ri-meta { flex:1; min-width:0; }
        .ri-badges { display:flex; gap:0.35rem; flex-wrap:wrap; margin-bottom:0.35rem; }
        .ri-badge {
            font-size:0.68rem; font-weight:700;
            padding:0.15rem 0.52rem; border-radius:50px;
        }
        .ri-title {
            font-family:'Syne',sans-serif; font-size:0.94rem; font-weight:700;
            color:var(--text); margin-bottom:0.22rem;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .ri-desc {
            font-size:0.78rem; color:var(--text-light); line-height:1.5;
            display:-webkit-box; -webkit-line-clamp:2;
            -webkit-box-orient:vertical; overflow:hidden;
        }
        .ri-foot {
            padding:0.75rem 1.2rem;
            display:flex; align-items:center; justify-content:space-between; gap:0.5rem;
        }
        .ri-pts {
            font-family:'Syne',sans-serif; font-size:1.08rem; font-weight:800;
            color:var(--primary); display:flex; align-items:baseline; gap:0.25rem;
        }
        .ri-pts small { font-size:0.7rem; font-weight:500; color:var(--text-light); font-family:'DM Sans',sans-serif; }
        .ri-val {
            font-size:0.75rem; font-weight:700;
            padding:0.18rem 0.6rem; border-radius:50px;
            background:var(--ro-lt); color:var(--ro);
        }
        .ri-acts { display:flex; gap:0.35rem; }
        .tbl-btn {
            width:30px; height:30px; border-radius:7px;
            border:1px solid var(--border); background:var(--bg);
            display:inline-flex; align-items:center; justify-content:center;
            font-size:0.85rem; cursor:pointer; transition:all 0.15s; text-decoration:none;
        }
        .tbl-btn:hover { border-color:var(--primary); background:var(--primary-light); }
        .tbl-btn.del:hover { border-color:var(--rr); background:var(--rr-lt); }

        /* form */
        .form-card {
            background:var(--card-bg); border:1px solid var(--border);
            border-radius:var(--radius); overflow:hidden;
        }
        .form-card-head {
            padding:1rem 1.5rem; border-bottom:1px solid var(--border);
            background:linear-gradient(135deg,var(--rp-lt),var(--rt-lt));
            display:flex; align-items:center; justify-content:space-between;
        }
        .form-card-head h3 {
            font-family:'Syne',sans-serif; font-size:1rem; font-weight:700;
            color:var(--text); margin:0;
        }
        .form-card-body { padding:1.5rem; }
        .frow {
            display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;
        }
        .frow.full  { grid-template-columns:1fr; }
        .frow.quad  { grid-template-columns:1fr 1fr 1fr 1fr; }
        @media(max-width:800px){ .frow.quad{ grid-template-columns:1fr 1fr; } }
        @media(max-width:600px){ .frow{ grid-template-columns:1fr; } }
        .fg label {
            display:block; font-size:0.8rem; font-weight:600;
            color:var(--text); margin-bottom:0.4rem; letter-spacing:0.02em;
        }
        .fg input,.fg select,.fg textarea {
            width:100%; padding:0.65rem 0.9rem;
            border:1.5px solid var(--border); border-radius:var(--radius-sm);
            background:var(--bg); color:var(--text);
            font-size:0.88rem; font-family:'DM Sans',sans-serif;
            transition:border-color 0.18s; box-sizing:border-box;
        }
        .fg input:focus,.fg select:focus,.fg textarea:focus { outline:none; border-color:var(--primary); }
        .fg textarea { resize:vertical; min-height:72px; }
        .tog-row {
            display:flex; align-items:center; gap:0.65rem;
            font-size:0.88rem; font-weight:500; color:var(--text);
            cursor:pointer; user-select:none;
        }
        .tog-row input { display:none; }
        .tog-track {
            width:38px; height:20px; border-radius:10px;
            background:var(--border); position:relative; transition:background 0.2s; flex-shrink:0;
        }
        .tog-track::after {
            content:''; position:absolute; top:2px; left:2px;
            width:16px; height:16px; border-radius:50%;
            background:#fff; transition:transform 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.2);
        }
        .tog-row input:checked + .tog-track { background:var(--rg); }
        .tog-row input:checked + .tog-track::after { transform:translateX(18px); }
        .btn-save {
            padding:0.7rem 1.8rem; background:var(--primary); color:#fff;
            border:none; border-radius:var(--radius-sm);
            font-size:0.9rem; font-weight:700; font-family:'Syne',sans-serif;
            cursor:pointer; transition:all 0.18s;
        }
        .btn-save:hover { opacity:0.88; transform:translateY(-1px); }
        .btn-cancel {
            padding:0.68rem 1.3rem; background:var(--bg); color:var(--text-light);
            border:1.5px solid var(--border); border-radius:var(--radius-sm);
            font-size:0.88rem; font-weight:600; text-decoration:none; transition:all 0.15s;
            cursor:pointer; display:inline-block;
        }
        .btn-cancel:hover { border-color:var(--text-light); color:var(--text); }

        /* redemptions */
        .pill { font-size:0.76rem; font-weight:700; padding:0.22rem 0.65rem; border-radius:50px; display:inline-block; }
        .pill-active  { background:var(--rg-lt); color:var(--rg); }
        .pill-used    { background:var(--border); color:var(--text-light); }
        .pill-expired { background:var(--rr-lt); color:var(--rr); }
        .coupon-pill {
            font-family:'Syne',monospace; font-size:0.79rem; font-weight:700;
            background:var(--bg); border:1px dashed var(--border);
            padding:0.18rem 0.55rem; border-radius:6px;
            color:var(--primary); letter-spacing:0.05em;
        }

        /* alert */
        .alert { padding:0.85rem 1rem; border-radius:var(--radius-sm); font-size:0.88rem; margin-bottom:1.2rem; }
        .alert-success { background:var(--rg-lt); color:var(--rg); border:1px solid rgba(16,185,129,0.25); }
        .alert-error   { background:var(--rr-lt); color:var(--rr); border:1px solid rgba(239,68,68,0.25); }

        .empty-hint { text-align:center; padding:3rem 2rem; color:var(--text-light); font-size:0.9rem; }
        .empty-hint .ei { font-size:2.5rem; margin-bottom:0.7rem; }
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
                <h1>🎁 Récompenses fidélité</h1>
                <p>Créez et gérez les récompenses échangeables par vos clients.</p>
            </div>
            <div style="display:flex; gap:0.8rem; align-items:center;">
                <span style="font-size:0.85rem; color:var(--text-light);"><?= date('d/m/Y') ?></span>
                <a href="?tab=add" class="btn-primary" style="padding:0.6rem 1.2rem; font-size:0.88rem;">
                    + Nouvelle récompense
                </a>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- STATS — same .stats-grid + .stat-card structure as dashboard -->
        <div class="stats-grid">
            <div class="stat-card c-plum">
                <div class="stat-icon" style="background:var(--rp-lt); color:var(--rp);">🎁</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['total_rewards'] ?></div>
                    <div class="stat-label">Total récompenses</div>
                </div>
            </div>
            <div class="stat-card c-green">
                <div class="stat-icon" style="background:var(--rg-lt); color:var(--rg);">✅</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['active_rewards'] ?></div>
                    <div class="stat-label">Actives</div>
                </div>
                <a href="?tab=rewards" style="font-size:0.75rem; color:var(--rg); font-weight:600;">Voir →</a>
            </div>
            <div class="stat-card c-gold">
                <div class="stat-icon" style="background:var(--ro-lt); color:var(--ro);">🎟️</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['total_redeemed'] ?></div>
                    <div class="stat-label">Échanges totaux</div>
                </div>
            </div>
            <div class="stat-card c-teal">
                <div class="stat-icon" style="background:var(--rt-lt); color:var(--rt);">🔑</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['active_coupons'] ?></div>
                    <div class="stat-label">Coupons actifs</div>
                </div>
                <?php if ($stats['active_coupons'] > 0): ?>
                <a href="?tab=redemptions" style="font-size:0.75rem; color:var(--rt); font-weight:600;">Voir →</a>
                <?php endif; ?>
            </div>
            <div class="stat-card c-red">
                <div class="stat-icon" style="background:var(--rr-lt); color:var(--rr);">✔️</div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['used_coupons'] ?></div>
                    <div class="stat-label">Coupons utilisés</div>
                </div>
            </div>
            <div class="stat-card c-green">
                <div class="stat-icon" style="background:var(--rg-lt); color:var(--rg);">⭐</div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['points_spent']) ?></div>
                    <div class="stat-label">Points dépensés</div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="tab-nav">
            <a href="?tab=rewards"     class="tab-btn <?= $tab==='rewards'     ?'active':'' ?>">🎁 Récompenses</a>
            <a href="?tab=add"         class="tab-btn <?= $tab==='add'         ?'active':'' ?>">➕ <?= $editing?'Modifier':'Ajouter' ?></a>
            <a href="?tab=redemptions" class="tab-btn <?= $tab==='redemptions' ?'active':'' ?>">
                🎟️ Échanges
                <?php if ($stats['active_coupons'] > 0): ?>
                <span class="tab-count"><?= $stats['active_coupons'] ?></span>
                <?php endif; ?>
            </a>
        </div>

        <?php if ($tab === 'rewards'): ?>
        <!-- ════════════ REWARDS LIST ════════════ -->
        <?php if (empty($rewards)): ?>
        <div class="empty-hint">
            <div class="ei">🎁</div>
            <div>Aucune récompense pour l'instant.</div>
            <a href="?tab=add" style="display:inline-block;margin-top:1rem;color:var(--primary);font-weight:600;">+ Créer la première →</a>
        </div>
        <?php else: ?>
        <div class="rewards-grid">
            <?php
            $typeMap = [
                'discount'   => ['🏷️','Réduction',   'var(--rg-lt)','var(--rg)','background:var(--rg-lt);color:var(--rg)'],
                'free_night' => ['🌙','Nuit gratuite','var(--rp-lt)','var(--rp)','background:var(--rp-lt);color:var(--rp)'],
                'free_surf'  => ['🏄','Surf gratuit', 'var(--rt-lt)','var(--rt)','background:var(--rt-lt);color:var(--rt)'],
                'coupon'     => ['🎟️','Coupon',        'var(--ro-lt)','var(--ro)','background:var(--ro-lt);color:var(--ro)'],
            ];
            foreach ($rewards as $r):
                [$icon,$typeLabel,$iconBg,$iconColor,$badgeStyle] = $typeMap[$r['type']] ?? ['🎁','Autre','var(--ro-lt)','var(--ro)','background:var(--ro-lt);color:var(--ro)'];
                $valueStr = $r['value_type']==='percent' ? number_format($r['value'],0).'%' : number_format($r['value'],0).' MAD';
            ?>
            <div class="reward-item <?= $r['is_active']?'':'inactive' ?>">
                <div class="ri-top">
                    <div class="ri-icon" style="background:<?= $iconBg ?>;color:<?= $iconColor ?>;"><?= $icon ?></div>
                    <div class="ri-meta">
                        <div class="ri-badges">
                            <span class="ri-badge" style="<?= $badgeStyle ?>"><?= $typeLabel ?></span>
                            <span class="ri-badge" style="<?= $r['is_active'] ? 'background:var(--rg-lt);color:var(--rg)' : 'background:var(--rr-lt);color:var(--rr)' ?>">
                                <?= $r['is_active']?'● Actif':'● Inactif' ?>
                            </span>
                            <?php if ($r['stock']!==null): ?>
                            <span class="ri-badge" style="background:var(--primary-light);color:var(--primary);">Stock: <?= $r['stock'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="ri-title"><?= htmlspecialchars($r['title']) ?></div>
                        <div class="ri-desc"><?= htmlspecialchars($r['description']??'') ?></div>
                    </div>
                </div>
                <div class="ri-foot">
                    <div style="display:flex;align-items:center;gap:0.55rem;flex-wrap:wrap;">
                        <div class="ri-pts">⭐ <?= number_format($r['points_cost']) ?> <small>pts</small></div>
                        <span class="ri-val">= <?= $valueStr ?></span>
                    </div>
                    <div class="ri-acts">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_reward">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="tbl-btn" title="<?= $r['is_active']?'Désactiver':'Activer' ?>"><?= $r['is_active']?'⏸️':'▶️' ?></button>
                        </form>
                        <a href="?edit=<?= $r['id'] ?>" class="tbl-btn" title="Modifier">✏️</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette récompense ?')">
                            <input type="hidden" name="action" value="delete_reward">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="tbl-btn del" title="Supprimer">🗑️</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php elseif ($tab === 'add'): ?>
        <!-- ════════════ ADD / EDIT FORM ════════════ -->
        <div class="form-card">
            <div class="form-card-head">
                <h3><?= $editing?'✏️ Modifier la récompense':'➕ Nouvelle récompense' ?></h3>
                <a href="?tab=rewards" class="btn-cancel">Annuler</a>
            </div>
            <div class="form-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_reward">
                    <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

                    <div class="frow">
                        <div class="fg">
                            <label>🏷️ Titre *</label>
                            <input type="text" name="title" placeholder="Ex: Réduction 10%" required value="<?= htmlspecialchars($editing['title']??'') ?>">
                        </div>
                        <div class="fg">
                            <label>📂 Type *</label>
                            <select name="type">
                                <?php foreach(['discount'=>'🏷️ Réduction','free_night'=>'🌙 Nuit gratuite','free_surf'=>'🏄 Surf gratuit','coupon'=>'🎟️ Coupon'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= ($editing['type']??'')===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="frow full">
                        <div class="fg">
                            <label>📝 Description</label>
                            <textarea name="description" placeholder="Décrivez cette récompense..."><?= htmlspecialchars($editing['description']??'') ?></textarea>
                        </div>
                    </div>
                    <div class="frow quad">
                        <div class="fg">
                            <label>⭐ Points *</label>
                            <input type="number" name="points_cost" min="1" placeholder="100" required value="<?= $editing['points_cost']??'' ?>">
                        </div>
                        <div class="fg">
                            <label>💰 Valeur *</label>
                            <input type="number" name="value" step="0.01" min="0.01" placeholder="10" required value="<?= $editing['value']??'' ?>">
                        </div>
                        <div class="fg">
                            <label>📐 Type valeur</label>
                            <select name="value_type">
                                <option value="percent" <?= ($editing['value_type']??'')==='percent'?'selected':'' ?>>% Pourcentage</option>
                                <option value="fixed"   <?= ($editing['value_type']??'')==='fixed'  ?'selected':'' ?>>MAD Fixe</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>📦 Stock <small style="font-weight:400;color:var(--text-light);">(vide=∞)</small></label>
                            <input type="number" name="stock" min="0" placeholder="Illimité" value="<?= $editing['stock']??'' ?>">
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-top:1.2rem;">
                        <label class="tog-row">
                            <input type="checkbox" name="is_active" <?= ($editing['is_active']??1)?'checked':'' ?>>
                            <span class="tog-track"></span>
                            Récompense active
                        </label>
                        <div style="display:flex;gap:0.7rem;align-items:center;">
                            <a href="?tab=rewards" class="btn-cancel">Annuler</a>
                            <button type="submit" class="btn-save"><?= $editing?'💾 Enregistrer':'➕ Créer' ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php elseif ($tab === 'redemptions'): ?>
        <!-- ════════════ REDEMPTIONS ════════════ -->
        <div class="card">
            <div class="card-header">
                <h3>🎟️ Historique des échanges</h3>
                <span style="font-size:0.8rem;color:var(--text-light);">50 derniers échanges</span>
            </div>
            <?php if (empty($redemptions)): ?>
            <div class="empty-hint">
                <div class="ei">🎟️</div>
                <div>Aucun échange pour l'instant.</div>
            </div>
            <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Membre</th><th>Récompense</th>
                            <th>Code coupon</th><th>Points</th>
                            <th>Statut</th><th>Expire le</th><th>Date</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($redemptions as $rd): ?>
                        <tr>
                            <td style="font-size:0.8rem;color:var(--text-light);">#<?= $rd['id'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:0.6rem;">
                                    <div style="width:32px;height:32px;border-radius:50%;background:var(--color-info-bg);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:0.85rem;flex-shrink:0;">
                                        <?= strtoupper(substr($rd['user_name']??'?',0,1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:0.86rem;"><?= htmlspecialchars($rd['user_name']??'N/A') ?></div>
                                        <div style="font-size:0.75rem;color:var(--text-light);"><?= htmlspecialchars($rd['user_email']??'') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:0.84rem;font-weight:500;"><?= htmlspecialchars($rd['reward_title']??'—') ?></td>
                            <td><span class="coupon-pill"><?= htmlspecialchars($rd['coupon_code']) ?></span></td>
                            <td style="font-weight:800;font-size:0.9rem;color:var(--primary);font-family:'Syne',sans-serif;">⭐ <?= number_format($rd['points_used']) ?></td>
                            <td>
                                <span class="pill pill-<?= $rd['status'] ?>">
                                    <?= $rd['status']==='active'?'🔑 Actif':($rd['status']==='used'?'✔️ Utilisé':'⏰ Expiré') ?>
                                </span>
                            </td>
                            <td style="font-size:0.8rem;color:var(--text-light);"><?= $rd['expires_at']?date('d/m/Y',strtotime($rd['expires_at'])):'—' ?></td>
                            <td style="font-size:0.8rem;color:var(--text-light);"><?= date('d/m/Y H:i',strtotime($rd['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:flex;gap:0.3rem;align-items:center;">
                                    <input type="hidden" name="action" value="update_redemption">
                                    <input type="hidden" name="id" value="<?= $rd['id'] ?>">
                                    <select name="status" style="font-size:0.78rem;padding:0.28rem 0.5rem;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);">
                                        <option value="active"  <?= $rd['status']==='active' ?'selected':'' ?>>Actif</option>
                                        <option value="used"    <?= $rd['status']==='used'   ?'selected':'' ?>>Utilisé</option>
                                        <option value="expired" <?= $rd['status']==='expired'?'selected':'' ?>>Expiré</option>
                                    </select>
                                    <button type="submit" class="tbl-btn" style="width:auto;padding:0 0.6rem;font-size:0.74rem;font-weight:700;">OK</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</main>
</div>
</body>
</html>