<?php
session_start();
include '../admin/config.php';
$id = (int)($_GET['id'] ?? 0);
if ($id<=0) { header('Location: index.php'); exit; }

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add_to_cart' || $action === 'buy_now') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
    if ($pid > 0) { $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty; }
    if ($action === 'buy_now') { header('Location: cart.php'); exit; }
    header('Location: detail.php?id='.$id.'&added=1');
    exit;
  } elseif ($action === 'toggle_wishlist') {
    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
    $uid = (int)$_SESSION['user_id'];
    $exists = mysqli_query($conn, "SELECT id FROM user_wishlist WHERE user_id=$uid AND produk_id=$id");
    if (mysqli_fetch_assoc($exists)) {
      mysqli_query($conn, "DELETE FROM user_wishlist WHERE user_id=$uid AND produk_id=$id");
      header('Location: detail.php?id='.$id.'&wl=0');
    } else {
      mysqli_query($conn, "INSERT IGNORE INTO user_wishlist (user_id, produk_id) VALUES ($uid, $id)");
      header('Location: detail.php?id='.$id.'&wl=1');
    }
    exit;
  }
}

$data = mysqli_query($conn, "SELECT * FROM produk WHERE id=$id");
$p = mysqli_fetch_assoc($data);
if (!$p) { header('Location: index.php'); exit; }

// Gallery images
$gal = mysqli_query($conn, "SELECT filename FROM produk_images WHERE produk_id=$id ORDER BY sort_order, id");
$images = [ $p['gambar'] ];
while ($gi = mysqli_fetch_assoc($gal)) { $images[] = $gi['filename']; }

