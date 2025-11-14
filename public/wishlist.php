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
  <link href="theme.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(145deg, #1a1a1a 0%, #2c0a05 100%); font-family: 'Quicksand', sans-serif; color: #fff; }
    .brand-title { font-family: 'Anton', sans-serif; letter-spacing: 2px; color: #ff3c00; text-shadow: 1px 1px 3px #000; }
    .card-glass { background: linear-gradient(135deg, rgba(26,26,26,0.5) 0%, rgba(44,10,5,0.5) 100%); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; box-shadow: 0 10px 30px rgba(255,60,0,0.1); backdrop-filter: blur(2px); }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="card-glass p-3 p-md-4">
      <div class="d-flex align-items-center mb-3">
        <i class="bi bi-heart text-light me-2" style="font-size:1.6rem;"></i>
        <h1 class="h5 m-0 brand-title">Wishlist</h1>
      </div>

      <?php if (empty($items)): ?>
        <div class="text-white-50">Belum ada produk di wishlist.</div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($items as $it): ?>
            <div class="col-md-4">
              <div class="card bg-transparent border-0">
                <a href="detail.php?id=<?= (int)$it['id'] ?>" class="text-decoration-none text-white">
                  <img src="../uploads/<?= htmlspecialchars($it['gambar']) ?>" class="card-img-top" alt="">
                  <div class="card-body px-0">
                    <div class="fw-semibold mb-1"><?= htmlspecialchars($it['nama']) ?></div>
                    <div class="text-white">Rp <?= number_format($it['harga'],0,',','.') ?></div>
                  </div>
                </a>
                <form method="POST" class="mt-1">
                  <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
