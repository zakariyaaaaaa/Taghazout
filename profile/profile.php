<?php
require_once '../includes/header.php';
require_once '../includes/config.php';

// ─── Auth check ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];

// ─── Fetch user ───────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $uid]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header('Location: ../auth/login.php'); exit; }

// ─── Fetch bookings ───────────────────────────────────────
$book_stmt = $pdo->prepare("
    SELECT b.*,
        CASE
            WHEN b.type = 'hotel' THEN h.name
            WHEN b.type = 'surf'  THEN s.title
        END AS item_name,
        CASE
            WHEN b.type = 'hotel' THEN h.image
            WHEN b.type = 'surf'  THEN s.image
        END AS item_image,
        CASE
            WHEN b.type = 'hotel' THEN h.location
            ELSE NULL
        END AS item_location
    FROM bookings b
    LEFT JOIN hotels      h ON b.type = 'hotel' AND b.reference_id = h.id
    LEFT JOIN surf_courses s ON b.type = 'surf'  AND b.reference_id = s.id
    WHERE b.user_id = :uid
    ORDER BY b.created_at DESC
");
$book_stmt->execute([':uid' => $uid]);
$bookings = $book_stmt->fetchAll();

// ─── Fetch favorites ──────────────────────────────────────
$fav_stmt = $pdo->prepare("
    SELECT f.*,
        CASE
            WHEN f.type = 'hotel'      THEN h.name
            WHEN f.type = 'activity'   THEN a.name
            WHEN f.type = 'surf'       THEN s.title
            WHEN f.type = 'restaurant' THEN r.name
        END AS item_name,
        CASE
            WHEN f.type = 'hotel'      THEN h.image
            WHEN f.type = 'activity'   THEN a.image
            WHEN f.type = 'surf'       THEN s.image
            WHEN f.type = 'restaurant' THEN r.image
        END AS item_image,
        CASE
            WHEN f.type = 'hotel'      THEN h.price
            WHEN f.type = 'activity'   THEN a.price
            WHEN f.type = 'surf'       THEN s.price
            WHEN f.type = 'restaurant' THEN NULL
        END AS item_price
    FROM favorites f
    LEFT JOIN hotels       h ON f.type = 'hotel'      AND f.reference_id = h.id
    LEFT JOIN activities   a ON f.type = 'activity'   AND f.reference_id = a.id
    LEFT JOIN surf_courses s ON f.type = 'surf'        AND f.reference_id = s.id
    LEFT JOIN restaurants  r ON f.type = 'restaurant' AND f.reference_id = r.id
    WHERE f.user_id = :uid
    ORDER BY f.created_at DESC
");
$fav_stmt->execute([':uid' => $uid]);
$favorites = $fav_stmt->fetchAll();

// ─── Fetch loyalty history ────────────────────────────────
$loy_stmt = $pdo->prepare("
    SELECT * FROM loyalty_history
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 10
");
$loy_stmt->execute([':uid' => $uid]);
$loyalty_history = $loy_stmt->fetchAll();

// ─── Fetch notifications ──────────────────────────────────
$notif_stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 8
");
$notif_stmt->execute([':uid' => $uid]);
$notifications = $notif_stmt->fetchAll();

// Mark all notifications as read
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid")
    ->execute([':uid' => $uid]);

// ─── Stats ────────────────────────────────────────────────
$total_bookings  = count($bookings);
$total_favorites = count($favorites);
$accepted        = count(array_filter($bookings, fn($b) => $b['status'] === 'accepted'));
$pending         = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));

// ─── Handle profile update ────────────────────────────────
$update_success = '';
$update_error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name  = trim($_POST['name']  ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    $new_pass  = $_POST['new_password']      ?? '';
    $cur_pass  = $_POST['current_password']  ?? '';

    if (strlen($new_name) < 2) {
        $update_error = 'Le nom doit contenir au moins 2 caractères.';
    } else {
        $avatar_name = $user['avatar'];

        // Avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $update_error = 'Format d\'image non supporté (jpg, jpeg, png, webp).';
            } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                $update_error = 'L\'image ne doit pas dépasser 2 Mo.';
            } else {
                $avatar_name = 'user_' . $uid . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['avatar']['tmp_name'], '../uploads/users/' . $avatar_name);
            }
        }

        if (!$update_error) {
            // Password change
            if ($new_pass !== '') {
                if (!password_verify($cur_pass, $user['password'])) {
                    $update_error = 'Mot de passe actuel incorrect.';
                } elseif (strlen($new_pass) < 6) {
                    $update_error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
                } else {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET name=:n, phone=:p, avatar=:a, password=:pw WHERE id=:id")
                        ->execute([':n'=>$new_name,':p'=>$new_phone,':a'=>$avatar_name,':pw'=>$hashed,':id'=>$uid]);
                    $update_success = 'Profil et mot de passe mis à jour avec succès.';
                }
            } else {
                $pdo->prepare("UPDATE users SET name=:n, phone=:p, avatar=:a WHERE id=:id")
                    ->execute([':n'=>$new_name,':p'=>$new_phone,':a'=>$avatar_name,':id'=>$uid]);
                $update_success = 'Profil mis à jour avec succès.';
            }

            if ($update_success) {
                // Refresh user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute([':id' => $uid]);
                $user = $stmt->fetch();
                $_SESSION['user_name'] = $user['name'];
            }
        }
    }
}

