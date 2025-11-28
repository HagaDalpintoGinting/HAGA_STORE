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
  <link href="style/theme.css?v=1763094445" rel="stylesheet">
  <link href="style/style.css?v=1763094445" rel="stylesheet">
</head>
<body class="orders-page" style="padding-top: 2rem; padding-bottom: 2rem;">
  <div class="container">
    <div class="mb-3">
      <a href="index.php" class="btn btn-outline-theme"><i class="bi bi-arrow-left"></i> Kembali</a>
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
          <div class="border rounded-3 p-3 mb-4 order-history-block" style="border-color: rgba(255,255,255,0.18) !important; background: rgba(0,0,0,0.18);">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
              <div>
                <div class="fw-bold h5 mb-1">Order <span class="text-theme">#<?= $oid ?></span></div>
                <div class="small text-white-50">Tanggal: <span class="fw-semibold text-light"><?= htmlspecialchars($o['created_at']) ?></span></div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-<?= $badge ?> text-uppercase px-3 py-2" style="font-size:1rem; letter-spacing:.3px; font-weight:600;">
                  <?= strtoupper(htmlspecialchars($o['status'])) ?>
                </span>
                <div class="fw-bold h5 m-0">Total: <span class="text-theme"><?= rupiah($o['total'] ?? 0) ?></span></div>
              </div>
            </div>
            <div class="mb-2">
              <span class="fw-semibold text-white-50">Detail Produk:</span>
            </div>
            <div class="table-responsive mb-2">
              <table class="table table-bordered align-middle m-0 order-table-custom">
                <thead>
                  <tr>
                    <th class="fw-semibold">Produk</th>
                    <th class="fw-semibold">Harga</th>
                    <th class="fw-semibold">Qty</th>
                    <th class="fw-semibold text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($it = mysqli_fetch_assoc($items)): ?>
                    <tr>
                      <td><?= htmlspecialchars($it['nama']) ?></td>
                      <td><?= rupiah($it['harga']) ?></td>
                      <td class="text-center"><?= (int)$it['qty'] ?></td>
                      <td class="text-end fw-bold"><?= rupiah($it['subtotal']) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
            
            <?php if (!in_array($status, ['completed','cancelled'])): ?>
              <div class="d-flex gap-2 mt-3">
                <?php if (in_array($status, ['shipped','paid'])): ?>
                <form method="POST" class="form-terima-pesanan">
                  <input type="hidden" name="order_id" value="<?= $oid ?>">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn btn-sm btn-theme"><i class="bi bi-check-circle me-1"></i>Terima Pesanan</button>
                </form>
                <?php endif; ?>
                <?php if (in_array($status, ['pending','paid'])): ?>
                <form method="POST" class="form-batal-pesanan">
                  <input type="hidden" name="order_id" value="<?= $oid ?>">
                  <input type="hidden" name="action" value="cancel">
                  <button class="btn btn-sm btn-outline-theme"><i class="bi bi-x-circle me-1"></i>Batalkan Pesanan</button>
                </form>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    const swalConfig = {
      icon: 'warning',
      showCancelButton: true,
      customClass: {
        popup: 'swal2-popup',
        confirmButton: 'swal2-confirm',
        cancelButton: 'swal2-cancel'
      }
    };

    document.querySelectorAll('.form-terima-pesanan').forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({
          ...swalConfig,
          title: 'Konfirmasi Penerimaan',
          text: "Apakah Anda yakin sudah menerima pesanan ini?",
          icon: 'question',
          confirmButtonText: 'Ya, Sudah Diterima',
          cancelButtonText: 'Batal',
        }).then((result) => {
          if (result.isConfirmed) {
            this.submit();
          }
        });
      });
    });

    document.querySelectorAll('.form-batal-pesanan').forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({
          ...swalConfig,
          title: 'Batalkan Pesanan?',
          text: "Stok produk akan dikembalikan. Anda yakin?",
          confirmButtonText: 'Ya, Batalkan',
          cancelButtonText: 'Tidak',
        }).then((result) => {
          if (result.isConfirmed) {
            this.submit();
          }
        });
      });
    });
  </script>
</body>
</html>
