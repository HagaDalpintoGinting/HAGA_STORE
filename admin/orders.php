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
  $res = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $viewId ORDER BY id ASC");
  while ($r = mysqli_fetch_assoc($res)) { $items[] = $r; }
  $od = mysqli_query($conn, "SELECT * FROM orders WHERE id=$viewId");
  $orderDetail = mysqli_fetch_assoc($od);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orders - Admin | HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Orders</h2>
      <div>
        <a href="index.php" class="btn btn-outline-secondary">Kembali ke Produk</a>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
      </div>
    </div>

    <?php if ($viewId > 0 && $orderDetail): ?>
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3">Order #<?= (int)$orderDetail['id'] ?> Detail</h5>
          <div class="row mb-3">
            <div class="col-md-3"><strong>User:</strong> <?= htmlspecialchars($orderDetail['user_id']) ?></div>
            <div class="col-md-3"><strong>Status:</strong> <?= htmlspecialchars($orderDetail['status']) ?></div>
            <div class="col-md-3"><strong>Total:</strong> Rp <?= number_format((int)($orderDetail['total'] ?? 0),0,',','.') ?></div>
            <div class="col-md-3"><strong>Tanggal:</strong> <?= htmlspecialchars($orderDetail['created_at']) ?></div>
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
              <button class="btn btn-primary w-100" name="update_status" value="1">Update Status</button>
            </div>
          </form>
          <?php endif; ?>
          <div class="row">
              <div class="col-md-6">
                <strong>Alamat</strong>
                <?php $addr = json_decode($orderDetail['address_snapshot'] ?? '', true); ?>
                <?php if (is_array($addr) && !empty($addr)): ?>
                  <div class="mt-1">
                    <div><span class="text-muted">Nama:</span> <?= htmlspecialchars($addr['recipient_name'] ?? '-') ?></div>
                    <div><span class="text-muted">Telepon:</span> <?= htmlspecialchars($addr['phone'] ?? '-') ?></div>
                    <div><span class="text-muted">Label:</span> <?= htmlspecialchars($addr['label'] ?? '-') ?></div>
                    <div><span class="text-muted">Alamat:</span> <?= htmlspecialchars($addr['address_text'] ?? '-') ?></div>
                    <div><span class="text-muted">Kota/Kode Pos:</span> <?= htmlspecialchars(($addr['city'] ?? '-'). ' ' .($addr['postal_code'] ?? '')) ?></div>
                  </div>
                <?php else: ?>
                  <div class="text-muted">Tidak ada data alamat.</div>
                <?php endif; ?>
              </div>
              <div class="col-md-6">
                <strong>Pembayaran</strong>
                <?php $pay = json_decode($orderDetail['payment_snapshot'] ?? '', true); ?>
                <?php if (is_array($pay) && !empty($pay)): ?>
                  <div class="mt-1">
                    <div><span class="text-muted">Metode:</span> <?= htmlspecialchars($pay['method'] ?? '-') ?></div>
                    <div><span class="text-muted">Provider:</span> <?= htmlspecialchars($pay['provider'] ?? '-') ?></div>
                    <div><span class="text-muted">Nama Akun:</span> <?= htmlspecialchars($pay['account_name'] ?? '-') ?></div>
                    <div><span class="text-muted">No. Akun:</span> <?= htmlspecialchars($pay['account_number'] ?? '-') ?></div>
                  </div>
                <?php else: ?>
                  <div class="text-muted">Tidak ada data pembayaran.</div>
                <?php endif; ?>
              </div>
          </div>
          <hr>
          <h6 class="mb-2">Items</h6>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="table-secondary">
                <tr>
                  <th>Produk</th>
                  <th>Harga</th>
                  <th>Qty</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td><?= htmlspecialchars($it['nama']) ?></td>
                    <td>Rp <?= number_format((int)$it['harga'],0,',','.') ?></td>
                    <td><?= (int)$it['qty'] ?></td>
                    <td>Rp <?= number_format($it['subtotal'],0,',','.') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Daftar Orders Terbaru</h5>
        <div class="table-responsive">
          <table class="table table-bordered mt-3">
            <thead class="table-secondary">
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Status</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($o = mysqli_fetch_assoc($orders)): ?>
                <tr>
                  <td><?= (int)$o['id'] ?></td>
                  <td><?= htmlspecialchars($o['user_name'] ?? ('#'.$o['user_id'])) ?></td>
                  <td><?= htmlspecialchars($o['status']) ?></td>
                  <td>Rp <?= number_format((int)($o['total'] ?? 0),0,',','.') ?></td>
                  <td><?= htmlspecialchars($o['created_at']) ?></td>
                  <td><a class="btn btn-sm btn-primary" href="orders.php?id=<?= (int)$o['id'] ?>">Lihat</a></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
