<?php
session_start();
include '../admin/config.php';
if (isset($_SESSION['user_id'])) {
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $p1 = $_POST['password'] ?? '';
  $p2 = $_POST['password2'] ?? '';

  if ($email === '' || $p1 === '' || $p2 === '') {
    $err = 'Semua field wajib diisi.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Format email tidak valid.';
  } elseif (strlen($p1) < 6) {
    $err = 'Password minimal 6 karakter.';
  } elseif ($p1 !== $p2) {
    $err = 'Konfirmasi password tidak sama.';
  } else {
    // Check if email exists
    if ($stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1')) {
      mysqli_stmt_bind_param($stmt, 's', $email);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_store_result($stmt);
      if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_free_result($stmt);
        mysqli_stmt_close($stmt);
        // Update password
        $hash = password_hash($p1, PASSWORD_DEFAULT);
        if ($ustmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE email = ?')) {
          mysqli_stmt_bind_param($ustmt, 'ss', $hash, $email);
          if (mysqli_stmt_execute($ustmt)) {
            if (mysqli_stmt_affected_rows($ustmt) >= 0) {
              $msg = 'Password berhasil diperbarui. Silakan login kembali.';
            } else {
              $err = 'Tidak ada perubahan yang dilakukan.';
            }
          } else {
            $err = 'Gagal memperbarui password. Coba lagi nanti.';
          }
          mysqli_stmt_close($ustmt);
        } else {
          $err = 'Terjadi kesalahan server. Coba lagi nanti.';
        }
      } else {
        $err = 'Email tidak ditemukan.';
        mysqli_stmt_close($stmt);
      }
    } else {
      $err = 'Terjadi kesalahan server. Coba lagi nanti.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lupa Password | HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="theme.css?v=1763094445" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(145deg, #1a1a1a 0%, #2c0a05 100%);
      font-family: 'Quicksand', sans-serif;
      color: #eee;
      min-height: 100vh;
      display: flex;
      align-items: center;
    }
    .brand-title {
      font-family: 'Anton', sans-serif;
      letter-spacing: 2px;
      color: #ff3c00;
      text-shadow: 1px 1px 3px #000;
    }
    .card-glass {
      background: linear-gradient(135deg, rgba(26,26,26,0.5) 0%, rgba(44,10,5,0.5) 100%);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(255,60,0,0.1);
      backdrop-filter: blur(2px);
    }
    .form-control.bg-glass {
      background: transparent;
      color: #fff;
      border: 1px solid rgba(255,255,255,0.2);
    }
    .form-control.bg-glass:focus {
      background: transparent;
      color: #fff;
      border-color: #ff3c00;
      box-shadow: 0 0 0 .25rem rgba(255,60,0,.15);
    }
    .form-control::placeholder { color: rgba(255,255,255,0.8); opacity: 1; }
    /* Buttons use global theme.css */
    a.link-light-orange { color: #ff7a52; text-decoration: none; }
    a.link-light-orange:hover { color: #ffa284; }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-7 col-lg-6 col-xl-5">
        <div class="p-4 p-md-5 card-glass">
          <div class="d-flex align-items-center mb-4">
            <i class="bi bi-shield-lock text-light me-2" style="font-size:1.6rem;"></i>
            <h1 class="h4 m-0 brand-title">Lupa Password</h1>
          </div>

          <?php if ($msg): ?>
            <div class="alert alert-success py-2" role="alert">
              <?= htmlspecialchars($msg) ?>
            </div>
          <?php endif; ?>

          <?php if ($err): ?>
            <div class="alert alert-danger py-2" role="alert">
              <?= htmlspecialchars($err) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="forgot.php" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control bg-glass" id="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password Baru</label>
              <input type="password" class="form-control bg-glass" id="password" name="password" placeholder="********" required>
              <div class="form-text text-light">Minimal 6 karakter.</div>
            </div>
            <div class="mb-4">
              <label for="password2" class="form-label">Konfirmasi Password</label>
              <input type="password" class="form-control bg-glass" id="password2" name="password2" placeholder="********" required>
            </div>
            <button type="submit" class="btn btn-theme w-100 py-2">Perbarui Password</button>
          </form>

          <div class="text-center mt-3">
            <small>Ingat password? <a class="link-light-orange" href="login.php">Login</a></small>
          </div>

          <div class="text-center mt-4">
            <a class="link-light-orange" href="index.php"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
