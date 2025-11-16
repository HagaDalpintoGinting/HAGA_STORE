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
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    @media (max-width: 991px) {
      .container { padding-left: 10px; padding-right: 10px; }
      .hero-section { padding: 40px; border-radius: 14px; margin-bottom: 18px; margin-top: 18px; }
      .navbar-brand { font-size: 1.2rem; }
      .search-bar-themed { width: 100% !important; }
      .row > .col-md-4 { flex: 0 0 50%; max-width: 50%; }
    }
    @media (max-width: 767px) {
      .container { padding-left: 5px; padding-right: 5px; }
      .hero-section { padding: 18px; border-radius: 10px; margin-bottom: 10px; margin-top: 10px; }
      .navbar-brand { font-size: 1rem; }
      .search-bar-themed { width: 100% !important; }
      .row { gap: 0.5rem 0; }
      .row > .col-md-4 { flex: 0 0 100%; max-width: 100%; margin-bottom: 1rem; }
      .product-tile { border-radius: 10px; }
      .product-tile .thumb { height: 120px; }
      .product-title { font-size: 1rem; min-height: 2em; }
      .product-price { font-size: 1rem; }
      .product-meta, .product-loc { font-size: 0.85rem; }
      .kategori-bar .btn { font-size: 0.95rem; padding: 7px 12px; }
      .dropdown-menu { font-size: 0.95rem; }
      footer { padding: 12px; font-size: 0.85rem; }
    }
    @media (max-width: 480px) {
      .container { padding-left: 2px; padding-right: 2px; }
      .hero-section { padding: 8px; border-radius: 7px; }
      .row > .col-md-4 { margin-bottom: 0.7rem; }
      .product-tile .thumb { height: 80px; }
      .product-title { font-size: 0.92rem; }
      .product-price { font-size: 0.95rem; }
      .navbar-brand { font-size: 0.95rem; }
      .dropdown-menu { font-size: 0.9rem; }
      footer { padding: 6px; font-size: 0.8rem; }
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
    @media (max-width: 576px) {
      .judul {
        font-size: 2rem;
        margin-top: 20px;
      }
      .subjudul {
        font-size: 0.95rem;
        margin-bottom: 20px;
      }
      .kategori-bar .btn {
        padding: 7px 12px;
        font-size: 0.95rem;
      }
      .product-tile .thumb {
        height: 140px;
      }
      .product-body {
        padding: 8px 7px;
      }
      .product-title {
        font-size: 1rem;
        min-height: 2em;
      }
      .product-price {
        font-size: 1rem;
      }
      .product-meta, .product-loc {
        font-size: 0.8rem;
      }
      .container {
        padding-left: 5px;
        padding-right: 5px;
      }
      .hero-section {
        padding: 30px;
        margin-bottom: 15px;
        margin-top: 15px;
      }
      .navbar-brand {
        font-size: 1.1rem;
      }
      .dropdown-menu {
        font-size: 0.95rem;
      }
    }
    @media (max-width: 400px) {
      .product-tile .thumb {
        height: 100px;
      }
      .product-title {
        font-size: 0.9rem;
      }
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
      background: linear-gradient(135deg, rgba(26,26,26,0.98) 0%, rgba(44,10,5,0.98) 100%);
      border: 1.5px solid #ff3c00;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(255,60,0,0.10);
      color: #fff;
      padding: 0.5rem 0;
      min-width: 180px;
      overflow: hidden;
    }
    .dropdown-menu .dropdown-header {
      color: #ff3c00;
      font-family: 'Anton', sans-serif;
      font-size: 1.05rem;
      letter-spacing: 1px;
      padding-bottom: 0.25rem;
      border-bottom: 1px solid rgba(255,60,0,0.15);
      margin-bottom: 0.25rem;
    }
    .dropdown-menu .dropdown-item {
      color: #fff;
      font-weight: 500;
      border-radius: 8px;
      padding: 8px 16px;
      transition: background 0.18s, color 0.18s;
      display: block;
      width: 100%;
      box-sizing: border-box;
    }
    .dropdown-menu .dropdown-item:hover, .dropdown-menu .dropdown-item:focus {
      background: rgba(255,60,0,0.18);
      color: #ff3c00;
    }
    .dropdown-menu .dropdown-divider {
      border-top: 1.5px solid #ff3c00;
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
          <input class="form-control" type="search" name="q" placeholder="Search products..." aria-label="Search" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <?php if (isset($_GET['kategori'])): ?>
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($_GET['kategori']) ?>">
          <?php endif; ?>
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
          <?php
            // Get avatar filename from database
            $avatarPath = '';
            $uid = (int)$_SESSION['user_id'];
            $avatarFile = '';
            if ($conn && is_object($conn) && get_class($conn) === 'mysqli') {
              $stmt = mysqli_prepare($conn, 'SELECT avatar FROM users WHERE id = ? LIMIT 1');
              if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $uid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $avatarFile);
                if (mysqli_stmt_fetch($stmt) && $avatarFile && file_exists(__DIR__ . '/../uploads/avatars/' . $avatarFile)) {
                  $avatarPath = '../uploads/avatars/' . htmlspecialchars($avatarFile);
                }
                mysqli_stmt_close($stmt);
              }
            }
            if (!$avatarPath) {
              // SVG fallback
              $avatarPath = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#2c0a05"/><stop offset="100%" stop-color="#1a1a1a"/></linearGradient></defs><rect width="40" height="40" rx="8" fill="url(#g)"/><circle cx="20" cy="15" r="10" fill="#444" stroke="#666" stroke-width="2"/><rect x="8" y="26" width="24" height="10" rx="5" fill="#444" stroke="#666" stroke-width="2"/></svg>');
            }
          ?>
          <div class="dropdown">
            <a href="#" class="btn btn-link text-light dropdown-toggle d-flex align-items-center" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?= $avatarPath ?>" alt="Avatar" class="rounded-circle me-1" style="width:32px;height:32px;object-fit:cover;border:1.5px solid #ff3c00;">
              <span class="ms-1 d-none d-sm-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Akun') ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
              <li><h6 class="dropdown-header">Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></h6></li>
              <li><a class="dropdown-item" href="orders.php">Orders</a></li>
              <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
              <li><a class="dropdown-item" href="profile.php">Profile</a></li>
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
      $q = trim($_GET['q'] ?? '');
      $sql = "SELECT * FROM produk";
      $where = [];
      if ($kategori) {
        $where[] = "kategori='" . mysqli_real_escape_string($conn, $kategori) . "'";
      }
      if ($q !== '') {
        $qEsc = mysqli_real_escape_string($conn, $q);
        $where[] = "(nama LIKE '%$qEsc%' OR kategori LIKE '%$qEsc%')";
      }
      if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
      }
      $data = mysqli_query($conn, $sql);

      while ($d = mysqli_fetch_assoc($data)) {
        $diskon = (int)($d['diskon'] ?? 0);
        $harga = (int)($d['harga'] ?? 0);
        $hargaDiskon = $diskon > 0 ? round($harga * (100 - $diskon) / 100) : $harga;
        echo '
        <div class="col-md-4 mb-4">
          <div class="product-tile h-100">
            '.($diskon > 0 ? '<span class="discount-badge">'.$diskon.'% OFF</span>' : '').'
            <a href="detail.php?id='.$d['id'].'" style="text-decoration:none; color:inherit;">
              <img src="../uploads/'.$d['gambar'].'" class="thumb" alt="'.htmlspecialchars($d['nama']).'">
            </a>
            <div class="product-body">
              <div class="product-title">'.htmlspecialchars($d['nama']).'</div>
              <div class="product-price">'.
                ($diskon > 0 ? '<span style="text-decoration:line-through;color:#ff7a52;font-size:1rem;">Rp '.number_format($harga,0,',','.').'</span> <span style="color:#fff;font-weight:700;font-size:1.2rem;">Rp '.number_format($hargaDiskon,0,',','.').'</span>'
                : 'Rp '.number_format($harga,0,',','.')).
              '</div>
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
