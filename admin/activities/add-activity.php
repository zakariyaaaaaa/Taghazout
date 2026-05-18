<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price']            ?? '';
    $duration    = trim($_POST['duration']    ?? '');
    $location    = trim($_POST['location']    ?? '');
    $rating      = $_POST['rating']           ?? '0.0';

    if ($name === '')                                      $errors[] = "Le nom est obligatoire.";
    if (!is_numeric($price) || $price < 0)                $errors[] = "Prix invalide.";
    if (!is_numeric($rating) || $rating < 0 || $rating > 5) $errors[] = "Note invalide (0–5).";

    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Format image invalide.";
        } else {
            $upload_dir = '../../uploads/activities/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $image_name = uniqid('act_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                $errors[] = "Erreur upload image.";
                $image_name = null;
            }
        }
    }

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO activities (name, description, price, duration, location, image, rating) VALUES (:name, :desc, :price, :duration, :location, :image, :rating)")
            ->execute([':name' => $name, ':desc' => $description, ':price' => (float)$price, ':duration' => $duration, ':location' => $location, ':image' => $image_name, ':rating' => (float)$rating]);
        header("Location: activities.php?saved=1"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une activité — Admin</title>
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
                <div class="topbar-title">Ajouter une activité</div>
                <div class="topbar-breadcrumb">
                    Home <span>/</span> Activités <span>/</span> Ajouter
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
                <h1>🎯 Ajouter une activité</h1>
                <p>Remplissez les informations de la nouvelle activité</p>
            </div>
            <a href="activities.php" class="btn btn-secondary">← Retour</a>
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

            <!-- LEFT: Form fields -->
            <div style="display:flex; flex-direction:column; gap:1.5rem;">

                <!-- Basic Info -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Informations générales</h3>
                    </div>
                    <div style="padding:1.5rem; display:flex; flex-direction:column; gap:1.2rem;">

                        <div class="form-group">
                            <label class="form-label">Nom de l'activité *</label>
                            <input type="text" name="name" class="form-control"
                                placeholder="Ex: Surf Session, Yoga sur la plage..."
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div class="form-group">
                                <label class="form-label">Localisation</label>
                                <input type="text" name="location" class="form-control"
                                    placeholder="Ex: Taghazout Beach"
                                    value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Durée</label>
                                <input type="text" name="duration" class="form-control"
                                    placeholder="Ex: 2h, 1 journée..."
                                    value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="5"
                                placeholder="Décrivez l'activité en détail..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                    </div>
                </div>

                <!-- Image Upload -->
                <div class="card">
                    <div class="card-header">
                        <h3>🖼️ Image de l'activité</h3>
                    </div>
                    <div style="padding:1.5rem;">
                        <div class="upload-zone" id="upload-zone">
                            <input type="file" name="image" id="image-input" accept="image/*" style="display:none;">
                            <div id="upload-preview" style="display:none; text-align:center;">
                                <img id="preview-img" src="" alt="Preview"
                                    style="max-height:220px; border-radius:var(--radius-md); object-fit:cover; width:100%;">
                                <button type="button" onclick="resetUpload()"
                                    style="margin-top:0.8rem; background:var(--color-danger-bg); color:var(--color-danger); border:none; border-radius:50px; padding:0.4rem 1rem; font-size:0.8rem; cursor:pointer; font-family:'DM Sans',sans-serif;">
                                    🗑️ Changer l'image
                                </button>
                            </div>
                            <div id="upload-placeholder" style="padding:1rem;">
                                <div style="font-size:3rem; margin-bottom:0.8rem; opacity:0.4;">📷</div>
                                <p style="font-size:0.9rem; color:var(--text-muted); margin:0 0 0.4rem; font-weight:500;">
                                    Glissez une image ou cliquez pour parcourir
                                </p>
                                <p style="font-size:0.78rem; color:var(--text-light); margin:0;">
                                    JPG, PNG, WEBP — Max 5 Mo
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT: Sidebar -->
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
                                <span style="position:absolute; left:0.9rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.9rem; font-weight:600;">MAD</span>
                                <input type="number" name="price" class="form-control"
                                    placeholder="0.00" min="0" step="0.01"
                                    style="padding-left:3rem;"
                                    value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Note initiale (0–5)</label>
                            <div style="position:relative;">
                                <span style="position:absolute; left:0.9rem; top:50%; transform:translateY(-50%); color:#f59e0b;">⭐</span>
                                <input type="number" name="rating" class="form-control"
                                    placeholder="0.0" min="0" max="5" step="0.1"
                                    style="padding-left:2.5rem;"
                                    value="<?= htmlspecialchars($_POST['rating'] ?? '0.0') ?>">
                            </div>
                            <span style="font-size:0.72rem; color:var(--text-light); margin-top:0.2rem;">
                                La note sera mise à jour automatiquement avec les avis.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div style="padding:1.5rem; display:flex; flex-direction:column; gap:0.8rem;">
                        <button type="submit" class="btn btn-primary btn-lg" style="width:100%; justify-content:center;">
                            ✅ Enregistrer l'activité
                        </button>
                        <a href="activities.php" class="btn btn-secondary" style="width:100%; justify-content:center; text-align:center;">
                            Annuler
                        </a>
                    </div>
                </div>

                <!-- Tips -->
                <div style="background:var(--color-info-bg); border:1px solid rgba(14,165,233,0.15); border-radius:var(--radius-md); padding:1.2rem;">
                    <h4 style="font-family:'Syne',sans-serif; font-size:0.88rem; font-weight:700; color:var(--color-info); margin-bottom:0.8rem;">
                        💡 Conseils
                    </h4>
                    <ul style="font-size:0.78rem; color:var(--text-muted); line-height:1.8; margin-left:1rem;">
                        <li>Utilisez une image de haute qualité</li>
                        <li>La description aide les clients à choisir</li>
                        <li>Précisez la durée exacte de l'activité</li>
                    </ul>
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
    padding: 2rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    min-height: 160px;
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
    transform: scale(1.01);
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
        previewImg.src   = e.target.result;
        preview.style.display      = 'flex';
        preview.style.flexDirection = 'column';
        preview.style.alignItems   = 'center';
        placeholder.style.display  = 'none';
    };
    reader.readAsDataURL(file);
}

function resetUpload() {
    input.value            = '';
    preview.style.display  = 'none';
    placeholder.style.display = 'block';
    previewImg.src         = '';
}
</script>
<script src="../../assets/js/main.js"></script>
</body>
</html>