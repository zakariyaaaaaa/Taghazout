<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

// Fetch hotels
$stmt = $pdo->query("SELECT * FROM hotels ORDER BY rating DESC LIMIT 3");
$hotels = $stmt->fetchAll();

// Fetch activities
$stmt = $pdo->query("SELECT * FROM activities ORDER BY rating DESC LIMIT 3");
$activities = $stmt->fetchAll();

// Fetch surf courses
$stmt = $pdo->query("SELECT * FROM surf_courses LIMIT 3");
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taghazout Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">

</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- ==============================
     HERO SECTION
============================== -->
<section class="hero">
    <video autoplay muted loop playsinline class="hero-video">
        <source src="../assets/videos/hero-video.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <p class="hero-eyebrow">🏄 Bienvenue à Taghazout</p>
        <h1>Découvrez le paradis du surf au Maroc</h1>
        <p class="hero-sub">Hôtels, activités, cours de surf et bien plus encore</p>
        <div class="hero-btns">
            <a href="hotels.php" class="btn-primary">Explorer les Hôtels</a>
            <a href="activities.php" class="btn-secondary">Voir les Activités</a>
        </div>
    </div>
</section>



<!-- ==============================
     HOTELS SECTION
============================== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>🏨 Hôtels Recommandés</h2>
            <a href="hotels.php" class="see-all">Voir tous →</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($hotels as $hotel): ?>
            <div class="card">
                <div class="card-img">
                    <img src="../uploads/hotels/<?= htmlspecialchars($hotel['image']) ?>"
                    onerror="this.src='../assets/images/default.jpg'"                         
                    alt="<?= htmlspecialchars($hotel['name']) ?>">
                    <span class="card-badge">⭐ <?= $hotel['rating'] ?></span>
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($hotel['name']) ?></h3>
                    <p>📍 <?= htmlspecialchars($hotel['location']) ?></p>
                    <div class="card-footer">
                        <span class="price"><?= $hotel['price'] ?> MAD/nuit</span>
                        <a href="hotel-details.php?id=<?= $hotel['id'] ?>" class="btn-card">Voir</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================
     ACTIVITIES SECTION
============================== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <h2>🎯 Activités Populaires</h2>
            <a href="activities.php" class="see-all">Voir tous →</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($activities as $activity): ?>
            <div class="card">
                <div class="card-img">
                    <img src="../uploads/activities/<?= htmlspecialchars($activity['image']) ?>"
                         onerror="this.src='../assets/images/default.jpg'"
                         alt="<?= htmlspecialchars($activity['name']) ?>">
                    <span class="card-badge">⭐ <?= $activity['rating'] ?></span>
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($activity['name']) ?></h3>
                    <p>⏱️ <?= htmlspecialchars($activity['duration']) ?></p>
                    <div class="card-footer">
                        <span class="price"><?= $activity['price'] ?> MAD</span>
                        <a href="activity-details.php?id=<?= $activity['id'] ?>" class="btn-card">Voir</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================
     SURF SECTION
============================== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>🏄 Cours de Surf</h2>
            <a href="surf-courses.php" class="see-all">Voir tous →</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($courses as $course): ?>
            <div class="card">
                <div class="card-img">
                    <img src="../uploads/surf/<?= htmlspecialchars($course['image']) ?>"
                         onerror="this.src='../assets/images/default.jpg'"
                         alt="<?= htmlspecialchars($course['title']) ?>">
                    <span class="card-badge"><?= ucfirst($course['level']) ?></span>
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($course['title']) ?></h3>
                    <p>⏱️ <?= htmlspecialchars($course['duration']) ?></p>
                    <div class="card-footer">
                        <span class="price"><?= $course['price'] ?> MAD</span>
                        <a href="Surf-course-details.php?id=<?= $course['id'] ?>" class="btn-card">Voir</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================
     CTA SECTION
============================== -->
<section class="cta">
    <div class="cta-content">
        <h2>Prêt à vivre l'expérience Taghazout?</h2>
        <p>Inscrivez-vous maintenant et profitez de nos offres exclusives</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="auth/register.php" class="btn-primary">Créer un compte</a>
        <?php else: ?>
            <a href="hotels.php" class="btn-primary">Réserver maintenant</a>
        <?php endif; ?>
    </div>
</section>


<?php require_once 'includes/footer.php'; ?>

<script src="../assets/js/main.js"></script>

</body>
</html>