<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php"); exit;
}

// Suppression réelle
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    header('Location: users.php?success=supprime'); exit;
}

// ✅ جيب les users من DB
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if (isset($_GET['success'])) {
    $msgs = ['ajoute'=>'Utilisateur ajouté avec succès.','modifie'=>'Utilisateur modifié avec succès.','supprime'=>'Utilisateur supprimé.'];
    $message = $msgs[$_GET['success']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Utilisateurs – Admin Taghazout</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
.user-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:1.2rem; margin-bottom:2rem; }
.u-stat {
    background:var(--white); border:1px solid var(--border); border-radius:var(--radius);
    padding:1.3rem 1.5rem; display:flex; align-items:center; gap:1rem; transition:box-shadow .2s;
}
.u-stat:hover { box-shadow:var(--shadow-md); }
.u-stat-icon {
    width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;
}
.u-stat-icon.teal   { background:rgba(14,165,233,.1); color:var(--ocean-teal); }
.u-stat-icon.green  { background:rgba(16,185,129,.1); color:#10b981; }
.u-stat-icon.purple { background:rgba(139,92,246,.1); color:#8b5cf6; }
.u-stat-icon.gold   { background:rgba(245,158,11,.1);  color:#f59e0b; }
.u-stat-val { font-size:1.55rem; font-weight:700; color:var(--text); font-family:'DM Mono',monospace; line-height:1; }
.u-stat-lbl { font-size:.77rem; color:var(--text-light); margin-top:3px; }

.toolbar {
    display:flex; align-items:center; justify-content:space-between;
    gap:.8rem; flex-wrap:wrap; margin-bottom:1.2rem;
}
.search-box {
    display:flex; align-items:center; gap:8px;
    background:var(--white); border:1px solid var(--border); border-radius:9px;
    padding:8px 14px; flex:1; max-width:320px;
}
.search-box input { border:none; outline:none; font-size:.875rem; background:transparent; color:var(--text); width:100%; font-family:'DM Sans',sans-serif; }

.user-av {
    width:36px; height:36px; border-radius:50%; flex-shrink:0;
    background:var(--color-info-bg); color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-size:.82rem; font-weight:700;
}
.user-av img { width:36px; height:36px; border-radius:50%; object-fit:cover; }
.user-name  { font-weight:600; font-size:.875rem; color:var(--text); }
.user-email { font-size:.78rem; color:var(--text-light); }

.badge { display:inline-flex; align-items:center; padding:3px 11px; border-radius:50px; font-size:.75rem; font-weight:600; }
.badge-admin { background:rgba(192,38,211,.1); color:#c026d3; }
.badge-user  { background:#f1f3f5; color:#495057; }

.icon-btn {
    width:32px; height:32px; border-radius:7px; border:1px solid var(--border);
    background:transparent; cursor:pointer; display:inline-flex; align-items:center;
    justify-content:center; transition:all .15s; text-decoration:none; color:var(--text-light);
}
.icon-btn:hover     { background:var(--bg); color:var(--text); border-color:#adb5bd; }
.icon-btn.del:hover { border-color:rgba(239,68,68,.4); background:rgba(239,68,68,.07); color:#ef4444; }
.icon-btn svg { width:15px; height:15px; }

.alert { padding:11px 16px; border-radius:9px; margin-bottom:1.4rem; font-size:.875rem; display:flex; align-items:center; gap:10px; border:1px solid transparent; }
.alert-success { background:rgba(16,185,129,.08); border-color:rgba(16,185,129,.25); color:#10b981; }

@media(max-width:900px){
    .user-stats { grid-template-columns:repeat(2,1fr); }
    .admin-sidebar { display:none; }
    .admin-main { margin-left:0; }
}
</style>
</head>
<body>
<div class="admin-shell">

<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Utilisateurs</div>
                <div class="topbar-breadcrumb">Home <span>/</span> Users</div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="add-user.php" class="btn-primary" style="padding:.6rem 1.2rem;font-size:.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;border-radius:8px;">
                + Ajouter
            </a>
            <button class="topbar-icon-btn">🔔<span class="notif-dot"></span></button>
        </div>
    </div>

    <div class="admin-body">
        <div class="page-header">
            <div>
                <h1>Utilisateurs</h1>
                <p>Gérez les comptes et les accès</p>
            </div>
            <span style="font-size:.85rem;color:var(--text-light);"><?= date('d/m/Y') ?></span>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="user-stats">
            <div class="u-stat">
                <div class="u-stat-icon teal">👥</div>
                <div>
                    <div class="u-stat-val"><?= count($users) ?></div>
                    <div class="u-stat-lbl">Total utilisateurs</div>
                </div>
            </div>
            <div class="u-stat">
                <div class="u-stat-icon green">🌊</div>
                <div>
                    <div class="u-stat-val"><?= count(array_filter($users, fn($u)=>$u['role']==='user')) ?></div>
                    <div class="u-stat-lbl">Utilisateurs</div>
                </div>
            </div>
            <div class="u-stat">
                <div class="u-stat-icon purple">👑</div>
                <div>
                    <div class="u-stat-val"><?= count(array_filter($users, fn($u)=>$u['role']==='admin')) ?></div>
                    <div class="u-stat-lbl">Administrateurs</div>
                </div>
            </div>
            <div class="u-stat">
                <div class="u-stat-icon gold">⭐</div>
                <div>
                    <div class="u-stat-val"><?= number_format(array_sum(array_column($users,'loyalty_points'))) ?></div>
                    <div class="u-stat-lbl">Points fidélité total</div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <span style="color:var(--text-light);font-size:1rem;">🔍</span>
                <input type="text" placeholder="Rechercher un utilisateur…" id="searchInput" oninput="filterTable()">
            </div>
            <button style="padding:.55rem 1.1rem;font-size:.85rem;border:none;cursor:pointer;border-radius:8px;display:inline-flex;align-items:center;gap:.4rem;background:var(--white);color:var(--text);border:1px solid var(--border);">
                ⬇ Exporter
            </button>
        </div>

        <!-- Table -->
        <div class="card" style="padding:0;overflow:hidden;">
            <div class="admin-table-wrap">
                <table class="admin-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utilisateur</th>
                            <th>Rôle</th>
                            <th>Points ⭐</th>
                            <th>Téléphone</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="mono" style="color:var(--text-light);font-size:.8rem;"><?= $u['id'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:.75rem;">
                                    <?php
                                    $avatarFile = $u['avatar'] ?? 'default.png';
                                    $avatarPath = '../../uploads/avatars/' . $avatarFile;
                                    ?>
                                    <?php if ($avatarFile !== 'default.png' && file_exists($avatarPath)): ?>
                                        <div class="user-av"><img src="<?= htmlspecialchars($avatarPath) ?>" alt="avatar"></div>
                                    <?php else: ?>
                                        <div class="user-av"><?= strtoupper(substr($u['name'], 0, 2)) ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $rc = ['admin'=>'badge-admin', 'user'=>'badge-user'];
                                $rl = ['admin'=>'👑 Admin',    'user'=>'🌊 User'];
                                ?>
                                <span class="badge <?= $rc[$u['role']] ?? 'badge-user' ?>">
                                    <?= $rl[$u['role']] ?? ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td style="font-family:'DM Mono',monospace;font-size:.82rem;">
                                <?= number_format($u['loyalty_points']) ?>
                            </td>
                            <td style="font-size:.82rem;color:var(--text-light);">
                                <?= $u['phone'] ? htmlspecialchars($u['phone']) : '<span style="color:#ccc;">—</span>' ?>
                            </td>
                            <td style="font-size:.8rem;color:var(--text-light);font-family:'DM Mono',monospace;">
                                <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                            </td>
                    
                        <td>
                            <div style="display:flex;gap:.4rem;">
                                <a href="view-user.php?id=<?= $u['id'] ?>" class="icon-btn" title="Voir">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="edit-user.php?id=<?= $u['id'] ?>" class="icon-btn" title="Modifier">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <a href="users.php?delete=<?= $u['id'] ?>" class="icon-btn del" title="Supprimer"
                                   onclick="return confirm('Supprimer <?= htmlspecialchars($u['name'], ENT_QUOTES) ?> ?')">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </a>
                            </div>
                        </td>
  
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.4rem;border-top:1px solid var(--border);font-size:.82rem;color:var(--text-light);">
                <span>Affichage de <?= count($users) ?> résultats</span>
                <div style="display:flex;gap:5px;">
                    <a href="#" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--border);display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;text-decoration:none;color:var(--text);">‹</a>
                    <a href="#" style="width:30px;height:30px;border-radius:7px;background:var(--ocean-teal);color:#fff;border:1px solid var(--ocean-teal);display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;text-decoration:none;">1</a>
                    <a href="#" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--border);display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;text-decoration:none;color:var(--text);">›</a>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
<script src="../../assets/js/main.js"></script>
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