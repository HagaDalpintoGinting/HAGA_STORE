<?php
include '../admin/config.php';
$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM produk WHERE id=$id");
$p = mysqli_fetch_assoc($data);
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($p['nama']) ?> - HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Quicksand', sans-serif;
      background: linear-gradient(135deg, #0e0e0e, #2e0f0f);
      color: #eee;
    }

    .container {
      margin-top: 60px;
    }

    h2 {
      font-family: 'Anton', sans-serif;
      font-size: 2.5rem;
      color: #ff4c29;
      margin-bottom: 10px;
    }

    h4 {
      font-size: 1.4rem;
      font-weight: 600;
      color: #ffd7ba;
      margin-bottom: 25px;
    }

    .product-img {
      width: 100%;
      border-radius: 20px;
      box-shadow: 0 8px 20px rgba(255, 76, 41, 0.2);
      border: 2px solid #292929;
    }

    .deskripsi {
      background-color: #1a1a1a;
      border-left: 5px solid #ff4c29;
      padding: 20px;
      border-radius: 12px;
      line-height: 1.7;
      color: #ccc;
      white-space: pre-wrap;
    }

    .btn-back {
      background-color: #ff4c29;
      color: white;
      border-radius: 25px;
      padding: 10px 22px;
      font-weight: bold;
      border: none;
    }

    .btn-back:hover {
      background-color: #e03e1e;
    }

    @media (max-width: 767px) {
      h2 { font-size: 2rem; }
      h4 { font-size: 1.2rem; }
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="index.php" class="btn btn-back mb-4">&larr; Kembali ke Beranda</a>

    <div class="row align-items-center">
      <div class="col-md-6 mb-4 mb-md-0">
        <img src="../uploads/<?= $p['gambar'] ?>" class="product-img" alt="<?= htmlspecialchars($p['nama']) ?>">
      </div>
      <div class="col-md-6">
        <h2><?= htmlspecialchars($p['nama']) ?></h2>
        <h4>Rp <?= number_format($p['harga'],0,',','.') ?></h4>
        <div class="deskripsi"><?= nl2br(htmlspecialchars($p['deskripsi'])) ?></div>
      </div>
    </div>
  </div>
</body>
</html>
