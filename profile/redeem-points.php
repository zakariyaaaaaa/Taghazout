<?php
require_once '../includes/header.php';
require_once '../includes/config.php';

// ─── Auth check ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode('profile/loyalty-store.php'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ─── Only accept POST ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: loyalty-store.php');
    exit;
}

$reward_id = (int)($_POST['reward_id'] ?? 0);
if ($reward_id <= 0) {
    header('Location: loyalty-store.php?error=' . urlencode('Récompense invalide'));
    exit;
}

// ─── Fetch reward ─────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM loyalty_rewards WHERE id = ? AND is_active = 1");
$stmt->execute([$reward_id]);
$reward = $stmt->fetch();

if (!$reward) {
    header('Location: loyalty-store.php?error=' . urlencode('Récompense introuvable ou inactive'));
    exit;
}

// ─── Fetch user points (from users table directly) ────────
$stmt = $pdo->prepare("SELECT loyalty_points FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_points = (int)$stmt->fetchColumn();

// ─── Check balance ────────────────────────────────────────
if ($user_points < $reward['points_cost']) {
    $missing = $reward['points_cost'] - $user_points;
    header('Location: loyalty-store.php?error=' . urlencode("Pas assez de points (-{$missing} pts manquants)"));
    exit;
}

// ─── Check stock ──────────────────────────────────────────
if ($reward['stock'] !== null && (int)$reward['stock'] <= 0) {
    header('Location: loyalty-store.php?error=' . urlencode('Cette récompense est en rupture de stock'));
    exit;
}

// ─── Generate unique coupon code ──────────────────────────
function generateCoupon(string $prefix = 'TAG'): string {
    return strtoupper($prefix . '-' . bin2hex(random_bytes(3)) . '-' . substr(time(), -4));
}

$coupon_code = generateCoupon(strtoupper(substr($reward['type'], 0, 3)));
$expires_at  = date('Y-m-d H:i:s', strtotime('+30 days'));

// ─── Transaction (atomic) ─────────────────────────────────
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    // 1. Deduct points from users table
    $pdo->prepare("
        UPDATE users
        SET loyalty_points = loyalty_points - ?
        WHERE id = ? AND loyalty_points >= ?
    ")->execute([$reward['points_cost'], $user_id, $reward['points_cost']]);

    // 2. Log in loyalty_points table (type=redeem, points négatif)
    $pdo->prepare("
        INSERT INTO loyalty_points (user_id, points, type, reason, created_at)
        VALUES (?, ?, 'redeem', ?, NOW())
    ")->execute([
        $user_id,
        -$reward['points_cost'],
        'Échange : ' . $reward['title']
    ]);

    // 3. Log in loyalty_history
    $pdo->prepare("
        INSERT INTO loyalty_history (user_id, points, reason, created_at)
        VALUES (?, ?, ?, NOW())
    ")->execute([
        $user_id,
        -$reward['points_cost'],
        'Échange récompense : ' . $reward['title']
    ]);

    // 4. Create redemption record
    $pdo->prepare("
        INSERT INTO loyalty_redemptions
            (user_id, reward_id, coupon_code, points_used, status, expires_at, created_at)
        VALUES (?, ?, ?, ?, 'active', ?, NOW())
    ")->execute([
        $user_id,
        $reward_id,
        $coupon_code,
        $reward['points_cost'],
        $expires_at
    ]);

    // 5. Decrement stock if limited
    if ($reward['stock'] !== null) {
        $pdo->prepare("
            UPDATE loyalty_rewards
            SET stock = GREATEST(0, stock - 1)
            WHERE id = ?
        ")->execute([$reward_id]);
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: loyalty-store.php?error=' . urlencode('Une erreur est survenue : ' . $e->getMessage()));
    exit;
}

// ─── Success page ─────────────────────────────────────────
$new_points  = $user_points - $reward['points_cost'];
$type_icons  = [
    'discount'   => '🎫',
    'free_night' => '🏨',
    'free_surf'  => '🏄',
    'coupon'     => '🎁',
    'surf_lesson'=> '🏄',
    'gift'       => '🎀',
    'upgrade'    => '⬆️',
    'cashback'   => '💰',
    'other'      => '🎁',
];
$icon        = $type_icons[$reward['type']] ?? '🎁';
$value_label = $reward['value_type'] === 'percent'
    ? number_format($reward['value'], 0) . '%'
    : number_format($reward['value'], 0, ',', ' ') . ' MAD';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récompense échangée — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <style>
        :root {
            --ocean-deep: #0a1628;
            --ocean-teal: #0e7c6b;
            --ocean-foam: #3fb8a0;
            --sand-warm:  #c8965a;
            --bg:         #f0f4f8;
            --card:       #ffffff;
            --radius-md:  14px;
            --radius-lg:  20px;
            --radius-xl:  26px;
            --shadow-md:  0 6px 28px rgba(10,22,40,0.10);
            --shadow-lg:  0 16px 48px rgba(10,22,40,0.14);
            --ease:       cubic-bezier(0.4,0,0.2,1);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            color: var(--ocean-deep);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh; margin: 0;
        }

        /* ── Hero ── */
        .success-hero {
            background: linear-gradient(135deg, #07101f 0%, #0b1d36 45%, #0d2e52 100%);
            position: relative; overflow: hidden;
            padding: 3.5rem 2rem 6rem;
            text-align: center;
        }
        .success-hero::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 60% 60% at 10% 120%, rgba(63,184,160,0.28) 0%, transparent 60%),
                radial-gradient(ellipse 45% 45% at 90% -5%,  rgba(200,150,90,0.18) 0%, transparent 55%);
        }
        .success-hero::after {
            content: '';
            position: absolute; bottom: -1px; left: 0; right: 0;
            height: 64px; background: var(--bg);
            clip-path: ellipse(56% 100% at 50% 100%);
        }
        .hero-inner { position: relative; z-index: 1; }

        .check-circle {
            width: 82px; height: 82px; border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.3rem;
            margin: 0 auto 1.2rem;
            animation: popIn .5s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        .success-hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.6rem, 3.5vw, 2.2rem);
            font-weight: 800; color: #fff;
            letter-spacing: -0.03em; margin: 0 0 .4rem;
            animation: fadeUp .5s .1s ease both;
        }
        .success-hero p {
            color: rgba(255,255,255,0.55); font-size: .9rem;
            animation: fadeUp .5s .2s ease both; margin: 0;
        }
        @keyframes fadeUp {
            from { transform: translateY(12px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        /* ── Layout ── */
        .page-wrap {
            max-width: 660px;
            margin: 0 auto;
            padding: 2rem 1.5rem 5rem;
        }

        /* ── Coupon card ── */
        .coupon-card {
            background: var(--card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 1.4rem;
            animation: fadeUp .5s .25s ease both;
        }
        .cc-top {
            padding: 1.8rem 1.8rem 1.4rem;
            background: linear-gradient(135deg, #f6fbff, #edf4ff);
            border-bottom: 2px dashed rgba(10,22,40,0.08);
            display: flex; align-items: center; gap: 1.2rem;
        }
        .cc-reward-icon {
            width: 62px; height: 62px; border-radius: 16px;
            background: linear-gradient(135deg, var(--ocean-deep), #0d3060);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.75rem; flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(10,22,40,0.2);
        }
        .cc-info h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem; font-weight: 800;
            color: var(--ocean-deep); margin: 0 0 .25rem;
            letter-spacing: -0.02em;
        }
        .cc-desc { font-size: .8rem; color: #7a90a4; line-height: 1.5; }
        .cc-value-badge {
            margin-top: .5rem;
            display: inline-flex; align-items: center; gap: .3rem;
            background: rgba(14,124,107,0.09); color: var(--ocean-teal);
            border-radius: 50px; font-size: .75rem; font-weight: 700;
            padding: .22rem .7rem;
        }

        /* notch */
        .cc-notch {
            display: flex; align-items: center; background: var(--bg);
        }
        .notch-dot {
            width: 22px; height: 22px; border-radius: 50%;
            background: var(--bg); flex-shrink: 0;
        }
        .notch-line { flex: 1; border-top: 2px dashed rgba(10,22,40,0.08); }

        .cc-bottom { padding: 1.5rem 1.8rem; }
        .cc-label {
            font-size: .7rem; font-weight: 700; color: #7a90a4;
            text-transform: uppercase; letter-spacing: .1em; margin-bottom: .6rem;
        }

        /* code box */
        .code-box {
            display: flex; align-items: center; gap: 1rem;
            background: #f0f4f8;
            border: 1.5px solid rgba(10,22,40,0.07);
            border-radius: var(--radius-md);
            padding: 1rem 1.2rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all .2s var(--ease);
            position: relative; overflow: hidden;
        }
        .code-box::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(63,184,160,0.07), transparent);
            transform: translateX(-100%);
            transition: transform .5s ease;
        }
        .code-box:hover::before { transform: translateX(100%); }
        .code-box:hover { border-color: var(--ocean-teal); background: #edfaf5; }
        .code-text {
            font-family: 'Courier New', monospace;
            font-size: 1.35rem; font-weight: 700;
            color: var(--ocean-deep); letter-spacing: .12em; flex: 1;
        }
        .copy-btn {
            background: var(--card);
            border: 1px solid rgba(10,22,40,0.1);
            border-radius: 8px; padding: .45rem .8rem;
            font-size: .8rem; font-weight: 600; color: #7a90a4;
            cursor: pointer; transition: all .15s var(--ease);
            white-space: nowrap; display: flex; align-items: center; gap: .3rem;
        }
        .copy-btn:hover { border-color: var(--ocean-teal); color: var(--ocean-teal); }
        .copy-btn.copied { border-color: var(--ocean-teal); color: var(--ocean-teal); background: #edfaf5; }

        .code-hint { font-size: .74rem; color: #7a90a4; text-align: center; }

        .cc-meta-row {
            display: flex; gap: .75rem; flex-wrap: wrap; margin-top: 1.1rem;
        }
        .meta-pill {
            display: flex; align-items: center; gap: .35rem;
            background: #f0f4f8; border-radius: 8px;
            padding: .45rem .85rem;
            font-size: .78rem; font-weight: 500; color: #7a90a4;
        }
        .meta-pill strong { color: var(--ocean-deep); font-weight: 700; }

        /* ── Points summary ── */
        .pts-summary {
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(10,22,40,0.07);
            box-shadow: var(--shadow-md);
            padding: 1.2rem 1.5rem;
            display: flex; align-items: center;
            justify-content: space-between; gap: 1rem;
            flex-wrap: wrap; margin-bottom: 1.4rem;
            animation: fadeUp .5s .35s ease both;
        }
        .ps-item { text-align: center; flex: 1; min-width: 70px; }
        .ps-val {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem; font-weight: 800; margin-bottom: .12rem;
        }
        .ps-val.before { color: var(--ocean-deep); }
        .ps-val.used   { color: #d64545; }
        .ps-val.after  { color: var(--ocean-teal); }
        .ps-lbl { font-size: .7rem; color: #7a90a4; text-transform: uppercase; letter-spacing: .06em; }
        .ps-div { color: #d0dae4; font-size: 1.1rem; }

        /* ── Actions ── */
        .actions {
            display: grid; grid-template-columns: 1fr 1fr; gap: .8rem;
            animation: fadeUp .5s .45s ease both;
        }
        .btn-primary, .btn-secondary {
            display: flex; align-items: center; justify-content: center; gap: .45rem;
            padding: .85rem; border-radius: var(--radius-md);
            font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 700;
            text-decoration: none; border: none; cursor: pointer;
            transition: all .2s var(--ease);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--ocean-deep), #0e5a50);
            color: #fff; box-shadow: 0 4px 14px rgba(14,124,107,0.25);
        }
        .btn-primary:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(14,124,107,0.3); }
        .btn-secondary {
            background: var(--card); color: var(--ocean-deep);
            border: 1.5px solid rgba(10,22,40,0.1);
        }
        .btn-secondary:hover { border-color: var(--ocean-teal); color: var(--ocean-teal); }

        @media (max-width: 600px) {
            .cc-top { flex-direction: column; text-align: center; }
            .actions { grid-template-columns: 1fr; }
            .code-text { font-size: 1.1rem; }
            .success-hero { padding: 2.5rem 1rem 5rem; }
        }
    </style>
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<div class="success-hero">
    <div class="hero-inner">
        <div class="check-circle">✅</div>
        <h1>Récompense échangée !</h1>
        <p>Votre coupon est prêt à être utilisé</p>
    </div>
</div>

<div class="page-wrap">

    <!-- COUPON CARD -->
    <div class="coupon-card">
        <div class="cc-top">
            <div class="cc-reward-icon"><?= $icon ?></div>
            <div class="cc-info">
                <h2><?= htmlspecialchars($reward['title']) ?></h2>
                <div class="cc-desc"><?= htmlspecialchars($reward['description'] ?? '') ?></div>
                <div class="cc-value-badge">✨ Valeur : <?= $value_label ?></div>
            </div>
        </div>

        <div class="cc-notch">
            <div class="notch-dot" style="margin-left:-11px;"></div>
            <div class="notch-line"></div>
            <div class="notch-dot" style="margin-right:-11px;"></div>
        </div>

        <div class="cc-bottom">
            <div class="cc-label">Votre code coupon</div>
            <div class="code-box" onclick="doCopy()">
                <span class="code-text" id="couponCode"><?= htmlspecialchars($coupon_code) ?></span>
                <button type="button" class="copy-btn" id="copyBtn">📋 Copier</button>
            </div>
            <div class="code-hint">Cliquez pour copier · Présentez-le lors de votre séjour</div>
            <div class="cc-meta-row">
                <div class="meta-pill">📅 Expire <strong><?= date('d/m/Y', strtotime($expires_at)) ?></strong></div>
                <div class="meta-pill">⭐ <strong><?= number_format($reward['points_cost']) ?> pts</strong> utilisés</div>
                <div class="meta-pill">🔑 Statut : <strong style="color:var(--ocean-teal);">Actif</strong></div>
            </div>
        </div>
    </div>

    <!-- POINTS SUMMARY -->
    <div class="pts-summary">
        <div class="ps-item">
            <div class="ps-val before"><?= number_format($user_points) ?></div>
            <div class="ps-lbl">Avant</div>
        </div>
        <div class="ps-div">−</div>
        <div class="ps-item">
            <div class="ps-val used"><?= number_format($reward['points_cost']) ?></div>
            <div class="ps-lbl">Utilisés</div>
        </div>
        <div class="ps-div">=</div>
        <div class="ps-item">
            <div class="ps-val after"><?= number_format($new_points) ?></div>
            <div class="ps-lbl">Solde restant</div>
        </div>
    </div>

    <!-- ACTIONS -->
    <div class="actions">
        <a href="loyalty-store.php" class="btn-primary">🎁 Boutique fidélité</a>
        <a href="profile.php" class="btn-secondary">👤 Mon profil</a>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
<script src="assets/js/navbar.js"></script>
<script>
function doCopy() {
    const code = document.getElementById('couponCode').textContent.trim();
    const btn  = document.getElementById('copyBtn');
    navigator.clipboard.writeText(code).then(() => {
        btn.textContent = '✅ Copié !';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = '📋 Copier'; btn.classList.remove('copied'); }, 2500);
    });
}
</script>
</body>
</html>