<?php
session_start();
include '../admin/config.php';

// Initialize cart
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
$msg = '';
$err = '';

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'remove') {
    $pid = (int)($_POST['product_id'] ?? 0);
    unset($_SESSION['cart'][$pid]);
    $msg = 'Item dihapus dari keranjang.';
  } elseif ($action === 'update_qty') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (isset($_SESSION['cart'][$pid])) { $_SESSION['cart'][$pid] = $qty; $msg = 'Jumlah diperbarui.'; }
  } elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
    $msg = 'Keranjang dikosongkan.';
  }
}

// Fetch product details for items in cart
$items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
  $ids = array_map('intval', array_keys($_SESSION['cart']));
  $idList = implode(',', $ids);
  $res = mysqli_query($conn, "SELECT id, nama, harga, gambar FROM produk WHERE id IN ($idList)");
  while ($row = mysqli_fetch_assoc($res)) {
    $qty = (int)($_SESSION['cart'][$row['id']] ?? 1);
    $subtotal = $qty * (int)$row['harga'];
    $row['qty'] = $qty;
    $row['subtotal'] = $subtotal;
    $items[] = $row;
    $total += $subtotal;
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Keranjang | HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="theme.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(145deg, #1a1a1a 0%, #2c0a05 100%); font-family: 'Quicksand', sans-serif; color: #fff; }
    .brand-title { font-family: 'Anton', sans-serif; letter-spacing: 2px; color: #ff3c00; text-shadow: 1px 1px 3px #000; }
    .card-glass { background: linear-gradient(135deg, rgba(26,26,26,0.5) 0%, rgba(44,10,5,0.5) 100%); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; box-shadow: 0 10px 30px rgba(255,60,0,0.1); backdrop-filter: blur(2px); }
    .form-control.bg-glass { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.2); }
    .form-control.bg-glass:focus { background: transparent; color: #fff; border-color: #ff3c00; box-shadow: 0 0 0 .25rem rgba(255,60,0,.15); }
    .form-control.bg-glass::placeholder { color: rgba(255,255,255,0.85); opacity: 1; }
    .btn-theme { background-color: #ff3c00; color: #fff; border: 0; }
    .btn-theme:hover { background-color: #ff521f; color: #fff; }
    a.link-light-orange { color: #ff7a52; text-decoration: none; }
    a.link-light-orange:hover { color: #ffa284; }

    /* Table glass theme */
    .table-glass {
      --bs-table-bg: transparent;
      --bs-table-color: #ffffff;
      --bs-table-striped-color: #ffffff;
      --bs-table-hover-color: #ffffff;
      color:#fff;
    }
    .table-glass thead th { background-color: rgba(255,255,255,0.06); color:#fff; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .table-glass tbody tr { border-bottom: 1px solid rgba(255,255,255,0.08); }
    .table-glass tbody tr:hover { background-color: rgba(255,60,0,0.06); }
    .table-glass td, .table-glass th { padding: .9rem .8rem; vertical-align: middle; }
    .price { color:#ffffff !important; font-weight:600; }
    .subtotal { color:#ffffff !important; font-weight:600; }
    .product-name { color:#ffffff; }
    footer { background: transparent !important; box-shadow: none !important; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-10">
        <div class="p-3 p-md-4 card-glass">
          <div class="d-flex align-items-center mb-3">
            <i class="bi bi-cart text-light me-2" style="font-size:1.6rem;"></i>
            <h1 class="h5 m-0 brand-title">Keranjang Belanja</h1>
          </div>

          <?php if ($msg): ?><div class="alert alert-success py-2"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
          <?php if ($err): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div><?php endif; ?>

          <?php if (empty($items)): ?>
            <div class="text-light">Keranjang Anda kosong.</div>
            <div class="mt-3"><a class="link-light-orange" href="index.php"><i class="bi bi-arrow-left"></i> Belanja sekarang</a></div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-borderless table-glass align-middle">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th style="width:140px;">Qty</th>
                    <th>Subtotal</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $it): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img src="../uploads/<?= htmlspecialchars($it['gambar']) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
                          <div class="product-name"><?= htmlspecialchars($it['nama']) ?></div>
                        </div>
                      </td>
                      <td class="price">Rp <?= number_format($it['harga'],0,',','.') ?></td>
                      <td>
                        <form method="POST" class="d-flex gap-2">
                          <input type="hidden" name="action" value="update_qty">
                          <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                          <input type="number" class="form-control bg-glass" name="qty" value="<?= (int)$it['qty'] ?>" min="1" style="width:80px;">
                          <button class="btn btn-sm btn-outline-light" type="submit">Ubah</button>
                        </form>
                      </td>
                      <td class="subtotal">Rp <?= number_format($it['subtotal'],0,',','.') ?></td>
                      <td>
                        <form method="POST" onsubmit="return confirm('Hapus item ini?');">
                          <input type="hidden" name="action" value="remove">
                          <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                          <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
              <form method="POST">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-outline-light">Kosongkan Keranjang</button>
              </form>
              <div class="h5 m-0">Total: Rp <?= number_format($total,0,',','.') ?></div>
            </div>
            <div class="text-end mt-3">
              <a href="index.php" class="btn btn-outline-light me-2">Lanjut Belanja</a>
              <a href="checkout.php" class="btn btn-theme">Lanjut Pembayaran</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
