<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// ─── Get course ID ────────────────────────────────────────
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: surf-courses.php'); exit; }

// ─── Fetch course ─────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM surf_courses WHERE id = :id");
$stmt->execute([':id' => $id]);
$course = $stmt->fetch();
if (!$course) { header('Location: surf-courses.php'); exit; }

// ─── Fetch reviews ────────────────────────────────────────
$rev_stmt = $pdo->prepare("
    SELECT r.*, u.name AS user_name, u.avatar AS user_avatar
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.type = 'surf' AND r.reference_id = :id
    ORDER BY r.created_at DESC
");
$rev_stmt->execute([':id' => $id]);
$reviews = $rev_stmt->fetchAll();

$avg_rating = count($reviews)
    ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1)
    : 0;

// ─── Check if already favorited ───────────────────────────
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $fav_stmt = $pdo->prepare("
        SELECT id FROM favorites
        WHERE user_id = :uid AND type = 'surf' AND reference_id = :rid
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
            $chk = $pdo->prepare("SELECT id FROM reviews WHERE user_id = :uid AND type = 'surf' AND reference_id = :rid");
            $chk->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
            if ($chk->fetch()) {
                $review_error = 'Vous avez déjà laissé un avis pour ce cours.';
            } else {
                $pdo->prepare("
                    INSERT INTO reviews (user_id, type, reference_id, rating, comment)
                    VALUES (:uid, 'surf', :rid, :rating, :comment)
                ")->execute([
                    ':uid'     => $_SESSION['user_id'],
                    ':rid'     => $id,
                    ':rating'  => $r_rating,
                    ':comment' => $r_comment,
                ]);
                // ✅ FIX 3 — redirect vers la même page
                header("Location: surf-course-details.php?id=$id&reviewed=1");
                exit;
            }
        }
    }
}
if (isset($_GET['reviewed'])) $review_success = 'Merci pour votre avis !';

// ─── Handle favorite toggle ───────────────────────────────
// ✅ FIX 2 — toggle_fav sur la même page
if (isset($_GET['toggle_fav']) && isset($_SESSION['user_id'])) {
    if ($is_favorite) {
        $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND type = 'surf' AND reference_id = :rid")
            ->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
    } else {
        $pdo->prepare("INSERT INTO favorites (user_id, type, reference_id) VALUES (:uid, 'surf', :rid)")
            ->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
    }
    header("Location: surf-course-details.php?id=$id");
    exit;
}

// ─── Booking errors from session ──────────────────────────
$booking_errors = [];
if (isset($_GET['booking_error']) && isset($_SESSION['booking_errors'])) {
    $booking_errors = $_SESSION['booking_errors'];
    unset($_SESSION['booking_errors']);
}

