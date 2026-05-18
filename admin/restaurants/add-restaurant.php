<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $cuisine     = trim($_POST['cuisine']     ?? '');
    $price_range = trim($_POST['price_range'] ?? '');
    $location    = trim($_POST['location']    ?? '');
    $rating      = $_POST['rating']           ?? '0.0';

    $allowed_price = ['cheap', 'moderate', 'expensive'];

    if ($name === '')                          $errors[] = "Le nom est obligatoire.";
    if (!in_array($price_range, $allowed_price)) $errors[] = "Gamme de prix invalide.";
    if (!is_numeric($rating) || $rating < 0 || $rating > 5) $errors[] = "Note invalide (0–5).";

    // ── Image upload ─────────────────────────────────────
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed_ext = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            $errors[] = "Format image invalide (jpg, jpeg, png, webp).";
        } else {
            $upload_dir = '../../uploads/restaurants/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $image_name = uniqid('resto_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                $errors[] = "Erreur lors de l'upload de l'image.";
                $image_name = null;
            }
        }
    }

    if (empty($errors)) {
        $pdo->prepare("
            INSERT INTO restaurants (name, description, cuisine, price_range, location, image, rating)
            VALUES (:name, :desc, :cuisine, :price_range, :location, :image, :rating)
        ")->execute([
            ':name'        => $name,
            ':desc'        => $description,
            ':cuisine'     => $cuisine,
            ':price_range' => $price_range,
            ':location'    => $location,
            ':image'       => $image_name,
            ':rating'      => (float)$rating,
        ]);
        header("Location: restaurants.php?saved=1"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un restaurant — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
<aside class="admin-sidebar">
    <div class="sb-logo"><div class="sb-logo-mark">🏄</div><div class="sb-logo-text"><strong>Taghazout</strong><span>Admin Panel</span></div></div>
    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">
        <a href="../dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
        <div class="sb-label">Contenu</div>
        <a href="../hotels/hotels.php"><span class="nav-icon">🏨</span><span>Hotels</span></a>
        <a href="../activities/activities.php"><span class="nav-icon">🎯</span><span>Activities</span></a>
        <a href="../surf-courses/courses.php"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
        <a href="restaurants.php" class="active"><span class="nav-icon">🍽️</span><span>Restaurants</span></a>
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
        <div class="topbar-title">Ajouter un restaurant</div>
        <div class="topbar-breadcrumb">Home <span>/</span> Restaurants <span>/</span> Ajouter</div>
    </div></div>
    <div class="topbar-right"><button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button></div>
</div>
<div class="admin-body">

    <div class="page-header">
        <div><h1>🍽️ Ajouter un restaurant</h1><p>Remplissez les informations ci-dessous</p></div>
        <a href="restaurants.php" class="btn-secondary">← Retour</a>
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
                    <label class="form-label">Nom du restaurant *</label>
                    <input type="text" name="name" class="form-input"
                           placeholder="Ex: La Sqala"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Localisation</label>
                    <input type="text" name="location" class="form-input"
                           placeholder="Ex: Taghazout Beach"
                           value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
            </div>

            <!-- Row 2 -->
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Type de cuisine</label>
                    <input type="text" name="cuisine" class="form-input"
                           placeholder="Ex: Marocaine, Italienne..."
                           value="<?= htmlspecialchars($_POST['cuisine'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Gamme de prix *</label>
                    <select name="price_range" class="form-input" required>
                        <option value="">— Choisir —</option>
                        <option value="cheap"     <?= ($_POST['price_range'] ?? '') === 'cheap'     ? 'selected' : '' ?>>€ Pas cher</option>
                        <option value="moderate"  <?= ($_POST['price_range'] ?? '') === 'moderate'  ? 'selected' : '' ?>>€€ Modéré</option>
                        <option value="expensive" <?= ($_POST['price_range'] ?? '') === 'expensive' ? 'selected' : '' ?>>€€€ Luxe</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Note (0–5)</label>
                    <input type="number" name="rating" class="form-input"
                           placeholder="0.0" min="0" max="5" step="0.1"
                           value="<?= htmlspecialchars($_POST['rating'] ?? '0.0') ?>">
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="5"
                          placeholder="Décrivez le restaurant..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <!-- Image -->
            <div class="form-group">
                <label class="form-label">Image</label>
                <div class="upload-zone" id="upload-zone">
                    <input type="file" name="image" id="image-input" accept="image/*" style="display:none;">
                    <div id="upload-preview" style="display:none;"><img id="preview-img" src="" alt="Preview" style="max-height:200px; border-radius:var(--radius-sm);"></div>
                    <div id="upload-placeholder">
                        <div style="font-size:2.5rem; margin-bottom:.5rem;">📷</div>
                        <p style="color:var(--text-light); font-size:.9rem; margin:0;">Cliquez ou glissez une image ici</p>
                        <p style="color:var(--text-light); font-size:.78rem; margin:.3rem 0 0;">JPG, PNG, WEBP</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex; gap:1rem; justify-content:flex-end; padding-top:.5rem; border-top:1px solid rgba(14,165,233,0.08);">
                <a href="restaurants.php" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">✅ Enregistrer le restaurant</button>
            </div>
        </form>
    </div>
</div>
</main>
</div>
<style>
.form-group{display:flex;flex-direction:column;gap:.4rem}
.form-label{font-size:.78rem;font-weight:600;color:var(--text-light);text-transform:uppercase;letter-spacing:.06em}
.form-input{padding:.7rem 1rem;border:1.5px solid rgba(14,165,233,0.15);border-radius:var(--radius-sm);background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s;width:100%;box-sizing:border-box}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 4px rgba(14,165,233,0.08)}
textarea.form-input{resize:vertical}
.upload-zone{border:2px dashed rgba(14,165,233,0.25);border-radius:var(--radius-sm);padding:2rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s}
.upload-zone:hover{border-color:var(--primary);background:rgba(14,165,233,0.03)}
.btn-secondary{padding:.65rem 1.4rem;border:1.5px solid rgba(14,165,233,0.2);border-radius:50px;color:var(--text-light);font-size:.88rem;font-weight:600;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem;background:transparent;cursor:pointer}
.btn-secondary:hover{border-color:var(--primary);color:var(--primary)}
</style>
<script>
const zone=document.getElementById('upload-zone'),input=document.getElementById('image-input'),preview=document.getElementById('upload-preview'),placeholder=document.getElementById('upload-placeholder'),previewImg=document.getElementById('preview-img');
zone.addEventListener('click',()=>input.click());
input.addEventListener('change',()=>showPreview(input.files[0]));
zone.addEventListener('dragover',e=>{e.preventDefault();zone.style.borderColor='var(--primary)'});
zone.addEventListener('dragleave',()=>zone.style.borderColor='');
zone.addEventListener('drop',e=>{e.preventDefault();zone.style.borderColor='';const f=e.dataTransfer.files[0];if(f){input.files=e.dataTransfer.files;showPreview(f)}});
function showPreview(file){if(!file)return;const r=new FileReader();r.onload=e=>{previewImg.src=e.target.result;preview.style.display='block';placeholder.style.display='none'};r.readAsDataURL(file)}
</script>
<script src="../../assets/js/main.js"></script>
</body>
</html>