<?php
session_start();
include '../admin/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$regErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $r_name = trim($_POST['r_name'] ?? '');
  $r_email = trim($_POST['r_email'] ?? '');
  $r_password = $_POST['r_password'] ?? '';
  $r_password2 = $_POST['r_password2'] ?? '';

  if ($r_name === '' || $r_email === '' || $r_password === '' || $r_password2 === '') {
    $regErr = 'Semua field pendaftaran wajib diisi.';
  } elseif (!filter_var($r_email, FILTER_VALIDATE_EMAIL)) {
    $regErr = 'Format email tidak valid.';
  } elseif (strlen($r_password) < 6) {
    $regErr = 'Password minimal 6 karakter.';
  } elseif ($r_password !== $r_password2) {
    $regErr = 'Konfirmasi password tidak sama.';
  } else {
    $hash = password_hash($r_password, PASSWORD_DEFAULT);
    if ($stmt = mysqli_prepare($conn, 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "user")')) {
      mysqli_stmt_bind_param($stmt, 'sss', $r_name, $r_email, $hash);
      if (mysqli_stmt_execute($stmt)) {
        $uid = mysqli_insert_id($conn);
        // Auto-login after registration
        $_SESSION['user_id'] = $uid;
        $_SESSION['user_name'] = $r_name;
        $_SESSION['user_email'] = $r_email;
        $_SESSION['user_role'] = 'user';
        header('Location: index.php');
        exit;
      } else {
        if (mysqli_errno($conn) == 1062) {
          $regErr = 'Email sudah terdaftar. Gunakan email lain atau login.';
        } else {
          $regErr = 'Pendaftaran gagal. Coba lagi nanti.';
        }
      }
      mysqli_stmt_close($stmt);
    } else {
      $regErr = 'Terjadi kesalahan server saat mendaftar. Coba lagi nanti.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar | THREAD THEORY</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="theme.css" rel="stylesheet">
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
    .btn-theme { background-color: #ff3c00; color: #fff; border: 0; }
    .btn-theme:hover { background-color: #ff521f; color: #fff; }
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
            <i class="bi bi-person-plus text-light me-2" style="font-size:1.6rem;"></i>
            <h1 class="h4 m-0 brand-title">Daftar Akun</h1>
          </div>

          <?php if (!empty($regErr)): ?>
            <div class="alert alert-danger py-2" role="alert">
              <?= htmlspecialchars($regErr) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="register.php" novalidate>
            <div class="mb-3">
              <label for="r_name" class="form-label">Nama Lengkap</label>
              <input type="text" class="form-control bg-glass" id="r_name" name="r_name" placeholder="Nama Anda" required>
            </div>
            <div class="mb-3">
              <label for="r_email" class="form-label">Email</label>
              <input type="email" class="form-control bg-glass" id="r_email" name="r_email" placeholder="you@example.com" required>
            </div>
            <div class="mb-3">
              <label for="r_password" class="form-label">Password</label>
              <input type="password" class="form-control bg-glass" id="r_password" name="r_password" placeholder="********" required>
              <div class="form-text text-light">Minimal 6 karakter.</div>
            </div>
            <div class="mb-4">
              <label for="r_password2" class="form-label">Konfirmasi Password</label>
              <input type="password" class="form-control bg-glass" id="r_password2" name="r_password2" placeholder="********" required>
            </div>
            <button type="submit" class="btn btn-theme w-100 py-2">Daftar</button>
          </form>

          <div class="text-center mt-3">
            <small>Sudah punya akun? <a class="link-light-orange" href="login.php">Login</a></small>
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
