<?php
session_start();
include '../admin/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['remove'])) {
  $pid = (int)($_POST['product_id'] ?? 0);
  mysqli_query($conn, "DELETE FROM user_wishlist WHERE user_id=$uid AND produk_id=$pid");
}

$res = mysqli_query($conn, "SELECT p.* FROM user_wishlist w JOIN produk p ON p.id=w.produk_id WHERE w.user_id=$uid ORDER BY w.id DESC");
$items = [];
while($row = mysqli_fetch_assoc($res)) { $items[] = $row; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Wishlist | HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="theme.css?v=1763094445" rel="stylesheet">
  <link href="style/style.css?v=1763094445" rel="stylesheet">
</head>
<body class="wishlist-page">
  <div class="container">
    <div class="card-glass p-3 p-md-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
          <i class="bi bi-heart-fill text-danger me-2" style="font-size:1.6rem;"></i>
          <h1 class="h4 m-0 brand-title">My Wishlist</h1>
        </div>
        <a href="index.php" class="btn btn-outline-theme"><i class="bi bi-arrow-left me-2"></i>Kembali</a>
      </div>

      <?php if (empty($items)): ?>
        <div class="text-center py-5">
          <i class="bi bi-heart" style="font-size: 4rem; color: rgba(255,255,255,0.2);"></i>
          <p class="mt-3 text-white-50">Wishlist Anda masih kosong.</p>
          <p class="text-white-50">Ayo jelajahi produk dan tambahkan yang Anda suka!</p>
          <a href="index.php" class="btn btn-theme mt-2">Jelajahi Produk</a>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($items as $it): ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
              <div class="product-tile">
                <a href="detail.php?id=<?= (int)$it['id'] ?>" class="text-decoration-none">
                  <img src="../uploads/<?= htmlspecialchars($it['gambar']) ?>" class="thumb" alt="<?= htmlspecialchars($it['nama']) ?>">
                </a>
                <div class="product-body">
                  <a href="detail.php?id=<?= (int)$it['id'] ?>" class="text-decoration-none">
                    <div class="product-title"><?= htmlspecialchars($it['nama']) ?></div>
                  </a>
                  <div class="product-price">Rp <?= number_format($it['harga'],0,',','.') ?></div>
                </div>
                <div class="product-footer">
                  <form method="POST" class="form-hapus">
                    <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                    <input type="hidden" name="remove" value="1">
                    <button type="submit" class="btn btn-sm btn-outline-theme w-100">
                      <i class="bi bi-trash3 me-1"></i> Hapus
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.querySelectorAll('.form-hapus').forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        
        Swal.fire({
          title: 'Anda Yakin?',
          text: "Item ini akan dihapus dari wishlist Anda secara permanen!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, Hapus!',
          cancelButtonText: 'Batal',
          customClass: {
            popup: 'swal2-popup',
            confirmButton: 'swal2-confirm',
            cancelButton: 'swal2-cancel'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            this.submit();
          }
        })
      });
    });
  </script>
</body>
</html>
