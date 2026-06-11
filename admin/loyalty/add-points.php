<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$success = $error = '';

// ─── Handle form submit ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $points  = (int)($_POST['points']  ?? 0);
    $type    = in_array($_POST['type'] ?? '', ['earn','redeem']) ? $_POST['type'] : 'earn';
    $reason  = trim($_POST['reason'] ?? '');

    if ($user_id <= 0 || $points <= 0 || $reason === '') {
        $error = "Tous les champs sont obligatoires.";
    } else {
        // Check balance if redeeming
        if ($type === 'redeem') {
            $balance = $pdo->prepare("
                SELECT COALESCE(SUM(CASE WHEN type='earn' THEN points ELSE -points END), 0)
                FROM loyalty_points WHERE user_id = ?
            ");
            $balance->execute([$user_id]);
            $bal = (int)$balance->fetchColumn();
            if ($points > $bal) {
                $error = "Solde insuffisant. Balance actuelle : <strong>{$bal} pts</strong>";
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("
                INSERT INTO loyalty_points (user_id, points, type, reason, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $points, $type, $reason, $_SESSION['user_id']]);
            $success = $type === 'earn'
                ? "✅ <strong>{$points} points</strong> ajoutés avec succès !"
                : "✅ <strong>{$points} points</strong> débités avec succès !";
        }
    }
}

// ─── Load users with balances ─────────────────────────────
$users = $pdo->query("
    SELECT u.id, u.name, u.email,
        COALESCE(SUM(CASE WHEN lp.type='earn' THEN lp.points ELSE -lp.points END), 0) AS balance
    FROM users u
    LEFT JOIN loyalty_points lp ON u.id = lp.user_id
    WHERE u.role = 'user'
    GROUP BY u.id, u.name, u.email
    ORDER BY u.name ASC
")->fetchAll();

// ─── Recent additions ─────────────────────────────────────
$recent = $pdo->query("
    SELECT lp.*, u.name AS user_name, a.name AS admin_name
    FROM loyalty_points lp
    LEFT JOIN users u ON lp.user_id = u.id
    LEFT JOIN users a ON lp.created_by = a.id
    ORDER BY lp.created_at DESC
    LIMIT 12
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Points — Loyalty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        :root {
            --earn:     #10b981;
            --earn-lt:  rgba(16,185,129,0.10);
            --redeem:   #f43f5e;
            --redeem-lt:rgba(244,63,94,0.10);
            --gold:     #f59e0b;
            --gold-lt:  rgba(245,158,11,0.12);
        }

        /* ── Split layout ─────────────────────────────────── */
        .add-points-layout {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .add-points-layout { grid-template-columns: 1fr; }
        }

        /* ── Form card ────────────────────────────────────── */
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .form-card-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(16,185,129,0.06), rgba(14,165,233,0.06));
        }
        .form-card-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }
        .form-card-body { padding: 1.5rem; }

        /* ── Type toggle ──────────────────────────────────── */
        .type-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }
        .type-btn {
            border: 2px solid var(--border);
            background: var(--bg);
            border-radius: var(--radius-sm);
            padding: 0.8rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.18s;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-light);
        }
        .type-btn.earn-active  { border-color: var(--earn);   background: var(--earn-lt);   color: var(--earn); }
        .type-btn.redeem-active{ border-color: var(--redeem); background: var(--redeem-lt); color: var(--redeem); }
        .type-btn:hover { opacity: 0.85; }

        /* ── Form fields ──────────────────────────────────── */
        .form-group { margin-bottom: 1.1rem; }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.4rem;
            letter-spacing: 0.02em;
        }
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            color: var(--text);
            font-size: 0.88rem;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.18s;
            box-sizing: border-box;
        }
        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }

        /* ── User balance preview ─────────────────────────── */
        .balance-preview {
            display: none;
            margin-top: 0.5rem;
            padding: 0.65rem 0.9rem;
            border-radius: var(--radius-sm);
            background: var(--primary-light);
            font-size: 0.84rem;
            color: var(--primary);
            font-weight: 600;
        }

        /* ── Quick presets ────────────────────────────────── */
        .presets {
            display: flex; gap: 0.5rem; flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }
        .preset-btn {
            padding: 0.3rem 0.75rem;
            border-radius: 50px;
            border: 1.5px solid var(--border);
            background: var(--bg);
            color: var(--text-light);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .preset-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

        /* ── Submit button ────────────────────────────────── */
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Syne', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.02em;
        }
        .btn-submit.earn-mode   { background: var(--earn);   color: #fff; }
        .btn-submit.redeem-mode { background: var(--redeem); color: #fff; }
        .btn-submit:hover { opacity: 0.88; transform: translateY(-1px); }

        /* ── Alert ────────────────────────────────────────── */
        .alert {
            padding: 0.85rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            margin-bottom: 1.1rem;
        }
        .alert-success { background: var(--earn-lt); color: var(--earn); border: 1px solid rgba(16,185,129,0.25); }
        .alert-error   { background: var(--redeem-lt); color: var(--redeem); border: 1px solid rgba(244,63,94,0.25); }

        /* ── History table ────────────────────────────────── */
        .tx-earn   { background: var(--earn-lt);   color: var(--earn);   font-size: 0.76rem; font-weight:700; padding: 0.22rem 0.65rem; border-radius: 50px; display: inline-block; }
        .tx-redeem { background: var(--redeem-lt); color: var(--redeem); font-size: 0.76rem; font-weight:700; padding: 0.22rem 0.65rem; border-radius: 50px; display: inline-block; }
    </style>
</head>
<body>
<div class="admin-shell">

<?php
$_SERVER['PHP_SELF'] = '/admin/loyalty/add-points.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<main class="admin-main">

    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Ajouter des points</div>
                <div class="topbar-breadcrumb">Home <span>/</span> <a href="../loyalty.php">Loyalty</a> <span>/</span> Ajouter points</div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="../loyalty.php" style="font-size:0.85rem; color:var(--primary); font-weight:600;">← Retour</a>
        </div>
    </div>

    <div class="admin-body">

        <div class="page-header">
            <div>
                <h1>⭐ Gestion des points</h1>
                <p>Attribuez ou déduisez des points de fidélité à vos clients.</p>
            </div>
        </div>

        <div class="add-points-layout">

            <!-- ── FORM ─────────────────────────────────── -->
            <div class="form-card">
                <div class="form-card-header">
                    <h2 id="formTitle">➕ Ajouter des points</h2>
                </div>
                <div class="form-card-body">

                    <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" id="pointsForm">
                        <input type="hidden" name="type" id="typeInput" value="earn">

                        <!-- Type toggle -->
                        <div class="type-toggle">
                            <div class="type-btn earn-active" id="btnEarn" onclick="setType('earn')">
                                ⭐ Ajouter points
                            </div>
                            <div class="type-btn" id="btnRedeem" onclick="setType('redeem')">
                                🔄 Déduire points
                            </div>
                        </div>

                        <!-- User select -->
                        <div class="form-group">
                            <label>👤 Membre</label>
                            <select name="user_id" id="userSelect" onchange="showBalance(this)" required>
                                <option value="">— Sélectionner un membre —</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" data-balance="<?= $u['balance'] ?>">
                                    <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="balance-preview" id="balancePreview">
                                Balance actuelle : <span id="balanceVal">0</span> pts
                            </div>
                        </div>

                        <!-- Points amount -->
                        <div class="form-group">
                            <label>🔢 Points</label>
                            <div class="presets">
                                <?php foreach ([50, 100, 200, 500, 1000] as $p): ?>
                                <button type="button" class="preset-btn" onclick="setPoints(<?= $p ?>)"><?= $p ?></button>
                                <?php endforeach; ?>
                            </div>
                            <input type="number" name="points" id="pointsInput" placeholder="Ex: 100" min="1" required>
                        </div>

                        <!-- Reason -->
                        <div class="form-group">
                            <label>📝 Raison</label>
                            <select name="reason" id="reasonSelect" onchange="handleReason(this)" style="margin-bottom:0.5rem;">
                                <option value="">— Raison prédéfinie —</option>
                                <option value="Réservation confirmée">🏨 Réservation confirmée</option>
                                <option value="Cours de surf complété">🏄 Cours de surf complété</option>
                                <option value="Avis laissé">⭐ Avis laissé</option>
                                <option value="Parrainage client">👥 Parrainage client</option>
                                <option value="Bonus anniversaire">🎂 Bonus anniversaire</option>
                                <option value="Promotion spéciale">🎉 Promotion spéciale</option>
                                <option value="Échange récompense">🎁 Échange récompense</option>
                                <option value="Correction manuelle">🔧 Correction manuelle</option>
                                <option value="custom">✏️ Autre (personnalisé)</option>
                            </select>
                            <textarea name="reason" id="reasonText" placeholder="Décrivez la raison..." style="display:none;" required></textarea>
                        </div>

                        <button type="submit" class="btn-submit earn-mode" id="submitBtn">
                            ⭐ Attribuer les points
                        </button>
                    </form>

                </div>
            </div>

            <!-- ── HISTORY TABLE ─────────────────────────── -->
            <div class="card">
                <div class="card-header">
                    <h3>🕑 Historique récent</h3>
                    <span style="font-size:0.8rem; color:var(--text-light);">12 dernières opérations</span>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Membre</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Raison</th>
                                <th>Par</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $tx): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; font-size:0.87rem;"><?= htmlspecialchars($tx['user_name'] ?? 'N/A') ?></div>
                                </td>
                                <td>
                                    <span class="tx-<?= $tx['type'] ?>">
                                        <?= $tx['type'] === 'earn' ? '⭐ Earn' : '🔄 Redeem' ?>
                                    </span>
                                </td>
                                <td style="font-weight:800; color:<?= $tx['type'] === 'earn' ? 'var(--earn)' : 'var(--redeem)' ?>; font-family:'Syne',sans-serif;">
                                    <?= $tx['type'] === 'earn' ? '+' : '-' ?><?= number_format($tx['points']) ?>
                                </td>
                                <td style="font-size:0.82rem; color:var(--text-light); max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($tx['reason'] ?? '—') ?>
                                </td>
                                <td style="font-size:0.82rem; color:var(--text-light);">
                                    <?= htmlspecialchars($tx['admin_name'] ?? 'Système') ?>
                                </td>
                                <td style="font-size:0.8rem; color:var(--text-light);">
                                    <?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-light);">Aucune transaction.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</main>
