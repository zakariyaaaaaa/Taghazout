<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// ─── Get activity ID ──────────────────────────────────────
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: activities.php'); exit; }

// ─── Fetch activity ───────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM activities WHERE id = :id");
$stmt->execute([':id' => $id]);
$activity = $stmt->fetch();
if (!$activity) { header('Location: activities.php'); exit; }

// ─── Fetch reviews ────────────────────────────────────────
$rev_stmt = $pdo->prepare("
    SELECT r.*, u.name AS user_name, u.avatar AS user_avatar
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.type = 'activity' AND r.reference_id = :id
    ORDER BY r.created_at DESC
");
$rev_stmt->execute([':id' => $id]);
$reviews = $rev_stmt->fetchAll();

$avg_rating = count($reviews)
    ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1)
    : $activity['rating'];

// ─── Check if already favorited ───────────────────────────
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $fav_stmt = $pdo->prepare("
        SELECT id FROM favorites
        WHERE user_id = :uid AND type = 'activity' AND reference_id = :rid
    ");
    $fav_stmt->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
    $is_favorite = (bool)$fav_stmt->fetch();
}

// ─── Handle review submit ─────────────────────────────────
$review_error   = '';
$review_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $review_error = 'Vous devez être connecté pour laisser un avis.';
    } else {
        $r_rating  = isset($_POST['rating'])  ? (int)$_POST['rating']  : 0;
        $r_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        if ($r_rating < 1 || $r_rating > 5) {
            $review_error = 'Veuillez choisir une note entre 1 et 5.';
        } elseif (strlen($r_comment) < 3) {
            $review_error = 'Votre commentaire doit contenir au moins 3 caractères.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM reviews WHERE user_id = :uid AND type = 'activity' AND reference_id = :rid");
            $chk->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
            if ($chk->fetch()) {
                $review_error = 'Vous avez déjà laissé un avis pour cette activité.';
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO reviews (user_id, type, reference_id, rating, comment)
                    VALUES (:uid, 'activity', :rid, :rating, :comment)
                ");
                $ins->execute([
                    ':uid'     => $_SESSION['user_id'],
                    ':rid'     => $id,
                    ':rating'  => $r_rating,
                    ':comment' => $r_comment,
                ]);
                $upd = $pdo->prepare("
                    UPDATE activities SET rating = (
                        SELECT ROUND(AVG(rating), 1) FROM reviews
                        WHERE type = 'activity' AND reference_id = :rid
                    ) WHERE id = :rid2
                ");
                $upd->execute([':rid' => $id, ':rid2' => $id]);
                header("Location: activity-details.php?id=$id&reviewed=1");
                exit;
            }
        }
    }
}
if (isset($_GET['reviewed'])) $review_success = 'Merci pour votre avis !';

// ─── Handle favorite toggle ───────────────────────────────
if (isset($_GET['toggle_fav']) && isset($_SESSION['user_id'])) {
    if ($is_favorite) {
        $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND type = 'activity' AND reference_id = :rid")
            ->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
    } else {
        $pdo->prepare("INSERT INTO favorites (user_id, type, reference_id) VALUES (:uid, 'activity', :rid)")
            ->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
    }
    header("Location: activity-details.php?id=$id");
    exit;
}

