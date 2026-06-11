<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: my-bookings.php');
    exit;
}

// ─── Fetch booking ─────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $id, ':uid' => $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my-bookings.php');
    exit;
}

// ─── Fetch user ────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// ─── Fetch item details ────────────────────────────────────────
if ($booking['type'] === 'hotel') {
    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = :id");
} else {
    $stmt = $pdo->prepare("SELECT * FROM surf_courses WHERE id = :id");
}
$stmt->execute([':id' => $booking['reference_id']]);
$item = $stmt->fetch();

$nights = (new DateTime($booking['check_in']))->diff(new DateTime($booking['check_out']))->days;

// ─── QR data ───────────────────────────────────────────────────
$item_name = htmlspecialchars($item['name'] ?? $item['title'] ?? '');
$qr_data   = "Taghazout Platform | Booking #" . $booking['id']
           . " | " . ($booking['type'] === 'hotel' ? 'Hotel' : 'Surf Course') . ": " . ($item['name'] ?? $item['title'] ?? '')
           . " | Check-in: " . $booking['check_in']
           . " | Check-out: " . $booking['check_out']
           . " | Guests: " . $booking['guests']
           . " | Total: " . $booking['total_price'] . " MAD"
           . " | Status: " . $booking['status'];
$qr_url    = "https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=" . urlencode($qr_data) . "&margin=6";

// ─── QR base64 pour PDF (évite le CORS de html2pdf) ───────────
$qr_base64 = '';
try {
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $qr_raw = @file_get_contents($qr_url, false, $ctx);
    if ($qr_raw) {
        $qr_base64 = 'data:image/png;base64,' . base64_encode($qr_raw);
    }
} catch (Exception $e) {}
// Fallback si file_get_contents désactivé : on garde l'URL externe
$qr_src_pdf = $qr_base64 ?: $qr_url;