</div>

<script>
let currentType = 'earn';

function setType(type) {
    currentType = type;
    document.getElementById('typeInput').value = type;

    const btnEarn   = document.getElementById('btnEarn');
    const btnRedeem = document.getElementById('btnRedeem');
    const submitBtn = document.getElementById('submitBtn');
    const formTitle = document.getElementById('formTitle');

    btnEarn.className   = 'type-btn' + (type === 'earn' ? ' earn-active' : '');
    btnRedeem.className = 'type-btn' + (type === 'redeem' ? ' redeem-active' : '');

    if (type === 'earn') {
        submitBtn.className = 'btn-submit earn-mode';
        submitBtn.textContent = '⭐ Attribuer les points';
        formTitle.textContent = '➕ Ajouter des points';
    } else {
        submitBtn.className = 'btn-submit redeem-mode';
        submitBtn.textContent = '🔄 Déduire les points';
        formTitle.textContent = '➖ Déduire des points';
    }
}

function showBalance(select) {
    const opt = select.options[select.selectedIndex];
    const preview = document.getElementById('balancePreview');
    const valEl = document.getElementById('balanceVal');
    if (opt.value) {
        preview.style.display = 'block';
        valEl.textContent = parseInt(opt.dataset.balance || 0).toLocaleString('fr-FR');
    } else {
        preview.style.display = 'none';
    }
}

function setPoints(n) {
    document.getElementById('pointsInput').value = n;
}

function handleReason(select) {
    const textarea = document.getElementById('reasonText');
    if (select.value === 'custom') {
        textarea.style.display = 'block';
        textarea.required = true;
        select.name = '_reason_select'; // remove name so textarea is used
        textarea.name = 'reason';
    } else {
        textarea.style.display = 'none';
        textarea.required = false;
        select.name = 'reason';
        textarea.name = '_reason_text';
    }
}
</script>
</body>
</html>