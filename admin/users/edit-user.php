<?php
// edit-user.php - Modifier un utilisateur

// Récupération de l'utilisateur (remplacer par une requête DB réelle)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: users.php'); exit; }

// Simulation de récupération depuis DB
$users_db = [
    1 => ['id' => 1, 'nom' => 'Ahmed Benali',    'email' => 'ahmed@example.com',   'role' => 'admin',      'statut' => 'actif',    'date_creation' => '2024-01-15', 'derniere_connexion' => '2024-12-10'],
    2 => ['id' => 2, 'nom' => 'Sara Moussaoui',  'email' => 'sara@example.com',    'role' => 'moderateur', 'statut' => 'actif',    'date_creation' => '2024-02-20', 'derniere_connexion' => '2024-12-08'],
    3 => ['id' => 3, 'nom' => 'Karim Idrissi',   'email' => 'karim@example.com',   'role' => 'utilisateur','statut' => 'inactif',  'date_creation' => '2024-03-10', 'derniere_connexion' => '2024-11-20'],
    4 => ['id' => 4, 'nom' => 'Fatima Zahra',    'email' => 'fatima@example.com',  'role' => 'utilisateur','statut' => 'actif',    'date_creation' => '2024-04-05', 'derniere_connexion' => '2024-12-09'],
    5 => ['id' => 5, 'nom' => 'Youssef El Amri', 'email' => 'youssef@example.com', 'role' => 'moderateur', 'statut' => 'suspendu', 'date_creation' => '2024-04-22', 'derniere_connexion' => '2024-10-15'],
];

$user = $users_db[$id] ?? null;
if (!$user) { header('Location: users.php'); exit; }

