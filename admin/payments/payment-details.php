<?php
// payment-details.php - Admin Payment Details
session_start();
// if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }

// Mock data - replace with DB query
$payment_id = $_GET['id'] ?? 1;

$payment = [
    'id'          => 'PAY-00423',
    'order_id'    => 'ORD-10987',
    'customer'    => 'Youssef El Amrani',
    'email'       => 'youssef@example.com',
    'amount'      => '349.00',
    'currency'    => 'MAD',
    'method'      => 'Credit Card',
    'card_last4'  => '4242',
    'status'      => 'completed',
    'date'        => '2026-05-17 14:32:05',
    'gateway'     => 'Stripe',
    'txn_id'      => 'ch_3PxK2LKZ2eZvKYlo',
    'ip'          => '105.158.22.41',
    'notes'       => '',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Details – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0d0f14;
    --surface: #13161d;
    --border: #1e222d;
    --accent: #00e5a0;
    --accent2: #6c63ff;
    --text: #e8eaf0;
    --muted: #5a5f72;
    --danger: #ff4d6a;
    --warn: #ffc947;
    --success: #00e5a0;
    --mono: 'DM Mono', monospace;
    --sans: 'Syne', sans-serif;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: var(--sans); min-height: 100vh; }

  /* --- Sidebar --- */
  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; width: 220px;
    background: var(--surface); border-right: 1px solid var(--border);
    display: flex; flex-direction: column; padding: 28px 0; z-index: 100;
  }
  .logo { padding: 0 24px 32px; font-size: 1.15rem; font-weight: 700; letter-spacing: .04em; color: var(--accent); }
  .nav-item {
    display: flex; align-items: center; gap: 10px; padding: 11px 24px;
    color: var(--muted); font-size: .85rem; text-decoration: none; transition: all .2s;
  }
  .nav-item:hover, .nav-item.active { color: var(--text); background: rgba(255,255,255,.04); }
  .nav-item.active { border-left: 2px solid var(--accent); color: var(--accent); }

  /* --- Main --- */
  .main { margin-left: 220px; padding: 40px 48px; }
  .breadcrumb { font-size: .8rem; color: var(--muted); margin-bottom: 20px; font-family: var(--mono); }
  .breadcrumb a { color: var(--muted); text-decoration: none; }
  .breadcrumb a:hover { color: var(--accent); }

  .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 36px; }
  .page-header h1 { font-size: 1.5rem; font-weight: 700; }
  .page-header .pid { font-family: var(--mono); font-size: .85rem; color: var(--muted); margin-top: 4px; }

  /* --- Status Badge --- */
  .badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: 50px; font-size: .78rem; font-family: var(--mono); font-weight: 500;
  }
  .badge.completed  { background: rgba(0,229,160,.12); color: var(--success); }
  .badge.pending    { background: rgba(255,201,71,.12); color: var(--warn); }
  .badge.failed     { background: rgba(255,77,106,.12); color: var(--danger); }
  .badge.refunded   { background: rgba(108,99,255,.12); color: var(--accent2); }
  .badge::before { content:''; width:7px; height:7px; border-radius:50%; background: currentColor; }

  /* --- Grid --- */
  .grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; }

  /* --- Card --- */
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
  .card-header { padding: 18px 24px; border-bottom: 1px solid var(--border); font-size: .8rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
  .card-body { padding: 24px; }

  .info-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); }
  .info-row:last-child { border-bottom: none; }
  .info-label { font-size: .82rem; color: var(--muted); }
  .info-value { font-size: .88rem; font-family: var(--mono); color: var(--text); }
  .info-value.highlight { color: var(--accent); }

  .amount-display {
    text-align: center; padding: 28px 0;
    border-bottom: 1px solid var(--border); margin-bottom: 20px;
  }
  .amount-display .amount { font-size: 2.4rem; font-weight: 700; font-family: var(--mono); color: var(--text); }
  .amount-display .currency { font-size: 1rem; color: var(--muted); margin-left: 6px; }

  /* --- Action Buttons --- */
  .actions { display: flex; flex-direction: column; gap: 10px; }
  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px 20px; border-radius: 8px; font-size: .85rem; font-family: var(--sans);
    font-weight: 600; cursor: pointer; border: none; transition: all .2s; text-decoration: none;
  }
  .btn-primary { background: var(--accent); color: #000; }
  .btn-primary:hover { opacity: .88; }
  .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
  .btn-outline:hover { border-color: var(--accent); color: var(--accent); }
  .btn-danger { background: rgba(255,77,106,.1); color: var(--danger); border: 1px solid rgba(255,77,106,.2); }
  .btn-danger:hover { background: rgba(255,77,106,.2); }

  /* --- Timeline --- */
  .timeline { padding-top: 4px; }
  .tl-item { display: flex; gap: 14px; padding-bottom: 22px; position: relative; }
  .tl-item::before {
    content: ''; position: absolute; left: 7px; top: 18px; bottom: 0;
    width: 1px; background: var(--border);
  }
  .tl-item:last-child::before { display: none; }
  .tl-dot { width: 15px; height: 15px; border-radius: 50%; flex-shrink: 0; margin-top: 3px; }
  .tl-dot.green  { background: var(--success); }
  .tl-dot.yellow { background: var(--warn); }
  .tl-dot.gray   { background: var(--muted); }
  .tl-info .tl-title { font-size: .85rem; font-weight: 600; }
  .tl-info .tl-time  { font-size: .75rem; color: var(--muted); font-family: var(--mono); margin-top: 3px; }

  @media (max-width: 900px) {
    .sidebar { display: none; }
    .main { margin-left: 0; padding: 24px 20px; }
    .grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">⬡ AdminPanel</div>
  <a href="dashboard.php" class="nav-item">⊞ Dashboard</a>
  <a href="payments.php" class="nav-item active">↔ Payments</a>
  <a href="refunds.php" class="nav-item">↩ Refunds</a>
  <a href="orders.php" class="nav-item">◈ Orders</a>
  <a href="customers.php" class="nav-item">◉ Customers</a>
  <a href="settings.php" class="nav-item" style="margin-top:auto">⚙ Settings</a>
</aside>

<!-- Main -->
<main class="main">
  <div class="breadcrumb">
    <a href="payments.php">Payments</a> / <?= htmlspecialchars($payment['id']) ?>
  </div>

  <div class="page-header">
    <div>
      <h1>Payment Details</h1>
      <div class="pid"><?= htmlspecialchars($payment['id']) ?> · <?= htmlspecialchars($payment['date']) ?></div>
    </div>
    <span class="badge <?= $payment['status'] ?>"><?= ucfirst($payment['status']) ?></span>
  </div>

  <div class="grid">
    <!-- Left column -->
    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- Amount -->
      <div class="card">
        <div class="card-header">Transaction Summary</div>
        <div class="card-body">
          <div class="amount-display">
            <span class="amount"><?= $payment['amount'] ?></span>
            <span class="currency"><?= $payment['currency'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Order ID</span>
            <span class="info-value highlight"><?= $payment['order_id'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Payment Method</span>
            <span class="info-value"><?= $payment['method'] ?> ···· <?= $payment['card_last4'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Gateway</span>
            <span class="info-value"><?= $payment['gateway'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Transaction ID</span>
            <span class="info-value"><?= $payment['txn_id'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">IP Address</span>
            <span class="info-value"><?= $payment['ip'] ?></span>
          </div>
        </div>
      </div>

      <!-- Customer -->
      <div class="card">
        <div class="card-header">Customer</div>
        <div class="card-body">
          <div class="info-row">
            <span class="info-label">Name</span>
            <span class="info-value"><?= htmlspecialchars($payment['customer']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value"><?= htmlspecialchars($payment['email']) ?></span>
          </div>
        </div>
      </div>

    </div>

    <!-- Right column -->
    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- Actions -->
      <div class="card">
        <div class="card-header">Actions</div>
        <div class="card-body">
          <div class="actions">
            <a href="refunds.php?payment=<?= $payment['id'] ?>" class="btn btn-primary">↩ Issue Refund</a>
            <a href="orders.php?id=<?= $payment['order_id'] ?>" class="btn btn-outline">◈ View Order</a>
            <button class="btn btn-outline" onclick="window.print()">⎙ Print Receipt</button>
            <?php if ($payment['status'] !== 'failed'): ?>
            <button class="btn btn-danger" onclick="confirm('Mark as failed?')">✕ Mark Failed</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div class="card">
        <div class="card-header">Activity Log</div>
        <div class="card-body">
          <div class="timeline">
            <div class="tl-item">
              <div class="tl-dot green"></div>
              <div class="tl-info">
                <div class="tl-title">Payment Completed</div>
                <div class="tl-time">2026-05-17 14:32:05</div>
              </div>
            </div>
            <div class="tl-item">
              <div class="tl-dot yellow"></div>
              <div class="tl-info">
                <div class="tl-title">Payment Initiated</div>
                <div class="tl-time">2026-05-17 14:31:48</div>
              </div>
            </div>
            <div class="tl-item">
              <div class="tl-dot gray"></div>
              <div class="tl-info">
                <div class="tl-title">Order Created</div>
                <div class="tl-time">2026-05-17 14:31:10</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>
</body>
</html>