// ─── Helpers ──────────────────────────────────────────────

/**
 * ✅ FIX: Avatar helper — كيتحقق من وجود الصورة قبل ما يعرضها
 * إلا كانت الصورة فارغة أو ما كانتش موجودة، كيرجع الصورة الافتراضية
 */
function getAvatarSrc(string $avatar = ''): string {
    $upload_path = '../uploads/users/' . $avatar;
    if (!empty($avatar) && file_exists($upload_path)) {
        return $upload_path;
    }
    return '../assets/images/default.jpg';
}

$status_labels = [
    'pending'  => ['label' => 'En attente', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'accepted' => ['label' => 'Confirmée',  'color' => '#10b981', 'bg' => '#d1fae5'],
    'rejected' => ['label' => 'Refusée',    'color' => '#ef4444', 'bg' => '#fee2e2'],
];
$fav_links = [
    'hotel'      => '../hotel-details.php',
    'activity'   => '../activity-details.php',
    'surf'       => '../course-details.php',
    'restaurant' => '../restaurant-details.php',
];
$fav_icons = [
    'hotel'      => '🏨',
    'activity'   => '🎯',
    'surf'       => '🏄',
    'restaurant' => '🍽️',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil — Taghazout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<div class="profile-hero">
    <h1>Mon Espace Personnel</h1>
</div>

<div class="profile-layout">

    <!-- ══════════════════════════════
         SIDEBAR
    ══════════════════════════════ -->
    <aside class="profile-card">
        <div class="profile-card-top">
            <div class="avatar-wrapper">
                <!-- ✅ FIX: استعمال getAvatarSrc() بدل العرض المباشر -->
                <img
                    src="<?= htmlspecialchars(getAvatarSrc($user['avatar'] ?? '')) ?>"
                    alt="<?= htmlspecialchars($user['name']) ?>"
                    id="sidebar-avatar"
                >
            </div>
            <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
            <span class="profile-role"><?= $user['role'] === 'admin' ? '👑 Admin' : '🌊 Surfeur' ?></span>
        </div>

        <!-- Stats -->
        <div class="profile-stats">
            <div class="profile-stat">
                <div class="num"><?= $total_bookings ?></div>
                <div class="lbl">Réservations</div>
            </div>
            <div class="profile-stat">
                <div class="num"><?= $accepted ?></div>
                <div class="lbl">Confirmées</div>
            </div>
            <div class="profile-stat">
                <div class="num"><?= $total_favorites ?></div>
                <div class="lbl">Favoris</div>
            </div>
        </div>

        <!-- Loyalty points -->
        <div class="loyalty-badge" style="margin-top:1rem">
            <span style="font-size:1.5rem">🏅</span>
            <div>
                <div class="pts"><?= number_format($user['loyalty_points']) ?> pts</div>
                <div class="pts-lbl">Points fidélité</div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="profile-nav">
            <a href="#" class="active" data-tab="bookings">📅 Mes Réservations</a>
            <a href="#" data-tab="favorites">❤️ Mes Favoris</a>
            <a href="#" data-tab="loyalty">🏅 Fidélité</a>
            <a href="#" data-tab="notifications">
                🔔 Notifications
                <?php
                $unread = count(array_filter($notifications, fn($n) => !$n['is_read']));
                if ($unread > 0): ?>
                    <span style="margin-left:auto;background:#ef4444;color:#fff;border-radius:20px;padding:.1rem .5rem;font-size:.72rem"><?= $unread ?></span>
                <?php endif; ?>
            </a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                   <a href="../admin/dashboard.php">⚙️ Dashboard Admin</a>
                    <?php endif; ?>
            <a href="#" data-tab="edit">⚙️ Modifier le profil</a>
            <a href="../auth/logout.php" class="logout">🚪 Se déconnecter</a>
        </nav>
    </aside>

    <!-- ══════════════════════════════
         MAIN CONTENT
    ══════════════════════════════ -->
    <div class="profile-main">

        <!-- ── Bookings ── -->
        <div class="profile-section active" id="tab-bookings">
            <div class="section-head">
                <h2>📅 Mes Réservations</h2>
                <span style="font-size:.85rem;color:#aaa"><?= $total_bookings ?> au total</span>
            </div>
            <div class="section-body">
                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <span class="emoji">📅</span>
                        <p>Vous n'avez pas encore de réservations.</p>
                        <a href="../hotels.php" class="btn-primary">Explorer les hôtels</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $b):
                        $s = $status_labels[$b['status']] ?? $status_labels['pending'];
                        $nights = ($b['check_in'] && $b['check_out'])
                            ? max(1, (strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400)
                            : null;
                    ?>
                    <div class="booking-item">
                        <img
                            src="../uploads/<?= $b['type'] === 'hotel' ? 'hotels' : 'surf' ?>/<?= htmlspecialchars($b['item_image'] ?? '') ?>"
                            onerror="this.src='../assets/images/default.jpg'"
                            alt="<?= htmlspecialchars($b['item_name'] ?? '') ?>"
                        >
                        <div class="booking-info">
                            <h4><?= htmlspecialchars($b['item_name'] ?? 'N/A') ?></h4>
                            <div class="booking-meta">
                                <?= $b['type'] === 'hotel' ? '🏨 Hôtel' : '🏄 Surf' ?>
                                <?php if ($b['item_location']): ?> · 📍 <?= htmlspecialchars($b['item_location']) ?><?php endif; ?>
                            </div>
                            <?php if ($b['check_in'] && $b['check_out']): ?>
                                <div class="booking-dates">
                                    📆 <?= date('d/m/Y', strtotime($b['check_in'])) ?> →
                                    <?= date('d/m/Y', strtotime($b['check_out'])) ?>
                                    <?php if ($nights): ?>(<?= $nights ?> nuit<?= $nights > 1 ? 's' : '' ?>)<?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.5rem">
                            <span class="status-badge" style="color:<?= $s['color'] ?>;background:<?= $s['bg'] ?>">
                                <?= $s['label'] ?>
                            </span>
                            <?php if ($b['total_price']): ?>
                                <div class="booking-price-tag"><?= number_format($b['total_price'], 0, ',', ' ') ?> MAD</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Favorites ── -->
        <div class="profile-section" id="tab-favorites">
            <div class="section-head">
                <h2>❤️ Mes Favoris</h2>
                <span style="font-size:.85rem;color:#aaa"><?= $total_favorites ?> au total</span>
            </div>
            <div class="section-body">
                <?php if (empty($favorites)): ?>
                    <div class="empty-state">
                        <span class="emoji">❤️</span>
                        <p>Aucun favori pour l'instant.</p>
                        <a href="../hotels.php" class="btn-primary">Découvrir des hôtels</a>
                    </div>
                <?php else: ?>
                    <div class="fav-grid">
                        <?php foreach ($favorites as $fav): ?>
                        <a href="<?= $fav_links[$fav['type']] ?? '#' ?>?id=<?= $fav['reference_id'] ?>" class="fav-card">
                            <img
                                src="../uploads/<?= $fav['type'] === 'surf' ? 'surf' : ($fav['type'] . 's') ?>/<?= htmlspecialchars($fav['item_image'] ?? '') ?>"
                                onerror="this.src='../assets/images/default.jpg'"
                                alt="<?= htmlspecialchars($fav['item_name'] ?? '') ?>"
                            >
                            <div class="fav-card-body">
                                <div class="fav-type"><?= $fav_icons[$fav['type']] ?> <?= ucfirst($fav['type']) ?></div>
                                <h4><?= htmlspecialchars($fav['item_name'] ?? 'N/A') ?></h4>
                                <?php if ($fav['item_price']): ?>
                                    <div class="fav-price"><?= number_format($fav['item_price'], 0, ',', ' ') ?> MAD</div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Loyalty ── -->
        <div class="profile-section" id="tab-loyalty">
            <div class="section-head">
                <h2>🏅 Programme Fidélité</h2>
            </div>
            <div class="section-body">
                <div class="loyalty-total">
                    <div>
                        <div class="big"><?= number_format($user['loyalty_points']) ?></div>
                    </div>
                    <div>
                        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1.05rem;color:#92400e">Points disponibles</div>
                        <div class="sub">Gagnez des points à chaque réservation confirmée</div>
                    </div>
                </div>

                <?php if (empty($loyalty_history)): ?>
                    <div class="empty-state">
                        <span class="emoji">🏅</span>
                        <p>Aucun historique de points pour l'instant.</p>
                    </div>
                <?php else: ?>
                    <h4 style="font-family:'Syne',sans-serif;font-size:.95rem;color:#1a1a2e;margin:0 0 .75rem">Historique des points</h4>
                    <?php foreach ($loyalty_history as $lh): ?>
                    <div class="loyalty-row">
                        <span class="reason"><?= htmlspecialchars($lh['reason'] ?? 'Transaction') ?></span>
                        <span class="date"><?= date('d/m/Y', strtotime($lh['created_at'])) ?></span>
                        <span class="<?= $lh['points'] >= 0 ? 'pts-pos' : 'pts-neg' ?>">
                            <?= $lh['points'] >= 0 ? '+' : '' ?><?= $lh['points'] ?> pts
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Notifications ── -->
        <div class="profile-section" id="tab-notifications">
            <div class="section-head">
                <h2>🔔 Notifications</h2>
            </div>
            <div class="section-body">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <span class="emoji">🔔</span>
                        <p>Aucune notification pour l'instant.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                    <div class="notif-item">
                        <div class="notif-icon">🔔</div>
                        <div class="notif-info">
                            <h4><?= htmlspecialchars($notif['title']) ?></h4>
                            <?php if ($notif['message']): ?>
                                <p><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                            <?php endif; ?>
                            <div class="notif-date"><?= date('d/m/Y à H:i', strtotime($notif['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Edit profile ── -->
        <div class="profile-section" id="tab-edit">
            <div class="section-head">
                <h2>⚙️ Modifier le profil</h2>
            </div>
            <div class="section-body">
                <?php if ($update_success): ?>
                    <div class="alert-success">✅ <?= $update_success ?></div>
                <?php endif; ?>
                <?php if ($update_error): ?>
                    <div class="alert-error">❌ <?= $update_error ?></div>
                <?php endif; ?>

                <form method="POST" action="profile.php" enctype="multipart/form-data">

                    <!-- Avatar -->
                    <div class="avatar-preview-wrap">
                        <!-- ✅ FIX: استعمال getAvatarSrc() بدل العرض المباشر -->
                        <img
                            src="<?= htmlspecialchars(getAvatarSrc($user['avatar'] ?? '')) ?>"
                            alt="Avatar"
                            id="avatar-preview"
                        >
                        <div>
                            <label for="avatar-input">📷 Changer la photo</label>
                            <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display:none" onchange="previewAvatar(this)">
                            <div style="font-size:.75rem;color:#bbb;margin-top:.3rem">JPG, PNG, WEBP — max 2 Mo</div>
                        </div>
                    </div>

                    <div class="edit-grid">
                        <div class="form-group">
                            <label>Nom complet</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+212 6XX XXX XXX">
                        </div>
                        <div class="form-group full">
                            <label>Email (non modifiable)</label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f8f9fa;color:#aaa;cursor:not-allowed">
                        </div>

                        <hr class="divider">

                        <div class="form-group full" style="margin-bottom:.25rem">
                            <label style="color:#2c5364">🔒 Changer le mot de passe (optionnel)</label>
                        </div>
                        <div class="form-group">
                            <label>Mot de passe actuel</label>
                            <input type="password" name="current_password" placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label>Nouveau mot de passe</label>
                            <input type="password" name="new_password" placeholder="Min. 6 caractères">
                        </div>

                        <div class="form-group full">
                            <button type="submit" name="update_profile" class="save-btn">💾 Sauvegarder les modifications</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div><!-- /profile-main -->
</div><!-- /profile-layout -->

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
// ── Tab navigation ─────────────────────────────────────────
const navLinks = document.querySelectorAll('.profile-nav a[data-tab]');
const sections = document.querySelectorAll('.profile-section');

navLinks.forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const tab = link.dataset.tab;

        navLinks.forEach(l => l.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));

        link.classList.add('active');
        const target = document.getElementById('tab-' + tab);
        if (target) target.classList.add('active');
    });
});

// ── Avatar preview ─────────────────────────────────────────
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('avatar-preview').src = e.target.result;
            document.getElementById('sidebar-avatar').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Open edit tab if update happened ──────────────────────
<?php if ($update_success || $update_error): ?>
document.querySelector('[data-tab="edit"]').click();
<?php endif; ?>
</script>
</body>
</html>