// ─── Similar activities ───────────────────────────────────
$sim_stmt = $pdo->prepare("
    SELECT * FROM activities
    WHERE id != :id
    ORDER BY ABS(rating - :rating) ASC, rating DESC
    LIMIT 3
");
$sim_stmt->execute([':id' => $id, ':rating' => $activity['rating']]);
$similar = $sim_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($activity['name']) ?> — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/hotel-details.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="detail-hero">
    <img
        src="../uploads/activities/<?= htmlspecialchars($activity['image'] ?? '') ?>"
        onerror="this.src='../assets/images/default.jpg'"
        alt="<?= htmlspecialchars($activity['name']) ?>"
    >
    <div class="detail-hero-overlay"></div>

    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="activity-details.php?id=<?= $id ?>&toggle_fav=1" class="fav-btn" >
        <?= $is_favorite ? '❤️' : '🤍' ?>
    </a>
    <?php endif; ?>

    <div class="detail-hero-content">
        <p class="breadcrumb">
            <a href="index.php">Accueil</a> › <a href="activities.php">Activités</a> › <?= htmlspecialchars($activity['name']) ?>
        </p>
        <h1><?= htmlspecialchars($activity['name']) ?></h1>
        <div class="detail-hero-meta">
            <span>📍 <?= htmlspecialchars($activity['location']) ?></span>
            <span class="meta-badge">⭐ <?= number_format($avg_rating, 1) ?> / 5</span>
            <?php if (!empty($activity['duration'])): ?>
                <span class="meta-badge">⏱ <?= htmlspecialchars($activity['duration']) ?></span>
            <?php endif; ?>
            <span class="meta-badge"><?= count($reviews) ?> avis</span>
        </div>
    </div>
</div>

<!-- LAYOUT -->
<div class="detail-layout">

    <!-- LEFT -->
    <div class="detail-main">

        <section>
            <h2>📝 Description</h2>
            <p class="detail-description">
                <?= nl2br(htmlspecialchars($activity['description'] ?? 'Aucune description disponible.')) ?>
            </p>
        </section>

        <section>
            <h2>ℹ️ Informations</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-icon">💰</span>
                    <div class="info-label">Prix / personne</div>
                    <div class="info-value"><?= number_format($activity['price'], 0, ',', ' ') ?> MAD</div>
                </div>
                <div class="info-item">
                    <span class="info-icon">⭐</span>
                    <div class="info-label">Note</div>
                    <div class="info-value"><?= number_format($avg_rating, 1) ?> / 5</div>
                </div>
                <div class="info-item">
                    <span class="info-icon">⏱</span>
                    <div class="info-label">Durée</div>
                    <div class="info-value"><?= htmlspecialchars($activity['duration'] ?? '—') ?></div>
                </div>
                <div class="info-item">
                    <span class="info-icon">📍</span>
                    <div class="info-label">Localisation</div>
                    <div class="info-value"><?= htmlspecialchars($activity['location']) ?></div>
                </div>
                <div class="info-item">
                    <span class="info-icon">💬</span>
                    <div class="info-label">Avis</div>
                    <div class="info-value"><?= count($reviews) ?> avis</div>
                </div>
            </div>
        </section>

        <section>
            <h2>💬 Avis des clients</h2>
            <div class="review-summary">
                <div class="review-big-score"><?= number_format($avg_rating, 1) ?></div>
                <div>
                    <div class="review-stars">
                        <?php
                        $full  = floor($avg_rating);
                        $half  = ($avg_rating - $full) >= 0.5 ? 1 : 0;
                        $empty = 5 - $full - $half;
                        echo str_repeat('⭐', $full);
                        echo $half ? '☆' : '';
                        echo str_repeat('☆', $empty);
                        ?>
                    </div>
                    <div class="review-count"><?= count($reviews) ?> avis au total</div>
                </div>
            </div>

            <?php if (empty($reviews)): ?>
                <p class="no-reviews">Aucun avis pour le moment. Soyez le premier ! 🌟</p>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                <div class="review-item">
                    <div class="review-header">
                        <img
                            src="../uploads/users/<?= htmlspecialchars($rev['user_avatar'] ?? 'default.png') ?>"
                            onerror="this.src='../assets/images/users/default.png'"
                            alt="<?= htmlspecialchars($rev['user_name']) ?>"
                            class="review-avatar"
                        >
                        <div>
                            <div class="review-author"><?= htmlspecialchars($rev['user_name']) ?></div>
                            <div class="review-date"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></div>
                        </div>
                        <div class="review-stars-small" style="margin-left:auto">
                            <?= str_repeat('⭐', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
                        </div>
                    </div>
                    <?php if (!empty($rev['comment'])): ?>
                        <p class="review-text"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="review-form">
                <h3>✍️ Laisser un avis</h3>
                <?php if ($review_success): ?>
                    <div class="alert-success">✅ <?= $review_success ?></div>
                <?php endif; ?>
                <?php if ($review_error): ?>
                    <div class="alert-error">❌ <?= $review_error ?></div>
                <?php endif; ?>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="login-notice">
                        <a href="auth/login.php">Connectez-vous</a> pour laisser un avis.
                    </div>
                <?php else: ?>
                    <form method="POST" action="activity-details.php?id=<?= $id ?>">
                        <div class="star-picker">
                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                <input type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>" required>
                                <label for="star<?= $s ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <textarea name="comment" placeholder="Partagez votre expérience..." required></textarea>
                        <button type="submit" name="submit_review" class="submit-btn">Publier l'avis</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

    </div>

    <!-- SIDEBAR -->
    <aside class="detail-sidebar">
        <div class="booking-card">
            <h3>Réserver cette activité</h3>
            <div class="booking-price">
                <?= number_format($activity['price'], 0, ',', ' ') ?> MAD <span>/ personne</span>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" action="../booking/activity-booking.php">
                <input type="hidden" name="type"         value="activity">
                <input type="hidden" name="reference_id" value="<?= $id ?>">

                <div class="form-group">
                    <label>Date souhaitée</label>
                    <input type="date" name="check_in" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Nombre de personnes</label>
                    <select name="guests" id="guests-select">
                        <?php for ($g = 1; $g <= 10; $g++): ?>
                            <option value="<?= $g ?>"><?= $g ?> personne<?= $g > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="price-breakdown">
                    <div class="row"><span>Prix / personne</span><span><?= number_format($activity['price'], 0, ',', ' ') ?> MAD</span></div>
                    <div class="row"><span>Nombre de personnes</span><span id="nb-persons">1</span></div>
                    <div class="row total"><span>Total estimé</span><span id="total-price"><?= number_format($activity['price'], 0, ',', ' ') ?> MAD</span></div>
                </div>

                <button type="submit" class="book-btn">🏄 Réserver maintenant</button>
            </form>
            <?php else: ?>
                <div class="book-login-notice">
                    <a href="auth/login.php">Connectez-vous</a> pour effectuer une réservation.
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($similar)): ?>
        <div>
            <h3 style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:#1a1a2e;margin:0 0 1.1rem;padding-bottom:.75rem;border-bottom:2px solid #f0f0f0;">
                🏄 Activités similaires
            </h3>
            <?php foreach ($similar as $sim): ?>
            <a href="activity-details.php?id=<?= $sim['id'] ?>" class="similar-card">
                <img
                    src="../uploads/activities/<?= htmlspecialchars($sim['image'] ?? '') ?>"
                    onerror="this.src='../assets/images/default.jpg'"
                    alt="<?= htmlspecialchars($sim['name']) ?>"
                >
                <div class="similar-info">
                    <h4><?= htmlspecialchars($sim['name']) ?></h4>
                    <p>📍 <?= htmlspecialchars($sim['location']) ?></p>
                    <span class="sim-price"><?= number_format($sim['price'], 0, ',', ' ') ?> MAD / personne</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </aside>

</div>

<?php require_once 'includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
const pricePerPerson = <?= (float)$activity['price'] ?>;
const guestsSelect   = document.getElementById('guests-select');

function calcPrice() {
    if (!guestsSelect) return;
    const persons = parseInt(guestsSelect.value) || 1;
    const total   = persons * pricePerPerson;
    document.getElementById('nb-persons').textContent  = persons + ' personne' + (persons > 1 ? 's' : '');
    document.getElementById('total-price').textContent = total.toLocaleString('fr-FR') + ' MAD';
}

if (guestsSelect) guestsSelect.addEventListener('change', calcPrice);
</script>
</body>
</html>