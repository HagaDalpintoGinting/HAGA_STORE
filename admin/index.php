<?php
session_start();
include 'config.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $deskripsi = $_POST['deskripsi'];
    $kategori = $_POST['kategori'];
    $gambar = $_FILES['gambar']['name'];
    $tmp = $_FILES['gambar']['tmp_name'];

    if ($gambar != '') {
        move_uploaded_file($tmp, "../uploads/" . $gambar);
        mysqli_query($conn, "INSERT INTO produk (nama, harga, gambar, deskripsi, kategori)
                             VALUES ('$nama','$harga','$gambar','$deskripsi','$kategori')");
    }
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Panel - HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Admin Panel - HAGA STORE</h2>
      <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <div class="card p-4 mb-4 shadow">
      <h4>Tambah Produk Baru</h4>
      <form method="POST" enctype="multipart/form-data">
        <input type="text" name="nama" class="form-control mb-2" placeholder="Nama produk" required>
        <input type="number" name="harga" class="form-control mb-2" placeholder="Harga" required>
        <textarea name="deskripsi" class="form-control mb-2" placeholder="Deskripsi produk" rows="3" required></textarea>
        <select name="kategori" class="form-control mb-2" required>
          <option value="">-- Pilih Kategori --</option>
          <option value="Clothes">Clothes</option>
          <option value="Pants">Pants</option>
          <option value="Shoes">Shoes</option>
        </select>
        <input type="file" name="gambar" class="form-control mb-2" required>
        <button name="simpan" class="btn btn-success">Simpan Produk</button>
      </form>
    </div>

    <div class="card p-4 shadow">
      <h4>Daftar Produk</h4>
      <table class="table table-bordered mt-3">
        <thead class="table-secondary">
          <tr>
            <th>Nama</th>
            <th>Harga</th>
            <th>Kategori</th>
            <th>Deskripsi</th>
            <th>Gambar</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $produk = mysqli_query($conn, "SELECT * FROM produk");
        while ($d = mysqli_fetch_assoc($produk)) {
          echo "<tr>
            <td>{$d['nama']}</td>
            <td>Rp " . number_format($d['harga'], 0, ',', '.') . "</td>
            <td>{$d['kategori']}</td>
            <td>{$d['deskripsi']}</td>
            <td><img src='../uploads/{$d['gambar']}' width='80'></td>
            <td>
              <a href='edit.php?id={$d['id']}' class='btn btn-warning btn-sm'>Edit</a>
              <a href='delete.php?id={$d['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin hapus produk ini?\")'>Delete</a>
            </td>
          </tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
