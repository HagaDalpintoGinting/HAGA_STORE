<?php
session_start();
include '../admin/config.php';

// Cart helpers
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
function cart_count() {
  $c = 0; if (!empty($_SESSION['cart'])) { foreach ($_SESSION['cart'] as $q) { $c += (int)$q; } }
  return $c;
}

// Wishlist count
function wishlist_count($conn) {
  if (empty($_SESSION['user_id'])) return 0;
  $uid = (int)$_SESSION['user_id'];
  $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM user_wishlist WHERE user_id=$uid");
  if ($res) {
    $row = mysqli_fetch_assoc($res);
    return (int)($row['c'] ?? 0);
  }
  return 0;
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
  <link href="theme.css?v=1763094445" rel="stylesheet">
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
      transition: all 0.2s ease-in-out;
    }
    /* Fallback styling for non-themed buttons, if any remain */
    .kategori-bar .btn:not(.btn-theme):not(.btn-outline-theme):not(.btn-white-theme) {
      color: #fff;
      border: 1px solid #555;
      background-color: rgba(255,255,255,0.05);
    }
    .kategori-bar .btn:not(.btn-theme):not(.btn-outline-theme):not(.btn-white-theme):hover {
      background-color: #ff3c00;
      border-color: #ff3c00;
    }

    /* Product tile - marketplace style */
    .product-tile { background: #1e1e1e; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5); transition: transform .25s ease, box-shadow .25s ease; position: relative; }
    .product-tile:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(255,60,0,0.20); }
    .product-tile .thumb { height: 220px; object-fit: cover; width: 100%; display: block; }
    .discount-badge { position:absolute; top:10px; left:10px; background:#ff3c00; color:#fff; font-weight:700; font-size:.8rem; padding:4px 8px; border-radius:6px; }
    .cashback-badge { position:absolute; top:10px; left:60px; background:rgba(255,255,255,0.12); color:#ffd5c8; font-size:.75rem; padding:3px 6px; border-radius:6px; border:1px solid rgba(255,255,255,0.15); }
    .product-body { padding:12px 14px; display:flex; flex-direction:column; gap:6px; }
    .product-title {
      color:#fff; font-weight:600; line-height:1.25;
      /* Multi-line clamp */
      display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient: vertical;
      /* Fallbacks */
      overflow:hidden; text-overflow: ellipsis; max-height:2.5em; /* 2 lines x 1.25 */
      line-clamp: 2; /* non-prefixed (progressive) */
      min-height:2.5em;
    }
    .product-price { color:#fff; font-weight:700; }
    .product-meta { color:#ddd; font-size:.85rem; display:flex; align-items:center; gap:10px; }
    .product-meta .dot { width:4px; height:4px; border-radius:50%; background:#777; display:inline-block; }
    .product-loc { color:#bbb; font-size:.85rem; }

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
  <nav class="navbar navbar-expand-lg navbar-dark bg-transparent py-3 navbar-themed">
    <div class="container">
      <!-- Logo kiri -->
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        THREAD THEORY
      </a>
      <!-- Searchbar tengah -->
      <form class="d-flex mx-auto w-50 search-bar-themed" method="GET" action="index.php">
        <div class="input-group">
          <input class="form-control" type="search" name="q" placeholder="Search products..." aria-label="Search">
          <button class="btn" type="submit">
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

        <!-- Wishlist link -->
        <a href="wishlist.php" class="btn btn-link text-light position-relative me-2">
          <i class="bi bi-heart" style="font-size:1.5rem;"></i>
          <?php $wcount = wishlist_count($conn); if ($wcount > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.8rem;"><?= $wcount ?></span>
          <?php endif; ?>
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
              <li><a class="dropdown-item" href="orders.php">Orders</a></li>
              <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
              <li><a class="dropdown-item" href="profile.php">Profile</a></li>
              <li><a class="dropdown-item" href="#">Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <!-- Tombol Login saat belum login -->
          <a href="login.php" class="btn btn-theme btn-sm ms-2">Login</a>
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
      <a href="index.php" class="btn <?= !isset($_GET['kategori']) ? 'btn-theme' : 'btn-outline-theme' ?>">All</a>
      <a href="index.php?kategori=Clothes" class="btn <?= ($_GET['kategori'] ?? '') == 'Clothes' ? 'btn-theme' : 'btn-outline-theme' ?>">Clothes</a>
      <a href="index.php?kategori=Pants" class="btn <?= ($_GET['kategori'] ?? '') == 'Pants' ? 'btn-theme' : 'btn-outline-theme' ?>">Pants</a>
      <a href="index.php?kategori=Shoes" class="btn <?= ($_GET['kategori'] ?? '') == 'Shoes' ? 'btn-theme' : 'btn-outline-theme' ?>">Shoes</a>
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
          <div class="product-tile h-100">
            <span class="discount-badge">35%</span>
            <a href="detail.php?id='.$d['id'].'" style="text-decoration:none; color:inherit;">
              <img src="../uploads/'.$d['gambar'].'" class="thumb" alt="'.htmlspecialchars($d['nama']).'">
            </a>
            <div class="product-body">
              <div class="product-title">'.htmlspecialchars($d['nama']).'</div>
              <div class="product-price">Rp '.number_format($d['harga'],0,',','.').'</div>
              <div class="product-meta">
                <span><i class="bi bi-star-fill text-warning"></i> 5.0</span>
                <span class="dot"></span>
                <span>500+ terjual</span>
              </div>
              <div class="product-loc"><i class="bi bi-geo-alt"></i> Kota Jakarta</div>
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
