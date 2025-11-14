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
    $stock = (int)($_POST['stock'] ?? 0);
    $sizes = trim($_POST['sizes'] ?? '');

    if ($_FILES['gambar']['name']) {
        $gambar = $_FILES['gambar']['name'];
        $tmp = $_FILES['gambar']['tmp_name'];
        move_uploaded_file($tmp, "../uploads/" . $gambar);
        mysqli_query($conn, "UPDATE produk SET 
          nama='$nama', 
          harga='$harga', 
          stock='$stock', 
          sizes='".mysqli_real_escape_string($conn,$sizes)."', 
          deskripsi='$deskripsi', 
          kategori='$kategori', 
          gambar='$gambar' 
          WHERE id=$id");
    } else {
        mysqli_query($conn, "UPDATE produk SET 
          nama='$nama', 
          harga='$harga', 
          stock='$stock', 
          sizes='".mysqli_real_escape_string($conn,$sizes)."', 
          deskripsi='$deskripsi', 
          kategori='$kategori' 
          WHERE id=$id");
    }

      // Handle gallery uploads
      if (!empty($_FILES['galeri']['name'][0])) {
        foreach ($_FILES['galeri']['name'] as $i => $gname) {
          if (!$gname) continue;
          $gtmp = $_FILES['galeri']['tmp_name'][$i];
          $safe = time().'_'.preg_replace('/[^a-zA-Z0-9_.-]/','',$gname);
          move_uploaded_file($gtmp, "../uploads/".$safe);
          mysqli_query($conn, "INSERT INTO produk_images (produk_id, filename, sort_order) VALUES ($id, '".$safe."', $i)");
        }
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
      <div class="row g-2 mb-2">
        <div class="col-md-6"><input type="number" name="stock" value="<?= (int)($p['stock'] ?? 0) ?>" class="form-control" min="0" placeholder="Stock"></div>
        <div class="col-md-6"><input type="text" name="sizes" value="<?= htmlspecialchars($p['sizes'] ?? '') ?>" class="form-control" placeholder="Ukuran tersedia (contoh: M,L,XL,XXL)"></div>
      </div>
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
      <label class="form-label">Tambahkan Foto Galeri</label>
      <input type="file" name="galeri[]" class="form-control mb-2" multiple>

      <?php
      $gal = mysqli_query($conn, "SELECT * FROM produk_images WHERE produk_id=$id ORDER BY sort_order, id");
      if (mysqli_num_rows($gal) > 0): ?>
        <div class="mb-3">
          <div class="d-flex flex-wrap gap-2">
            <?php while($gi = mysqli_fetch_assoc($gal)): ?>
              <div class="border p-1 text-center">
                <img src="../uploads/<?= htmlspecialchars($gi['filename']) ?>" width="80" height="80" style="object-fit:cover">
                <div><a href="delete_image.php?id=<?= (int)$gi['id'] ?>&pid=<?= (int)$id ?>" class="btn btn-sm btn-outline-danger mt-1" onclick="return confirm('Hapus gambar ini?')">Hapus</a></div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      <?php endif; ?>
      <button name="update" class="btn btn-primary">Update</button>
      <a href="index.php" class="btn btn-secondary ms-2">Kembali</a>
    </form>
  </div>
</body>
</html>