$errors = [];
$values = $user; // valeurs initiales = données existantes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = array_merge($user, array_map('trim', $_POST));

    if (empty($values['nom']))  $errors['nom']  = 'Le nom est requis.';
    if (empty($values['email'])) $errors['email'] = 'L\'email est requis.';
    elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';

    // Changer le mot de passe uniquement si rempli
    if (!empty($values['mot_de_passe'])) {
        if (strlen($values['mot_de_passe']) < 8) $errors['mot_de_passe'] = 'Minimum 8 caractères.';
        if (($values['confirmer'] ?? '') !== $values['mot_de_passe']) $errors['confirmer'] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        // UPDATE users SET ... WHERE id = $id
        header('Location: users.php?success=modifie');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modifier l'utilisateur — Admin</title>
<style>
  :root {
    --bg: #f5f6fa; --surface: #ffffff; --border: #e2e6ea;
    --text: #1a1d23; --muted: #6b7280; --primary: #3b5bdb;
    --primary-light: #eef2ff; --danger: #e03131; --danger-light: #fff5f5;
    --success: #2f9e44; --success-light: #ebfbee;
    --warning: #e8590c; --warning-light: #fff4e6;
    --radius: 8px;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); font-size: 14px; line-height: 1.5; }
  .layout { display: flex; min-height: 100vh; }
  .sidebar { width: 220px; background: #1e2a3b; flex-shrink: 0; }
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

  .form-grid { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
  .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
  .card-header h2 { font-size: 15px; font-weight: 600; }
  .card-body { padding: 20px; }
  .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 6px; }
  .section-title svg { width: 14px; height: 14px; }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .field { margin-bottom: 16px; }
  .field:last-child { margin-bottom: 0; }
  .field label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 6px; }
  .field label .req { color: var(--danger); margin-left: 2px; }
  .field input, .field select {
    width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: var(--radius);
    font-size: 14px; color: var(--text); background: var(--surface); font-family: inherit;
    transition: border-color .15s, box-shadow .15s; outline: none;
  }
  .field input:focus, .field select:focus {
    border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,91,219,.1);
  }
  .field.has-error input, .field.has-error select { border-color: var(--danger); }
  .error-msg { font-size: 12px; color: var(--danger); margin-top: 5px; display: flex; align-items: center; gap: 4px; }
  .error-msg svg { width: 12px; height: 12px; flex-shrink: 0; }
  .help-text { font-size: 12px; color: var(--muted); margin-top: 5px; }
  .password-wrap { position: relative; }
  .password-wrap input { padding-right: 40px; }
  .toggle-pw { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); padding: 4px; }
  .toggle-pw svg { width: 16px; height: 16px; display: block; }

  .form-actions { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-top: 1px solid var(--border); background: #f8f9fb; border-radius: 0 0 var(--radius) var(--radius); }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: var(--radius); font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; border: 1px solid transparent; transition: all .15s; font-family: inherit; }
  .btn-primary { background: var(--primary); color: #fff; }
  .btn-primary:hover { background: #2f4ac0; }
  .btn-outline { background: transparent; border-color: var(--border); color: var(--text); }
  .btn-outline:hover { border-color: #adb5bd; background: var(--bg); }
  .btn-danger { background: var(--danger-light); color: var(--danger); border-color: #ffc9c9; }
  .btn-danger:hover { background: #ffe3e3; }

  /* Profile card */
  .profile-card { text-align: center; padding: 24px 20px; }
  .profile-av { width: 72px; height: 72px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; margin: 0 auto 12px; border: 3px solid var(--border); }
  .profile-name { font-size: 16px; font-weight: 600; }
  .profile-email { font-size: 12px; color: var(--muted); margin-top: 2px; }
  .profile-role { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; margin-top: 8px; }

  /* Meta info */
  .meta-list { list-style: none; }
  .meta-list li { display: flex; align-items: center; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--border); font-size: 12px; }
  .meta-list li:last-child { border-bottom: none; }
  .meta-list .meta-key { color: var(--muted); display: flex; align-items: center; gap: 6px; }
  .meta-list .meta-key svg { width: 13px; height: 13px; }
  .meta-list .meta-val { font-weight: 500; }

  /* Status badges */
  .badge { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 500; }
  .statut-actif   { background: var(--success-light); color: var(--success); }
  .statut-inactif { background: #f1f3f5; color: var(--muted); }
  .statut-suspendu{ background: var(--warning-light); color: var(--warning); }

  /* Danger zone */
  .danger-zone { border: 1px solid #ffc9c9; border-radius: var(--radius); background: var(--danger-light); }
  .danger-zone .card-header { border-bottom-color: #ffc9c9; background: transparent; }
  .danger-zone .card-header h2 { color: var(--danger); font-size: 14px; }
  .danger-zone .card-body { padding: 14px 20px; }
  .danger-zone p { font-size: 12px; color: #c92a2a; margin-bottom: 12px; line-height: 1.6; }
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
        <h1>Modifier l'utilisateur</h1>
        <div class="breadcrumb">
          <a href="#">Admin</a> / <a href="users.php">Utilisateurs</a> / Modifier #<?= $id ?>
        </div>
      </div>
      <div class="avatar">AB</div>
    </div>

    <div class="content">
      <div class="form-grid">

        <!-- Left: form -->
        <div style="display:flex;flex-direction:column;gap:16px;">

          <form method="POST" action="edit-user.php?id=<?= $id ?>">

            <!-- Infos principales -->
            <div class="card" style="margin-bottom:16px;">
              <div class="card-header">
                <h2>Informations personnelles</h2>
              </div>
              <div class="card-body">
                <div class="section-title">
                  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Identité
                </div>
                <div class="form-row">
                  <div class="field <?= isset($errors['nom']) ? 'has-error' : '' ?>">
                    <label>Nom complet <span class="req">*</span></label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($values['nom']) ?>" id="nomInput" oninput="updateProfile()">
                    <?php if (isset($errors['nom'])): ?>
                    <div class="error-msg">
                      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                      <?= $errors['nom'] ?>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                    <label>Adresse email <span class="req">*</span></label>
                    <input type="email" name="email" value="<?= htmlspecialchars($values['email']) ?>" id="emailInput" oninput="updateProfile()">
                    <?php if (isset($errors['email'])): ?>
                    <div class="error-msg">
                      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                      <?= $errors['email'] ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="form-row" style="margin-top:4px;">
                  <div class="field">
                    <label>Rôle <span class="req">*</span></label>
                    <select name="role" id="roleSelect" onchange="updateProfile()">
                      <option value="utilisateur" <?= $values['role'] === 'utilisateur' ? 'selected' : '' ?>>Utilisateur</option>
                      <option value="moderateur"  <?= $values['role'] === 'moderateur'  ? 'selected' : '' ?>>Modérateur</option>
                      <option value="admin"       <?= $values['role'] === 'admin'       ? 'selected' : '' ?>>Administrateur</option>
                    </select>
                  </div>
                  <div class="field">
                    <label>Statut</label>
                    <select name="statut">
                      <option value="actif"    <?= $values['statut'] === 'actif'    ? 'selected' : '' ?>>Actif</option>
                      <option value="inactif"  <?= $values['statut'] === 'inactif'  ? 'selected' : '' ?>>Inactif</option>
                      <option value="suspendu" <?= $values['statut'] === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                    </select>
                  </div>
                </div>

                <div style="border-top:1px solid var(--border);margin:16px 0 16px;"></div>

                <div class="section-title">
                  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  Changer le mot de passe
                </div>

                <div class="form-row">
                  <div class="field <?= isset($errors['mot_de_passe']) ? 'has-error' : '' ?>">
                    <label>Nouveau mot de passe</label>
                    <div class="password-wrap">
                      <input type="password" name="mot_de_passe" id="pwInput" placeholder="Laisser vide pour ne pas changer">
                      <button type="button" class="toggle-pw" onclick="togglePw('pwInput')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      </button>
                    </div>
                    <div class="help-text">Minimum 8 caractères</div>
                    <?php if (isset($errors['mot_de_passe'])): ?>
                    <div class="error-msg">
                      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                      <?= $errors['mot_de_passe'] ?>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="field <?= isset($errors['confirmer']) ? 'has-error' : '' ?>">
                    <label>Confirmer</label>
                    <div class="password-wrap">
                      <input type="password" name="confirmer" id="pw2Input" placeholder="Confirmer le nouveau mot de passe">
                      <button type="button" class="toggle-pw" onclick="togglePw('pw2Input')">
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
              </div>
              <div class="form-actions">
                <a href="users.php" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-primary">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                  Enregistrer les modifications
                </button>
              </div>
            </div>

          </form>

          <!-- Danger zone -->
          <div class="card danger-zone">
            <div class="card-header">
              <h2>&#9888; Zone dangereuse</h2>
            </div>
            <div class="card-body">
              <p>La suppression de cet utilisateur est irréversible. Toutes ses données associées seront définitivement perdues.</p>
              <a href="users.php?delete=<?= $id ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                Supprimer cet utilisateur
              </a>
            </div>
          </div>

        </div>

        <!-- Right: meta -->
        <div style="display:flex;flex-direction:column;gap:16px;">

          <!-- Profile -->
          <div class="card">
            <div class="profile-card">
              <div class="profile-av" id="profileAv"><?= strtoupper(substr($user['nom'], 0, 2)) ?></div>
              <div class="profile-name" id="profileName"><?= htmlspecialchars($user['nom']) ?></div>
              <div class="profile-email" id="profileEmail"><?= htmlspecialchars($user['email']) ?></div>
              <span class="profile-role" id="profileRole"><?= ucfirst($user['role']) ?></span>
            </div>
          </div>

          <!-- Meta -->
          <div class="card">
            <div class="card-header"><h2 style="font-size:13px;">Informations du compte</h2></div>
            <div class="card-body" style="padding:12px 20px;">
              <ul class="meta-list">
                <li>
                  <span class="meta-key">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Créé le
                  </span>
                  <span class="meta-val"><?= $user['date_creation'] ?></span>
                </li>
                <li>
                  <span class="meta-key">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Dernière connexion
                  </span>
                  <span class="meta-val"><?= $user['derniere_connexion'] ?></span>
                </li>
                <li>
                  <span class="meta-key">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    ID utilisateur
                  </span>
                  <span class="meta-val" style="font-family:monospace;font-size:12px;">#<?= $id ?></span>
                </li>
                <li>
                  <span class="meta-key">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Statut actuel
                  </span>
                  <span class="badge statut-<?= $user['statut'] ?>"><?= ucfirst($user['statut']) ?></span>
                </li>
              </ul>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
const roleColors = {
  admin:       {bg:'#fff0f6', color:'#c2255c', label:'Administrateur'},
  moderateur:  {bg:'#e7f5ff', color:'#1971c2', label:'Modérateur'},
  utilisateur: {bg:'#f1f3f5', color:'#495057', label:'Utilisateur'},
};

function updateProfile() {
  const nom   = document.getElementById('nomInput').value || 'Nom complet';
  const email = document.getElementById('emailInput').value || 'email@example.com';
  const role  = document.getElementById('roleSelect').value;
  const initials = nom.trim().split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() || '?';
  document.getElementById('profileAv').textContent    = initials;
  document.getElementById('profileName').textContent  = nom;
  document.getElementById('profileEmail').textContent = email;
  const rc = roleColors[role] || roleColors.utilisateur;
  const badge = document.getElementById('profileRole');
  badge.textContent = rc.label;
  badge.style.background = rc.bg;
  badge.style.color = rc.color;
}

function togglePw(inputId) {
  const input = document.getElementById(inputId);
  input.type = input.type === 'password' ? 'text' : 'password';
}

// Init badge color
const initRole = document.getElementById('roleSelect').value;
const rc = roleColors[initRole] || roleColors.utilisateur;
const badge = document.getElementById('profileRole');
badge.style.background = rc.bg;
badge.style.color = rc.color;
</script>
</body>
</html>