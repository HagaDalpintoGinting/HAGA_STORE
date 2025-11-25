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
  <link href="style/theme.css?v=1763094445" rel="stylesheet">
  <link href="style/style.css?v=1763094445" rel="stylesheet">
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
