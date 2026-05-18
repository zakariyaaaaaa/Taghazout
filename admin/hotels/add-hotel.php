<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $location    = trim($_POST['location']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $rating      = (float)($_POST['rating']   ?? 0);
    $stars       = (int)($_POST['stars']      ?? 0);
    $type        = trim($_POST['type']        ?? '');

    if (strlen($name) < 2)     $errors[] = 'Le nom doit contenir au moins 2 caractères.';
    if (strlen($location) < 2) $errors[] = 'La localisation est requise.';
    if ($price <= 0)            $errors[] = 'Le prix doit être supérieur à 0.';
    if ($rating < 0 || $rating > 5) $errors[] = 'La note doit être entre 0 et 5.';

    $image_name = '';
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format image non supporté (jpg, jpeg, png, webp).';
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'L\'image ne doit pas dépasser 3 Mo.';
        } else {
            $image_name = 'hotel_' . time() . '_' . rand(100, 999) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/hotels/' . $image_name);
        }
    }

    if (empty($errors)) {
        $pdo->prepare("
            INSERT INTO hotels (name, location, description, price, rating, stars, type, image)
            VALUES (:name, :location, :description, :price, :rating, :stars, :type, :image)
        ")->execute([
            ':name'        => $name,
            ':location'    => $location,
            ':description' => $description,
            ':price'       => $price,
            ':rating'      => $rating,
            ':stars'       => $stars ?: null,
            ':type'        => $type ?: null,
            ':image'       => $image_name,
        ]);
        header("Location: hotels.php?saved=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un hôtel — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>

<div class="admin-shell">

<aside class="admin-sidebar">
    <div class="sb-logo">
        <div class="sb-logo-mark">🏄</div>
        <div class="sb-logo-text"><strong>Taghazout</strong><span>Admin Panel</span></div>
    </div>
    <div class="sb-label">Dashboard</div>
    <nav class="sb-nav">
        <a href="../dashboard.php"><span class="nav-icon">📊</span><span>Dashboard</span></a>
        <div class="sb-label">Contenu</div>
        <a href="hotels.php" class="active"><span class="nav-icon">🏨</span><span>Hotels</span></a>
        <a href="../activities/activities.php"><span class="nav-icon">🎯</span><span>Activities</span></a>
        <a href="../surf-courses/courses.php"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
        <a href="../restaurants/restaurants.php"><span class="nav-icon">🍽️</span><span>Restaurants</span></a>
        <div class="sb-label">Gestion</div>
        <a href="../bookings/bookings.php"><span class="nav-icon">📅</span><span>Bookings</span></a>
        <a href="../users/users.php"><span class="nav-icon">👥</span><span>Users</span></a>
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
            <div class="topbar-title">Ajouter un hôtel</div>
            <div class="topbar-breadcrumb">
                Home <span>/</span> <a href="hotels.php">Hôtels</a> <span>/</span> Ajouter
            </div>
        </div>
    </div>
</div>

<div class="admin-body">

    <div class="page-header">
        <div>
            <h1>🏨 Ajouter un hôtel</h1>
            <p>Remplissez les informations du nouvel hôtel</p>
        </div>
        <a href="hotels.php" style="height:42px; padding:0 1.2rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.2); color:var(--text-light); font-size:.88rem; text-decoration:none; gap:.4rem; transition:var(--transition);">← Retour</a>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="errors" style="margin-bottom:1.5rem;">
        <ul><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">

                <div class="form-group">
                    <label>Nom de l'hôtel *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Ex: Taghazout Bay Resort" required>
                </div>

                <div class="form-group">
                    <label>Localisation *</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" placeholder="Ex: Taghazout Beach" required>
                </div>

                <div class="form-group">
                    <label>Prix / nuit (MAD) *</label>
                    <input type="number" name="price" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" placeholder="500" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Note (0 - 5)</label>
                    <input type="number" name="rating" value="<?= htmlspecialchars($_POST['rating'] ?? '0') ?>" min="0" max="5" step="0.1">
                </div>

                <div class="form-group">
                    <label>Nombre d'étoiles</label>
                    <select name="stars">
                        <option value="">— Sélectionner —</option>
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <option value="<?= $s ?>" <?= (isset($_POST['stars']) && $_POST['stars'] == $s) ? 'selected' : '' ?>>
                                <?= str_repeat('★', $s) ?> (<?= $s ?> étoile<?= $s > 1 ? 's' : '' ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Type d'hôtel</label>
                    <input type="text" name="type" value="<?= htmlspecialchars($_POST['type'] ?? '') ?>" placeholder="Ex: riad, resort, boutique...">
                </div>

            </div>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Description de l'hôtel..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <!-- Image Upload -->
            <div class="form-group" style="margin-bottom:2rem;">
                <label>Photo de l'hôtel</label>
                <div id="drop-zone" style="border:2px dashed rgba(14,165,233,0.25); border-radius:var(--radius-sm); padding:2.5rem; text-align:center; cursor:pointer; transition:var(--transition); background:var(--primary-light);" onclick="document.getElementById('image-input').click()">
                    <div id="drop-preview" style="display:none; margin-bottom:1rem;">
                        <img id="preview-img" src="" style="max-height:180px; border-radius:var(--radius-sm); margin:0 auto;">
                    </div>
                    <div id="drop-text">
                        <div style="font-size:2rem; margin-bottom:.5rem;">📷</div>
                        <div style="font-weight:600; color:var(--text); margin-bottom:.25rem;">Cliquez ou glissez une image</div>
                        <div style="font-size:.78rem; color:var(--text-light);">JPG, PNG, WEBP — max 3 Mo</div>
                    </div>
                    <input type="file" id="image-input" name="image" accept="image/*" style="display:none" onchange="previewImage(this)">
                </div>
            </div>

            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                <a href="hotels.php" style="height:46px; padding:0 1.5rem; display:flex; align-items:center; border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.2); color:var(--text-light); font-size:.9rem; text-decoration:none; transition:var(--transition);">Annuler</a>
                <button type="submit" class="btn-primary" style="height:46px; padding:0 2rem;">💾 Enregistrer l'hôtel</button>
            </div>

        </form>
    </div>

</div>
</main>
</div>

<script src="../../assets/js/main.js"></script>
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('drop-preview').style.display = 'block';
            document.getElementById('drop-text').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Drag & drop
const zone = document.getElementById('drop-zone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor = 'var(--primary)'; });
zone.addEventListener('dragleave', () => { zone.style.borderColor = 'rgba(14,165,233,0.25)'; });
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.style.borderColor = 'rgba(14,165,233,0.25)';
    const file = e.dataTransfer.files[0];
    if (file) {
        const input = document.getElementById('image-input');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        previewImage(input);
    }
});
</script>
</body>
</html>