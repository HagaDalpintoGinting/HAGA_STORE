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

    $stock = (int)($_POST['stock'] ?? 0);
    $sizes = trim($_POST['sizes'] ?? 'M,L,XL,XXL');
    if ($gambar != '') {
      move_uploaded_file($tmp, "../uploads/" . $gambar);
      mysqli_query($conn, "INSERT INTO produk (nama, harga, stock, sizes, gambar, deskripsi, kategori)
                 VALUES ('$nama','$harga','$stock','".mysqli_real_escape_string($conn,$sizes)."','$gambar','$deskripsi','$kategori')");

      $pid = mysqli_insert_id($conn);
      if (!empty($_FILES['galeri']['name'][0])) {
        foreach ($_FILES['galeri']['name'] as $i => $gname) {
          if (!$gname) continue;
          $gtmp = $_FILES['galeri']['tmp_name'][$i];
          $safe = time().'_'.preg_replace('/[^a-zA-Z0-9_.-]/','',$gname);
          move_uploaded_file($gtmp, "../uploads/".$safe);
          mysqli_query($conn, "INSERT INTO produk_images (produk_id, filename, sort_order) VALUES ($pid, '".$safe."', $i)");
        }
      }
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
      <div>
        <a href="orders.php" class="btn btn-outline-primary me-2">Orders</a>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
      </div>
    </div>

    <div class="card p-4 mb-4 shadow">
      <h4>Tambah Produk Baru</h4>
      <form method="POST" enctype="multipart/form-data">
        <input type="text" name="nama" class="form-control mb-2" placeholder="Nama produk" required>
        <input type="number" name="harga" class="form-control mb-2" placeholder="Harga" required>
        <div class="row g-2 mb-2">
          <div class="col-md-6"><input type="number" name="stock" class="form-control" placeholder="Stock" min="0" value="0"></div>
          <div class="col-md-6"><input type="text" name="sizes" class="form-control" placeholder="Ukuran tersedia (contoh: M,L,XL,XXL)" value="M,L,XL,XXL"></div>
        </div>
        <textarea name="deskripsi" class="form-control mb-2" placeholder="Deskripsi produk" rows="3" required></textarea>
        <select name="kategori" class="form-control mb-2" required>
          <option value="">-- Pilih Kategori --</option>
          <option value="Clothes">Clothes</option>
          <option value="Pants">Pants</option>
          <option value="Shoes">Shoes</option>
        </select>
        <input type="file" name="gambar" class="form-control mb-2" required>
        <label class="form-label">Galeri (boleh lebih dari satu)</label>
        <input type="file" name="galeri[]" class="form-control mb-3" multiple>
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
            <th>Stock</th>
            <th>Ukuran</th>
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
            <td>{$d['stock']}</td>
            <td>{$d['sizes']}</td>
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
