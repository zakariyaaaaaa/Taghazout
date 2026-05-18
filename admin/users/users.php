<?php
// users.php - Gestion des utilisateurs

// Connexion base de données (à adapter)
// $pdo = new PDO('mysql:host=localhost;dbname=yourdb', 'user', 'pass');

// Données de démonstration
$users = [
    ['id' => 1, 'nom' => 'Ahmed Benali',    'email' => 'ahmed@example.com',   'role' => 'admin',     'statut' => 'actif',    'date_creation' => '2024-01-15'],
    ['id' => 2, 'nom' => 'Sara Moussaoui',  'email' => 'sara@example.com',    'role' => 'moderateur','statut' => 'actif',    'date_creation' => '2024-02-20'],
    ['id' => 3, 'nom' => 'Karim Idrissi',   'email' => 'karim@example.com',   'role' => 'utilisateur','statut' => 'inactif', 'date_creation' => '2024-03-10'],
    ['id' => 4, 'nom' => 'Fatima Zahra',    'email' => 'fatima@example.com',  'role' => 'utilisateur','statut' => 'actif',   'date_creation' => '2024-04-05'],
    ['id' => 5, 'nom' => 'Youssef El Amri', 'email' => 'youssef@example.com', 'role' => 'moderateur','statut' => 'suspendu','date_creation' => '2024-04-22'],
];

// Suppression (exemple)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // DELETE FROM users WHERE id = $id
    header('Location: users.php?success=supprime');
    exit;
}

$message = '';
if (isset($_GET['success'])) {
    $msgs = [
        'ajoute'   => 'Utilisateur ajouté avec succès.',
        'modifie'  => 'Utilisateur modifié avec succès.',
        'supprime' => 'Utilisateur supprimé.',
    ];
    $message = $msgs[$_GET['success']] ?? '';
}

