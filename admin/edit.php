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
    $diskon = (int)($_POST['diskon'] ?? 0);
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
          gambar='$gambar', 
          diskon='$diskon' 
          WHERE id=$id");
    } else {
        mysqli_query($conn, "UPDATE produk SET 
          nama='$nama', 
          harga='$harga', 
          stock='$stock', 
          sizes='".mysqli_real_escape_string($conn,$sizes)."', 
          deskripsi='$deskripsi', 
          kategori='$kategori', 
          diskon='$diskon' 
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
  <title>Edit Produk - Admin | THREAD THEORY</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="../public/theme.css?v=1763094445" rel="stylesheet">
  <link href="theme.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="p-4">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Edit Produk</h2>
        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
    
    <div class="card p-4">
        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Nama Produk</label>
            <input type="text" name="nama" value="<?= htmlspecialchars($p['nama']) ?>" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Harga</label>
            <input type="number" name="harga" value="<?= $p['harga'] ?>" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Diskon (%)</label>
            <input type="number" name="diskon" value="<?= (int)($p['diskon'] ?? 0) ?>" class="form-control" min="0" max="100" placeholder="0">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Stock</label>
              <input type="number" name="stock" value="<?= (int)($p['stock'] ?? 0) ?>" class="form-control" min="0" placeholder="Stock">
            </div>
            <div class="col-md-6">
              <label class="form-label">Ukuran</label>
              <input type="text" name="sizes" value="<?= htmlspecialchars($p['sizes'] ?? '') ?>" class="form-control" placeholder="Contoh: M,L,XL,XXL">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($p['deskripsi']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Kategori</label>
            <select name="kategori" class="form-select" required>
              <option value="Clothes" <?= $p['kategori'] == 'Clothes' ? 'selected' : '' ?>>Clothes</option>
              <option value="Pants" <?= $p['kategori'] == 'Pants' ? 'selected' : '' ?>>Pants</option>
              <option value="Shoes" <?= $p['kategori'] == 'Shoes' ? 'selected' : '' ?>>Shoes</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Ganti Gambar Utama</label>
            <input type="file" name="gambar" class="form-control">
            <div class="mt-2">
              <img src="../uploads/<?= $p['gambar'] ?>" width="120" alt="Gambar Sekarang" class="rounded">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Tambahkan Foto Galeri (bisa pilih lebih dari satu)</label>
            <input type="file" name="galeri[]" class="form-control" multiple>
          </div>

          <?php
          $gal = mysqli_query($conn, "SELECT * FROM produk_images WHERE produk_id=$id ORDER BY sort_order, id");
          if (mysqli_num_rows($gal) > 0): ?>
            <div class="mb-3">
              <label class="form-label">Galeri Saat Ini</label>
              <div class="d-flex flex-wrap gap-2">
                <?php while($gi = mysqli_fetch_assoc($gal)): ?>
                  <div class="border p-1 text-center bg-dark rounded">
                    <img src="../uploads/<?= htmlspecialchars($gi['filename']) ?>" width="80" height="80" style="object-fit:cover" class="rounded">
                    <div><a href="delete_image.php?id=<?= (int)$gi['id'] ?>&pid=<?= (int)$id ?>" class="btn btn-sm btn-outline-danger mt-1 delete-btn">Hapus</a></div>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          <?php endif; ?>
          <div class="mt-4">
            <button name="update" class="btn btn-theme"><i class="bi bi-check-circle"></i> Update Produk</button>
          </div>
        </form>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.href;
                Swal.fire({
                    title: 'Anda yakin?',
                    text: "Gambar ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    });
  </script>
</body>
</html>
