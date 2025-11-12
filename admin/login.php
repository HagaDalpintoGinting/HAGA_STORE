<?php
session_start();
include 'config.php';

if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM admin WHERE username='$user' AND password='$pass'");
    if (mysqli_num_rows($result) > 0) {
        $_SESSION['login'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="height:100vh;">
  <div class="card p-4 shadow" style="min-width:300px;">
    <h4 class="mb-3">Login Admin</h4>
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method="POST">
      <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
      <input type="password" name="password" placeholder="Password" class="form-control mb-3" required>
      <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
    </form>
  </div>
</body>
</html>
