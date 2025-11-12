<?php include '../admin/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
  background: linear-gradient(145deg, #1a1a1a 0%, #2c0a05 100%);
  font-family: 'Quicksand', sans-serif;
  color: #eee;
}
    .judul {
      font-family: 'Anton', sans-serif;
      font-size: 3.5rem;
      text-align: center;
      margin-top: 40px;
      color: #ff3c00;
      letter-spacing: 2px;
      text-shadow: 1px 1px 3px #000;
    }

    .subjudul {
      font-size: 1.1rem;
      text-align: center;
      color: #aaa;
      margin-bottom: 40px;
    }

    .kategori-bar {
      text-align: center;
      margin-bottom: 40px;
    }

    .kategori-bar .btn {
      margin: 5px;
      border-radius: 30px;
      padding: 10px 20px;
      font-weight: 600;
      color: #fff;
      border: 1px solid #555;
      background-color: rgba(255, 255, 255, 0.05);
      transition: all 0.2s ease-in-out;
    }

    .kategori-bar .btn:hover {
      background-color: #ff3c00;
      border-color: #ff3c00;
    }

    .kategori-bar .active {
      background-color: #ff3c00;
      border-color: #ff3c00;
    }

    .produk-card {
      background-color: #1e1e1e;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0,0,0,0.5);
      transition: all 0.3s ease;
    }

    .produk-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 30px rgba(255,60,0,0.2);
    }

    .produk-card img {
      height: 220px;
      object-fit: cover;
    }

    .produk-card .card-title {
      font-family: 'Anton', sans-serif;
      font-size: 1.3rem;
      color: #fff;
    }

    .produk-card .card-text {
      color: #ccc;
    }

    .produk-badge {
      font-size: 0.8rem;
      background-color: #ff3c00;
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      position: absolute;
      top: 12px;
      left: 12px;
      font-weight: bold;
    }

    footer {
      margin-top: 60px;
      padding: 30px;
      background-color: #1a1a1a;
      color: #aaa;
      text-align: center;
      border-top: 2px solid #333;
      font-size: 0.9rem;
    }

    footer a {
      color: #ff3c00;
      text-decoration: none;
    }
  </style>
</head>
<body>

  <div class="container">
    <h1 class="judul">HAGA STORE</h1>
    <p class="subjudul">Temukan style terbaik kamu. Original, fresh, dan sangar!</p>

    <!-- FILTER KATEGORI -->
    <div class="kategori-bar">
      <a href="index.php" class="btn <?= !isset($_GET['kategori']) ? 'active' : '' ?>">All</a>
      <a href="index.php?kategori=Clothes" class="btn <?= ($_GET['kategori'] ?? '') == 'Clothes' ? 'active' : '' ?>">Clothes</a>
      <a href="index.php?kategori=Pants" class="btn <?= ($_GET['kategori'] ?? '') == 'Pants' ? 'active' : '' ?>">Pants</a>
      <a href="index.php?kategori=Shoes" class="btn <?= ($_GET['kategori'] ?? '') == 'Shoes' ? 'active' : '' ?>">Shoes</a>
    </div>

    <!-- PRODUK GRID -->
    <div class="row">
      <?php
      $kategori = $_GET['kategori'] ?? '';
      $data = $kategori 
              ? mysqli_query($conn, "SELECT * FROM produk WHERE kategori='$kategori'")
              : mysqli_query($conn, "SELECT * FROM produk");

      while ($d = mysqli_fetch_assoc($data)) {
        echo '
        <div class="col-md-4 mb-4">
          <a href="detail.php?id='.$d['id'].'" style="text-decoration:none; color:inherit;">
            <div class="card produk-card position-relative">
              <span class="produk-badge">🔥 Best Seller</span>
              <img src="../uploads/'.$d['gambar'].'" class="card-img-top" alt="'.htmlspecialchars($d['nama']).'">
              <div class="card-body">
                <h5 class="card-title">'.htmlspecialchars($d['nama']).'</h5>
                <p class="card-text">Rp '.number_format($d['harga'],0,',','.').'</p>
              </div>
            </div>
          </a>
        </div>';
      }
      ?>
    </div>
  </div>

  <footer>
    © 2025 HAGA STORE — All Rights Reserved<br>
    Instagram: <a href="#" target="_blank">@hagzstore</a>
  </footer>

</body>
</html>
