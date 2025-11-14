<?php
session_start();
include '../admin/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

// Handle user actions: accept (completed) or cancel (cancelled)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $oid = (int)($_POST['order_id'] ?? 0);
  if ($oid > 0 && ($action === 'accept' || $action === 'cancel')) {
    // Ensure order belongs to this user
    $chk = mysqli_query($conn, "SELECT status FROM orders WHERE id=$oid AND user_id=$uid");
    if ($chk && ($row = mysqli_fetch_assoc($chk))) {
      $prev = strtolower($row['status'] ?? 'pending');
      if ($action === 'accept' && $prev !== 'completed' && $prev !== 'cancelled') {
        mysqli_query($conn, "UPDATE orders SET status='completed' WHERE id=$oid");
      } elseif ($action === 'cancel' && $prev !== 'cancelled' && $prev !== 'completed') {
        // Update status
        if (mysqli_query($conn, "UPDATE orders SET status='cancelled' WHERE id=$oid")) {
          // Restock items
          $it = mysqli_query($conn, "SELECT produk_id, qty FROM order_items WHERE order_id=$oid");
          while ($r = mysqli_fetch_assoc($it)) {
            $pid = (int)$r['produk_id'];
            $q = (int)$r['qty'];
            mysqli_query($conn, "UPDATE produk SET stock = stock + $q WHERE id=$pid");
          }
        }
      }
    }
    header('Location: orders.php');
    exit;
  }
}

// Fetch orders
$orders = mysqli_query($conn, "SELECT * FROM orders WHERE user_id=$uid ORDER BY created_at DESC");
$all = [];
while ($o = mysqli_fetch_assoc($orders)) { $all[] = $o; }

function rupiah($n){ return 'Rp '.number_format((int)$n,0,',','.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Orders Saya | HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="theme.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(145deg, #1a1a1a 0%, #2c0a05 100%); font-family: 'Quicksand', sans-serif; color: #fff; }
    .brand-title { font-family: 'Anton', sans-serif; letter-spacing: 2px; color: #ff3c00; text-shadow: 1px 1px 3px #000; }
    .card-glass { background: linear-gradient(135deg, rgba(26,26,26,0.5) 0%, rgba(44,10,5,0.5) 100%); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; box-shadow: 0 10px 30px rgba(255,60,0,0.1); backdrop-filter: blur(2px); }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="mb-3">
      <a href="index.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-glass p-3 p-md-4">
      <div class="d-flex align-items-center mb-3">
        <i class="bi bi-receipt text-light me-2" style="font-size:1.6rem;"></i>
        <h1 class="h5 m-0 brand-title">Orders Saya</h1>
      </div>

      <?php if (empty($all)): ?>
        <div class="text-white-50">Belum ada pesanan.</div>
      <?php else: ?>
        <?php foreach ($all as $o): ?>
          <?php
            $oid = (int)$o['id'];
            $items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id=$oid");
            $status = strtolower($o['status'] ?? 'pending');
            $map = [
              'pending' => 'warning',
              'paid' => 'primary',
              'shipped' => 'info',
              'completed' => 'success',
              'cancelled' => 'danger'
            ];
            $badge = $map[$status] ?? 'secondary';
          ?>
          <div class="border rounded-3 p-3 mb-3" style="border-color: rgba(255,255,255,0.12) !important; background: rgba(0,0,0,0.15);">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <div class="fw-semibold">Order #<?= $oid ?></div>
                <div class="text-white-50 small">Tanggal: <?= htmlspecialchars($o['created_at']) ?></div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-<?= $badge ?> text-uppercase" style="letter-spacing:.3px;">&nbsp;<?= htmlspecialchars($o['status']) ?>&nbsp;</span>
                <div class="fw-bold">Total: <?= rupiah($o['total'] ?? 0) ?></div>
              </div>
            </div>
            <?php if (!in_array($status, ['completed','cancelled'])): ?>
              <div class="d-flex gap-2 mt-2">
                <?php if (in_array($status, ['shipped','paid'])): ?>
                <form method="POST" onsubmit="return confirm('Terima pesanan ini?');">
                  <input type="hidden" name="order_id" value="<?= $oid ?>">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn btn-sm btn-success">Terima Pesanan</button>
                </form>
                <?php endif; ?>
                <?php if (in_array($status, ['pending','paid'])): ?>
                <form method="POST" onsubmit="return confirm('Batalkan pesanan ini? Stok akan dikembalikan.');">
                  <input type="hidden" name="order_id" value="<?= $oid ?>">
                  <input type="hidden" name="action" value="cancel">
                  <button class="btn btn-sm btn-outline-danger">Batalkan Pesanan</button>
                </form>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <hr class="border-0" style="border-top:1px solid rgba(255,255,255,0.1)!important; opacity:1;">
            <div class="table-responsive mt-2">
              <table class="table table-sm table-borderless align-middle m-0" style="color:#e9e9e9;">
                <thead>
                  <tr>
                    <th class="text-white-50 fw-semibold">Produk</th>
                    <th class="text-white-50 fw-semibold">Harga</th>
                    <th class="text-white-50 fw-semibold">Qty</th>
                    <th class="text-white-50 fw-semibold text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($it = mysqli_fetch_assoc($items)): ?>
                    <tr>
                      <td class="text-white"><?= htmlspecialchars($it['nama']) ?></td>
                      <td class="text-white"><?= rupiah($it['harga']) ?></td>
                      <td class="text-white"><?= (int)$it['qty'] ?></td>
                      <td class="text-white text-end"><?= rupiah($it['subtotal']) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
