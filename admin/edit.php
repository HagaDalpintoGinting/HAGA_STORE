<?php
session_start();
include 'config.php';

// Cek login
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// Ambil data produk
$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM produk WHERE id=$id");
$p = mysqli_fetch_assoc($data);

// Update produk
if (isset($_POST['update'])) {
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $deskripsi = $_POST['deskripsi'];
    $kategori = $_POST['kategori'];

    if ($_FILES['gambar']['name']) {
        $gambar = $_FILES['gambar']['name'];
        $tmp = $_FILES['gambar']['tmp_name'];
        move_uploaded_file($tmp, "../uploads/" . $gambar);
        mysqli_query($conn, "UPDATE produk SET 
            nama='$nama', 
            harga='$harga', 
            deskripsi='$deskripsi', 
            kategori='$kategori', 
            gambar='$gambar' 
            WHERE id=$id");
    } else {
        mysqli_query($conn, "UPDATE produk SET 
            nama='$nama', 
            harga='$harga', 
            deskripsi='$deskripsi', 
            kategori='$kategori' 
            WHERE id=$id");
    }

    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Produk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
  <div class="container">
    <h2 class="mb-4">Edit Produk</h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="text" name="nama" value="<?= htmlspecialchars($p['nama']) ?>" class="form-control mb-2" required>
      <input type="number" name="harga" value="<?= $p['harga'] ?>" class="form-control mb-2" required>
      <textarea name="deskripsi" class="form-control mb-2" rows="3"><?= htmlspecialchars($p['deskripsi']) ?></textarea>
      <select name="kategori" class="form-control mb-2" required>
        <option value="Clothes" <?= $p['kategori'] == 'Clothes' ? 'selected' : '' ?>>Clothes</option>
        <option value="Pants" <?= $p['kategori'] == 'Pants' ? 'selected' : '' ?>>Pants</option>
        <option value="Shoes" <?= $p['kategori'] == 'Shoes' ? 'selected' : '' ?>>Shoes</option>
      </select>
      <input type="file" name="gambar" class="form-control mb-2">
      <div class="mb-2">
        <img src="../uploads/<?= $p['gambar'] ?>" width="120" alt="Gambar Sekarang">
      </div>
      <button name="update" class="btn btn-primary">Update</button>
      <a href="index.php" class="btn btn-secondary ms-2">Kembali</a>
    </form>
  </div>
</body>
</html>
