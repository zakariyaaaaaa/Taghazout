<?php
// payments.php - Admin Payments List
session_start();
// if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }

// Mock data - replace with DB query + pagination
$payments = [
  ['id'=>'PAY-00423','order'=>'ORD-10987','customer'=>'Youssef El Amrani','amount'=>'349.00','method'=>'Credit Card','status'=>'completed','date'=>'2026-05-17 14:32'],
  ['id'=>'PAY-00422','order'=>'ORD-10986','customer'=>'Fatima Benali','amount'=>'120.50','method'=>'PayPal','status'=>'pending','date'=>'2026-05-17 13:11'],
  ['id'=>'PAY-00421','order'=>'ORD-10985','customer'=>'Amine Tazi','amount'=>'850.00','method'=>'Bank Transfer','status'=>'completed','date'=>'2026-05-17 11:55'],
  ['id'=>'PAY-00420','order'=>'ORD-10984','customer'=>'Salma Karimi','amount'=>'200.00','method'=>'Credit Card','status'=>'failed','date'=>'2026-05-17 10:20'],
  ['id'=>'PAY-00419','order'=>'ORD-10983','customer'=>'Omar Fassi','amount'=>'75.00','method'=>'PayPal','status'=>'refunded','date'=>'2026-05-16 18:03'],
  ['id'=>'PAY-00418','order'=>'ORD-10982','customer'=>'Nora Alaoui','amount'=>'1200.00','method'=>'Credit Card','status'=>'completed','date'=>'2026-05-16 15:47'],
  ['id'=>'PAY-00417','order'=>'ORD-10981','customer'=>'Karim Bennani','amount'=>'530.00','method'=>'Bank Transfer','status'=>'pending','date'=>'2026-05-16 09:30'],
];

$totals = [
  'total'     => array_sum(array_column(array_filter($payments, fn($p)=>$p['status']==='completed'), 'amount')),
  'pending'   => count(array_filter($payments, fn($p)=>$p['status']==='pending')),
  'failed'    => count(array_filter($payments, fn($p)=>$p['status']==='failed')),
  'refunded'  => count(array_filter($payments, fn($p)=>$p['status']==='refunded')),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments – Admin</title>
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
  .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
  .page-header h1 { font-size: 1.5rem; font-weight: 700; }

  /* Stats */
  .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
  .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 18px 20px; }
  .stat-label { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 8px; }
  .stat-val { font-size: 1.6rem; font-weight: 700; font-family: var(--mono); }
  .stat-val.green { color: var(--success); }
  .stat-val.yellow { color: var(--warn); }
  .stat-val.red { color: var(--danger); }
  .stat-val.purple { color: var(--accent2); }

  /* Filter bar */
  .filter-bar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
  .filter-bar input, .filter-bar select {
    background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
    color: var(--text); padding: 9px 14px; font-family: var(--sans); font-size: .85rem;
    outline: none; transition: border .2s;
  }
  .filter-bar input:focus, .filter-bar select:focus { border-color: var(--accent); }
  .filter-bar input { flex: 1; min-width: 200px; }
  .btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: 8px; font-size: .85rem; font-family: var(--sans);
    font-weight: 600; cursor: pointer; border: none; transition: all .2s; text-decoration: none;
  }
  .btn-primary { background: var(--accent); color: #000; }
  .btn-primary:hover { opacity: .88; }

  /* Table */
  .table-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; }
  thead th {
    padding: 13px 16px; text-align: left; font-size: .75rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .07em; color: var(--muted);
    border-bottom: 1px solid var(--border); background: rgba(255,255,255,.02);
  }
  tbody tr { transition: background .15s; }
  tbody tr:hover { background: rgba(255,255,255,.03); }
  tbody td { padding: 14px 16px; font-size: .85rem; border-bottom: 1px solid var(--border); }
  tbody tr:last-child td { border-bottom: none; }
  .mono { font-family: var(--mono); }

  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 50px; font-size: .75rem; font-family: var(--mono);
  }
  .badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
  .badge.completed { background:rgba(0,229,160,.1); color:var(--success); }
  .badge.pending   { background:rgba(255,201,71,.1); color:var(--warn); }
  .badge.failed    { background:rgba(255,77,106,.1); color:var(--danger); }
  .badge.refunded  { background:rgba(108,99,255,.1); color:var(--accent2); }

  .action-link { color: var(--muted); font-size:.8rem; text-decoration:none; padding: 4px 8px; border-radius:5px; transition: all .15s; }
  .action-link:hover { color: var(--accent); background: rgba(0,229,160,.08); }

  .pagination { display: flex; gap: 6px; justify-content: flex-end; padding: 20px 0 0; }
  .page-btn {
    width: 34px; height: 34px; border-radius: 7px; display: flex; align-items: center; justify-content: center;
    font-size: .82rem; font-family: var(--mono); cursor: pointer;
    background: var(--surface); border: 1px solid var(--border); color: var(--muted); text-decoration: none; transition: all .2s;
  }
  .page-btn.active, .page-btn:hover { border-color: var(--accent); color: var(--accent); }

  @media (max-width: 900px) {
    .sidebar { display: none; }
    .main { margin-left: 0; padding: 20px 16px; }
    .stats { grid-template-columns: repeat(2, 1fr); }
    table thead th:nth-child(3), table tbody td:nth-child(3),
    table thead th:nth-child(5), table tbody td:nth-child(5) { display: none; }
  }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">⬡ AdminPanel</div>
  <a href="dashboard.php" class="nav-item">⊞ Dashboard</a>
  <a href="payments.php" class="nav-item active">↔ Payments</a>
  <a href="refunds.php" class="nav-item">↩ Refunds</a>
  <a href="orders.php" class="nav-item">◈ Orders</a>
  <a href="customers.php" class="nav-item">◉ Customers</a>
  <a href="settings.php" class="nav-item" style="margin-top:auto">⚙ Settings</a>
