<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// ─── Auth check ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php?redirect=loyalty-store.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ─── Fetch user points ────────────────────────────────────
$user = $pdo->prepare("SELECT name, loyalty_points FROM users WHERE id = :id");
$user->execute([':id' => $user_id]);
$user = $user->fetch();
$points = (int)($user['loyalty_points'] ?? 0);

// ─── Fetch rewards ────────────────────────────────────────
$rewards = $pdo->query("
    SELECT * FROM loyalty_rewards
    WHERE is_active = 1
    ORDER BY points_cost ASC
")->fetchAll();

// ─── Fetch user's active coupons ──────────────────────────
$my_coupons = $pdo->prepare("
    SELECT lr.*, r.title, r.type, r.value, r.value_type
    FROM loyalty_redemptions lr
    JOIN loyalty_rewards r ON lr.reward_id = r.id
    WHERE lr.user_id = :uid
    ORDER BY lr.created_at DESC
    LIMIT 5
");
$my_coupons->execute([':uid' => $user_id]);
$my_coupons = $my_coupons->fetchAll();

// ─── Flash messages ───────────────────────────────────────
$success = isset($_GET['redeemed'])  ? 'Récompense échangée avec succès ! Votre coupon est prêt.' : '';
$error   = isset($_GET['error'])     ? htmlspecialchars($_GET['error']) : '';

// ─── Type icons & labels ──────────────────────────────────
$type_icons = [
    'discount'   => '🎫',
    'free_night' => '🏨',
    'free_surf'  => '🏄',
    'coupon'     => '🎁',
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
        /* ── Page vars ── */
        :root {
            --ocean-deep:  #0a1628;
            --ocean-teal:  #0e7c6b;
            --ocean-foam:  #3fb8a0;
            --sand-warm:   #c8965a;
            --sand-light:  #f0d9b5;
            --bg:          #f0f4f8;
            --card:        #ffffff;
            --radius-md:   14px;
            --radius-lg:   20px;
            --radius-xl:   26px;
            --shadow-sm:   0 2px 10px rgba(10,22,40,0.06);
            --shadow-md:   0 6px 28px rgba(10,22,40,0.10);
            --transition:  0.22s cubic-bezier(0.4,0,0.2,1);
        }

        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: #0a1628; -webkit-font-smoothing: antialiased; }

        /* ── Hero ── */
        .loyalty-hero {
            background: linear-gradient(135deg, var(--ocean-deep) 0%, #0d2240 50%, #0e3a60 100%);
            position: relative;
            overflow: hidden;
            padding: 3.5rem 2rem 5rem;
            text-align: center;
        }

        .loyalty-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 15% 110%, rgba(63,184,160,0.3) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 85% -10%, rgba(200,150,90,0.2) 0%, transparent 55%);
        }

        .loyalty-hero::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 60px;
            background: var(--bg);
            clip-path: ellipse(55% 100% at 50% 100%);
        }

        .loyalty-hero-inner { position: relative; z-index: 1; }

        .loyalty-hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.03em;
            margin-bottom: .5rem;
        }

        .loyalty-hero p { color: rgba(255,255,255,0.6); font-size: .95rem; margin-bottom: 1.5rem; }

        /* Points badge */
        .points-badge {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 50px;
            padding: .65rem 1.4rem;
        }

        .points-badge-icon { font-size: 1.4rem; }

        .points-badge-num {
            font-family: 'Syne', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .points-badge-label { font-size: .75rem; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: .08em; }

        /* ── Layout ── */
        .store-layout {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }

        /* ── Section title ── */
        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ocean-deep);
            letter-spacing: -0.02em;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        /* ── Alerts ── */
        .alert-success {
            background: #edfaf5;
            border: 1px solid rgba(14,124,107,0.2);
            color: #0a5a45;
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            font-size: .9rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background: #fff2f2;
            border: 1px solid rgba(214,69,69,0.2);
            color: #932020;
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            font-size: .9rem;
            margin-bottom: 1.5rem;
        }

        /* ── Rewards grid ── */
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }

        .reward-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(10,22,40,0.07);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: transform var(--transition), box-shadow var(--transition);
            display: flex;
            flex-direction: column;
        }

        .reward-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .reward-card.locked { opacity: .65; }
        .reward-card.locked:hover { transform: none; box-shadow: var(--shadow-sm); }

        /* Card top */
        .reward-card-top {
            padding: 1.5rem 1.25rem 1rem;
            flex: 1;
        }

        .reward-type-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--ocean-deep), #0d3060);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(10,22,40,0.2);
        }

        .reward-title {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--ocean-deep);
            margin-bottom: .4rem;
            letter-spacing: -0.01em;
        }

        .reward-desc {
            font-size: .82rem;
            color: #7a90a4;
            line-height: 1.6;
            margin-bottom: .85rem;
        }

        .reward-value {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: rgba(14,124,107,0.08);
            color: var(--ocean-teal);
            border-radius: 50px;
            font-size: .78rem;
            font-weight: 700;
            padding: .25rem .75rem;
            letter-spacing: .03em;
        }

        /* Card bottom */
        .reward-card-bottom {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(10,22,40,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            background: #fafcff;
        }

        .reward-cost {
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .reward-cost-num {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--sand-warm);
        }

        .reward-cost-label {
            font-size: .72rem;
            color: #7a90a4;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        /* Redeem button */
        .btn-redeem {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all var(--transition);
            white-space: nowrap;
            text-decoration: none;
        }

        .btn-redeem.available {
            background: linear-gradient(135deg, var(--ocean-deep), var(--ocean-teal));
            color: #fff;
            box-shadow: 0 3px 12px rgba(14,124,107,0.25);
        }

        .btn-redeem.available:hover {
            opacity: .9;
            transform: translateY(-1px);
            box-shadow: 0 5px 18px rgba(14,124,107,0.35);
        }

        .btn-redeem.locked {
            background: #f0f4f8;
            color: #b2c4d4;
            cursor: not-allowed;
        }

        .points-needed {
            font-size: .72rem;
            color: #d64545;
            font-weight: 600;
        }

        /* Progress bar */
        .reward-progress {
            margin-top: .5rem;
        }

        .progress-bar-wrap {
            height: 4px;
            background: rgba(10,22,40,0.08);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: .25rem;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--ocean-teal), var(--ocean-foam));
            border-radius: 4px;
            transition: width .6s ease;
        }

        .progress-label {
            font-size: .68rem;
            color: #b2c4d4;
        }

        /* ── My coupons ── */
        .coupons-list {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }

        .coupon-item {
            background: var(--card);
            border-radius: var(--radius-md);
            border: 1px solid rgba(10,22,40,0.07);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
        }

        .coupon-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--ocean-deep), #0d3060);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .coupon-info { flex: 1; min-width: 0; }

        .coupon-title {
            font-weight: 600;
            font-size: .9rem;
            color: var(--ocean-deep);
            margin-bottom: .2rem;
        }

        .coupon-meta {
            font-size: .75rem;
            color: #7a90a4;
        }

        .coupon-code-wrap {
            display: flex;
            align-items: center;
            gap: .5rem;
            background: #f0f4f8;
            border-radius: 8px;
            padding: .4rem .85rem;
            flex-shrink: 0;
        }

        .coupon-code {
            font-family: 'Courier New', monospace;
            font-size: .88rem;
            font-weight: 700;
            color: var(--ocean-deep);
            letter-spacing: .06em;
        }

        .copy-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: .85rem;
            padding: 0;
            color: #7a90a4;
            transition: color var(--transition);
        }

        .copy-btn:hover { color: var(--ocean-teal); }

        .coupon-status {
            display: inline-block;
            padding: .2rem .6rem;
            border-radius: 50px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .coupon-status.active   { background: #edfaf5; color: var(--ocean-teal); }
        .coupon-status.used     { background: #f0f4f8; color: #7a90a4; }
        .coupon-status.expired  { background: #fff2f2; color: #d64545; }

        /* ── Empty state ── */
        .empty-coupons {
            text-align: center;
            padding: 2.5rem;
            color: #7a90a4;
            background: var(--card);
            border-radius: var(--radius-md);
            border: 1px solid rgba(10,22,40,0.07);
        }

        .empty-coupons .emoji { font-size: 2.5rem; display: block; margin-bottom: .75rem; opacity: .4; }

        @media (max-width: 600px) {
            .rewards-grid { grid-template-columns: 1fr; }
            .loyalty-hero { padding: 2.5rem 1rem 4rem; }
            .store-layout { padding: 1.5rem 1rem 3rem; }
            .coupon-item { flex-wrap: wrap; }
            .coupon-code-wrap { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="loyalty-hero">
    <div class="loyalty-hero-inner">
        <h1>🏅 Boutique Fidélité</h1>
        <p>Échangez vos points contre des récompenses exclusives</p>
        <div class="points-badge">
            <span class="points-badge-icon">🏅</span>
            <div>
                <div class="points-badge-num"><?= number_format($points) ?></div>
                <div class="points-badge-label">Points disponibles</div>
            </div>
        </div>
    </div>
</div>

<div class="store-layout">

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert-success">✅ <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-error">❌ <?= $error ?></div>
    <?php endif; ?>

    <!-- Rewards -->
    <div class="section-title">🎁 Récompenses disponibles</div>

    <div class="rewards-grid">
        <?php foreach ($rewards as $r):
            $can_afford  = $points >= $r['points_cost'];
            $percent     = min(100, round(($points / $r['points_cost']) * 100));
            $missing     = $r['points_cost'] - $points;
            $value_label = $r['value_type'] === 'percent'
                ? number_format($r['value'], 0) . '%'
                : number_format($r['value'], 0, ',', ' ') . ' MAD';
        ?>
        <div class="reward-card <?= $can_afford ? '' : 'locked' ?>">
            <div class="reward-card-top">
                <div class="reward-type-icon"><?= $type_icons[$r['type']] ?? '🎁' ?></div>
                <div class="reward-title"><?= htmlspecialchars($r['title']) ?></div>
                <div class="reward-desc"><?= htmlspecialchars($r['description']) ?></div>
                <div class="reward-value">
                    ✨ Valeur : <?= $value_label ?>
                </div>

                <?php if (!$can_afford): ?>
                <div class="reward-progress">
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" style="width:<?= $percent ?>%"></div>
                    </div>
                    <div class="progress-label"><?= $percent ?>% atteint — encore <?= number_format($missing) ?> pts</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="reward-card-bottom">
                <div class="reward-cost">
                    <span class="reward-cost-num"><?= number_format($r['points_cost']) ?></span>
                    <span class="reward-cost-label">pts</span>
                </div>

                <?php if ($can_afford): ?>
                <form method="POST" action="redeem-points.php" onsubmit="return confirm('Utiliser <?= $r['points_cost'] ?> points pour « <?= addslashes($r['title']) ?> » ?')">
                    <input type="hidden" name="reward_id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn-redeem available">
                        🎫 Échanger
                    </button>
                </form>
                <?php else: ?>
                <div style="text-align:right;">
                    <div class="btn-redeem locked">🔒 Verrouillé</div>
                    <div class="points-needed" style="margin-top:.3rem;">-<?= number_format($missing) ?> pts</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- My coupons -->
    <div class="section-title">🎫 Mes coupons</div>

    <?php if (empty($my_coupons)): ?>
    <div class="empty-coupons">
        <span class="emoji">🎫</span>
        <p>Vous n'avez pas encore de coupons.<br>Échangez vos points pour en obtenir !</p>
    </div>
    <?php else: ?>
    <div class="coupons-list">
        <?php foreach ($my_coupons as $c):
            $expires = $c['expires_at'] ? date('d/m/Y', strtotime($c['expires_at'])) : 'Pas d\'expiration';
        ?>
        <div class="coupon-item">
            <div class="coupon-icon"><?= $type_icons[$c['type']] ?? '🎁' ?></div>
            <div class="coupon-info">
                <div class="coupon-title"><?= htmlspecialchars($c['title']) ?></div>
                <div class="coupon-meta">
                    <?= number_format($c['points_used']) ?> pts utilisés
                    · Expire: <?= $expires ?>
                </div>
            </div>
            <div class="coupon-code-wrap">
                <span class="coupon-code" id="code-<?= $c['id'] ?>"><?= htmlspecialchars($c['coupon_code']) ?></span>
                <button class="copy-btn" onclick="copyCode('<?= $c['coupon_code'] ?>', this)" title="Copier">📋</button>
            </div>
            <span class="coupon-status <?= $c['status'] ?>">
                <?= ['active' => 'Actif', 'used' => 'Utilisé', 'expired' => 'Expiré'][$c['status']] ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/navbar.js"></script>
<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        btn.textContent = '✅';
        setTimeout(() => btn.textContent = '📋', 2000);
    });
}
</script>
</body>
</html>