$role_colors = [
    'admin'       => 'badge-admin',
    'moderateur'  => 'badge-modo',
    'utilisateur' => 'badge-user',
];
$statut_colors = [
    'actif'    => 'statut-actif',
    'inactif'  => 'statut-inactif',
    'suspendu' => 'statut-suspendu',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Utilisateurs — Admin</title>
<style>
  :root {
    --bg: #f5f6fa;
    --surface: #ffffff;
    --border: #e2e6ea;
    --text: #1a1d23;
    --muted: #6b7280;
    --primary: #3b5bdb;
    --primary-light: #eef2ff;
    --danger: #e03131;
    --danger-light: #fff5f5;
    --success: #2f9e44;
    --success-light: #ebfbee;
    --warning: #e8590c;
    --warning-light: #fff4e6;
    --radius: 8px;
    --shadow: 0 1px 3px rgba(0,0,0,0.08);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); font-size: 14px; line-height: 1.5; }

  .layout { display: flex; min-height: 100vh; }

  /* Sidebar */
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

  /* Main */
  .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
  .topbar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 14px 28px; display: flex; align-items: center; justify-content: space-between; }
  .topbar h1 { font-size: 18px; font-weight: 600; }
  .topbar .breadcrumb { font-size: 12px; color: var(--muted); margin-top: 2px; }
  .topbar .breadcrumb a { color: var(--muted); text-decoration: none; }
  .topbar .breadcrumb a:hover { color: var(--primary); }
  .topbar-right { display: flex; align-items: center; gap: 12px; }
  .avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; cursor: pointer; }

  .content { padding: 24px 28px; flex: 1; overflow-y: auto; }

  /* Alert */
  .alert { padding: 10px 16px; border-radius: var(--radius); margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; gap: 8px; }
  .alert-success { background: var(--success-light); color: var(--success); border: 1px solid #b2f2bb; }

  /* Toolbar */
  .toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 12px; flex-wrap: wrap; }
  .search-box { display: flex; align-items: center; gap: 8px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 7px 12px; }
  .search-box input { border: none; outline: none; font-size: 13px; background: transparent; color: var(--text); width: 220px; }
  .search-box svg { color: var(--muted); width: 15px; height: 15px; }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: var(--radius); font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; border: 1px solid transparent; transition: all .15s; }
  .btn-primary { background: var(--primary); color: #fff; }
  .btn-primary:hover { background: #2f4ac0; }
  .btn-sm { padding: 4px 10px; font-size: 12px; }
  .btn-outline { background: transparent; border-color: var(--border); color: var(--text); }
  .btn-outline:hover { border-color: #adb5bd; background: var(--bg); }
  .btn-danger { background: var(--danger-light); color: var(--danger); border-color: #ffc9c9; }
  .btn-danger:hover { background: #ffe3e3; }

  /* Stats */
  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin-bottom: 24px; }
  .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 18px; }
  .stat-card .label { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-bottom: 6px; }
  .stat-card .value { font-size: 26px; font-weight: 700; color: var(--text); }
  .stat-card .sub { font-size: 11px; color: var(--muted); margin-top: 2px; }

  /* Table */
  .table-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
  table { width: 100%; border-collapse: collapse; }
  thead th { padding: 11px 16px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); background: #f8f9fb; border-bottom: 1px solid var(--border); white-space: nowrap; }
  tbody tr { border-bottom: 1px solid var(--border); transition: background .1s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #fafbfc; }
  tbody td { padding: 12px 16px; vertical-align: middle; }

  /* User info */
  .user-cell { display: flex; align-items: center; gap: 10px; }
  .user-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
  .user-name { font-weight: 500; font-size: 13px; }
  .user-email { font-size: 12px; color: var(--muted); }

  /* Badges */
  .badge { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 500; }
  .badge-admin    { background: #fff0f6; color: #c2255c; }
  .badge-modo     { background: #e7f5ff; color: #1971c2; }
  .badge-user     { background: #f1f3f5; color: #495057; }
  .statut-actif   { background: var(--success-light); color: var(--success); }
  .statut-inactif { background: #f1f3f5; color: var(--muted); }
  .statut-suspendu{ background: var(--warning-light); color: var(--warning); }

  /* Actions */
  .actions { display: flex; align-items: center; gap: 6px; }
  .icon-btn { width: 30px; height: 30px; border-radius: 6px; border: 1px solid var(--border); background: transparent; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all .15s; text-decoration: none; color: var(--muted); }
  .icon-btn:hover { background: var(--bg); color: var(--text); }
  .icon-btn.del:hover { border-color: #ffc9c9; background: var(--danger-light); color: var(--danger); }
  .icon-btn svg { width: 15px; height: 15px; }

  /* Pagination */
  .pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--border); font-size: 12px; color: var(--muted); }
  .page-btns { display: flex; gap: 4px; }
  .page-btns a { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 6px; border: 1px solid var(--border); font-size: 12px; text-decoration: none; color: var(--text); }
  .page-btns a.active { background: var(--primary); color: #fff; border-color: var(--primary); }
  .page-btns a:hover:not(.active) { background: var(--bg); }
</style>
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
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
      <a href="../orders/orders.php">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Commandes
      </a>
      <div class="nav-section">Système</div>
      <a href="../settings/settings.php">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a1 1 0 0 0-1.41 0l-.71.71A7 7 0 1 0 12 5V4a1 1 0 0 0-2 0v1a7 7 0 0 0-4.95 11.95l-.71.71a1 1 0 1 0 1.41 1.41l.71-.71A7 7 0 0 0 19.07 4.93z"/></svg>
        Paramètres
      </a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="main">
    <div class="topbar">
      <div>
        <h1>Utilisateurs</h1>
        <div class="breadcrumb"><a href="#">Admin</a> / <a href="users.php">Utilisateurs</a></div>
      </div>
      <div class="topbar-right">
        <div class="avatar" title="Mon compte">AB</div>
      </div>
    </div>

    <div class="content">

      <?php if ($message): ?>
      <div class="alert alert-success">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= htmlspecialchars($message) ?>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats">
        <div class="stat-card">
          <div class="label">Total</div>
          <div class="value"><?= count($users) ?></div>
          <div class="sub">utilisateurs</div>
        </div>
        <div class="stat-card">
          <div class="label">Actifs</div>
          <div class="value"><?= count(array_filter($users, fn($u) => $u['statut'] === 'actif')) ?></div>
          <div class="sub">en ligne</div>
        </div>
        <div class="stat-card">
          <div class="label">Admins</div>
          <div class="value"><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></div>
          <div class="sub">privilégiés</div>
        </div>
        <div class="stat-card">
          <div class="label">Suspendus</div>
          <div class="value"><?= count(array_filter($users, fn($u) => $u['statut'] === 'suspendu')) ?></div>
          <div class="sub">bloqués</div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="search-box">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" placeholder="Rechercher un utilisateur…" id="searchInput" oninput="filterTable()">
        </div>
        <div style="display:flex;gap:8px;">
          <a href="add-user.php" class="btn btn-primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Ajouter
          </a>
          <button class="btn btn-outline">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exporter
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="table-card">
        <table id="usersTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Utilisateur</th>
              <th>Rôle</th>
              <th>Statut</th>
              <th>Créé le</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td style="color:var(--muted);font-size:12px;"><?= $u['id'] ?></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar"><?= strtoupper(substr($u['nom'], 0, 2)) ?></div>
                  <div>
                    <div class="user-name"><?= htmlspecialchars($u['nom']) ?></div>
                    <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge <?= $role_colors[$u['role']] ?? 'badge-user' ?>"><?= ucfirst($u['role']) ?></span></td>
              <td><span class="badge <?= $statut_colors[$u['statut']] ?? '' ?>"><?= ucfirst($u['statut']) ?></span></td>
              <td style="font-size:12px;color:var(--muted);"><?= $u['date_creation'] ?></td>
              <td>
                <div class="actions">
                  <a href="edit-user.php?id=<?= $u['id'] ?>" class="icon-btn" title="Modifier">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </a>
                  <a href="users.php?delete=<?= $u['id'] ?>" class="icon-btn del" title="Supprimer" onclick="return confirm('Supprimer cet utilisateur ?')">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="pagination">
          <span>Affichage de <?= count($users) ?> résultats</span>
          <div class="page-btns">
            <a href="#">&laquo;</a>
            <a href="#" class="active">1</a>
            <a href="#">2</a>
            <a href="#">&raquo;</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#usersTable tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>