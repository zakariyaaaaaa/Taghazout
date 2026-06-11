<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

$errors = [];
$values = ['nom'=>'','email'=>'','role'=>'user','mot_de_passe'=>'','confirmer'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = array_map('trim', $_POST);
    if (empty($values['nom']))          $errors['nom'] = 'Le nom est requis.';
    if (empty($values['email']))        $errors['email'] = 'L\'email est requis.';
    elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';
    if (empty($values['mot_de_passe'])) $errors['mot_de_passe'] = 'Le mot de passe est requis.';
    elseif (strlen($values['mot_de_passe']) < 8) $errors['mot_de_passe'] = 'Minimum 8 caractères.';
    if (($values['confirmer'] ?? '') !== $values['mot_de_passe']) $errors['confirmer'] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$values['email']]);
        if ($check->fetch()) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($values['mot_de_passe'], PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
                ->execute([$values['nom'], $values['email'], $hash, $values['role']]);
            header('Location: users.php?success=ajoute'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouter un utilisateur – Admin Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
.form-grid  { display:grid; grid-template-columns:1fr 300px; gap:1.5rem; align-items:start; }
.form-row   { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.field      { margin-bottom:1.1rem; }
.field:last-child { margin-bottom:0; }
.field label { display:block; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-light); margin-bottom:6px; }
.field label .req { color:#ef4444; margin-left:2px; }
.field input,.field select {
    width:100%; padding:9px 13px; border:1px solid var(--border); border-radius:9px;
    font-size:.875rem; color:var(--text); background:var(--bg); font-family:'DM Sans',sans-serif;
    outline:none; transition:border .2s, box-shadow .2s;
}
.field input:focus,.field select:focus { border-color:var(--ocean-teal); box-shadow:0 0 0 3px rgba(14,165,233,.1); }
.field.has-error input,.field.has-error select { border-color:#ef4444; }
.error-msg { font-size:.78rem; color:#ef4444; margin-top:5px; display:flex; align-items:center; gap:4px; }
.pw-wrap    { position:relative; }
.pw-wrap input { padding-right:42px; }
.toggle-pw  { position:absolute; right:11px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-light); padding:4px; }
.toggle-pw:hover { color:var(--text); }
.toggle-pw svg { width:16px; height:16px; display:block; }
.pw-bar     { height:3px; border-radius:2px; background:var(--border); overflow:hidden; margin-top:8px; }
.pw-fill    { height:100%; border-radius:2px; width:0%; transition:width .3s, background .3s; }
.pw-lbl     { font-size:.75rem; color:var(--text-light); margin-top:4px; }
.av-circle  {
    width:76px; height:76px; border-radius:50%;
    background:var(--color-info-bg); color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-size:1.8rem; font-weight:700; margin:0 auto 12px;
    border:3px solid var(--border);
}
.role-badge { display:inline-block; padding:3px 13px; border-radius:50px; font-size:.75rem; font-weight:600; margin-top:5px; }
.form-actions { display:flex; align-items:center; justify-content:flex-end; gap:.7rem; padding:1.1rem 1.4rem; border-top:1px solid var(--border); background:#f8f9fb; border-radius:0 0 var(--radius) var(--radius); }
.info-box { background:rgba(14,165,233,.07); border:1px solid rgba(14,165,233,.2); border-radius:9px; padding:12px 14px; font-size:.8rem; color:var(--ocean-teal); display:flex; gap:8px; }
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
                <div class="topbar-title">Ajouter un utilisateur</div>
                <div class="topbar-breadcrumb">
                    Home <span>/</span>
                    <a href="users.php" style="color:var(--text-light);text-decoration:none;">Users</a>
                    <span>/</span> Ajouter
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
                <h1>Nouveau utilisateur</h1>
                <p>Créer un nouveau compte utilisateur</p>
            </div>
        </div>

        <form method="POST" action="add-user.php" novalidate>
            <div class="form-grid">

                <div class="card">
                    <div class="card-header"><h3>👤 Informations du compte</h3></div>
                    <div style="padding:1.4rem;">

                        <div class="form-row">
                            <div class="field <?= isset($errors['nom']) ? 'has-error' : '' ?>">
                                <label>Nom complet <span class="req">*</span></label>
                                <input type="text" name="nom" value="<?= htmlspecialchars($values['nom']) ?>" placeholder="Ex: Ahmed Benali" id="nomInput" oninput="updatePreview()">
                                <?php if (isset($errors['nom'])): ?><div class="error-msg">⚠ <?= $errors['nom'] ?></div><?php endif; ?>
                            </div>
                            <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                                <label>Adresse email <span class="req">*</span></label>
                                <input type="email" name="email" value="<?= htmlspecialchars($values['email']) ?>" placeholder="ahmed@example.com" id="emailInput" oninput="updatePreview()">
                                <?php if (isset($errors['email'])): ?><div class="error-msg">⚠ <?= $errors['email'] ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="field">
                            <label>Rôle <span class="req">*</span></label>
                            <select name="role" id="roleSelect" onchange="updatePreview()">
                                <option value="user"  <?= ($values['role']==='user')  ?'selected':'' ?>>🌊 Utilisateur</option>
                                <option value="admin" <?= ($values['role']==='admin') ?'selected':'' ?>>👑 Administrateur</option>
                            </select>
                        </div>

                        <div class="field <?= isset($errors['mot_de_passe']) ? 'has-error' : '' ?>">
                            <label>Mot de passe <span class="req">*</span></label>
                            <div class="pw-wrap">
                                <input type="password" name="mot_de_passe" id="pwInput" placeholder="Minimum 8 caractères" oninput="checkStrength(this.value)">
                                <button type="button" class="toggle-pw" onclick="togglePw('pwInput')">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
                            <div class="pw-lbl" id="pwLabel">Entrez un mot de passe</div>
                            <?php if (isset($errors['mot_de_passe'])): ?><div class="error-msg">⚠ <?= $errors['mot_de_passe'] ?></div><?php endif; ?>
                        </div>

                        <div class="field <?= isset($errors['confirmer']) ? 'has-error' : '' ?>">
                            <label>Confirmer le mot de passe <span class="req">*</span></label>
                            <div class="pw-wrap">
                                <input type="password" name="confirmer" id="pw2Input" placeholder="Répétez le mot de passe">
                                <button type="button" class="toggle-pw" onclick="togglePw('pw2Input')">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <?php if (isset($errors['confirmer'])): ?><div class="error-msg">⚠ <?= $errors['confirmer'] ?></div><?php endif; ?>
                        </div>

                    </div>
                    <div class="form-actions">
                        <a href="users.php" style="padding:.6rem 1.1rem;border-radius:8px;border:1px solid var(--border);color:var(--text);text-decoration:none;font-size:.875rem;font-weight:500;">Annuler</a>
                        <button type="submit" class="btn-primary" style="padding:.6rem 1.3rem;border:none;cursor:pointer;border-radius:8px;font-size:.875rem;font-weight:600;display:inline-flex;align-items:center;gap:.4rem;">
                            ✓ Créer l'utilisateur
                        </button>
                    </div>
                </div>

                <!-- Sidebar -->
                <div style="display:flex;flex-direction:column;gap:1.2rem;">
                    <div class="card">
                        <div class="card-header"><h3>👁 Aperçu</h3></div>
                        <div style="text-align:center;padding:1.5rem 1.2rem;">
                            <div class="av-circle" id="avCircle">?</div>
                            <div style="font-weight:700;font-size:1rem;color:var(--text);" id="avName">Nom complet</div>
                            <div style="font-size:.78rem;color:var(--text-light);margin-top:3px;" id="avEmail">email@example.com</div>
                            <span class="role-badge" id="avRole" style="background:#f1f3f5;color:#495057;">Utilisateur</span>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:1.1rem 1.2rem;">
                            <div class="info-box">
                                ℹ️
                                <div>Le compte sera créé et accessible immédiatement.</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
<script>
const roleColors = {
    admin: {bg:'rgba(192,38,211,.1)', color:'#c026d3', label:'👑 Administrateur'},
    user:  {bg:'#f1f3f5', color:'#495057', label:'🌊 Utilisateur'},
};
function updatePreview() {
    const nom   = document.getElementById('nomInput').value || 'Nom complet';
    const email = document.getElementById('emailInput').value || 'email@example.com';
    const role  = document.getElementById('roleSelect').value;
    const initials = nom.trim().split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase() || '?';
    document.getElementById('avCircle').textContent = initials;
    document.getElementById('avName').textContent   = nom;
    document.getElementById('avEmail').textContent  = email;
    const rc = roleColors[role] || roleColors.user;
    const badge = document.getElementById('avRole');
    badge.textContent = rc.label;
    badge.style.background = rc.bg;
    badge.style.color = rc.color;
}
function checkStrength(pw) {
    const fill  = document.getElementById('pwFill');
    const label = document.getElementById('pwLabel');
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const levels = [
        {w:'0%',   bg:'var(--border)',          txt:'Entrez un mot de passe'},
        {w:'25%',  bg:'#ef4444',                txt:'Très faible'},
        {w:'50%',  bg:'#f59e0b',                txt:'Faible'},
        {w:'75%',  bg:'#10b981',                txt:'Bon'},
        {w:'100%', bg:'var(--ocean-teal)',       txt:'Excellent'},
    ];
    const lvl = pw.length ? levels[score] || levels[1] : levels[0];
    fill.style.width = lvl.w;
    fill.style.background = lvl.bg;
    label.textContent = lvl.txt;
}
function togglePw(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
updatePreview();
</script>
</body>
</html>