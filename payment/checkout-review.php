<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$booking_id = (int)($_GET['booking_id'] ?? 0);

if (!$booking_id) {
    header('Location: ../booking/my-bookings.php');
    exit;
}

// ─── Fetch booking ────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: ../booking/my-bookings.php');
    exit;
}

if ($booking['status'] === 'accepted') {
    header('Location: ../booking/booking-details.php?id=' . $booking_id);
    exit;
}

// ─── Fetch item ───────────────────────────────────────────
if ($booking['type'] === 'hotel') {
    $stmt = $pdo->prepare("SELECT name, image, location FROM hotels WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT title AS name, image, 'Taghazout' AS location FROM surf_courses WHERE id = ?");
}
$stmt->execute([$booking['reference_id']]);
$item = $stmt->fetch();

// ─── Fetch user points ────────────────────────────────────
$stmt = $pdo->prepare("SELECT loyalty_points FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_points = (int)$stmt->fetchColumn();

// ─── Fetch user active coupons ────────────────────────────
$stmt = $pdo->prepare("
    SELECT lr.*, rw.title, rw.value, rw.value_type, rw.type AS reward_type
    FROM loyalty_redemptions lr
    JOIN loyalty_rewards rw ON lr.reward_id = rw.id
    WHERE lr.user_id = ?
      AND lr.status = 'active'
      AND (lr.expires_at IS NULL OR lr.expires_at > NOW())
    ORDER BY lr.created_at DESC
");
$stmt->execute([$user_id]);
$active_coupons = $stmt->fetchAll();

// ─── Handle coupon apply (AJAX) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_coupon'])) {
    header('Content-Type: application/json');
    $code = trim($_POST['coupon_code'] ?? '');

    $stmt = $pdo->prepare("
        SELECT lr.*, rw.value, rw.value_type, rw.title
        FROM loyalty_redemptions lr
        JOIN loyalty_rewards rw ON lr.reward_id = rw.id
        WHERE lr.coupon_code = ?
          AND lr.user_id = ?
          AND lr.status = 'active'
          AND (lr.expires_at IS NULL OR lr.expires_at > NOW())
    ");
    $stmt->execute([$code, $user_id]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(['valid' => false, 'message' => 'Code invalide ou expiré']);
    } else {
        $original = (float)$booking['total_price'];
        if ($coupon['value_type'] === 'percent') {
            $discount = $original * ($coupon['value'] / 100);
        } else {
            $discount = min((float)$coupon['value'], $original);
        }
        $final = max(0, $original - $discount);
        echo json_encode([
            'valid'     => true,
            'title'     => $coupon['title'],
            'discount'  => $discount,
            'final'     => $final,
            'value'     => $coupon['value'],
            'value_type'=> $coupon['value_type'],
            'message'   => 'Coupon appliqué ✅'
        ]);
    }
    exit;
}

$nights = (new DateTime($booking['check_in']))->diff(new DateTime($booking['check_out']))->days;
$booking_ref = 'TGZ-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif & Paiement — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --ocean-deep: #0a1628;
            --ocean-teal: #0e7c6b;
            --sand-warm:  #c8965a;
            --bg:         #f0f4f8;
            --card:       #ffffff;
            --ease:       cubic-bezier(0.4,0,0.2,1);
        }
        *, *::before, *::after { box-sizing: border-box; }

        .checkout-wrap {
            max-width: 780px;
            margin: 7rem auto 5rem;
            padding: 0 1.5rem;
        }

        /* ── Page title ── */
        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.7rem; font-weight: 800;
            color: var(--ocean-deep);
            letter-spacing: -0.03em;
            margin-bottom: .25rem;
        }
        .page-sub { font-size: .88rem; color: #7a90a4; margin-bottom: 2rem; }

        /* ── Card ── */
        .co-card {
            background: var(--card);
            border-radius: 20px;
            border: 1px solid rgba(10,22,40,0.07);
            box-shadow: 0 4px 24px rgba(10,22,40,0.07);
            overflow: hidden;
            margin-bottom: 1.2rem;
        }
        .co-card-head {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid rgba(10,22,40,0.06);
            display: flex; align-items: center; gap: .6rem;
            font-family: 'Syne', sans-serif;
            font-size: .92rem; font-weight: 700; color: var(--ocean-deep);
        }
        .co-card-body { padding: 1.4rem 1.5rem; }

        /* ── Booking summary ── */
        .booking-row {
            display: flex; align-items: center; gap: 1.1rem;
        }
        .booking-img {
            width: 80px; height: 65px;
            border-radius: 10px; object-fit: cover; flex-shrink: 0;
        }
        .booking-info { flex: 1; }
        .booking-type {
            font-size: .72rem; font-weight: 700;
            background: rgba(14,165,233,0.08); color: #0369a1;
            padding: .18rem .6rem; border-radius: 50px;
            display: inline-block; margin-bottom: .3rem;
        }
        .booking-name {
            font-family: 'Syne', sans-serif;
            font-size: 1rem; font-weight: 800; color: var(--ocean-deep);
            margin-bottom: .2rem;
        }
        .booking-dates { font-size: .8rem; color: #7a90a4; }
        .booking-ref {
            font-family: 'Courier New', monospace;
            font-size: .8rem; color: #0369a1; font-weight: 700;
        }

        /* ── Details grid ── */
        .detail-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: .7rem;
            margin-top: 1rem;
        }
        .detail-item {
            background: #f8fafc;
            border-radius: 10px; padding: .75rem 1rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .detail-lbl { font-size: .78rem; color: #7a90a4; }
        .detail-val {
            font-family: 'Syne', sans-serif;
            font-size: .9rem; font-weight: 700; color: var(--ocean-deep);
        }

        /* ── Coupon section ── */
        .coupon-input-wrap {
            display: flex; gap: .6rem;
        }
        .coupon-input {
            flex: 1;
            padding: .75rem 1rem;
            border: 1.5px solid rgba(10,22,40,0.1);
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: .95rem; font-weight: 700;
            color: var(--ocean-deep);
            letter-spacing: .08em;
            text-transform: uppercase;
            transition: border-color .15s;
            outline: none;
        }
        .coupon-input:focus { border-color: var(--ocean-teal); }
        .coupon-input.valid   { border-color: #10b981; background: #f0fdf4; }
        .coupon-input.invalid { border-color: #ef4444; background: #fff5f5; }

        .btn-apply {
            padding: .75rem 1.3rem;
            background: var(--ocean-deep); color: #fff;
            border: none; border-radius: 10px;
            font-family: 'Syne', sans-serif;
            font-size: .85rem; font-weight: 700;
            cursor: pointer; transition: all .15s var(--ease);
            white-space: nowrap;
        }
        .btn-apply:hover { opacity: .88; transform: translateY(-1px); }
        .btn-apply:disabled { opacity: .45; cursor: not-allowed; transform: none; }

        .coupon-msg {
            margin-top: .55rem;
            font-size: .82rem; font-weight: 600;
            display: none;
        }
        .coupon-msg.ok  { color: #059669; display: block; }
        .coupon-msg.err { color: #dc2626; display: block; }

        /* quick-select coupons */
        .my-coupons-list {
            display: flex; flex-wrap: wrap; gap: .5rem;
            margin-top: .85rem;
        }
        .my-coupon-chip {
            display: flex; align-items: center; gap: .4rem;
            padding: .35rem .85rem;
            border: 1.5px solid rgba(10,22,40,0.1);
            border-radius: 50px; background: #f8fafc;
            font-size: .76rem; font-weight: 700;
            cursor: pointer; transition: all .14s var(--ease);
            color: var(--ocean-deep);
        }
        .my-coupon-chip:hover { border-color: var(--ocean-teal); background: #edfaf5; color: var(--ocean-teal); }
        .my-coupon-chip .chip-code {
            font-family: 'Courier New', monospace;
            letter-spacing: .06em;
        }
        .my-coupon-chip .chip-val {
            background: rgba(14,124,107,0.09); color: var(--ocean-teal);
            padding: .1rem .45rem; border-radius: 50px; font-size: .7rem;
        }

        /* ── Price summary ── */
        .price-line {
            display: flex; justify-content: space-between; align-items: center;
            padding: .65rem 0;
            border-bottom: 1px solid rgba(10,22,40,0.05);
            font-size: .88rem;
        }
        .price-line:last-child { border-bottom: none; }
        .price-line .lbl { color: #7a90a4; }
        .price-line .val { font-weight: 600; color: var(--ocean-deep); }
        .price-line.discount .val { color: #059669; }
        .price-line.total {
            padding-top: 1rem; margin-top: .3rem;
            border-top: 2px solid rgba(10,22,40,0.07);
            border-bottom: none;
        }
        .price-line.total .lbl {
            font-family: 'Syne', sans-serif;
            font-size: 1rem; font-weight: 800; color: var(--ocean-deep);
        }
        .price-line.total .val {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem; font-weight: 800; color: #0369a1;
        }

        /* ── Pay button ── */
        .btn-pay {
            display: flex; align-items: center; justify-content: center; gap: .6rem;
            width: 100%; padding: 1.05rem;
            background: linear-gradient(135deg, #0c4a6e, #0ea5e9);
            color: #fff; border: none; border-radius: 14px;
            font-family: 'Syne', sans-serif;
            font-size: 1rem; font-weight: 800;
            cursor: pointer; transition: all .2s var(--ease);
            box-shadow: 0 6px 20px rgba(14,165,233,0.3);
            text-decoration: none;
        }
        .btn-pay:hover { opacity: .92; transform: translateY(-2px); box-shadow: 0 8px 28px rgba(14,165,233,0.38); }

        .btn-back {
            display: block; text-align: center;
            margin-top: .85rem;
            font-size: .85rem; color: #7a90a4;
            text-decoration: none; transition: color .14s;
        }
        .btn-back:hover { color: var(--ocean-deep); }

        /* ── Points info ── */
        .pts-info {
            display: flex; align-items: center; gap: .6rem;
            background: rgba(200,150,90,0.07);
            border: 1px solid rgba(200,150,90,0.2);
            border-radius: 10px; padding: .75rem 1rem;
            font-size: .82rem; color: #92400e;
            margin-top: 1rem;
        }
        .pts-info strong { font-weight: 800; }

        /* ── Stripe badge ── */
        .stripe-badge {
            display: flex; align-items: center; justify-content: center; gap: .4rem;
            font-size: .74rem; color: #9ca3af; margin-top: .7rem;
        }

        @media (max-width: 600px) {
            .detail-grid { grid-template-columns: 1fr; }
            .booking-row { flex-wrap: wrap; }
            .checkout-wrap { margin-top: 5rem; }
        }
    </style>
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<div class="checkout-wrap">

    <div class="page-title">💳 Récapitulatif & Paiement</div>
    <div class="page-sub">Vérifiez votre réservation et appliquez un coupon avant de payer</div>

    <!-- ── BOOKING SUMMARY ── -->
    <div class="co-card">
        <div class="co-card-head">🏷️ Votre réservation</div>
        <div class="co-card-body">
            <div class="booking-row">
                <img
                    src="../uploads/<?= $booking['type'] === 'hotel' ? 'hotels' : 'surf' ?>/<?= htmlspecialchars($item['image'] ?? '') ?>"
                    onerror="this.src='../assets/images/default.jpg'"
                    class="booking-img" alt="">
                <div class="booking-info">
                    <span class="booking-type"><?= $booking['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf Course' ?></span>
                    <div class="booking-name"><?= htmlspecialchars($item['name'] ?? '') ?></div>
                    <div class="booking-dates">
                        📅 <?= date('d/m/Y', strtotime($booking['check_in'])) ?>
                        → <?= date('d/m/Y', strtotime($booking['check_out'])) ?>
                        · <?= $nights ?> nuit<?= $nights > 1 ? 's' : '' ?>
                        · <?= $booking['guests'] ?> pers.
                    </div>
                </div>
                <div class="booking-ref"><?= $booking_ref ?></div>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-lbl">Check-in</span>
                    <span class="detail-val"><?= date('d M Y', strtotime($booking['check_in'])) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-lbl">Check-out</span>
                    <span class="detail-val"><?= date('d M Y', strtotime($booking['check_out'])) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-lbl">Nuits / Jours</span>
                    <span class="detail-val"><?= $nights ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-lbl">Personnes</span>
                    <span class="detail-val"><?= $booking['guests'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── COUPON ── -->
    <div class="co-card">
        <div class="co-card-head">🎫 Code coupon fidélité</div>
        <div class="co-card-body">

            <div class="coupon-input-wrap">
                <input type="text" id="couponInput" class="coupon-input"
                       placeholder="EX: DIS-6CB165-3906"
                       maxlength="20" autocomplete="off"
                       oninput="this.value=this.value.toUpperCase()">
                <button class="btn-apply" id="applyBtn" onclick="applyCoupon()">Appliquer</button>
            </div>
            <div class="coupon-msg" id="couponMsg"></div>

            <?php if (!empty($active_coupons)): ?>
            <div style="margin-top:1rem;">
                <div style="font-size:.75rem; font-weight:700; color:#7a90a4; text-transform:uppercase; letter-spacing:.08em; margin-bottom:.5rem;">
                    Mes coupons disponibles
                </div>
                <div class="my-coupons-list">
                    <?php foreach ($active_coupons as $c):
                        $val_lbl = $c['value_type'] === 'percent'
                            ? number_format($c['value'],0).'%'
                            : number_format($c['value'],0).' MAD';
                    ?>
                    <div class="my-coupon-chip" onclick="selectCoupon('<?= htmlspecialchars($c['coupon_code']) ?>')">
                        <span class="chip-code"><?= htmlspecialchars($c['coupon_code']) ?></span>
                        <span class="chip-val">-<?= $val_lbl ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="pts-info">
                ⭐ Vous avez <strong><?= number_format($user_points) ?> points</strong> —
                <a href="../profile/loyalty-store.php" style="color:#92400e; font-weight:700; margin-left:.2rem;">Obtenir un coupon →</a>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ── PRICE SUMMARY ── -->
    <div class="co-card">
        <div class="co-card-head">💰 Récapitulatif du prix</div>
        <div class="co-card-body">
            <div class="price-line">
                <span class="lbl">Prix de base</span>
                <span class="val"><?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD</span>
            </div>
            <div class="price-line discount" id="discountLine" style="display:none;">
                <span class="lbl" id="discountLbl">Réduction coupon</span>
                <span class="val" id="discountVal">— MAD</span>
            </div>
            <div class="price-line total">
                <span class="lbl">Total à payer</span>
                <span class="val" id="totalVal"><?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD</span>
            </div>
        </div>
    </div>

    <!-- ── PAY BUTTON ── -->
    <form id="payForm" method="GET" action="../payment/checkout.php">
        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
        <input type="hidden" name="coupon_code" id="finalCoupon" value="">
        <button type="submit" class="btn-pay">
            🔒 Payer maintenant —
            <span id="payAmount"><?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD</span>
        </button>
    </form>

    <a href="../booking/booking-details.php?id=<?= $booking_id ?>" class="btn-back">← Retour aux détails</a>

    <div class="stripe-badge">
        🔒 Paiement sécurisé par <strong style="color:#635bff;">Stripe</strong>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
const basePrice   = <?= (float)$booking['total_price'] ?>;
let   appliedCode = '';
let   discount    = 0;

function selectCoupon(code) {
    document.getElementById('couponInput').value = code;
    applyCoupon();
}

async function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim();
    if (!code) return;

    const btn = document.getElementById('applyBtn');
    btn.disabled = true;
    btn.textContent = '...';

    try {
        const res  = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'check_coupon=1&coupon_code=' + encodeURIComponent(code)
        });
        const data = await res.json();
        const msg  = document.getElementById('couponMsg');
        const inp  = document.getElementById('couponInput');

        if (data.valid) {
            appliedCode = code;
            discount    = data.discount;

            inp.classList.remove('invalid');
            inp.classList.add('valid');
            msg.className = 'coupon-msg ok';
            msg.textContent = '✅ ' + data.title + ' — Réduction de '
                + (data.value_type === 'percent' ? data.value + '%' : data.value + ' MAD') + ' appliquée';

            // update price display
            document.getElementById('discountLine').style.display = 'flex';
            document.getElementById('discountLbl').textContent = '🎫 ' + data.title;
            document.getElementById('discountVal').textContent = '−' + data.discount.toLocaleString('fr') + ' MAD';
            document.getElementById('totalVal').textContent    = data.final.toLocaleString('fr') + ' MAD';
            document.getElementById('payAmount').textContent   = data.final.toLocaleString('fr') + ' MAD';
            document.getElementById('finalCoupon').value       = code;

        } else {
            appliedCode = '';
            inp.classList.remove('valid');
            inp.classList.add('invalid');
            msg.className = 'coupon-msg err';
            msg.textContent = '❌ ' + data.message;

            // reset price
            document.getElementById('discountLine').style.display = 'none';
            document.getElementById('totalVal').textContent  = basePrice.toLocaleString('fr') + ' MAD';
            document.getElementById('payAmount').textContent = basePrice.toLocaleString('fr') + ' MAD';
            document.getElementById('finalCoupon').value = '';
        }
    } catch (e) {
        console.error(e);
    }

    btn.disabled = false;
    btn.textContent = 'Appliquer';
}

// Enter key
document.getElementById('couponInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); }
});
</script>
</body>
</html>