\<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: activities.php"); exit; }

// ─── Fetch activity ───────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM activities WHERE id = :id");
$stmt->execute([':id' => $id]);
$activity = $stmt->fetch();
if (!$activity) { header("Location: activities.php"); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price']            ?? '';
    $duration    = trim($_POST['duration']    ?? '');
    $location    = trim($_POST['location']    ?? '');
    $rating      = $_POST['rating']           ?? '0.0';

    if ($name === '')                                         $errors[] = "Le nom est obligatoire.";
    if (!is_numeric($price) || $price < 0)                   $errors[] = "Prix invalide.";
    if (!is_numeric($rating) || $rating < 0 || $rating > 5)  $errors[] = "Note invalide (0–5).";

    // ─── Image upload ─────────────────────────────────────
    $image_name = $activity['image'];
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Format image invalide.";
        } else {
            $upload_dir = '../../uploads/activities/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $new_image = uniqid('act_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_image)) {
                // Delete old image
                if ($activity['image'] && file_exists($upload_dir . $activity['image'])) {
                    unlink($upload_dir . $activity['image']);
                }
                $image_name = $new_image;
            } else {
                $errors[] = "Erreur upload image.";
            }
        }
    }

    if (empty($errors)) {
        $pdo->prepare("
            UPDATE activities
            SET name=:name, description=:desc, price=:price,
                duration=:duration, location=:location,
                image=:image, rating=:rating
            WHERE id=:id
        ")->execute([
            ':name'     => $name,
            ':desc'     => $description,
            ':price'    => (float)$price,
            ':duration' => $duration,
            ':location' => $location,
            ':image'    => $image_name,
            ':rating'   => (float)$rating,
            ':id'       => $id,
        ]);

        // Refresh
        $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $activity = $stmt->fetch();

        header("Location: activities.php?saved=1"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier activité #<?= $id ?> — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-shell">

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sb-logo">
        <div class="sb-logo-mark">🏄</div>
        <div class="sb-logo-text"><strong>Taghazout</strong><span>Admin Panel</span></div>
    </div>
    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">
        <a href="../dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
        <div class="sb-label">Contenu</div>
        <a href="../hotels/hotels.php"><span class="nav-icon">🏨</span><span>Hotels</span></a>
        <a href="activities.php" class="active"><span class="nav-icon">🎯</span><span>Activities</span></a>
        <a href="../surf-courses/courses.php"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
        <a href="../restaurants/restaurants.php"><span class="nav-icon">🍽️</span><span>Restaurants</span></a>
        <div class="sb-label">Gestion</div>
        <a href="../bookings/bookings.php"><span class="nav-icon">📅</span><span>Bookings</span></a>
        <a href="../payments/payments.php"><span class="nav-icon">💳</span><span>Payments</span></a>
        <a href="../users/users.php"><span class="nav-icon">👥</span><span>Users</span></a>
        <a href="../reviews/reviews.php"><span class="nav-icon">⭐</span><span>Reviews</span></a>
    </nav>
    <div class="sb-admin">
        <img src="../../assets/images/default.jpg" class="sb-admin-avatar">
        <div class="sb-admin-info">
            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>
            <span>Administrator</span>
        </div>
        <a href="../../auth/logout.php" class="sb-logout">🚪</a>
    </div>
</aside>

<main class="admin-main">

    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Modifier l'activité</div>
                <div class="topbar-breadcrumb">
                    Home <span>/</span> Activités <span>/</span> Modifier #<?= $id ?>
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
                <h1>✏️ Modifier l'activité</h1>
                <p>Modifiez les informations de "<?= htmlspecialchars($activity['name']) ?>"</p>
            </div>
            <div style="display:flex; gap:0.8rem;">
                <a href="../../activity-details.php?id=<?= $id ?>" target="_blank" class="btn btn-secondary">👁️ Voir</a>
                <a href="activities.php" class="btn btn-secondary">← Retour</a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom:1.5rem;">
            <span class="alert-icon">❌</span>
            <div>
                <strong>Erreurs détectées :</strong>
                <ul style="margin:.4rem 0 0 1rem;">
                    <?php foreach ($errors as $e): ?>
                        <li style="margin-top:.2rem;"><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
        <div style="display:grid; grid-template-columns:1fr 360px; gap:1.5rem; align-items:start;">

            <!-- LEFT -->
            <div style="display:flex; flex-direction:column; gap:1.5rem;">

                <!-- Basic Info -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Informations générales</h3>
                        <span style="font-size:0.78rem; color:var(--text-muted);">ID #<?= $id ?></span>
                    </div>
                    <div style="padding:1.5rem; display:flex; flex-direction:column; gap:1.2rem;">

                        <div class="form-group">
                            <label class="form-label">Nom de l'activité *</label>
                            <input type="text" name="name" class="form-control"
                                placeholder="Ex: Surf Session"
                                value="<?= htmlspecialchars($_POST['name'] ?? $activity['name']) ?>" required>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div class="form-group">
                                <label class="form-label">Localisation</label>
                                <input type="text" name="location" class="form-control"
                                    placeholder="Ex: Taghazout Beach"
                                    value="<?= htmlspecialchars($_POST['location'] ?? $activity['location']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Durée</label>
                                <input type="text" name="duration" class="form-control"
                                    placeholder="Ex: 2h"
                                    value="<?= htmlspecialchars($_POST['duration'] ?? $activity['duration']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="5"
                                placeholder="Décrivez l'activité..."><?= htmlspecialchars($_POST['description'] ?? $activity['description']) ?></textarea>
                        </div>

                    </div>
                </div>

                <!-- Image -->
                <div class="card">
                    <div class="card-header">
                        <h3>🖼️ Image de l'activité</h3>
                    </div>
                    <div style="padding:1.5rem;">

                        <?php if ($activity['image']): ?>
                        <div style="margin-bottom:1rem; text-align:center;">
                            <p style="font-size:0.78rem; color:var(--text-muted); margin-bottom:0.6rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600;">Image actuelle</p>
                            <img src="../../uploads/activities/<?= htmlspecialchars($activity['image']) ?>"
                                onerror="this.src='../../assets/images/default.jpg'"
                                style="max-height:180px; border-radius:var(--radius-md); object-fit:cover; width:100%; border:1px solid var(--border-subtle);">
                        </div>
                        <?php endif; ?>

                        <div class="upload-zone" id="upload-zone">
                            <input type="file" name="image" id="image-input" accept="image/*" style="display:none;">
                            <div id="upload-preview" style="display:none; text-align:center;">
                                <img id="preview-img" src="" alt="Preview"
                                    style="max-height:200px; border-radius:var(--radius-md); object-fit:cover; width:100%;">
                                <button type="button" onclick="resetUpload()"
                                    style="margin-top:0.8rem; background:var(--color-danger-bg); color:var(--color-danger); border:none; border-radius:50px; padding:0.4rem 1rem; font-size:0.8rem; cursor:pointer; font-family:'DM Sans',sans-serif;">
                                    🗑️ Changer
                                </button>
                            </div>
                            <div id="upload-placeholder" style="padding:1rem;">
                                <div style="font-size:2.5rem; margin-bottom:0.6rem; opacity:0.4;">📷</div>
                                <p style="font-size:0.88rem; color:var(--text-muted); margin:0 0 0.3rem; font-weight:500;">
                                    <?= $activity['image'] ? 'Changer l\'image' : 'Ajouter une image' ?>
                                </p>
                                <p style="font-size:0.75rem; color:var(--text-light); margin:0;">JPG, PNG, WEBP</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT -->
            <div style="display:flex; flex-direction:column; gap:1.5rem;">

                <!-- Pricing -->
                <div class="card">
                    <div class="card-header">
                        <h3>💰 Tarification</h3>
                    </div>
                    <div style="padding:1.5rem; display:flex; flex-direction:column; gap:1.2rem;">
                        <div class="form-group">
                            <label class="form-label">Prix par personne (MAD) *</label>
                            <div style="position:relative;">
                                <span style="position:absolute; left:0.9rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.88rem; font-weight:600;">MAD</span>
                                <input type="number" name="price" class="form-control"
                                    placeholder="0.00" min="0" step="0.01"
                                    style="padding-left:3rem;"
                                    value="<?= htmlspecialchars($_POST['price'] ?? $activity['price']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Note (0–5)</label>
                            <div style="position:relative;">
                                <span style="position:absolute; left:0.9rem; top:50%; transform:translateY(-50%); color:#f59e0b;">⭐</span>
                                <input type="number" name="rating" class="form-control"
                                    placeholder="0.0" min="0" max="5" step="0.1"
                                    style="padding-left:2.5rem;"
                                    value="<?= htmlspecialchars($_POST['rating'] ?? $activity['rating']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="card">
                    <div class="card-header">
                        <h3>📊 Statistiques</h3>
                    </div>
                    <div style="padding:1.5rem; display:flex; flex-direction:column; gap:0.8rem;">
                        <?php
                        $bookings_count = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE type='activity' AND reference_id=:id");
                        $bookings_count->execute([':id' => $id]);
                        $bc = $bookings_count->fetchColumn();

                        $reviews_count = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE type='activity' AND reference_id=:id");
                        $reviews_count->execute([':id' => $id]);
                        $rc = $reviews_count->fetchColumn();

                        $favorites_count = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE type='activity' AND reference_id=:id");
                        $favorites_count->execute([':id' => $id]);
                        $fc = $favorites_count->fetchColumn();
                        ?>
                        <div style="display:flex; justify-content:space-between; padding:0.7rem; background:var(--bg-surface); border-radius:var(--radius-sm);">
                            <span style="font-size:0.85rem; color:var(--text-muted);">📅 Réservations</span>
                            <strong style="color:var(--ocean-teal);"><?= $bc ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:0.7rem; background:var(--bg-surface); border-radius:var(--radius-sm);">
                            <span style="font-size:0.85rem; color:var(--text-muted);">⭐ Avis</span>
                            <strong style="color:#f59e0b;"><?= $rc ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:0.7rem; background:var(--bg-surface); border-radius:var(--radius-sm);">
                            <span style="font-size:0.85rem; color:var(--text-muted);">❤️ Favoris</span>
                            <strong style="color:#ef4444;"><?= $fc ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div style="padding:1.5rem; display:flex; flex-direction:column; gap:0.8rem;">
                        <button type="submit" class="btn btn-primary btn-lg" style="width:100%; justify-content:center;">
                            💾 Sauvegarder les modifications
                        </button>
                        <a href="activities.php" class="btn btn-secondary" style="width:100%; justify-content:center; text-align:center;">
                            Annuler
                        </a>
                        <hr style="border:none; border-top:1px solid var(--border-subtle); margin:0.3rem 0;">
                        <a href="delete-activity.php?id=<?= $id ?>"
                            onclick="return confirm('Supprimer définitivement cette activité ?')"
                            class="btn btn-danger" style="width:100%; justify-content:center; text-align:center;">
                            🗑️ Supprimer l'activité
                        </a>
                    </div>
                </div>

            </div>

        </div>
        </form>

    </div>
</main>
</div>

<style>
.upload-zone {
    border: 2px dashed rgba(14,165,233,0.25);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    min-height: 130px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}
.upload-zone:hover {
    border-color: var(--ocean-teal);
    background: rgba(14,165,233,0.03);
}
.upload-zone.dragover {
    border-color: var(--ocean-teal);
    background: rgba(14,165,233,0.06);
}
</style>

<script>
const zone        = document.getElementById('upload-zone');
const input       = document.getElementById('image-input');
const preview     = document.getElementById('upload-preview');
const placeholder = document.getElementById('upload-placeholder');
const previewImg  = document.getElementById('preview-img');

zone.addEventListener('click', () => input.click());
input.addEventListener('change', () => showPreview(input.files[0]));

zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) { const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; showPreview(file); }
});

function showPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        preview.style.display     = 'flex';
        preview.style.flexDirection = 'column';
        preview.style.alignItems  = 'center';
        placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function resetUpload() {
    input.value               = '';
    preview.style.display     = 'none';
    placeholder.style.display = 'block';
    previewImg.src            = '';
}
</script>
<script src="../../assets/js/main.js"></script>
</body>
</html>