<?php
session_start();
include 'config.php';

if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch orders (users table has `name`, not `username`)
$orders = mysqli_query($conn, "SELECT o.*, u.name AS user_name, u.email AS user_email
                               FROM orders o
                               LEFT JOIN users u ON u.id = o.user_id
                               ORDER BY o.created_at DESC
                               LIMIT 50");

$items = [];
// Handle status update
if ($viewId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  // Admin can only set these statuses
  $valid = ['pending','paid','shipped'];
  $newStatus = strtolower(trim($_POST['status'] ?? ''));
  $tracking = trim($_POST['tracking_number'] ?? '');
  if (!in_array($newStatus, $valid, true)) { $newStatus = 'pending'; }

  // Fetch previous status
  $prevRes = mysqli_query($conn, "SELECT status FROM orders WHERE id=$viewId");
  $prevRow = mysqli_fetch_assoc($prevRes);
  $prevStatus = $prevRow['status'] ?? 'pending';

  // Ensure tracking_number column exists (MySQL 5.7 compatible)
  $colRes = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='tracking_number'");
  if ($colRes && !mysqli_fetch_row($colRes)) {
    @mysqli_query($conn, "ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(100) NULL");
  }

  $trackingEsc = mysqli_real_escape_string($conn, $tracking);
  // Update order
  $upd = mysqli_query($conn, "UPDATE orders SET status='".$newStatus."', tracking_number=".(strlen($trackingEsc)?("'".$trackingEsc."'"):'NULL')." WHERE id=$viewId");

  // No admin-side restock here; user cancellation handles restock.

  header('Location: orders.php?id='.$viewId);
  exit;
}

if ($viewId > 0) {
  $res = mysqli_query($conn, "SELECT oi.*, p.nama AS product_name 
                               FROM order_items oi 
                               LEFT JOIN produk p ON p.id = oi.produk_id 
                               WHERE oi.order_id = $viewId 
                               ORDER BY oi.id ASC");
  while ($r = mysqli_fetch_assoc($res)) { $items[] = $r; }
  $od = mysqli_query($conn, "SELECT * FROM orders WHERE id=$viewId");
  $orderDetail = mysqli_fetch_assoc($od);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orders - Admin | THREAD THEORY</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="../public/theme.css?v=1763094445" rel="stylesheet">
  <link href="theme.css" rel="stylesheet">
  <style>
    body { font-family: 'Quicksand', sans-serif; background: linear-gradient(145deg, #1a1a1a 0%, #2c0a05 100%); color: #fff; }
    .container-fluid { padding-left: 2rem; padding-right: 2rem; }
    .card { background: linear-gradient(135deg, rgba(26,26,26,0.5) 0%, rgba(44,10,5,0.5) 100%); border-radius: 18px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 10px 30px rgba(255,60,0,0.08); }
    .table { background: rgba(26,26,26,0.5); color: #fff; }
    .table th, .table td { border-color: rgba(255,255,255,0.12); }
    .badge.bg-info { background-color: #ff3c00 !important; color: #fff; }
    @media (max-width: 991px) {
      .container-fluid { padding-left: 1rem; padding-right: 1rem; }
      .card { border-radius: 12px; }
      .table-responsive { overflow-x: auto; }
    }
    @media (max-width: 767px) {
      .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; }
      .card { padding: 1rem !important; border-radius: 10px; }
      h2, h4, .card-title { font-size: 1.1rem; }
      .row.mb-3 > .col-md-3 { flex: 0 0 100%; max-width: 100%; margin-bottom: 0.5rem; }
      .table th, .table td { font-size: 0.95rem; padding: 0.5rem; }
      .form-control, .btn { font-size: 1rem; }
    }
    @media (max-width: 480px) {
      .container-fluid { padding-left: 0.2rem; padding-right: 0.2rem; }
      .card { padding: 0.5rem !important; }
      .table th, .table td { font-size: 0.9rem; padding: 0.35rem; }
    }
  </style>
</head>
<body class="p-4">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="bi bi-box-seam"></i> Orders</h2>
      <div>
        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Produk</a>
        <a href="logout.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </div>

    <?php if ($viewId > 0 && $orderDetail): ?>
      <div class="card mb-4">
        <div class="card-body">
          <h4 class="card-title mb-3">Order #<?= (int)$orderDetail['id'] ?> Detail</h4>
          <div class="row mb-3">
            <div class="col-md-3"><strong>User ID:</strong> <?= htmlspecialchars($orderDetail['user_id'] ?? '') ?></div>
            <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-info"><?= htmlspecialchars($orderDetail['status'] ?? '') ?></span></div>
            <div class="col-md-3"><strong>Total:</strong> Rp <?= number_format((int)($orderDetail['total'] ?? 0),0,',','.') ?></div>
            <div class="col-md-3"><strong>Tanggal:</strong> <?= htmlspecialchars($orderDetail['created_at'] ?? '') ?></div>
          </div>
          <?php $cur = $orderDetail['status'] ?? 'pending'; ?>
          <?php if (!in_array(strtolower($cur), ['completed','cancelled'])): ?>
          <form method="POST" class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
              <label class="form-label">Ubah Status</label>
              <select name="status" class="form-select">
                <?php $opts=['pending','paid','shipped']; foreach($opts as $s): ?>
                  <option value="<?= $s ?>" <?= strtolower($cur)=== $s ?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">No. Resi (opsional)</label>
              <input type="text" name="tracking_number" class="form-control" value="<?= htmlspecialchars($orderDetail['tracking_number'] ?? '') ?>" placeholder="Mis. JNE-AB12345">
            </div>
            <div class="col-md-2">
              <button class="btn btn-theme w-100" name="update_status" value="1">Update Status</button>
            </div>
          </form>
          <?php endif; ?>
          <hr style="border-color: rgba(255,255,255,0.2);">
          <h5>Items</h5>
          <table class="table table-sm">
              <thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
              <tbody>
              <?php foreach($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['nama'] ?? ($item['product_name'] ?? 'Produk')) ?></td>
                <td><?= (int)($item['qty'] ?? 0) ?></td>
                <td>Rp <?= number_format((int)($item['harga'] ?? 0), 0, ',', '.') ?></td>
                <td>Rp <?= number_format((int)($item['subtotal'] ?? ((int)($item['harga'] ?? 0) * (int)($item['qty'] ?? 0))), 0, ',', '.') ?></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <div class="card p-4">
        <h4>Daftar Semua Order</h4>
        <div class="table-responsive mt-3">
            <table class="table table-bordered table-striped table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Tanggal</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php while($order = mysqli_fetch_assoc($orders)): ?>
                <tr>
                  <td><?= $order['id'] ?></td>
                  <td><?= htmlspecialchars($order['user_name'] ?? 'Guest') ?> (<?= htmlspecialchars($order['user_email'] ?? 'N/A') ?>)</td>
                  <td>Rp <?= number_format($order['total'], 0, ',', '.') ?></td>
                  <td><span class="badge bg-primary"><?= htmlspecialchars($order['status'] ?? '') ?></span></td>
                  <td><?= htmlspecialchars($order['created_at'] ?? '') ?></td>
                  <td>
                    <a href="?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-info">Detail</a>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
        </div>
    </div>
  </div>
</body>
</html>
