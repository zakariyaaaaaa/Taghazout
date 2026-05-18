<?php
// refunds.php - Admin Refunds
session_start();
// if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }

// Handle refund submission
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'issue_refund') {
    $payment_id = trim($_POST['payment_id'] ?? '');
    $amount     = floatval($_POST['amount'] ?? 0);
    $reason     = trim($_POST['reason'] ?? '');

    if (!$payment_id || $amount <= 0) {
      $error_msg = 'Payment ID and valid amount are required.';
    } else {
      // TODO: call gateway API (Stripe, etc.)
      $success_msg = "Refund of {$amount} MAD issued for payment {$payment_id}.";
    }
  }
}

// Prefill from URL
$prefill_payment = htmlspecialchars($_GET['payment'] ?? '');

// Mock refund history
$refunds = [
  ['id'=>'REF-0091','payment'=>'PAY-00419','customer'=>'Omar Fassi','amount'=>'75.00','reason'=>'Customer request','status'=>'completed','date'=>'2026-05-16 18:15'],
  ['id'=>'REF-0090','payment'=>'PAY-00410','customer'=>'Hind Berrada','amount'=>'200.00','reason'=>'Duplicate payment','status'=>'completed','date'=>'2026-05-15 12:44'],
  ['id'=>'REF-0089','payment'=>'PAY-00405','customer'=>'Mehdi Chaoui','amount'=>'50.00','reason'=>'Product not received','status'=>'processing','date'=>'2026-05-14 09:30'],
  ['id'=>'REF-0088','payment'=>'PAY-00399','customer'=>'Sara Moukrim','amount'=>'340.00','reason'=>'Wrong item shipped','status'=>'completed','date'=>'2026-05-13 16:05'],
  ['id'=>'REF-0087','payment'=>'PAY-00388','customer'=>'Rachid Ousseini','amount'=>'90.00','reason'=>'Changed mind','status'=>'failed','date'=>'2026-05-12 11:20'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Refunds – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0d0f14; --surface: #13161d; --border: #1e222d;
    --accent: #00e5a0; --accent2: #6c63ff;
    --text: #e8eaf0; --muted: #5a5f72;
    --danger: #ff4d6a; --warn: #ffc947; --success: #00e5a0;
    --mono: 'DM Mono', monospace; --sans: 'Syne', sans-serif;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: var(--sans); min-height: 100vh; }

  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; width: 220px;
    background: var(--surface); border-right: 1px solid var(--border);
    display: flex; flex-direction: column; padding: 28px 0; z-index: 100;
  }
  .logo { padding: 0 24px 32px; font-size: 1.15rem; font-weight: 700; color: var(--accent); }
  .nav-item {
    display: flex; align-items: center; gap: 10px; padding: 11px 24px;
    color: var(--muted); font-size: .85rem; text-decoration: none; transition: all .2s;
  }
  .nav-item:hover, .nav-item.active { color: var(--text); background: rgba(255,255,255,.04); }
  .nav-item.active { border-left: 2px solid var(--accent); color: var(--accent); }

  .main { margin-left: 220px; padding: 40px 48px; }
  .page-header { margin-bottom: 32px; }
  .page-header h1 { font-size: 1.5rem; font-weight: 700; }
  .page-header p  { color: var(--muted); font-size: .85rem; margin-top: 6px; }

  .layout { display: grid; grid-template-columns: 380px 1fr; gap: 28px; }

  /* Card */
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
  .card-header { padding: 16px 22px; border-bottom: 1px solid var(--border); font-size: .78rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
  .card-body { padding: 22px; }

  /* Form */
  .form-group { margin-bottom: 18px; }
  .form-label { display: block; font-size: .8rem; color: var(--muted); margin-bottom: 7px; font-weight: 600; letter-spacing: .04em; }
  .form-control {
    width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
    color: var(--text); padding: 10px 14px; font-family: var(--mono); font-size: .88rem;
    outline: none; transition: border .2s;
  }
  .form-control:focus { border-color: var(--accent); }
  textarea.form-control { min-height: 90px; resize: vertical; font-family: var(--sans); }
  select.form-control { cursor: pointer; }

  .amount-row { display: grid; grid-template-columns: 1fr 100px; gap: 10px; }

  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    padding: 11px 20px; border-radius: 8px; font-size: .88rem; font-family: var(--sans);
    font-weight: 600; cursor: pointer; border: none; transition: all .2s; width: 100%;
  }
  .btn-danger-solid { background: var(--danger); color: #fff; }
  .btn-danger-solid:hover { opacity: .88; }

  /* Alert */
  .alert {
    padding: 13px 18px; border-radius: 9px; font-size: .85rem; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
  }
  .alert.success { background: rgba(0,229,160,.1); border: 1px solid rgba(0,229,160,.25); color: var(--success); }
  .alert.error   { background: rgba(255,77,106,.1); border: 1px solid rgba(255,77,106,.25); color: var(--danger); }

  /* Warning box */
  .warning-box {
    background: rgba(255,201,71,.07); border: 1px solid rgba(255,201,71,.2);
    border-radius: 9px; padding: 13px 16px; font-size: .82rem; color: var(--warn); margin-bottom: 20px;
  }

  /* Table */
  .table-wrap { overflow: hidden; }
  table { width: 100%; border-collapse: collapse; }
  thead th {
    padding: 12px 14px; text-align: left; font-size: .73rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .07em; color: var(--muted);
    border-bottom: 1px solid var(--border); background: rgba(255,255,255,.02);
  }
  tbody tr:hover { background: rgba(255,255,255,.03); }
  tbody td { padding: 13px 14px; font-size: .84rem; border-bottom: 1px solid var(--border); }
  tbody tr:last-child td { border-bottom: none; }
  .mono { font-family: var(--mono); }

  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 50px; font-size: .73rem; font-family: var(--mono);
  }
  .badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
  .badge.completed  { background:rgba(0,229,160,.1); color:var(--success); }
  .badge.processing { background:rgba(255,201,71,.1); color:var(--warn); }
  .badge.failed     { background:rgba(255,77,106,.1); color:var(--danger); }

  /* Stats row */
  .stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 22px; }
  .mini-stat { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 14px 18px; }
  .mini-stat .label { font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
  .mini-stat .val { font-size: 1.3rem; font-weight: 700; font-family: var(--mono); }

  @media (max-width: 1024px) {
    .layout { grid-template-columns: 1fr; }
    .sidebar { display: none; }
    .main { margin-left: 0; padding: 20px 16px; }
  }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">⬡ AdminPanel</div>
  <a href="dashboard.php" class="nav-item">⊞ Dashboard</a>
  <a href="payments.php" class="nav-item">↔ Payments</a>
  <a href="refunds.php" class="nav-item active">↩ Refunds</a>
  <a href="orders.php" class="nav-item">◈ Orders</a>
  <a href="customers.php" class="nav-item">◉ Customers</a>
  <a href="settings.php" class="nav-item" style="margin-top:auto">⚙ Settings</a>
</aside>

<main class="main">
  <div class="page-header">
    <h1>Refunds</h1>
    <p>Issue and manage payment refunds</p>
  </div>

  <!-- Stats row -->
  <div class="stats-row">
    <div class="mini-stat">
      <div class="label">Total Refunded</div>
      <div class="val" style="color:var(--accent2)">755.00 MAD</div>
    </div>
    <div class="mini-stat">
      <div class="label">Processing</div>
      <div class="val" style="color:var(--warn)">1</div>
    </div>
    <div class="mini-stat">
      <div class="label">This Month</div>
      <div class="val" style="color:var(--text)">5</div>
    </div>
  </div>

  <div class="layout">

    <!-- Issue Refund Form -->
    <div>
      <div class="card">
        <div class="card-header">↩ Issue New Refund</div>
        <div class="card-body">

          <?php if ($success_msg): ?>
            <div class="alert success">✓ <?= htmlspecialchars($success_msg) ?></div>
          <?php endif; ?>
          <?php if ($error_msg): ?>
            <div class="alert error">✕ <?= htmlspecialchars($error_msg) ?></div>
          <?php endif; ?>

          <div class="warning-box">
            ⚠ Refunds are irreversible. Double-check the amount and payment before submitting.
          </div>

          <form method="POST" action="refunds.php">
            <input type="hidden" name="action" value="issue_refund">

            <div class="form-group">
              <label class="form-label">Payment ID</label>
              <input type="text" name="payment_id" class="form-control"
                     placeholder="PAY-00XXX"
                     value="<?= $prefill_payment ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Refund Amount (MAD)</label>
              <div class="amount-row">
                <input type="number" name="amount" class="form-control"
                       placeholder="0.00" step="0.01" min="0.01" required>
                <select name="currency" class="form-control">
                  <option>MAD</option>
                  <option>EUR</option>
                  <option>USD</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Refund Type</label>
              <select name="type" class="form-control">
                <option value="full">Full Refund</option>
                <option value="partial">Partial Refund</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Reason</label>
              <select name="reason" class="form-control">
                <option value="">Select reason...</option>
                <option>Customer request</option>
                <option>Duplicate payment</option>
                <option>Product not received</option>
                <option>Wrong item shipped</option>
                <option>Changed mind</option>
                <option>Fraud suspected</option>
                <option>Other</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Internal Notes (optional)</label>
              <textarea name="notes" class="form-control" placeholder="Add any internal notes..."></textarea>
            </div>

            <button type="submit" class="btn btn-danger-solid">↩ Issue Refund</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Refund History -->
    <div class="card">
      <div class="card-header">Refund History</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Refund ID</th>
              <th>Payment</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($refunds as $r): ?>
            <tr>
              <td class="mono" style="color:var(--accent2)"><?= $r['id'] ?></td>
              <td>
                <a href="payment-details.php?id=<?= urlencode($r['payment']) ?>"
                   style="color:var(--accent);font-family:var(--mono);text-decoration:none;font-size:.82rem">
                  <?= $r['payment'] ?>
                </a>
              </td>
              <td><?= htmlspecialchars($r['customer']) ?></td>
              <td class="mono" style="font-weight:600"><?= $r['amount'] ?> MAD</td>
              <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($r['reason']) ?></td>
              <td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
              <td class="mono" style="color:var(--muted);font-size:.78rem"><?= $r['date'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>
</body>
</html>