</aside>

<main class="main">
  <div class="page-header">
    <h1>Payments</h1>
    <a href="export.php?type=payments" class="btn btn-primary">⬇ Export CSV</a>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-label">Total Collected</div>
      <div class="stat-val green"><?= number_format($totals['total'], 2) ?> MAD</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending</div>
      <div class="stat-val yellow"><?= $totals['pending'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Failed</div>
      <div class="stat-val red"><?= $totals['failed'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Refunded</div>
      <div class="stat-val purple"><?= $totals['refunded'] ?></div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <input type="text" placeholder="Search payment ID, customer...">
    <select>
      <option value="">All Statuses</option>
      <option>completed</option>
      <option>pending</option>
      <option>failed</option>
      <option>refunded</option>
    </select>
    <select>
      <option>All Methods</option>
      <option>Credit Card</option>
      <option>PayPal</option>
      <option>Bank Transfer</option>
    </select>
    <input type="date" value="<?= date('Y-m-d') ?>">
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Payment ID</th>
          <th>Customer</th>
          <th>Order</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Status</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td class="mono" style="color:var(--accent)"><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['customer']) ?></td>
          <td class="mono"><?= $p['order'] ?></td>
          <td class="mono" style="font-weight:600"><?= $p['amount'] ?> MAD</td>
          <td style="color:var(--muted)"><?= $p['method'] ?></td>
          <td><span class="badge <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
          <td class="mono" style="color:var(--muted);font-size:.78rem"><?= $p['date'] ?></td>
          <td>
            <a href="payment-details.php?id=<?= urlencode($p['id']) ?>" class="action-link">View →</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="pagination">
    <a href="#" class="page-btn">‹</a>
    <a href="#" class="page-btn active">1</a>
    <a href="#" class="page-btn">2</a>
    <a href="#" class="page-btn">3</a>
    <a href="#" class="page-btn">›</a>
  </div>
</main>
</body>
</html>