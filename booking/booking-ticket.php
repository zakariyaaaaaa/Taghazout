<?php
session_start();
require '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT bookings.id as booking_id, bookings.booked_at,
           events.title, events.date_event, events.location
    FROM bookings
    JOIN events ON bookings.event_id = events.id
    WHERE bookings.user_id = :user_id
    ORDER BY bookings.booked_at DESC
");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr" data-checkout-review.phptheme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Tickets — Events</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/logo.png.jpg">
    <link rel="stylesheet" href="css/tickets.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <svg class="brand-icon" viewBox="0 0 24 24" fill="currentColor"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            Events
        </a>
        <div class="nav-right">
            <span class="user-greeting">Bonjour, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
            <button class="theme-toggle" id="toggleBtn" aria-label="Changer le thème">
                <span class="toggle-icon">☀</span>
            </button>
            <a href="index.php" class="nav-link">← Retour</a>
        </div>
    </nav>

    <main class="tickets-wrap">

        <header class="page-header">
            <div class="header-label">Mes réservations</div>
            <h1>Mes <em>Tickets</em></h1>
            <p class="ticket-count">
                <?= count($bookings) ?> ticket<?= count($bookings) > 1 ? 's' : '' ?> actif<?= count($bookings) > 1 ? 's' : '' ?>
            </p>
        </header>

        <?php if (empty($bookings)): ?>
            <div class="no-tickets">
                <div class="no-tickets-icon">◎</div>
                <p>Aucun ticket pour le moment.</p>
                <a href="index.php" class="btn-cta">Explorer les événements</a>
            </div>
        <?php else: ?>
            <div class="tickets-grid">
                <?php foreach ($bookings as $i => $booking): ?>
                    <?php
                        $qr_data = "Booking #" . $booking['booking_id'] .
                                   " | Event: " . $booking['title'] .
                                   " | User: " . $user['name'];
                        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data) . "&color=ffffff&bgcolor=000000&margin=8";
                        $qr_url_light = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data) . "&color=000000&bgcolor=ffffff&margin=8";

                        $date_raw = $booking['date_event'];
                        $ts = strtotime($date_raw);
                        $jour = $ts ? date('d', $ts) : '--';
                        $mois = $ts ? strtoupper(date('M', $ts)) : '--';
                        $annee = $ts ? date('Y', $ts) : '--';
                        $heure = $ts ? date('H:i', $ts) : '';

                        $booked_ts = strtotime($booking['booked_at']);
                        $booked_fmt = $booked_ts ? date('d/m/Y à H:i', $booked_ts) : $booking['booked_at'];

                        $booking_code = strtoupper(substr(md5($booking['booking_id']), 0, 8));
                    ?>
                    <article class="ticket" style="animation-delay: <?= $i * 0.1 ?>s">

                        <!-- Partie principale du ticket -->
                        <div class="ticket-main">

                            <div class="ticket-top">
                                <div class="event-meta">
                                    <div class="event-date-badge">
                                        <span class="date-day"><?= $jour ?></span>
                                        <span class="date-month"><?= $mois ?></span>
                                        <span class="date-year"><?= $annee ?></span>
                                    </div>
                                    <div class="event-details">
                                        <h2 class="event-title"><?= htmlspecialchars($booking['title']) ?></h2>
                                        <div class="event-loc">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <?= htmlspecialchars($booking['location']) ?>
                                        </div>
                                        <?php if ($heure): ?>
                                        <div class="event-time">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            <?= $heure ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="valid-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Valide
                                </div>
                            </div>

                            <div class="ticket-divider">
                                <div class="notch notch-left"></div>
                                <div class="dash-line"></div>
                                <div class="notch notch-right"></div>
                            </div>

                            <div class="ticket-bottom">
                                <div class="ticket-info-row">
                                    <div class="info-block">
                                        <span class="info-label">Titulaire</span>
                                        <span class="info-val"><?= htmlspecialchars($user['name']) ?></span>
                                    </div>
                                    <div class="info-block">
                                        <span class="info-label">Réservé le</span>
                                        <span class="info-val"><?= $booked_fmt ?></span>
                                    </div>
                                    <div class="info-block">
                                        <span class="info-label">Code</span>
                                        <span class="info-val mono"><?= $booking_code ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Souche du ticket (QR) -->
                        <div class="ticket-stub">
                            <div class="stub-inner">
                                <img class="qr-code qr-dark" src="<?= $qr_url ?>" alt="QR Code ticket #<?= $booking['booking_id'] ?>" width="100" height="100">
                                <img class="qr-code qr-light" src="<?= $qr_url_light ?>" alt="QR Code ticket #<?= $booking['booking_id'] ?>" width="100" height="100">
                                <div class="booking-id mono">#<?= str_pad($booking['booking_id'], 5, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <script src="js/tickets.js"></script>
</body>
</html>