// ─── Similar courses ──────────────────────────────────────
$sim_stmt = $pdo->prepare("
    SELECT * FROM surf_courses
    WHERE id != :id
    ORDER BY FIELD(level, :level, 'beginner', 'intermediate', 'advanced')
    LIMIT 3
");
$sim_stmt->execute([':id' => $id, ':level' => $course['level']]);
$similar = $sim_stmt->fetchAll();

// ─── Level labels ─────────────────────────────────────────
$level_labels = [
    'beginner'     => '🟢 Débutant',
    'intermediate' => '🟡 Intermédiaire',
    'advanced'     => '🔴 Avancé',
];
$level_colors = [
    'beginner'     => '#10b981',
    'intermediate' => '#f59e0b',
    'advanced'     => '#ef4444',
];
$level_color = $level_colors[$course['level']] ?? 'var(--primary)';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/hotel-details.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="detail-hero">
    <img
        src="uploads/surf/<?= htmlspecialchars($course['image'] ?? '') ?>"
        onerror="this.src='assets/images/default.jpg'"
        alt="<?= htmlspecialchars($course['title']) ?>"
    >
    <div class="detail-hero-overlay"></div>

    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- ✅ FIX 2 — toggle_fav sur surf-course-details.php -->
    <a href="surf-course-details.php?id=<?= $id ?>&toggle_fav=1"
       class="fav-btn"
       title="<?= $is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
        <?= $is_favorite ? '❤️' : '🤍' ?>
    </a>
    <?php endif; ?>

    <div class="detail-hero-content">
        <p class="breadcrumb">
            <a href="index.php">Accueil</a> ›
            <a href="surf-courses.php">Cours de Surf</a> ›
            <?= htmlspecialchars($course['title']) ?>
        </p>
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <div class="detail-hero-meta">
            <?php if (!empty($course['level'])): ?>
                <span class="meta-badge"><?= $level_labels[$course['level']] ?? $course['level'] ?></span>
            <?php endif; ?>
            <?php if (!empty($course['duration'])): ?>
                <span class="meta-badge">⏱ <?= htmlspecialchars($course['duration']) ?></span>
            <?php endif; ?>
            <span class="meta-badge">👥 Max <?= $course['max_students'] ?> étudiants</span>
            <?php if ($avg_rating > 0): ?>
                <span class="meta-badge">⭐ <?= number_format($avg_rating, 1) ?> / 5</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- LAYOUT -->
<div class="detail-layout">

    <!-- LEFT -->
    <div class="detail-main">

        <!-- Booking errors -->
        <?php if (!empty($booking_errors)): ?>
        <div class="alert-error" style="margin-bottom:1.5rem;">
            <strong>❌ Erreur de réservation :</strong>
            <ul style="margin:.5rem 0 0 1rem;">
                <?php foreach ($booking_errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['booking_success'])): ?>
        <div class="alert-success" style="margin-bottom:1.5rem;">
            ✅ Réservation envoyée avec succès ! En attente de confirmation.
        </div>
        <?php endif; ?>

        <section>
            <h2>📝 Description</h2>
            <p class="detail-description">
                <?= nl2br(htmlspecialchars($course['description'] ?? 'Aucune description disponible.')) ?>
            </p>
        </section>

        <section>
            <h2>ℹ️ Informations</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-icon">💰</span>
                    <div class="info-label">Prix</div>
                    <div class="info-value"><?= number_format($course['price'], 0, ',', ' ') ?> MAD / pers.</div>
                </div>
                <div class="info-item">
                    <span class="info-icon">🎯</span>
                    <div class="info-label">Niveau</div>
                    <div class="info-value" style="color:<?= $level_color ?>">
                        <?= $level_labels[$course['level']] ?? $course['level'] ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-icon">⏱</span>
                    <div class="info-label">Durée</div>
                    <div class="info-value"><?= htmlspecialchars($course['duration'] ?? '—') ?></div>
                </div>
                <div class="info-item">
                    <span class="info-icon">👥</span>
                    <div class="info-label">Max étudiants</div>
                    <div class="info-value"><?= $course['max_students'] ?> personnes</div>
                </div>
                <?php if ($avg_rating > 0): ?>
                <div class="info-item">
                    <span class="info-icon">⭐</span>
                    <div class="info-label">Note</div>
                    <div class="info-value"><?= number_format($avg_rating, 1) ?> / 5</div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Reviews -->
        <section>
            <h2>💬 Avis des clients</h2>

            <?php if ($avg_rating > 0): ?>
            <div class="review-summary">
                <div class="review-big-score"><?= number_format($avg_rating, 1) ?></div>
                <div>
                    <div class="review-stars">
                        <?php
                        $full  = floor($avg_rating);
                        $half  = ($avg_rating - $full) >= 0.5 ? 1 : 0;
                        $empty = 5 - $full - $half;
                        echo str_repeat('⭐', $full);
                        echo $half ? '✨' : '';
                        echo str_repeat('☆', $empty);
                        ?>
                    </div>
                    <div class="review-count"><?= count($reviews) ?> avis au total</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <p class="no-reviews">Aucun avis pour le moment. Soyez le premier ! 🌟</p>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                <div class="review-item">
                    <div class="review-header">
                        <img
                            src="uploads/users/<?= htmlspecialchars($rev['user_avatar'] ?? '') ?>"
                            onerror="this.src='assets/images/default.jpg'"
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

            <!-- Review form -->
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
                    <!-- ✅ FIX 3 — action vers surf-course-details.php -->
                    <form method="POST" action="surf-course-details.php?id=<?= $id ?>">
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
            <h3>Réserver ce cours</h3>
            <div class="booking-price">
                <?= number_format($course['price'], 0, ',', ' ') ?> MAD <span>/ personne</span>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <!-- ✅ FIX 1 — action vers booking/booking-surf.php -->
            <form method="POST" action="booking/surf-booking.php">
                
                <input type="hidden" name="reference_id" value="<?= $id ?>">

                <div class="form-group">
                    <label>Date du cours</label>
                    <input type="date" name="check_in" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Nombre de personnes</label>
                    <select name="guests" id="guests-select">
                        <?php for ($g = 1; $g <= min(10, $course['max_students']); $g++): ?>
                            <option value="<?= $g ?>"><?= $g ?> personne<?= $g > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="price-breakdown">
                    <div class="row">
                        <span>Prix / personne</span>
                        <span><?= number_format($course['price'], 0, ',', ' ') ?> MAD</span>
                    </div>
                    <div class="row">
                        <span>Nombre de personnes</span>
                        <span id="nb-persons">1 personne</span>
                    </div>
                    <div class="row total">
                        <span>Total estimé</span>
                        <span id="total-price"><?= number_format($course['price'], 0, ',', ' ') ?> MAD</span>
                    </div>
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
                🏄 Autres cours
            </h3>
            <?php foreach ($similar as $sim): ?>
            <!-- ✅ FIX 2 — lien vers surf-course-details.php -->
            <a href="surf-course-details.php?id=<?= $sim['id'] ?>" class="similar-card">
                <img
                    src="uploads/surf/<?= htmlspecialchars($sim['image'] ?? '') ?>"
                    onerror="this.src='assets/images/default.jpg'"
                    alt="<?= htmlspecialchars($sim['title']) ?>"
                >
                <div class="similar-info">
                    <h4><?= htmlspecialchars($sim['title']) ?></h4>
                    <p><?= $level_labels[$sim['level']] ?? $sim['level'] ?></p>
                    <span class="sim-price"><?= number_format($sim['price'], 0, ',', ' ') ?> MAD / cours</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </aside>

</div>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
const pricePerPerson = <?= (float)$course['price'] ?>;
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