<?php
session_start();
include '../admin/config.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
  header('Location: cart.php');
  exit;
}

$userId = $_SESSION['user_id'] ?? null;
$defaultAddress = null;
$defaultPayment = null;

if ($userId) {
  // Load default address
  $stmt = mysqli_prepare($conn, "SELECT id,label,recipient_name,phone,address_text,city,postal_code FROM user_addresses WHERE user_id=? AND is_default=1 LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $defaultAddress = mysqli_fetch_assoc($res) ?: null;
  mysqli_stmt_close($stmt);

  // Load primary payment
  $stmt = mysqli_prepare($conn, "SELECT id,method,provider,account_name,account_number FROM user_payments WHERE user_id=? AND is_primary=1 LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $defaultPayment = mysqli_fetch_assoc($res) ?: null;
  mysqli_stmt_close($stmt);
}

// Build cart items from DB
$items = [];
$total = 0;
$ids = array_map('intval', array_keys($_SESSION['cart']));
$idList = implode(',', $ids);
$res = mysqli_query($conn, "SELECT id, nama, harga, gambar FROM produk WHERE id IN ($idList)");
while ($row = mysqli_fetch_assoc($res)) {
  $qty = (int)($_SESSION['cart'][$row['id']] ?? 1);
  $row['qty'] = $qty;
  $row['subtotal'] = $qty * (int)$row['harga'];
  $total += $row['subtotal'];
  $items[] = $row;
}

// Handle place order (mock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
  // Build snapshots
  $addrSnap = $defaultAddress ? json_encode($defaultAddress) : null;
  $paySnap = $defaultPayment ? json_encode($defaultPayment) : null;

  // Insert order
  $uidSql = $userId ? (int)$userId : 'NULL';
  $totalInt = (int)$total;
  $addrEsc = $addrSnap ? ("'".mysqli_real_escape_string($conn, $addrSnap)."'") : 'NULL';
  $payEsc  = $paySnap ? ("'".mysqli_real_escape_string($conn, $paySnap)."'") : 'NULL';
  $ok = mysqli_query($conn, "INSERT INTO orders (user_id, total, address_snapshot, payment_snapshot, status) VALUES ($uidSql, $totalInt, $addrEsc, $payEsc, 'pending')");
  if ($ok) {
    $orderId = mysqli_insert_id($conn);
    foreach ($items as $it) {
      $pid = (int)$it['id'];
      $nm  = mysqli_real_escape_string($conn, $it['nama']);
      $q   = (int)$it['qty'];
      $harga = (int)$it['harga'];
      $sub = (int)$it['subtotal'];
      mysqli_query($conn, "INSERT INTO order_items (order_id, produk_id, nama, qty, harga, subtotal) VALUES ($orderId, $pid, '$nm', $q, $harga, $sub)");
      // reduce stock
      mysqli_query($conn, "UPDATE produk SET stock = GREATEST(stock - $q, 0) WHERE id=$pid");
    }
    $_SESSION['cart'] = [];
    $_SESSION['checkout_success'] = 'Pesanan #'.$orderId.' berhasil dibuat. Terima kasih!';
  } else {
    $_SESSION['checkout_success'] = 'Terjadi kesalahan saat membuat pesanan.';
  }
  header('Location: cart.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout | HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="style/theme.css?v=1763094445" rel="stylesheet">
  <link href="style/style.css?v=1763094445" rel="stylesheet">
</head>
<body class="checkout-page" style="padding-top: 2rem; padding-bottom: 2rem;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-10">
        <div class="p-3 p-md-4 card-glass">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
              <i class="bi bi-credit-card text-light me-2" style="font-size:1.6rem;"></i>
              <h1 class="h5 m-0 brand-title">Checkout</h1>
            </div>
            <a href="cart.php" class="btn btn-sm btn-outline-theme"><i class="bi bi-arrow-left"></i> Kembali ke Keranjang</a>
          </div>

          <div class="row g-3">
            <div class="col-md-7">
              <div class="mb-3">
                <h6 class="text-uppercase text-white-50 mb-2">Ringkasan Keranjang</h6>
                <div class="table-responsive">
                  <table class="table table-borderless table-glass align-middle">
                    <thead>
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
                        <td class="text-white">
                          <div class="d-flex align-items-center gap-2">
                            <img src="../uploads/<?= htmlspecialchars($it['gambar']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                            <div><?= htmlspecialchars($it['nama']) ?></div>
                          </div>
                        </td>
                        <td class="price">Rp <?= number_format($it['harga'],0,',','.') ?></td>
                        <td class="text-white"><?= (int)$it['qty'] ?></td>
                        <td class="subtotal">Rp <?= number_format($it['subtotal'],0,',','.') ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-md-5">
              <div class="mb-3">
                <h6 class="text-uppercase text-white-50 mb-2">Alamat Pengiriman</h6>
                <?php if ($defaultAddress): ?>
                  <div class="border rounded p-3" style="border-color:rgba(255,255,255,0.2) !important;">
                    <div class="text-white fw-semibold"><?= htmlspecialchars($defaultAddress['recipient_name']) ?> (<?= htmlspecialchars($defaultAddress['phone']) ?>)</div>
                    <div class="text-white-50 small mb-1"><?= htmlspecialchars($defaultAddress['label'] ?? 'Alamat Utama') ?></div>
                    <div class="text-white"><?= nl2br(htmlspecialchars($defaultAddress['address_text'])) ?>, <?= htmlspecialchars($defaultAddress['city']) ?> <?= htmlspecialchars($defaultAddress['postal_code']) ?></div>
                    <div class="mt-2"><a href="profile.php#alamat" class="link-light-orange">Ubah alamat</a></div>
                  </div>
                <?php else: ?>
                  <div class="alert alert-warning">Belum ada alamat default. <a href="profile.php#alamat">Tambahkan sekarang</a>.</div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <h6 class="text-uppercase text-white-50 mb-2">Metode Pembayaran</h6>
                <?php if ($defaultPayment): ?>
                  <div class="border rounded p-3" style="border-color:rgba(255,255,255,0.2) !important;">
                    <div class="text-white fw-semibold text-capitalize">Metode: <?= htmlspecialchars($defaultPayment['method']) ?></div>
                    <?php if (!empty($defaultPayment['provider'])): ?><div class="text-white">Provider: <?= htmlspecialchars($defaultPayment['provider']) ?></div><?php endif; ?>
                    <?php if (!empty($defaultPayment['account_name'])): ?><div class="text-white">Nama: <?= htmlspecialchars($defaultPayment['account_name']) ?></div><?php endif; ?>
                    <?php if (!empty($defaultPayment['account_number'])): ?><div class="text-white">No: <?= htmlspecialchars($defaultPayment['account_number']) ?></div><?php endif; ?>
                    <div class="mt-2"><a href="profile.php#pembayaran" class="link-light-orange">Ubah pembayaran</a></div>
                  </div>
                <?php else: ?>
                  <div class="alert alert-warning">Belum ada preferensi pembayaran. <a href="profile.php#pembayaran">Atur sekarang</a>.</div>
                <?php endif; ?>
              </div>

              <div class="border rounded p-3" style="border-color:rgba(255,255,255,0.2) !important;">
                <div class="d-flex justify-content-between text-white mb-2">
                  <span>Total</span>
                  <span class="fw-bold">Rp <?= number_format($total,0,',','.') ?></span>
                </div>
                <form method="POST">
                  <input type="hidden" name="place_order" value="1" />
                  <button type="submit" class="btn btn-theme w-100">Buat Pesanan</button>
                </form>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
