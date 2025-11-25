<?php
session_start();
include '../admin/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle login only
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $err = 'Email dan password wajib diisi.';
  } else {
    // Prepared statement to avoid SQL injection
    if ($stmt = mysqli_prepare($conn, 'SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1')) {
      mysqli_stmt_bind_param($stmt, 's', $email);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_store_result($stmt);

      if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_bind_result($stmt, $uid, $name, $uemail, $hash, $role);
        mysqli_stmt_fetch($stmt);
        if (is_string($hash) && $hash !== '' && password_verify($password, $hash)) {
          // Success: set session and redirect
          $_SESSION['user_id'] = $uid;
          $_SESSION['user_name'] = $name;
          $_SESSION['user_email'] = $uemail;
          $_SESSION['user_role'] = $role;
          header('Location: index.php');
          exit;
        } else {
          $err = 'Email atau password salah.';
        }
      } else {
        $err = 'Email atau password salah.';
      }
      mysqli_stmt_close($stmt);
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
  <title>Login | HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="theme.css?v=1763094445" rel="stylesheet">
  <link href="style/style.css?v=1763094445" rel="stylesheet">
</head>
<body class="login-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-7 col-lg-5">
        <div class="p-4 p-md-5 login-card">
          <div class="d-flex align-items-center mb-4">
            <i class="bi bi-bag-check text-light me-2" style="font-size:1.6rem;"></i>
            <h1 class="h4 m-0 brand-title">THREAD THEORY</h1>
          </div>

          

          <?php if ($err): ?>
            <div class="alert alert-danger py-2" role="alert">
              <?= htmlspecialchars($err) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="login.php" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control bg-glass" id="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="mb-2">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" class="form-control bg-glass" id="password" name="password" placeholder="********" required>
                <button class="btn btn-outline-theme" type="button" id="togglePwd">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="d-flex justify-content-end align-items-center mb-4">
              <a href="forgot.php" class="link-light-orange">Lupa password?</a>
            </div>
            <button type="submit" class="btn btn-theme w-100 py-2">Masuk</button>
          </form>

          <div class="text-center mt-3">
            <small>Belum punya akun? <a class="link-light-orange" href="register.php">Daftar</a></small>
          </div>

          <div class="text-center mt-4">
            <a class="link-light-orange" href="index.php"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle password visibility
    const toggle = document.getElementById('togglePwd');
    const pwd = document.getElementById('password');
    if (toggle) {
      toggle.addEventListener('click', () => {
        const isText = pwd.getAttribute('type') === 'text';
        pwd.setAttribute('type', isText ? 'password' : 'text');
        toggle.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      });
    }
  </script>
</body>
</html>