// Sizes
$sizesCsv = trim($p['sizes'] ?? 'M,L,XL,XXL');
$sizes = array_filter(array_map('trim', explode(',', $sizesCsv)));
$stock = (int)($p['stock'] ?? 0);
$wishAdded = false; $isWish = false;
if (isset($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  $rs = mysqli_query($conn, "SELECT 1 FROM user_wishlist WHERE user_id=$uid AND produk_id=$id");
  $isWish = (bool)mysqli_fetch_row($rs);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($p['nama']) ?> - HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="style/theme.css?v=1763094445" rel="stylesheet">
  <link href="style/style.css?v=1763094445" rel="stylesheet">
</head>
<body style="padding-top: 2rem; padding-bottom: 2rem;">
  <div class="container">
    <div class="mb-3">
      <a href="index.php" class="btn btn-outline-theme btn-pill btn-icon-left"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
    <?php if (isset($_GET['added'])): ?><div class="alert alert-success py-2">Ditambahkan ke keranjang.</div><?php endif; ?>
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card-glass p-3">
          <div class="text-center">
            <img id="mainImage" src="../uploads/<?= htmlspecialchars($images[0]) ?>" alt="" style="width:100%; max-height:520px; object-fit:contain; border-radius:12px;">
          </div>
          <div class="thumbs d-flex flex-wrap gap-2 mt-3">
            <?php foreach ($images as $idx=>$img): ?>
              <img src="../uploads/<?= htmlspecialchars($img) ?>" data-src="../uploads/<?= htmlspecialchars($img) ?>" class="<?= $idx===0?'active':'' ?>">
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card-glass p-3 p-md-4">
          <h1 class="h4 brand-title mb-2"><?= htmlspecialchars($p['nama']) ?></h1>
          <?php
            $diskon = isset($p['diskon']) ? (int)$p['diskon'] : 0;
            $harga_awal = (int)$p['harga'];
            $harga_diskon = $harga_awal;
            if ($diskon > 0) {
              $harga_diskon = $harga_awal - intval($harga_awal * $diskon / 100);
            }
          ?>
          <div class="price h3">
            <?php if ($diskon > 0): ?>
              <span style="text-decoration:line-through;color:#bbb;font-size:1rem;">Rp <?= number_format($harga_awal,0,',','.') ?></span>
              <span class="ms-2">Rp <?= number_format($harga_diskon,0,',','.') ?></span>
              <span class="badge bg-danger ms-2">-<?= $diskon ?>%</span>
            <?php else: ?>
              Rp <?= number_format($harga_awal,0,',','.') ?>
            <?php endif; ?>
          </div>
          <div class="small text-white-50 mb-2">Terjual 500+ • <i class="bi bi-star-fill text-warning"></i> 5 (328 rating)</div>

          <div class="mb-3">
            <div class="text-white-50 small mb-1">Pilih ukuran pakaian:</div>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($sizes as $i=>$sz): ?>
                <button type="button" class="size-btn <?= $i===0?'active':'' ?>" data-size="<?= htmlspecialchars($sz) ?>"><?= htmlspecialchars($sz) ?></button>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <div class="text-white-50 small mb-1">Atur jumlah dan catatan</div>
            <div class="d-flex align-items-center gap-2">
              <button class="qty-btn" id="dec">-</button>
              <input id="qty" type="number" value="1" min="1" class="form-control bg-transparent text-white" style="width:80px; border:1px solid rgba(255,255,255,0.3)">
              <button class="qty-btn" id="inc">+</button>
              <div class="ms-auto small">Stok: <?= $stock ?></div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-white-50">Subtotal</div>
            <div class="subtotal h5 m-0" id="subtotal">Rp <?= number_format($harga_diskon,0,',','.') ?></div>
          </div>

          <div class="d-flex gap-2 mb-3">
            <a href="#" id="chatBtn" class="btn btn-outline-theme btn-pill btn-icon-left"><i class="bi bi-chat-dots"></i> Chat</a>
            <form method="POST">
              <input type="hidden" name="action" value="toggle_wishlist">
              <button type="submit" class="btn btn-outline-theme btn-pill btn-icon-left">
                <i class="bi bi-heart<?= $isWish ? '-fill text-danger' : '' ?>"></i> Wishlist
              </button>
            </form>
          </div>

          <div class="d-flex gap-2">
            <form method="POST">
              <input type="hidden" name="action" value="add_to_cart">
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="qty" id="formQty1" value="1">
              <button class="btn btn-outline-theme btn-pill btn-icon-left">+ Keranjang</button>
            </form>
            <form method="POST">
              <input type="hidden" name="action" value="buy_now">
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="qty" id="formQty2" value="1">
              <button class="btn btn-theme btn-pill btn-icon-left">Beli Langsung</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="card-glass mt-4 p-3 p-md-4">
      <ul class="nav nav-tabs" id="pTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#detail" type="button" role="tab">Detail Produk</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#spec" type="button" role="tab">Spesifikasi</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">Info Penting</button>
        </li>
      </ul>
      <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="detail" role="tabpanel">
          <?= nl2br(htmlspecialchars($p['deskripsi'])) ?>
        </div>
        <div class="tab-pane fade" id="spec" role="tabpanel">
          Bahan: -<br>Ukuran tersedia: <?= htmlspecialchars($sizesCsv) ?>
        </div>
        <div class="tab-pane fade" id="info" role="tabpanel">
          Garansi barang sesuai deskripsi. Hubungi chat untuk bantuan.
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const price = <?= (int)$harga_diskon ?>;
    const qtyInput = document.getElementById('qty');
    const subtotal = document.getElementById('subtotal');
    const formQty1 = document.getElementById('formQty1');
    const formQty2 = document.getElementById('formQty2');
    function updateSubtotal(){
      const q = Math.max(1, parseInt(qtyInput.value||'1',10));
      const num = q * price;
      subtotal.textContent = new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', maximumFractionDigits:0 }).format(num).replace('IDR','Rp');
      formQty1.value = q; formQty2.value = q;
    }
    document.getElementById('dec').addEventListener('click', (e)=>{ e.preventDefault(); qtyInput.value = Math.max(1, (parseInt(qtyInput.value||'1',10)-1)); updateSubtotal(); });
    document.getElementById('inc').addEventListener('click', (e)=>{ e.preventDefault(); qtyInput.value = Math.max(1, (parseInt(qtyInput.value||'1',10)+1)); updateSubtotal(); });
    qtyInput.addEventListener('input', updateSubtotal);
    updateSubtotal();

    // Thumbnails
    document.querySelectorAll('.thumbs img').forEach(img=>{
      img.addEventListener('click', ()=>{
        document.getElementById('mainImage').src = img.dataset.src;
        document.querySelectorAll('.thumbs img').forEach(i=>i.classList.remove('active'));
        img.classList.add('active');
      });
    });

    // WhatsApp Chat
    function currentSize(){
      const active = document.querySelector('.size-btn.active');
      return active ? active.getAttribute('data-size') : '';
    }
    document.querySelectorAll('.size-btn').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        document.querySelectorAll('.size-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
    document.getElementById('chatBtn').addEventListener('click', function(e){
      e.preventDefault();
      const qty = Math.max(1, parseInt(qtyInput.value||'1',10));
      const size = currentSize();
      const name = <?= json_encode($p['nama']) ?>;
      const url = window.location.href;
      const text = `Halo, saya tertarik dengan produk: ${name}%0AUkuran: ${size}%0AQty: ${qty}%0ALink: ${encodeURIComponent(url)}`;
      // Open WhatsApp with chooser (no specific number)
      const wa = `https://wa.me/?text=${text}`;
      window.open(wa, '_blank');
    });
  </script>
</body>
</html>
