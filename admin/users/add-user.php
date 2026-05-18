<?php
// add-user.php - Ajouter un utilisateur

$errors = [];
$values = ['nom' => '', 'email' => '', 'role' => 'utilisateur', 'statut' => 'actif', 'mot_de_passe' => '', 'confirmer' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = array_map('trim', $_POST);

    if (empty($values['nom']))          $errors['nom'] = 'Le nom est requis.';
    if (empty($values['email']))        $errors['email'] = 'L\'email est requis.';
    elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';
    if (empty($values['mot_de_passe'])) $errors['mot_de_passe'] = 'Le mot de passe est requis.';
    elseif (strlen($values['mot_de_passe']) < 8)                  $errors['mot_de_passe'] = 'Minimum 8 caractères.';
    if ($values['confirmer'] !== $values['mot_de_passe'])         $errors['confirmer'] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        // Ici : INSERT INTO users ...
        // $hash = password_hash($values['mot_de_passe'], PASSWORD_DEFAULT);
        header('Location: users.php?success=ajoute');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouter un utilisateur — Admin</title>
<style>
  :root {
    --bg: #f5f6fa; --surface: #ffffff; --border: #e2e6ea;
    --text: #1a1d23; --muted: #6b7280; --primary: #3b5bdb;
    --primary-light: #eef2ff; --danger: #e03131; --danger-light: #fff5f5;
    --radius: 8px;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); font-size: 14px; line-height: 1.5; }
  .layout { display: flex; min-height: 100vh; }
  .sidebar { width: 220px; background: #1e2a3b; padding: 0; flex-shrink: 0; }
  .sidebar-logo { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08); }
  .sidebar-logo span { color: #fff; font-size: 16px; font-weight: 600; }
  .sidebar-logo small { display: block; color: rgba(255,255,255,0.4); font-size: 11px; margin-top: 2px; }
  .sidebar nav a { display: flex; align-items: center; gap: 10px; padding: 10px 24px; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; transition: all .15s; }
  .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.07); color: #fff; }
  .sidebar nav a.active { border-left: 3px solid var(--primary); padding-left: 21px; }
  .sidebar nav a svg { width: 16px; height: 16px; opacity: .7; flex-shrink: 0; }
  .sidebar nav a.active svg { opacity: 1; }
  .nav-section { padding: 14px 24px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,0.25); }
  .main { flex: 1; display: flex; flex-direction: column; }
  .topbar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 14px 28px; display: flex; align-items: center; justify-content: space-between; }
  .topbar h1 { font-size: 18px; font-weight: 600; }
  .topbar .breadcrumb { font-size: 12px; color: var(--muted); margin-top: 2px; }
  .topbar .breadcrumb a { color: var(--muted); text-decoration: none; }
  .topbar .breadcrumb a:hover { color: var(--primary); }
  .avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; }
  .content { padding: 28px; flex: 1; overflow-y: auto; }

  /* Form layout */
  .form-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
  .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
  .card-header h2 { font-size: 15px; font-weight: 600; }
  .card-header .icon { width: 32px; height: 32px; border-radius: 8px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; }
  .card-header .icon svg { width: 16px; height: 16px; }
  .card-body { padding: 20px; }

  /* Form elements */
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .field { margin-bottom: 16px; }
  .field:last-child { margin-bottom: 0; }
  .field label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 6px; }
  .field label .req { color: var(--danger); margin-left: 2px; }
  .field input, .field select, .field textarea {
    width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: var(--radius);
    font-size: 14px; color: var(--text); background: var(--surface); font-family: inherit;
    transition: border-color .15s, box-shadow .15s; outline: none;
  }
  .field input:focus, .field select:focus, .field textarea:focus {
    border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,91,219,.1);
  }
  .field.has-error input, .field.has-error select { border-color: var(--danger); }
  .field.has-error input:focus, .field.has-error select:focus { box-shadow: 0 0 0 3px rgba(224,49,49,.1); }
  .error-msg { font-size: 12px; color: var(--danger); margin-top: 5px; display: flex; align-items: center; gap: 4px; }
  .error-msg svg { width: 12px; height: 12px; flex-shrink: 0; }
  .help-text { font-size: 12px; color: var(--muted); margin-top: 5px; }
  .password-wrap { position: relative; }
  .password-wrap input { padding-right: 40px; }
  .toggle-pw { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); padding: 4px; }
  .toggle-pw:hover { color: var(--text); }
  .toggle-pw svg { width: 16px; height: 16px; display: block; }

  /* Password strength */
  .pw-strength { margin-top: 8px; }
  .pw-bar { height: 3px; border-radius: 2px; background: var(--border); overflow: hidden; }
  .pw-fill { height: 100%; border-radius: 2px; width: 0%; transition: width .3s, background .3s; }
  .pw-label { font-size: 11px; color: var(--muted); margin-top: 4px; }

  /* Avatar preview */
  .avatar-preview { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 20px; text-align: center; }
  .av-circle { width: 80px; height: 80px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; border: 3px solid var(--border); }
  .av-name { font-weight: 600; font-size: 16px; }
  .av-email { font-size: 12px; color: var(--muted); }
  .role-badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; margin-top: 4px; }

  /* Info box */
  .info-box { background: #e7f5ff; border: 1px solid #a5d8ff; border-radius: var(--radius); padding: 12px 14px; font-size: 12px; color: #1971c2; display: flex; gap: 8px; margin-bottom: 0; }
  .info-box svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }

  /* Actions */
  .form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid var(--border); background: #f8f9fb; border-radius: 0 0 var(--radius) var(--radius); }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: var(--radius); font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; border: 1px solid transparent; transition: all .15s; font-family: inherit; }
  .btn-primary { background: var(--primary); color: #fff; }
  .btn-primary:hover { background: #2f4ac0; }
  .btn-outline { background: transparent; border-color: var(--border); color: var(--text); }
  .btn-outline:hover { border-color: #adb5bd; background: var(--bg); }
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">
      <span>&#9670; AdminPanel</span>
      <small>v2.1.0</small>
    </div>
    <nav>
      <div class="nav-section">Principal</div>
      <a href="../dashboard.php">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Tableau de bord
      </a>
      <div class="nav-section">Gestion</div>
      <a href="users.php" class="active">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/></svg>
        Utilisateurs
      </a>
      <a href="../products/products.php">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Produits
      </a>
    </nav>
  </aside>

  <div class="main">
    <div class="topbar">
      <div>
        <h1>Ajouter un utilisateur</h1>
        <div class="breadcrumb">
          <a href="#">Admin</a> / <a href="users.php">Utilisateurs</a> / Ajouter
        </div>
      </div>
      <div class="avatar">AB</div>
    </div>

    <div class="content">
      <form method="POST" action="add-user.php" novalidate>
        <div class="form-grid">

          <!-- Main form -->
          <div class="card">
            <div class="card-header">
              <div class="icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <h2>Informations du compte</h2>
            </div>
            <div class="card-body">

              <div class="form-row">
                <div class="field <?= isset($errors['nom']) ? 'has-error' : '' ?>">
                  <label>Nom complet <span class="req">*</span></label>
                  <input type="text" name="nom" value="<?= htmlspecialchars($values['nom']) ?>" placeholder="Ex: Ahmed Benali" id="nomInput" oninput="updatePreview()">
                  <?php if (isset($errors['nom'])): ?>
                  <div class="error-msg">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= $errors['nom'] ?>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                  <label>Adresse email <span class="req">*</span></label>
                  <input type="email" name="email" value="<?= htmlspecialchars($values['email']) ?>" placeholder="ahmed@example.com" id="emailInput" oninput="updatePreview()">
                  <?php if (isset($errors['email'])): ?>
                  <div class="error-msg">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= $errors['email'] ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="form-row">
                <div class="field">
                  <label>Rôle <span class="req">*</span></label>
                  <select name="role" id="roleSelect" onchange="updatePreview()">
                    <option value="utilisateur" <?= $values['role'] === 'utilisateur' ? 'selected' : '' ?>>Utilisateur</option>
                    <option value="moderateur"  <?= $values['role'] === 'moderateur'  ? 'selected' : '' ?>>Modérateur</option>
                    <option value="admin"       <?= $values['role'] === 'admin'       ? 'selected' : '' ?>>Administrateur</option>
                  </select>
                </div>
                <div class="field">
                  <label>Statut <span class="req">*</span></label>
                  <select name="statut">
                    <option value="actif"    <?= $values['statut'] === 'actif'    ? 'selected' : '' ?>>Actif</option>
                    <option value="inactif"  <?= $values['statut'] === 'inactif'  ? 'selected' : '' ?>>Inactif</option>
                    <option value="suspendu" <?= $values['statut'] === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                  </select>
                </div>
              </div>

              <div class="field <?= isset($errors['mot_de_passe']) ? 'has-error' : '' ?>">
                <label>Mot de passe <span class="req">*</span></label>
                <div class="password-wrap">
                  <input type="password" name="mot_de_passe" id="pwInput" placeholder="Minimum 8 caractères" oninput="checkStrength(this.value)">
                  <button type="button" class="toggle-pw" onclick="togglePw('pwInput', this)">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" id="eyeIcon1"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <div class="pw-strength">
                  <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
                  <div class="pw-label" id="pwLabel">Entrez un mot de passe</div>
                </div>
                <?php if (isset($errors['mot_de_passe'])): ?>
                <div class="error-msg">
                  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <?= $errors['mot_de_passe'] ?>
                </div>
                <?php endif; ?>
              </div>

              <div class="field <?= isset($errors['confirmer']) ? 'has-error' : '' ?>">
                <label>Confirmer le mot de passe <span class="req">*</span></label>
                <div class="password-wrap">
                  <input type="password" name="confirmer" id="pw2Input" placeholder="Répétez le mot de passe">
                  <button type="button" class="toggle-pw" onclick="togglePw('pw2Input', this)">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <?php if (isset($errors['confirmer'])): ?>
                <div class="error-msg">
                  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <?= $errors['confirmer'] ?>
                </div>
                <?php endif; ?>
              </div>

            </div>
            <div class="form-actions">
              <a href="users.php" class="btn btn-outline">Annuler</a>
              <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                Créer l'utilisateur
              </button>
            </div>
          </div>

          <!-- Sidebar panels -->
          <div style="display:flex;flex-direction:column;gap:16px;">
            <!-- Preview card -->
            <div class="card">
              <div class="card-header">
                <h2>Aperçu</h2>
              </div>
              <div class="avatar-preview">
                <div class="av-circle" id="avCircle">?</div>
                <div>
                  <div class="av-name" id="avName">Nom complet</div>
                  <div class="av-email" id="avEmail">email@example.com</div>
                  <span class="role-badge" id="avRole" style="background:#f1f3f5;color:#495057;">Utilisateur</span>
                </div>
              </div>
            </div>

            <!-- Info -->
            <div class="card">
              <div class="card-body">
                <div class="info-box">
                  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                  <div>Un email de bienvenue sera envoyé automatiquement à l'utilisateur après la création du compte.</div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </form>
    </div>
  </div>
</div>

<script>
const roleColors = {
  admin:       {bg:'#fff0f6', color:'#c2255c', label:'Administrateur'},
  moderateur:  {bg:'#e7f5ff', color:'#1971c2', label:'Modérateur'},
  utilisateur: {bg:'#f1f3f5', color:'#495057', label:'Utilisateur'},
};

function updatePreview() {
  const nom   = document.getElementById('nomInput').value || 'Nom complet';
  const email = document.getElementById('emailInput').value || 'email@example.com';
  const role  = document.getElementById('roleSelect').value;
  const initials = nom.trim().split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() || '?';
  document.getElementById('avCircle').textContent = initials;
  document.getElementById('avName').textContent   = nom;
  document.getElementById('avEmail').textContent  = email;
  const rc = roleColors[role] || roleColors.utilisateur;
  const badge = document.getElementById('avRole');
  badge.textContent = rc.label;
  badge.style.background = rc.bg;
  badge.style.color = rc.color;
}

function checkStrength(pw) {
  const fill = document.getElementById('pwFill');
  const label = document.getElementById('pwLabel');
  let score = 0;
  if (pw.length >= 8)          score++;
  if (/[A-Z]/.test(pw))        score++;
  if (/[0-9]/.test(pw))        score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const levels = [
    {w:'0%',   bg:'#e9ecef', txt:'Entrez un mot de passe'},
    {w:'25%',  bg:'#e03131', txt:'Très faible'},
    {w:'50%',  bg:'#f08c00', txt:'Faible'},
    {w:'75%',  bg:'#2f9e44', txt:'Bon'},
    {w:'100%', bg:'#1971c2', txt:'Excellent'},
  ];
  const lvl = pw.length ? levels[score] || levels[1] : levels[0];
  fill.style.width = lvl.w;
  fill.style.background = lvl.bg;
  label.textContent = lvl.txt;
}

function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  input.type = input.type === 'password' ? 'text' : 'password';
}

updatePreview();
</script>
</body>
</html>