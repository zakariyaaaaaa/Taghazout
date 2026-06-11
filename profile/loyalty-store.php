<?php
require_once '../includes/header.php';
require_once '../includes/config.php';

// ─── Auth check ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode('profile/loyalty-store.php'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ─── Fetch user points ────────────────────────────────────
$stmt = $pdo->prepare("SELECT name, loyalty_points FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user    = $stmt->fetch();
$points  = (int)($user['loyalty_points'] ?? 0);

// ─── Fetch rewards ────────────────────────────────────────
$rewards = $pdo->query("
    SELECT * FROM loyalty_rewards
    WHERE is_active = 1
    ORDER BY points_cost ASC
")->fetchAll();

// ─── Fetch user's coupons (last 10) ──────────────────────
$stmt = $pdo->prepare("
    SELECT lr.*, rw.title, rw.type, rw.value, rw.value_type
    FROM loyalty_redemptions lr
    JOIN loyalty_rewards rw ON lr.reward_id = rw.id
    WHERE lr.user_id = ?
    ORDER BY lr.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$my_coupons = $stmt->fetchAll();

// ─── Flash messages ───────────────────────────────────────
$success = isset($_GET['redeemed']) ? 'Récompense échangée avec succès ! Votre coupon est prêt.' : '';
$error   = isset($_GET['error'])    ? htmlspecialchars(urldecode($_GET['error'])) : '';

// ─── Helpers ──────────────────────────────────────────────
$type_icons = [
    'discount'   => '🎫',
    'free_night' => '🏨',
    'free_surf'  => '🏄',
    'coupon'     => '🎁',
];
$type_labels = [
    'discount'   => 'Réduction',
    'free_night' => 'Nuit gratuite',
    'free_surf'  => 'Surf gratuit',
    'coupon'     => 'Coupon',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique Fidélité — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <style>
        :root {
            --ocean-deep: #0a1628;
            --ocean-teal: #0e7c6b;
            --ocean-foam: #3fb8a0;
            --sand-warm:  #c8965a;
            --sand-light: #f0d9b5;
            --bg:         #f0f4f8;
            --card:       #ffffff;
            --radius-sm:  8px;
            --radius-md:  14px;
            --radius-lg:  20px;
            --radius-xl:  26px;
            --shadow-sm:  0 2px 10px rgba(10,22,40,0.06);
            --shadow-md:  0 6px 28px rgba(10,22,40,0.10);
            --shadow-lg:  0 16px 48px rgba(10,22,40,0.13);
            --ease:       cubic-bezier(0.4,0,0.2,1);
        }

        *, *::before, *::after { box-sizing: border-box; }
        body {
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            color: var(--ocean-deep);
            -webkit-font-smoothing: antialiased;
            margin: 0;
        }

        /* ════════════════════════════════
           HERO
        ════════════════════════════════ */
        .loy-hero {
            background: linear-gradient(135deg, #07101f 0%, #0b1d36 45%, #0d2e52 100%);
            position: relative;
            overflow: hidden;
            padding: 4rem 2rem 6rem;
            text-align: center;
        }
        .loy-hero::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 65% 65% at 12% 115%, rgba(63,184,160,0.28) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 88% -5%,  rgba(200,150,90,0.18) 0%, transparent 55%),
                radial-gradient(ellipse 40% 40% at 50% 50%,  rgba(14,124,107,0.06) 0%, transparent 70%);
        }
        /* wave bottom */
        .loy-hero::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 64px;
            background: var(--bg);
            clip-path: ellipse(56% 100% at 50% 100%);
        }
        .loy-hero-inner { position: relative; z-index: 1; }

        .loy-hero-eyebrow {
            display: inline-flex; align-items: center; gap: .4rem;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 50px;
            padding: .3rem .9rem;
            font-size: .72rem; font-weight: 700;
            color: rgba(255,255,255,0.6);
            letter-spacing: .1em; text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .loy-hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.9rem, 4.5vw, 2.8rem);
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.03em;
            margin: 0 0 .5rem;
            line-height: 1.1;
        }
        .loy-hero p {
            color: rgba(255,255,255,0.55);
            font-size: .92rem;
            margin: 0 0 2rem;
        }

        /* Points badge */
        .pts-badge {
            display: inline-flex; align-items: center; gap: 1rem;
            background: rgba(255,255,255,0.09);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 60px;
            padding: .75rem 1.8rem;
            margin-bottom: .8rem;
        }
        .pts-badge-icon { font-size: 1.5rem; }
        .pts-badge-body { text-align: left; }
        .pts-badge-num {
            font-family: 'Syne', sans-serif;
            font-size: 1.75rem; font-weight: 800;
            color: #fff; line-height: 1;
            letter-spacing: -0.02em;
        }
        .pts-badge-lbl {
            font-size: .68rem; color: rgba(255,255,255,0.5);
            text-transform: uppercase; letter-spacing: .1em;
            margin-top: .1rem;
        }

        /* hero stats strip */
        .hero-strip {
            display: flex; justify-content: center; flex-wrap: wrap; gap: .5rem;
            margin-top: .8rem;
        }
        .hero-chip {
            display: flex; align-items: center; gap: .35rem;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: .3rem .85rem;
            font-size: .75rem; color: rgba(255,255,255,0.65);
        }
        .hero-chip strong { color: #fff; }

        /* ════════════════════════════════
           LAYOUT
        ════════════════════════════════ */
        .store-wrap {
            max-width: 1120px;
            margin: 0 auto;
            padding: 2.2rem 1.5rem 5rem;
        }

        /* ════════════════════════════════
           ALERTS
        ════════════════════════════════ */
        .alert {
            display: flex; align-items: flex-start; gap: .75rem;
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            font-size: .88rem; font-weight: 500;
            margin-bottom: 1.5rem;
            animation: slideDown .3s var(--ease) both;
        }
        @keyframes slideDown {
            from { transform: translateY(-8px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .alert-success {
            background: #edfaf5;
            border: 1px solid rgba(14,124,107,.22);
            color: #0a5a45;
        }
        .alert-error {
            background: #fff2f2;
            border: 1px solid rgba(214,69,69,.2);
            color: #932020;
        }
        .alert-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: .05rem; }

        /* ════════════════════════════════
           SECTION HEADER
        ════════════════════════════════ */
        .sec-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.3rem; gap: 1rem;
        }
        .sec-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.15rem; font-weight: 800;
            color: var(--ocean-deep);
            letter-spacing: -0.02em;
            display: flex; align-items: center; gap: .5rem;
        }
        .sec-count {
            font-size: .72rem; font-weight: 700;
            background: var(--ocean-deep); color: #fff;
            padding: .15rem .55rem; border-radius: 50px;
            font-family: 'DM Sans', sans-serif;
        }

        /* filter tabs */
        .filter-tabs {
            display: flex; gap: .35rem; flex-wrap: wrap;
        }
        .filter-tab {
            padding: .3rem .8rem;
            border: 1.5px solid rgba(10,22,40,0.1);
            border-radius: 50px;
            font-size: .76rem; font-weight: 600;
            color: #7a90a4; background: var(--card);
            cursor: pointer; transition: all .15s var(--ease);
            user-select: none;
        }
        .filter-tab:hover { border-color: var(--ocean-teal); color: var(--ocean-teal); }
        .filter-tab.active {
            background: var(--ocean-deep); color: #fff;
            border-color: var(--ocean-deep);
        }

        /* ════════════════════════════════
           REWARDS GRID
        ════════════════════════════════ */
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(268px, 1fr));
            gap: 1.2rem;
            margin-bottom: 3rem;
        }

        .reward-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(10,22,40,0.07);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            display: flex; flex-direction: column;
            transition: transform .22s var(--ease), box-shadow .22s var(--ease);
        }
        .reward-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .reward-card.locked { opacity: .6; }
        .reward-card.locked:hover { transform: none; box-shadow: var(--shadow-sm); }
        .reward-card[data-hidden="1"] { display: none; }

        .rc-top {
            padding: 1.5rem 1.3rem 1.1rem;
            flex: 1;
        }

        /* icon + type badge row */
        .rc-head {
            display: flex; align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .rc-icon {
            width: 50px; height: 50px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--ocean-deep) 0%, #0d3060 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.45rem;
            box-shadow: 0 5px 14px rgba(10,22,40,0.18);
            flex-shrink: 0;
        }
        .rc-type-badge {
            font-size: .68rem; font-weight: 700;
            padding: .22rem .65rem; border-radius: 50px;
            background: rgba(14,124,107,0.08);
            color: var(--ocean-teal);
            letter-spacing: .04em;
            align-self: flex-start;
        }

        .rc-title {
            font-family: 'Syne', sans-serif;
            font-size: .98rem; font-weight: 800;
            color: var(--ocean-deep);
            letter-spacing: -0.01em;
            margin-bottom: .35rem;
        }
        .rc-desc {
            font-size: .8rem; color: #7a90a4;
            line-height: 1.6; margin-bottom: .85rem;
        }
        .rc-value {
            display: inline-flex; align-items: center; gap: .3rem;
            background: rgba(200,150,90,0.1);
            color: var(--sand-warm);
            border-radius: 50px;
            font-size: .76rem; font-weight: 800;
            padding: .22rem .75rem;
        }

        /* progress */
        .rc-progress { margin-top: .75rem; }
        .rc-prog-bar {
            height: 5px;
            background: rgba(10,22,40,0.07);
            border-radius: 5px; overflow: hidden;
            margin-bottom: .28rem;
        }
        .rc-prog-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--ocean-teal), var(--ocean-foam));
            border-radius: 5px;
            transition: width .7s var(--ease);
        }
        .rc-prog-lbl { font-size: .67rem; color: #b2c4d4; }

        /* stock badge */
        .rc-stock {
            display: inline-flex; align-items: center; gap: .25rem;
            font-size: .68rem; font-weight: 700;
            background: rgba(245,158,11,0.09);
            color: #b45309;
            padding: .18rem .6rem; border-radius: 50px;
            margin-top: .6rem;
        }

        .rc-bottom {
            padding: .9rem 1.3rem;
            border-top: 1px solid rgba(10,22,40,0.055);
            background: #fafcff;
            display: flex; align-items: center;
            justify-content: space-between; gap: .75rem;
        }
        .rc-cost {
            display: flex; align-items: baseline; gap: .3rem;
        }
        .rc-cost-num {
            font-family: 'Syne', sans-serif;
            font-size: 1.15rem; font-weight: 800;
            color: var(--sand-warm);
        }
        .rc-cost-lbl {
            font-size: .7rem; color: #7a90a4;
            text-transform: uppercase; letter-spacing: .07em;
        }

        /* buttons */
        .btn-redeem {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .5rem 1.1rem;
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: .82rem; font-weight: 700;
            border: none; cursor: pointer;
            transition: all .2s var(--ease);
            white-space: nowrap; text-decoration: none;
            line-height: 1;
        }
        .btn-redeem.can {
            background: linear-gradient(135deg, var(--ocean-deep), #0e5a50);
            color: #fff;
            box-shadow: 0 3px 12px rgba(14,124,107,0.28);
        }
        .btn-redeem.can:hover {
            opacity: .9; transform: translateY(-1px);
            box-shadow: 0 5px 18px rgba(14,124,107,0.36);
        }
        .btn-redeem.no {
            background: #f0f4f8; color: #b2c4d4; cursor: not-allowed;
        }
        .pts-needed {
            font-size: .7rem; color: #d64545; font-weight: 700; margin-top: .3rem;
        }

        /* ════════════════════════════════
           MY COUPONS
        ════════════════════════════════ */
        .coupons-list {
            display: flex; flex-direction: column; gap: .8rem;
        }

        .coupon-item {
            background: var(--card);
            border-radius: var(--radius-md);
            border: 1px solid rgba(10,22,40,0.07);
            box-shadow: var(--shadow-sm);
            display: flex; align-items: center; gap: 1rem;
            padding: 1rem 1.25rem;
            transition: box-shadow .18s var(--ease);
        }
        .coupon-item:hover { box-shadow: var(--shadow-md); }
        .coupon-item.used    { opacity: .65; }
        .coupon-item.expired { opacity: .5; }

        .ci-icon {
            width: 44px; height: 44px; border-radius: 11px;
            background: linear-gradient(135deg, var(--ocean-deep), #0d3060);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; flex-shrink: 0;
        }
        .ci-info { flex: 1; min-width: 0; }
        .ci-title {
            font-weight: 700; font-size: .88rem;
            color: var(--ocean-deep);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            margin-bottom: .18rem;
        }
        .ci-meta { font-size: .73rem; color: #7a90a4; }

        .ci-code-wrap {
            display: flex; align-items: center; gap: .5rem;
            background: #f0f4f8;
            border: 1px dashed rgba(10,22,40,0.12);
            border-radius: var(--radius-sm);
            padding: .42rem .9rem;
            flex-shrink: 0;
            cursor: pointer; transition: all .15s var(--ease);
        }
        .ci-code-wrap:hover { border-color: var(--ocean-teal); background: #edfaf5; }
        .ci-code {
            font-family: 'Courier New', monospace;
            font-size: .86rem; font-weight: 700;
            color: var(--ocean-deep); letter-spacing: .07em;
        }
        .ci-copy {
            background: none; border: none; cursor: pointer;
            font-size: .82rem; color: #7a90a4; padding: 0;
            transition: color .14s; line-height: 1;
        }
        .ci-copy:hover { color: var(--ocean-teal); }

        .ci-status {
            padding: .22rem .65rem; border-radius: 50px;
            font-size: .67rem; font-weight: 700;
            letter-spacing: .05em; text-transform: uppercase;
            flex-shrink: 0;
        }
        .ci-status.active   { background: #edfaf5; color: var(--ocean-teal); }
        .ci-status.used     { background: #f0f4f8; color: #7a90a4; }
        .ci-status.expired  { background: #fff2f2; color: #d64545; }

        /* empty state */
        .empty-box {
            text-align: center; padding: 3rem 2rem;
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(10,22,40,0.07);
            color: #7a90a4;
        }
        .empty-box .ei { font-size: 2.8rem; margin-bottom: .65rem; opacity: .35; }
        .empty-box p { font-size: .88rem; line-height: 1.6; margin: 0; }

        /* ════════════════════════════════
           RESPONSIVE
        ════════════════════════════════ */
        @media (max-width: 640px) {
            .rewards-grid { grid-template-columns: 1fr; }
            .loy-hero { padding: 2.8rem 1rem 5rem; }
            .store-wrap { padding: 1.5rem 1rem 4rem; }
            .coupon-item { flex-wrap: wrap; }
            .ci-code-wrap { width: 100%; justify-content: space-between; }
            .ci-status { align-self: flex-start; }
        }
    </style>
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<!-- ══════════════ HERO ══════════════ -->
<div class="loy-hero">
    <div class="loy-hero-inner">
        <div class="loy-hero-eyebrow">⭐ Programme de fidélité</div>
        <h1>Boutique Fidélité</h1>
        <p>Échangez vos points contre des récompenses exclusives</p>

        <div class="pts-badge">
            <span class="pts-badge-icon">🏅</span>
            <div class="pts-badge-body">
                <div class="pts-badge-num"><?= number_format($points) ?></div>
                <div class="pts-badge-lbl">Points disponibles</div>
            </div>
        </div>

        <div class="hero-strip">
            <div class="hero-chip">🎁 <strong><?= count($rewards) ?></strong> récompenses dispo</div>
            <?php $active_c = count(array_filter($my_coupons, fn($c) => $c['status'] === 'active')); ?>
            <?php if ($active_c > 0): ?>
            <div class="hero-chip">🎫 <strong><?= $active_c ?></strong> coupon<?= $active_c > 1 ? 's' : '' ?> actif<?= $active_c > 1 ? 's' : '' ?></div>
            <?php endif; ?>
            <?php
            $affordable = count(array_filter($rewards, fn($r) => $points >= $r['points_cost']));
            if ($affordable > 0):
            ?>
            <div class="hero-chip">✅ <strong><?= $affordable ?></strong> accessible<?= $affordable > 1 ? 's' : '' ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══════════════ BODY ══════════════ -->
<div class="store-wrap">

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success">
        <span class="alert-icon">✅</span>
        <div><?= $success ?></div>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error">
        <span class="alert-icon">❌</span>
        <div><?= $error ?></div>
    </div>
    <?php endif; ?>

    <!-- ── REWARDS ── -->
    <div class="sec-head">
        <div class="sec-title">
            🎁 Récompenses disponibles
            <span class="sec-count"><?= count($rewards) ?></span>
        </div>
        <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterCards('all',this)">Tout</div>
            <div class="filter-tab" onclick="filterCards('discount',this)">🎫 Réduction</div>
            <div class="filter-tab" onclick="filterCards('free_night',this)">🏨 Nuit</div>
            <div class="filter-tab" onclick="filterCards('free_surf',this)">🏄 Surf</div>
            <div class="filter-tab" onclick="filterCards('coupon',this)">🎁 Coupon</div>
        </div>
    </div>

    <?php if (empty($rewards)): ?>
    <div class="empty-box" style="margin-bottom:3rem;">
        <div class="ei">🎁</div>
        <p>Aucune récompense disponible pour l'instant.<br>Revenez bientôt !</p>
    </div>
    <?php else: ?>
    <div class="rewards-grid" id="rewardsGrid">
        <?php foreach ($rewards as $r):
            $can_afford  = $points >= $r['points_cost'];
            $percent     = $r['points_cost'] > 0 ? min(100, round(($points / $r['points_cost']) * 100)) : 100;
            $missing     = $r['points_cost'] - $points;
            $value_label = $r['value_type'] === 'percent'
                ? number_format($r['value'], 0) . '%'
                : number_format($r['value'], 0, ',', ' ') . ' MAD';
            $icon        = $type_icons[$r['type']]  ?? '🎁';
            $type_lbl    = $type_labels[$r['type']] ?? 'Offre';
        ?>
        <div class="reward-card <?= $can_afford ? '' : 'locked' ?>" data-type="<?= $r['type'] ?>">
            <div class="rc-top">
                <div class="rc-head">
                    <div class="rc-icon"><?= $icon ?></div>
                    <span class="rc-type-badge"><?= $type_lbl ?></span>
                </div>
                <div class="rc-title"><?= htmlspecialchars($r['title']) ?></div>
                <div class="rc-desc"><?= htmlspecialchars($r['description'] ?? '') ?></div>
                <div class="rc-value">✨ <?= $value_label ?></div>

                <?php if ($r['stock'] !== null && $r['stock'] <= 10): ?>
                <div class="rc-stock">⚠️ Plus que <?= $r['stock'] ?> disponible<?= $r['stock'] > 1 ? 's' : '' ?></div>
                <?php endif; ?>

                <?php if (!$can_afford): ?>
                <div class="rc-progress">
                    <div class="rc-prog-bar">
                        <div class="rc-prog-fill" style="width:<?= $percent ?>%"></div>
                    </div>
                    <div class="rc-prog-lbl"><?= $percent ?>% — encore <?= number_format($missing) ?> pts nécessaires</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="rc-bottom">
                <div class="rc-cost">
                    <span class="rc-cost-num"><?= number_format($r['points_cost']) ?></span>
                    <span class="rc-cost-lbl">pts</span>
                </div>

                <?php if ($can_afford): ?>
                <form method="POST" action="redeem-points.php"
                      onsubmit="return confirm('Utiliser <?= $r['points_cost'] ?> points pour «\u00a0<?= addslashes(htmlspecialchars($r['title'])) ?>\u00a0» ?')">
                    <input type="hidden" name="reward_id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn-redeem can">🎫 Échanger</button>
                </form>
                <?php else: ?>
                <div style="text-align:right;">
                    <div class="btn-redeem no">🔒 Verrouillé</div>
                    <div class="pts-needed">−<?= number_format($missing) ?> pts</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── MY COUPONS ── -->
    <div class="sec-head">
        <div class="sec-title">
            🎫 Mes coupons
            <?php if (!empty($my_coupons)): ?>
            <span class="sec-count"><?= count($my_coupons) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($my_coupons)): ?>
    <div class="empty-box">
        <div class="ei">🎫</div>
        <p>Vous n'avez pas encore de coupons.<br>Échangez vos points ci-dessus pour en obtenir !</p>
    </div>
    <?php else: ?>
    <div class="coupons-list">
        <?php foreach ($my_coupons as $c):
            $expires  = $c['expires_at'] ? date('d/m/Y', strtotime($c['expires_at'])) : '—';
            $c_icon   = $type_icons[$c['type']] ?? '🎁';
            $statuses = ['active' => 'Actif', 'used' => 'Utilisé', 'expired' => 'Expiré'];
        ?>
        <div class="coupon-item <?= $c['status'] ?>">
            <div class="ci-icon"><?= $c_icon ?></div>
            <div class="ci-info">
                <div class="ci-title"><?= htmlspecialchars($c['title']) ?></div>
                <div class="ci-meta">
                    ⭐ <?= number_format($c['points_used']) ?> pts
                    · 📅 Expire : <?= $expires ?>
                </div>
            </div>
            <div class="ci-code-wrap" onclick="copyCoupon('<?= htmlspecialchars($c['coupon_code']) ?>', 'copy-<?= $c['id'] ?>')">
                <span class="ci-code"><?= htmlspecialchars($c['coupon_code']) ?></span>
                <button class="ci-copy" id="copy-<?= $c['id'] ?>" title="Copier">📋</button>
            </div>
            <span class="ci-status <?= $c['status'] ?>"><?= $statuses[$c['status']] ?? $c['status'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
<script src="assets/js/navbar.js"></script>
<script>
// ── Filter tabs ──────────────────────────────────────────
function filterCards(type, tab) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('#rewardsGrid .reward-card').forEach(card => {
        card.dataset.hidden = (type !== 'all' && card.dataset.type !== type) ? '1' : '0';
    });
}

// ── Copy coupon code ─────────────────────────────────────
function copyCoupon(code, btnId) {
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.textContent = '✅';
        setTimeout(() => btn.textContent = '📋', 2200);
    });
}
</script>
</body>
</html>