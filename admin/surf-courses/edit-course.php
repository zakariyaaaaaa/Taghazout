<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: courses.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM surf_courses WHERE id = :id");
$stmt->execute([':id' => $id]);
$course = $stmt->fetch();
if (!$course) { header("Location: courses.php"); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title']        ?? '');
    $description  = trim($_POST['description']  ?? '');
    $price        = $_POST['price']             ?? '';
    $duration     = trim($_POST['duration']     ?? '');
    $level        = trim($_POST['level']        ?? 'beginner');
    $max_students = (int)($_POST['max_students'] ?? 10);

    $allowed_levels = ['beginner','intermediate','advanced'];

    if ($title === '')                      $errors[] = "Le titre est obligatoire.";
    if ($description === '')               $errors[] = "La description est obligatoire.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Prix invalide.";
    if (!in_array($level, $allowed_levels)) $errors[] = "Niveau invalide.";
    if ($max_students < 1)                 $errors[] = "Nombre d'étudiants invalide.";

    $image_name = $course['image'];
    if (!empty($_FILES['image']['name'])) {
        $allowed_ext = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            $errors[] = "Format image invalide.";
        } else {
            $upload_dir = '../../uploads/surf/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $new_name = uniqid('surf_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                if ($course['image'] && file_exists($upload_dir . $course['image'])) {
                    unlink($upload_dir . $course['image']);
                }
                $image_name = $new_name;
            } else {
                $errors[] = "Erreur lors de l'upload de l'image.";
            }
        }
    }

    if (empty($errors)) {
        $pdo->prepare("
            UPDATE surf_courses
            SET title=:title, description=:desc, price=:price,
                duration=:duration, level=:level, max_students=:max_students, image=:image
            WHERE id=:id
        ")->execute([
            ':title'        => $title,
            ':desc'         => $description,
            ':price'        => (float)$price,
            ':duration'     => $duration,
            ':level'        => $level,
            ':max_students' => $max_students,
            ':image'        => $image_name,
            ':id'           => $id,
        ]);
        header("Location: courses.php?saved=1"); exit;
    }

    $course = array_merge($course, compact('title','description','price','duration','level','max_students'));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier cours #<?= $id ?> — Admin</title>
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
        <a href="courses.php" class="active"><span class="nav-icon">🏄</span><span>Surf Courses</span></a>
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
        <div class="topbar-title">Modifier le cours</div>
        <div class="topbar-breadcrumb">Home <span>/</span> Surf Courses <span>/</span> Modifier #<?= $id ?></div>
    </div></div>
    <div class="topbar-right"><button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button></div>
</div>
<div class="admin-body">

    <div class="page-header">
        <div><h1>✏️ Modifier le cours</h1><p>#<?= $id ?> — <?= htmlspecialchars($course['title']) ?></p></div>
        <div style="display:flex;gap:.75rem;">
            <a href="delete-course.php?id=<?= $id ?>" onclick="return confirm('Supprimer ce cours ?')"
               style="padding:.65rem 1.2rem;border:1.5px solid rgba(239,68,68,.3);border-radius:50px;color:#ef4444;font-size:.88rem;font-weight:600;text-decoration:none;transition:all .2s;">🗑️ Supprimer</a>
            <a href="courses.php" class="btn-secondary">← Retour</a>
        </div>
    </div>

    <!-- Meta info -->
    <div style="display:flex;gap:1.5rem;background:var(--card-bg);border:1px solid rgba(14,165,233,.06);border-radius:var(--radius-sm);padding:.85rem 1.2rem;margin-bottom:1.5rem;font-size:.85rem;color:var(--text-light);">
        <span>🆔 ID : <strong style="color:var(--text);">#<?= $id ?></strong></span>
        <span>📅 Créé le : <strong style="color:var(--text);"><?= date('d/m/Y', strtotime($course['created_at'] ?? 'now')) ?></strong></span>
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
        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:1.5rem;">

            <!-- Infos de base -->
            <div>
                <h3 style="font-size:.8rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;margin-bottom:1.1rem;padding-bottom:.6rem;border-bottom:1px solid rgba(14,165,233,.08);">📋 Informations de base</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Titre du cours *</label>
                        <input type="text" name="title" class="form-input"
                               value="<?= htmlspecialchars($course['title']) ?>" required>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-input" rows="4"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Niveau</label>
                        <select name="level" class="form-input">
                            <option value="beginner"     <?= $course['level']==='beginner'     ?'selected':''?>>🟢 Débutant</option>
                            <option value="intermediate" <?= $course['level']==='intermediate' ?'selected':''?>>🟡 Intermédiaire</option>
                            <option value="advanced"     <?= $course['level']==='advanced'     ?'selected':''?>>🔴 Avancé</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max étudiants</label>
                        <input type="number" name="max_students" class="form-input"
                               min="1" value="<?= intval($course['max_students'] ?? 10) ?>">
                    </div>
                </div>
            </div>

            <!-- Prix & Durée -->
            <div>
                <h3 style="font-size:.8rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;margin-bottom:1.1rem;padding-bottom:.6rem;border-bottom:1px solid rgba(14,165,233,.08);">💰 Prix & Durée</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Prix (MAD)</label>
                        <input type="number" name="price" class="form-input"
                               min="0" step="0.01" value="<?= htmlspecialchars($course['price'] ?? '0') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Durée</label>
                        <input type="text" name="duration" class="form-input"
                               value="<?= htmlspecialchars($course['duration'] ?? '') ?>"
                               placeholder="Ex: 2h, 3 jours">
                    </div>
                </div>
            </div>

            <!-- Image -->
            <div>
                <h3 style="font-size:.8rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;margin-bottom:1.1rem;padding-bottom:.6rem;border-bottom:1px solid rgba(14,165,233,.08);">🖼️ Image du cours</h3>
                <div style="display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap;">
                    <?php if (!empty($course['image'])): ?>
                    <div style="flex-shrink:0;">
                        <p style="font-size:.75rem;color:var(--text-light);margin:0 0 .4rem;">Image actuelle</p>
                        <img src="../../uploads/surf/<?= htmlspecialchars($course['image']) ?>"
                             onerror="this.src='../../assets/images/default.jpg'"
                             style="width:120px;height:90px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid rgba(14,165,233,0.12);">
                    </div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:200px;">
                        <p style="font-size:.75rem;color:var(--text-light);margin:0 0 .4rem;">Nouvelle image (optionnel)</p>
                        <div class="upload-zone" id="upload-zone">
                            <input type="file" name="image" id="image-input" accept="image/*" style="display:none;">
                            <div id="upload-preview" style="display:none;"><img id="preview-img" src="" alt="Preview" style="max-height:150px;border-radius:var(--radius-sm);"></div>
                            <div id="upload-placeholder">
                                <div style="font-size:1.8rem;margin-bottom:.3rem;">📷</div>
                                <p style="color:var(--text-light);font-size:.85rem;margin:0;">Cliquez pour changer l'image</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:1rem;justify-content:flex-end;padding-top:.5rem;border-top:1px solid rgba(14,165,233,0.08);">
                <a href="courses.php" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">💾 Enregistrer les modifications</button>
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
.upload-zone{border:2px dashed rgba(14,165,233,0.25);border-radius:var(--radius-sm);padding:1.5rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s}
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