// ─── Formatted dates ───────────────────────────────────────────
$checkin_fmt  = date('d M Y', strtotime($booking['check_in']));
$checkout_fmt = date('d M Y', strtotime($booking['check_out']));
$booking_ref  = 'TGZ-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation <?= $booking_ref ?> — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ── PDF Print styles ──────────────────────────────── */
        @media print {
            body * { visibility: hidden; }
            #pdf-ticket, #pdf-ticket * { visibility: visible; }
            #pdf-ticket {
                position: fixed;
                inset: 0;
                margin: 0;
                padding: 40px;
                background: #fff !important;
                color: #111 !important;
                font-family: 'DM Sans', sans-serif;
            }
            .no-print { display: none !important; }
        }

        /* ── QR section ─────────────────────────────────────── */
        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 1.8rem;
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            border: 1px dashed rgba(14,165,233,0.25);
            text-align: center;
        }
        .qr-section img {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            border: 3px solid white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .qr-section p {
            font-size: 0.75rem;
            color: var(--text-light);
            letter-spacing: 0.04em;
        }
        .qr-ref {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.1em;
        }

        /* ── PDF ticket layout ──────────────────────────────── */
        #pdf-ticket {
            display: none; /* shown only when printing */
        }

        /* ── Bouton PDF ─────────────────────────────────────── */
        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(14,165,233,0.35);
            transition: var(--transition);
            text-decoration: none;
            font-family: inherit;
        }
        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(14,165,233,0.45);
        }
        .btn-pdf svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* ── PDF ticket design ───────────────────────────────── */
        #pdf-content { font-family: 'DM Sans', sans-serif; background: #fff; }

        /* Header band bleu foncé */
        .pdf-header-band {
            background: linear-gradient(135deg, #0c4a6e 0%, #0ea5e9 100%);
            border-radius: 10px 10px 0 0;
            padding: 22px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pdf-logo-wrap { display: flex; align-items: center; gap: 10px; }
        .pdf-logo-img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
        .pdf-logo-text { color: #fff; }
        .pdf-logo-text strong { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; display: block; line-height: 1; }
        .pdf-logo-text small { font-size: 11px; opacity: 0.75; letter-spacing: 0.04em; }
        .pdf-header-right { text-align: right; }
        .pdf-ref-badge {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.1em;
            display: block;
        }
        .pdf-status-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1.5px solid rgba(255,255,255,0.4);
            color: #fff;
        }

        /* Contenu principal */
        .pdf-body-wrap {
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 10px 10px;
            overflow: hidden;
        }
        .pdf-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        .pdf-cell {
            padding: 18px 22px;
            border-right: 1px solid #f3f4f6;
            border-bottom: 1px solid #f3f4f6;
        }
        .pdf-cell:nth-child(even) { border-right: none; }
        .pdf-cell-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #9ca3af;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .pdf-cell-row {
            display: flex;
            justify-content: space-between;
            font-size: 12.5px;
            padding: 5px 0;
            border-bottom: 1px solid #f9fafb;
        }
        .pdf-cell-row:last-child { border-bottom: none; }
        .pdf-cell-row .lbl { color: #6b7280; }
        .pdf-cell-row .val { font-weight: 600; color: #111; }

        /* Bande prix */
        .pdf-price-band {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0f9ff;
            border-top: 2px solid #0ea5e9;
            padding: 16px 22px;
        }
        .pdf-price-label { font-size: 13px; font-weight: 600; color: #0369a1; }
        .pdf-price-sub   { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .pdf-price-value { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 700; color: #0ea5e9; }

        /* QR band */
        .pdf-qr-band {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 20px 22px;
            background: #fafafa;
            border-top: 1px solid #f3f4f6;
        }
        .pdf-qr-band img {
            width: 130px !important;
            height: 130px !important;
            max-width: 130px !important;
            border-radius: 8px;
            border: 3px solid #e0f2fe;
            flex-shrink: 0;
        }
        .pdf-qr-info { flex: 1; }
        .pdf-qr-ref  { font-family: 'Courier New', monospace; font-size: 16px; font-weight: 700; color: #0ea5e9; letter-spacing: 0.1em; }
        .pdf-qr-msg  { font-size: 11px; color: #6b7280; margin-top: 6px; line-height: 1.5; }
        .pdf-qr-conditions { font-size: 10px; color: #9ca3af; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e5e7eb; line-height: 1.6; }

        /* Footer */
        .pdf-footer {
            padding: 12px 22px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            border-radius: 0 0 10px 10px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<div style="max-width:700px; margin:8rem auto 4rem; padding:0 1.5rem;">

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['success'])): ?>
    <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:var(--radius-sm); padding:1rem 1.5rem; margin-bottom:2rem; color:#065f46; text-align:center; font-weight:500;">
        ✅ Réservation effectuée avec succès! En attente de confirmation.
    </div>
    <?php endif; ?>

    <div style="background:var(--card-bg); border-radius:var(--radius); padding:2.5rem; box-shadow:var(--card-shadow); border:1px solid rgba(14,165,233,0.06);">

        <!-- PAY BUTTON -->
        <?php if ($booking['status'] === 'pending'): ?>
        <div style="margin-bottom:2rem; text-align:center; padding-bottom:2rem; border-bottom:1px solid rgba(14,165,233,0.08);">
            <p style="color:var(--text-light); font-size:0.85rem; margin-bottom:1rem;">
                ⏳ Votre réservation est en attente de paiement
            </p>
            <a href="../payment/checkout-review.php?booking_id=<?= $booking['id'] ?>"
                style="display:inline-block; padding:1rem 2.5rem; background:var(--primary); color:white; border-radius:50px; font-weight:700; font-size:1rem; box-shadow:0 4px 20px var(--glow); transition:var(--transition);">
                💳 Payer maintenant — <?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD
            </a>
        </div>
        <?php endif; ?>

        <!-- HEADER -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid rgba(14,165,233,0.08);">
            <div>
                <p style="color:var(--text-light); font-size:0.85rem; margin-bottom:0.3rem;">Réservation</p>
                <h1 style="font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:700; color:var(--text);">
                    <?= $booking_ref ?>
                </h1>
            </div>
            <span style="padding:0.5rem 1.2rem; border-radius:50px; font-size:0.85rem; font-weight:600;
                background:<?= $booking['status'] === 'accepted' ? 'rgba(16,185,129,0.1)' : ($booking['status'] === 'rejected' ? 'rgba(239,68,68,0.1)' : 'rgba(14,165,233,0.1)') ?>;
                color:<?= $booking['status'] === 'accepted' ? '#065f46' : ($booking['status'] === 'rejected' ? '#991b1b' : 'var(--primary)') ?>;">
                <?= $booking['status'] === 'accepted' ? '✅ Confirmée' : ($booking['status'] === 'rejected' ? '❌ Refusée' : '⏳ En attente') ?>
            </span>
        </div>

        <!-- ITEM + QR côte à côte -->
        <div style="display:flex; gap:1.2rem; align-items:center; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid rgba(14,165,233,0.08);">
            <img
                src="../uploads/<?= $booking['type'] === 'hotel' ? 'hotels' : 'surf' ?>/<?= htmlspecialchars($item['image'] ?? '') ?>"
                onerror="this.src='../assets/images/default.jpg'"
                style="width:90px; height:75px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0;"
            >
            <div style="flex:1; min-width:0;">
                <p style="font-size:0.8rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.3rem;">
                    <?= $booking['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf Course' ?>
                </p>
                <h3 style="font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:700; color:var(--text);">
                    <?= htmlspecialchars($item['name'] ?? $item['title'] ?? '') ?>
                </h3>
            </div>
            <!-- QR à droite -->
            <div style="display:flex; flex-direction:column; align-items:center; gap:5px; flex-shrink:0; margin-left:-4px;">
                <img src="<?= $qr_url ?>" alt="QR" id="qr-img"
                    style="width:110px; height:110px; border-radius:8px; border:2px solid rgba(14,165,233,0.2); display:block;">
                <span style="font-family:'Courier New',monospace; font-size:9px; color:var(--text-light); letter-spacing:0.06em;">
                    <?= $booking_ref ?>
                </span>
            </div>
        </div>

        <!-- DETAILS GRID -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:2rem;">
            <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1rem; text-align:center;">
                <p style="font-size:0.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.06em;">Check-in</p>
                <p style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text); margin-top:0.3rem;">
                    <?= $checkin_fmt ?>
                </p>
            </div>
            <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1rem; text-align:center;">
                <p style="font-size:0.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.06em;">Check-out</p>
                <p style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text); margin-top:0.3rem;">
                    <?= $checkout_fmt ?>
                </p>
            </div>
            <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1rem; text-align:center;">
                <p style="font-size:0.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.06em;">
                    <?= $booking['type'] === 'hotel' ? 'Nuits' : 'Jours' ?>
                </p>
                <p style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text); margin-top:0.3rem;">
                    <?= $nights ?>
                </p>
            </div>
            <div style="background:var(--primary-light); border-radius:var(--radius-sm); padding:1rem; text-align:center;">
                <p style="font-size:0.75rem; color:var(--text-light); text-transform:uppercase; letter-spacing:0.06em;">Personnes</p>
                <p style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text); margin-top:0.3rem;">
                    <?= $booking['guests'] ?>
                </p>
            </div>
        </div>

        <!-- TOTAL -->
        <div style="display:flex; justify-content:space-between; align-items:center; padding:1.2rem 1.5rem; background:var(--primary-light); border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.1); margin-bottom:2rem;">
            <span style="font-weight:600; color:var(--text);">Total</span>
            <span style="font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:700; color:var(--primary);">
                <?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD
            </span>
        </div>

        <!-- ═══ BOUTON TÉLÉCHARGER PDF ════════════════════════ -->
        <div style="margin-bottom:2rem; text-align:center;">
            <button class="btn-pdf no-print" onclick="downloadPDF()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Télécharger le ticket PDF
            </button>
        </div>

        <!-- ACTIONS -->
        <div style="display:flex; gap:1rem;" class="no-print">
            <a href="my-bookings.php"
                style="flex:1; padding:0.85rem; background:var(--primary-light); color:var(--primary); border-radius:50px; font-weight:600; text-align:center; font-size:0.9rem; text-decoration:none;">
                ← Mes réservations
            </a>
            <a href="../index.php"
                style="flex:1; padding:0.85rem; background:var(--primary); color:white; border-radius:50px; font-weight:600; text-align:center; font-size:0.9rem; box-shadow:0 4px 15px var(--glow); text-decoration:none;">
                🏠 Accueil
            </a>
        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     HIDDEN PDF TICKET — généré par html2pdf.js
═══════════════════════════════════════════════════════════ -->
<div id="pdf-content" style="display:none; width:680px; font-family:'DM Sans',sans-serif; background:#fff; border-radius:10px; overflow:hidden;">

    <!-- ── HEADER BAND ──────────────────────────────────────── -->
    <div class="pdf-header-band">
        <div class="pdf-logo-wrap">
            <img src="../assets/images/logo.png"
                 onerror="this.style.display='none'"
                 class="pdf-logo-img" alt="Logo">
            <div class="pdf-logo-text">
                <strong>Taghazout Platform</strong>
                <small>taghazout-platform.ma</small>
            </div>
        </div>
        <div class="pdf-header-right">
            <span class="pdf-ref-badge"><?= $booking_ref ?></span>
            <span class="pdf-status-badge">
                <?= $booking['status'] === 'accepted' ? '✅ Confirmée' : ($booking['status'] === 'rejected' ? '❌ Refusée' : '⏳ En attente') ?>
            </span>
        </div>
    </div>

    <!-- ── BODY ─────────────────────────────────────────────── -->
    <div class="pdf-body-wrap">

        <!-- Grille 2 colonnes -->
        <div class="pdf-grid">

            <!-- Titulaire -->
            <div class="pdf-cell">
                <div class="pdf-cell-title">👤 Titulaire</div>
                <div class="pdf-cell-row">
                    <span class="lbl">Nom</span>
                    <span class="val"><?= htmlspecialchars($user['name'] ?? '') ?></span>
                </div>
                <div class="pdf-cell-row">
                    <span class="lbl">Email</span>
                    <span class="val"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                </div>
                <div class="pdf-cell-row">
                    <span class="lbl">Référence</span>
                    <span class="val" style="font-family:'Courier New',monospace; color:#0ea5e9;"><?= $booking_ref ?></span>
                </div>
            </div>

            <!-- Prestation -->
            <div class="pdf-cell">
                <div class="pdf-cell-title"><?= $booking['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf Course' ?></div>
                <div class="pdf-cell-row">
                    <span class="lbl">Type</span>
                    <span class="val"><?= $booking['type'] === 'hotel' ? 'Hôtel' : 'Surf Course' ?></span>
                </div>
                <div class="pdf-cell-row">
                    <span class="lbl">Nom</span>
                    <span class="val"><?= htmlspecialchars($item['name'] ?? $item['title'] ?? '') ?></span>
                </div>
                <div class="pdf-cell-row">
                    <span class="lbl">Statut</span>
                    <span class="val" style="color:<?= $booking['status'] === 'accepted' ? '#059669' : ($booking['status'] === 'rejected' ? '#dc2626' : '#0ea5e9') ?>">
                        <?= $booking['status'] === 'accepted' ? 'Confirmée' : ($booking['status'] === 'rejected' ? 'Refusée' : 'En attente') ?>
                    </span>
                </div>
            </div>

            <!-- Dates -->
            <div class="pdf-cell">
                <div class="pdf-cell-title">📅 Dates</div>
                <div class="pdf-cell-row">
                    <span class="lbl">Check-in</span>
                    <span class="val"><?= $checkin_fmt ?></span>
                </div>
                <div class="pdf-cell-row">
                    <span class="lbl">Check-out</span>
                    <span class="val"><?= $checkout_fmt ?></span>
                </div>
                <div class="pdf-cell-row">
                    <span class="lbl">Généré le</span>
                    <span class="val"><?= date('d/m/Y H:i') ?></span>
                </div>
            </div>

            <!-- Détails séjour -->
            <div class="pdf-cell">
                <div class="pdf-cell-title">📋 Détails séjour</div>
                <div class="pdf-cell-row">
                    <span class="lbl"><?= $booking['type'] === 'hotel' ? 'Nuits' : 'Jours' ?></span>
                    <span class="val"><?= $nights ?></span>
                </div>
                <div class="pdf-cell-row">
                    <span class="lbl">Personnes</span>
                    <span class="val"><?= $booking['guests'] ?></span>
                </div>
                <div class="pdf-cell-row">
                    <span class="lbl">Prix / <?= $booking['type'] === 'hotel' ? 'nuit' : 'jour' ?></span>
                    <span class="val"><?= $nights > 0 ? number_format($booking['total_price'] / $nights, 0, ',', ' ') : '—' ?> MAD</span>
                </div>
            </div>

        </div>

        <!-- ── PRIX TOTAL ──────────────────────────────────── -->
        <div class="pdf-price-band">
            <div>
                <div class="pdf-price-label">Montant total</div>
                <div class="pdf-price-sub">
                    <?= $nights ?> <?= $booking['type'] === 'hotel' ? 'nuit(s)' : 'jour(s)' ?> × <?= $booking['guests'] ?> personne(s)
                </div>
            </div>
            <div class="pdf-price-value">
                <?= number_format($booking['total_price'], 0, ',', ' ') ?> MAD
            </div>
        </div>

        <!-- ── QR BAND ─────────────────────────────────────── -->
        <div class="pdf-qr-band">
            <img src="<?= $qr_src_pdf ?>" alt="QR Code" style="width:130px; height:130px; max-width:130px; max-height:130px; display:block; flex-shrink:0;">
            <div class="pdf-qr-info">
                <div class="pdf-qr-ref"><?= $booking_ref ?></div>
                <div class="pdf-qr-msg">
                    Présentez ce QR code à l'accueil pour valider votre réservation.<br>
                    Ce billet est nominatif et non transférable.
                </div>
                <div class="pdf-qr-conditions">
                    📍 Taghazout, Agadir-Ida-Ou-Tanane, Maroc &nbsp;|&nbsp;
                    ✉ contact@taghazout-platform.ma &nbsp;|&nbsp;
                    🌐 taghazout-platform.ma<br>
                    Ce document est généré automatiquement et tient lieu de confirmation officielle.
                    En cas de problème, contactez-nous avec votre référence de réservation.
                </div>
            </div>
        </div>

        <!-- ── FOOTER ──────────────────────────────────────── -->
        <div class="pdf-footer">
            <span>© <?= date('Y') ?> Taghazout Platform — Tous droits réservés</span>
            <span>Généré le <?= date('d/m/Y à H:i') ?></span>
        </div>

    </div>
</div>

<!-- html2pdf.js via CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
function downloadPDF() {
    const el  = document.getElementById('pdf-content');
    const ref = '<?= $booking_ref ?>';

    // Afficher temporairement pour le rendu
    el.style.display = 'block';

    const opt = {
        margin:       [10, 10, 10, 10],
        filename:     'ticket-' + ref + '.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf()
        .set(opt)
        .from(el)
        .save()
        .then(function() {
            el.style.display = 'none';
        });
}
</script>
</body>
</html>