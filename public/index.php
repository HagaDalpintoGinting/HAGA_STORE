<?php
session_start();
include '../admin/config.php';

// Cart helpers
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
function cart_count() {
  $c = 0; if (!empty($_SESSION['cart'])) { foreach ($_SESSION['cart'] as $q) { $c += (int)$q; } }
  return $c;
}

// Handle add to cart / buy now
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add_to_cart' || $action === 'buy_now') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    if ($pid > 0) {
      $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + max(1, $qty);
    }
    if ($action === 'buy_now') {
      header('Location: cart.php');
      exit;
    } else {
      header('Location: index.php?added=1');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thread Theory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="theme.css" rel="stylesheet">
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
      letter-spacing: 1px;
      text-shadow: 1px 1px 3px #000;
    }

    .subjudul {
      font-size: 1.1rem;
      text-align: center;
      color: #ffffff;
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
      ::-webkit-input-placeholder { color: #fff !important; opacity: 1; }
      :-moz-placeholder { color: #fff !important; opacity: 1; }
      ::-moz-placeholder { color: #fff !important; opacity: 1; }
      :-ms-input-placeholder { color: #fff !important; opacity: 1; }
      input[type="search"]::placeholder { color: #fff !important; opacity: 1; }
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
    input[type="search"]::placeholder { color: #fff !important; opacity: 1; }

    .dropdown-menu {
      background-color: #1e1e1e;
      border: 1px solid #555;
      border-radius: 10px;
    }

    .dropdown-item {
      color: #fff;
    }

    .dropdown-item:hover {
      background-color: #ff3c00;
      color: #fff;
    }

    .dropdown-divider {
      border-top-color: #555;
    }

    .navbar-brand {
      color: #ff3c00 !important;
      font-family: 'Anton', sans-serif;
      font-size: 1.5rem;
      letter-spacing: 1px;
      text-shadow: 1px 1px 2px #000;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-transparent py-3">
    <div class="container">
      <!-- Logo kiri -->
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        THREAD THEORY
      </a>
      <!-- Searchbar tengah -->
      <form class="d-flex mx-auto w-50" method="GET" action="index.php" style="border:1px solid rgba(255,255,255,0.9); border-radius:40px; background:transparent; padding:4px;">
        <div class="input-group">
          <input class="form-control bg-transparent text-white" type="search" name="q" placeholder="Search" aria-label="Search" style="border:0; box-shadow:none; outline:0;">
          <button class="btn text-white" type="submit" style="background:transparent; border: none;">
        <i class="bi bi-search"></i>
          </button>
        </div>
      </form>
      <!-- Icon kanan -->
      <div class="d-flex align-items-center ms-auto">
        <!-- Keranjang dengan badge jumlah -->
        <a href="cart.php" class="btn btn-link text-light position-relative me-2">
          <i class="bi bi-cart" style="font-size:1.5rem;"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.8rem;"><?= cart_count() ?></span>
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Dropdown Profile saat sudah login -->
          <div class="dropdown">
            <a href="#" class="btn btn-link text-light dropdown-toggle d-flex align-items-center" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle" style="font-size:1.5rem"></i>
              <span class="ms-1 d-none d-sm-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Akun') ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
              <li><h6 class="dropdown-header">Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></h6></li>
              <li><a class="dropdown-item" href="profile.php">Profile</a></li>
              <li><a class="dropdown-item" href="#">Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <!-- Tombol Login saat belum login -->
          <a href="login.php" class="btn btn-sm ms-2" style="background-color:#ff3c00; color:#fff; border:0;">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="container">
    <?php if (isset($_GET['added'])): ?>
      <div class="alert alert-success py-2">Produk ditambahkan ke keranjang.</div>
    <?php endif; ?>
    <div class="hero-section" style="background: linear-gradient(135deg, rgba(26,26,26,0.5) 0%, rgba(44,10,5,0.5) 100%); padding: 80px; border-radius: 20px; margin-bottom: 30px; margin-top: 30px; position: relative; box-shadow: 0 10px 30px rgba(255,60,0,0.1); border: 1px solid rgba(255,255,255,0.1);">
      <p class="subjudul" style="position: absolute; bottom: 10px; right: 10px; margin: 0;">Temukan style terbaik kamu. Original, fresh, dan sangar!</p>
    </div>

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
          <div class="card produk-card position-relative h-100 d-flex flex-column">
            <span class="produk-badge">🔥 Best Seller</span>
            <a href="detail.php?id='.$d['id'].'" style="text-decoration:none; color:inherit;">
              <img src="../uploads/'.$d['gambar'].'" class="card-img-top" alt="'.htmlspecialchars($d['nama']).'">
            </a>
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">'.htmlspecialchars($d['nama']).'</h5>
              <p class="card-text mb-3">Rp '.number_format($d['harga'],0,',','.').'</p>
              <div class="mt-auto d-flex gap-2">
                <form method="POST" class="flex-grow-1">
                  <input type="hidden" name="action" value="add_to_cart">
                  <input type="hidden" name="product_id" value="'.$d['id'].'">
                  <button type="submit" class="btn btn-outline-light w-100">Tambahkan</button>
                </form>
                <form method="POST" class="flex-grow-1">
                  <input type="hidden" name="action" value="buy_now">
                  <input type="hidden" name="product_id" value="'.$d['id'].'">
                  <button type="submit" class="btn" style="background-color:#ff3c00; color:#fff; border:0; width:100%">Beli Sekarang</button>
                </form>
              </div>
            </div>
          </div>
        </div>';
      }
      ?>
    </div>
  </div>

  <footer>
    © 2025 THREAD THEORY — All Rights Reserve<br>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
