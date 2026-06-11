<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: users.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: users.php'); exit; }

$errors = [];
$values = [
    'nom'   => $user['name'],
    'email' => $user['email'],
    'role'  => $user['role'],
    'mot_de_passe' => '',
    'confirmer'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = array_merge($values, array_map('trim', $_POST));

    if (empty($values['nom']))   $errors['nom']   = 'Le nom est requis.';
    if (empty($values['email'])) $errors['email'] = 'L\'email est requis.';
    elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';

    // تحقق إذا email مستعمل من user آخر
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$values['email'], $id]);
    if ($check->fetch()) $errors['email'] = 'Cet email est déjà utilisé.';

    if (!empty($values['mot_de_passe'])) {
        if (strlen($values['mot_de_passe']) < 8) $errors['mot_de_passe'] = 'Minimum 8 caractères.';
        if (($values['confirmer'] ?? '') !== $values['mot_de_passe']) $errors['confirmer'] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        if (!empty($values['mot_de_passe'])) {
            $hash = password_hash($values['mot_de_passe'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?")
                ->execute([$values['nom'], $values['email'], $hash, $values['role'], $id]);
        } else {
            $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?")
                ->execute([$values['nom'], $values['email'], $values['role'], $id]);
        }
        header('Location: users.php?success=modifie'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modifier utilisateur – Admin Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
.form-grid { display:grid; grid-template-columns:1fr 300px; gap:1.5rem; align-items:start; }
.form-row  { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.field     { margin-bottom:1.1rem; }
.field:last-child { margin-bottom:0; }
.field label { display:block; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-light); margin-bottom:6px; }
.field label .req { color:#ef4444; }
.field input,.field select {
    width:100%; padding:9px 13px; border:1px solid var(--border); border-radius:9px;
    font-size:.875rem; color:var(--text); background:var(--bg); font-family:'DM Sans',sans-serif;
    outline:none; transition:border .2s, box-shadow .2s;
}
.field input:focus,.field select:focus { border-color:var(--ocean-teal); box-shadow:0 0 0 3px rgba(14,165,233,.1); }
.field.has-error input,.field.has-error select { border-color:#ef4444; }
.error-msg { font-size:.78rem; color:#ef4444; margin-top:5px; }
.help-text { font-size:.75rem; color:var(--text-light); margin-top:4px; }
.pw-wrap   { position:relative; }
.pw-wrap input { padding-right:42px; }
.toggle-pw { position:absolute; right:11px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-light); padding:4px; }
.toggle-pw svg { width:16px; height:16px; display:block; }
.section-lbl { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-light); margin-bottom:1rem; padding-bottom:.6rem; border-bottom:1px solid var(--border); }
.profile-av {
    width:68px; height:68px; border-radius:50%; margin:0 auto 12px;
    background:var(--color-info-bg); color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-size:1.6rem; font-weight:700; border:3px solid var(--border);
}
.meta-row { display:flex; align-items:center; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--border); font-size:.82rem; }
.meta-row:last-child { border-bottom:none; }
.meta-key { color:var(--text-light); }
.meta-val { font-weight:600; font-family:'DM Mono',monospace; font-size:.8rem; }
.badge { display:inline-flex; align-items:center; padding:3px 11px; border-radius:50px; font-size:.75rem; font-weight:600; }
.badge-admin { background:rgba(192,38,211,.1); color:#c026d3; }
.badge-user  { background:#f1f3f5; color:#495057; }
.form-actions { display:flex; align-items:center; justify-content:space-between; padding:1.1rem 1.4rem; border-top:1px solid var(--border); background:#f8f9fb; border-radius:0 0 var(--radius) var(--radius); }
.danger-zone { border:1px solid rgba(239,68,68,.25) !important; }
.danger-zone .card-header h3 { color:#ef4444; }
@media(max-width:900px){ .form-grid{grid-template-columns:1fr;} .form-row{grid-template-columns:1fr;} .admin-sidebar{display:none;} .admin-main{margin-left:0;} }
</style>
</head>
<body>
<div class="admin-shell">

<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Modifier utilisateur</div>
                <div class="topbar-breadcrumb">
                    Home <span>/</span>
                    <a href="users.php" style="color:var(--text-light);text-decoration:none;">Users</a>
                    <span>/</span> Modifier #<?= $id ?>
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
                <h1>Modifier l'utilisateur</h1>
                <p><?= htmlspecialchars($user['name']) ?> · #<?= $id ?></p>
            </div>
        </div>

        <div class="form-grid">

            <!-- LEFT -->
            <div style="display:flex;flex-direction:column;gap:1.4rem;">

                <form method="POST" action="edit-user.php?id=<?= $id ?>">
                    <div class="card">
                        <div class="card-header"><h3>✏️ Informations personnelles</h3></div>
                        <div style="padding:1.4rem;">

                            <div class="section-lbl">👤 Identité</div>
                            <div class="form-row">
                                <div class="field <?= isset($errors['nom']) ? 'has-error' : '' ?>">
                                    <label>Nom complet <span class="req">*</span></label>
                                    <input type="text" name="nom" value="<?= htmlspecialchars($values['nom']) ?>" id="nomInput" oninput="updateProfile()">
                                    <?php if (isset($errors['nom'])): ?><div class="error-msg">⚠ <?= $errors['nom'] ?></div><?php endif; ?>
                                </div>
                                <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                                    <label>Adresse email <span class="req">*</span></label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($values['email']) ?>" id="emailInput" oninput="updateProfile()">
                                    <?php if (isset($errors['email'])): ?><div class="error-msg">⚠ <?= $errors['email'] ?></div><?php endif; ?>
                                </div>
                            </div>

                            <div class="field">
                                <label>Rôle <span class="req">*</span></label>
                                <select name="role" id="roleSelect" onchange="updateProfile()">
                                    <option value="user"  <?= $values['role']==='user'  ?'selected':'' ?>>🌊 Utilisateur</option>
                                    <option value="admin" <?= $values['role']==='admin' ?'selected':'' ?>>👑 Administrateur</option>
                                </select>
                            </div>

                            <div style="border-top:1px solid var(--border);margin:1rem 0;"></div>
                            <div class="section-lbl">🔒 Changer le mot de passe</div>

                            <div class="form-row">
                                <div class="field <?= isset($errors['mot_de_passe']) ? 'has-error' : '' ?>">
                                    <label>Nouveau mot de passe</label>
                                    <div class="pw-wrap">
                                        <input type="password" name="mot_de_passe" id="pwInput" placeholder="Laisser vide = inchangé">
                                        <button type="button" class="toggle-pw" onclick="togglePw('pwInput')">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                    <div class="help-text">Minimum 8 caractères</div>
                                    <?php if (isset($errors['mot_de_passe'])): ?><div class="error-msg">⚠ <?= $errors['mot_de_passe'] ?></div><?php endif; ?>
                                </div>
                                <div class="field <?= isset($errors['confirmer']) ? 'has-error' : '' ?>">
                                    <label>Confirmer</label>
                                    <div class="pw-wrap">
                                        <input type="password" name="confirmer" id="pw2Input" placeholder="Confirmer le mot de passe">
                                        <button type="button" class="toggle-pw" onclick="togglePw('pw2Input')">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                    <?php if (isset($errors['confirmer'])): ?><div class="error-msg">⚠ <?= $errors['confirmer'] ?></div><?php endif; ?>
                                </div>
                            </div>

                        </div>
                        <div class="form-actions">
                            <a href="users.php" style="padding:.6rem 1.1rem;border-radius:8px;border:1px solid var(--border);color:var(--text);text-decoration:none;font-size:.875rem;font-weight:500;">Annuler</a>
                            <button type="submit" class="btn-primary" style="padding:.6rem 1.3rem;border:none;cursor:pointer;border-radius:8px;font-size:.875rem;font-weight:600;display:inline-flex;align-items:center;gap:.4rem;">
                                ✓ Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Danger zone -->
                <div class="card danger-zone">
                    <div class="card-header"><h3>⚠️ Zone dangereuse</h3></div>
                    <div style="padding:1.1rem 1.4rem;">
                        <p style="font-size:.82rem;color:#ef4444;margin-bottom:1rem;line-height:1.6;">
                            La suppression est irréversible. Toutes les données associées seront perdues.
                        </p>
                        <a href="delete-user.php?id=<?= $id ?>"
                           style="display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.2rem;border-radius:8px;background:rgba(239,68,68,.08);color:#ef4444;border:1px solid rgba(239,68,68,.25);font-size:.875rem;font-weight:600;text-decoration:none;">
                            🗑️ Supprimer cet utilisateur
                        </a>
                    </div>
                </div>

            </div>

            <!-- RIGHT -->
            <div style="display:flex;flex-direction:column;gap:1.4rem;">

                <!-- Profile card -->
                <div class="card">
                    <div style="text-align:center;padding:1.6rem 1.2rem;">
                        <div class="profile-av" id="profileAv"><?= strtoupper(substr($user['name'],0,2)) ?></div>
                        <div style="font-weight:700;font-size:1rem;" id="profileName"><?= htmlspecialchars($user['name']) ?></div>
                        <div style="font-size:.78rem;color:var(--text-light);margin-top:3px;" id="profileEmail"><?= htmlspecialchars($user['email']) ?></div>
                        <span style="display:inline-block;padding:3px 13px;border-radius:50px;font-size:.75rem;font-weight:600;margin-top:8px;" id="profileRole"></span>
                    </div>
                </div>

                <!-- Meta info -->
                <div class="card">
                    <div class="card-header"><h3>📋 Informations du compte</h3></div>
                    <div style="padding:.5rem 1.4rem 1rem;">
                        <div class="meta-row">
                            <span class="meta-key">📅 Créé le</span>
                            <span class="meta-val"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-key">📞 Téléphone</span>
                            <span class="meta-val"><?= $user['phone'] ? htmlspecialchars($user['phone']) : '—' ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-key">⭐ Points fidélité</span>
                            <span class="meta-val"><?= number_format($user['loyalty_points']) ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-key">🆔 ID</span>
                            <span class="meta-val">#<?= $id ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-key">🎭 Rôle actuel</span>
                            <span class="badge <?= $user['role']==='admin' ? 'badge-admin' : 'badge-user' ?>">
                                <?= $user['role']==='admin' ? '👑 Admin' : '🌊 User' ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
<script>
const roleColors = {
    admin: {bg:'rgba(192,38,211,.1)', color:'#c026d3', label:'👑 Administrateur'},
    user:  {bg:'#f1f3f5', color:'#495057', label:'🌊 Utilisateur'},
};
function updateProfile() {
    const nom   = document.getElementById('nomInput').value || 'Nom complet';
    const email = document.getElementById('emailInput').value || 'email@example.com';
    const role  = document.getElementById('roleSelect').value;
    const initials = nom.trim().split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase() || '?';
    document.getElementById('profileAv').textContent    = initials;
    document.getElementById('profileName').textContent  = nom;
    document.getElementById('profileEmail').textContent = email;
    const rc = roleColors[role] || roleColors.user;
    const badge = document.getElementById('profileRole');
    badge.textContent = rc.label;
    badge.style.background = rc.bg;
    badge.style.color = rc.color;
}
function togglePw(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
updateProfile();
</script>
</body>
</html>