<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: activities.php"); exit; }

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

    if ($name === '')              $errors[] = "Le nom est obligatoire.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Prix invalide.";
    if (!is_numeric($rating) || $rating < 0 || $rating > 5) $errors[] = "Note invalide (0–5).";

    // ── Image upload ─────────────────────────────────────
    $image_name = $activity['image']; // garde l'ancienne par défaut
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Format image invalide (jpg, jpeg, png, webp).";
        } else {
            $upload_dir = '../../uploads/activities/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $new_name = uniqid('act_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                // Supprimer l'ancienne image
                if ($activity['image'] && file_exists($upload_dir . $activity['image'])) {
                    unlink($upload_dir . $activity['image']);
                }
                $image_name = $new_name;
            } else {
                $errors[] = "Erreur lors de l'upload de l'image.";
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
        header("Location: activities.php?saved=1"); exit;
    }

    // Pour réafficher les valeurs saisies en cas d'erreur
    $activity['name']        = $name;
    $activity['description'] = $description;
    $activity['price']       = $price;
    $activity['duration']    = $duration;
    $activity['location']    = $location;
    $activity['rating']      = $rating;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier activité #<?= $id ?> — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-shell">

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sb-logo"><div class="sb-logo-mark">🏄</div><div class="sb-logo-text"><strong>Taghazout</strong><span>Admin Panel</span></div></div>
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
        <div class="sb-admin-info"><strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong><span>Administrator</span></div>
        <a href="../../auth/logout.php" class="sb-logout">🚪</a>
    </div>
</aside>

<main class="admin-main">
<div class="admin-topbar">
    <div class="topbar-left"><div>
        <div class="topbar-title">Modifier l'activité</div>
        <div class="topbar-breadcrumb">Home <span>/</span> Activités <span>/</span> Modifier #<?= $id ?></div>
    </div></div>
    <div class="topbar-right"><button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button></div>
</div>

<div class="admin-body">
    <div class="page-header">
        <div><h1>✏️ Modifier l'activité</h1><p>#<?= $id ?> — <?= htmlspecialchars($activity['name']) ?></p></div>
        <a href="activities.php" class="btn-secondary">← Retour</a>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert-error" style="margin-bottom:1.5rem;">
        <strong>❌ Erreurs :</strong>
        <ul style="margin:.5rem 0 0 1.2rem;">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1.5rem;">

            <!-- Row 1 -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Nom de l'activité *</label>
                    <input type="text" name="name" class="form-input"
                           value="<?= htmlspecialchars($activity['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Localisation</label>
                    <input type="text" name="location" class="form-input"
                           value="<?= htmlspecialchars($activity['location'] ?? '') ?>">
                </div>
            </div>

            <!-- Row 2 -->
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Prix (MAD) *</label>
                    <input type="number" name="price" class="form-input"
                           value="<?= htmlspecialchars($activity['price']) ?>"
                           min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Durée</label>
                    <input type="text" name="duration" class="form-input"
                           value="<?= htmlspecialchars($activity['duration'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Note (0–5)</label>
                    <input type="number" name="rating" class="form-input"
                           value="<?= htmlspecialchars($activity['rating']) ?>"
                           min="0" max="5" step="0.1">
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="5"><?= htmlspecialchars($activity['description'] ?? '') ?></textarea>
            </div>

            <!-- Image -->
            <div class="form-group">
                <label class="form-label">Image</label>
                <div style="display:flex; gap:1.5rem; align-items:flex-start; flex-wrap:wrap;">
                    <!-- Current image -->
                    <?php if (!empty($activity['image'])): ?>
                    <div style="flex-shrink:0;">
                        <p style="font-size:.75rem; color:var(--text-light); margin:0 0 .4rem;">Image actuelle</p>
                        <img 
                                                            src="../../uploads/activities/<?= htmlspecialchars($activity['image']) ?>"
                                    onerror="this.src='../../assets/images/default.jpg'"
                                    alt="<?= htmlspecialchars($activity['name']) ?>"
                                    style="height:160px;width:auto;max-width:100%;border-radius:var(--radius-md);object-fit:cover;border:1px solid var(--border-subtle);"
                                    id="current-img"
                                >
                    </div>
                    <?php endif; ?>
                    <!-- Upload new -->
                    <div style="flex:1; min-width:200px;">
                        <p style="font-size:.75rem; color:var(--text-light); margin:0 0 .4rem;">Nouvelle image (optionnel)</p>
                        <div class="upload-zone" id="upload-zone">
                            <input type="file" name="image" id="image-input" accept="image/*" style="display:none;">
                            <div class="upload-preview" id="upload-preview" style="display:none;">
                                <img id="preview-img" src="" style="max-height:160px;border-radius:var(--radius-md);margin:0 auto;border:1px solid var(--border-subtle);">
                        </div>
                            <div id="upload-placeholder">
                                <div style="font-size:1.8rem; margin-bottom:.3rem;">📷</div>
                                <p style="color:var(--text-light); font-size:.85rem; margin:0;">Cliquez pour changer l'image</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex; gap:1rem; justify-content:flex-end; padding-top:.5rem; border-top:1px solid rgba(14,165,233,0.08);">
                <a href="activities.php" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">💾 Enregistrer les modifications</button>
            </div>

        </form>
    </div>
</div>
</main>
</div>

<style>
.form-group { display:flex; flex-direction:column; gap:.4rem; }
.form-label { font-size:.78rem; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; }
.form-input { padding:.7rem 1rem; border:1.5px solid rgba(14,165,233,0.15); border-radius:var(--radius-sm); background:var(--bg); color:var(--text); font-family:'DM Sans',sans-serif; font-size:.9rem; outline:none; transition:border-color .2s; width:100%; box-sizing:border-box; }
.form-input:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(14,165,233,0.08); }
textarea.form-input { resize:vertical; }
.upload-zone { border:2px dashed rgba(14,165,233,0.25); border-radius:var(--radius-sm); padding:1.5rem; text-align:center; cursor:pointer; transition:border-color .2s, background .2s; }
.upload-zone:hover { border-color:var(--primary); background:rgba(14,165,233,0.03); }
.upload-preview img { max-height:150px; border-radius:var(--radius-sm); object-fit:cover; }
.btn-secondary { padding:.65rem 1.4rem; border:1.5px solid rgba(14,165,233,0.2); border-radius:50px; color:var(--text-light); font-size:.88rem; font-weight:600; text-decoration:none; transition:all .2s; display:inline-flex; align-items:center; gap:.4rem; background:transparent; cursor:pointer; }
.btn-secondary:hover { border-color:var(--primary); color:var(--primary); }
</style>
<script>
const zone     = document.getElementById('upload-zone');
const input    = document.getElementById('image-input');
const preview  = document.getElementById('upload-preview');
const placeholder = document.getElementById('upload-placeholder');
const previewImg  = document.getElementById('preview-img');

zone.addEventListener('click', () => input.click());
input.addEventListener('change', () => showPreview(input.files[0]));
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) { input.files = e.dataTransfer.files; showPreview(file); }
});

function showPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
}
</script>
<script src="../../assets/js/main.js"></script>
</body>
</html>