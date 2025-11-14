<?php
session_start();
include 'config.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $gambar = $_FILES['gambar']['name'];
    $tmp = $_FILES['gambar']['tmp_name'];
    $stock = (int)($_POST['stock'] ?? 0);
    $sizes = trim($_POST['sizes'] ?? 'M,L,XL,XXL');
    $diskon = (int)($_POST['diskon'] ?? 0);
    if ($gambar != '') {
      $safe_gambar = time().'_'.preg_replace('/[^a-zA-Z0-9_.-]/','',$gambar);
      move_uploaded_file($tmp, "../uploads/" . $safe_gambar);
      mysqli_query($conn, "INSERT INTO produk (nama, harga, stock, sizes, gambar, deskripsi, kategori, diskon)
                 VALUES ('$nama','$harga','$stock','".mysqli_real_escape_string($conn,$sizes)."','$safe_gambar','$deskripsi','$kategori','$diskon')");

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
  <title>Admin Panel - THREAD THEORY</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="../public/theme.css?v=1763094445" rel="stylesheet">
  <link href="theme.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>ADMIN PANEL - THREAD THEORY</h2>
      <div>
        <a href="orders.php" class="btn btn-outline-theme me-2"><i class="bi bi-box-seam"></i> Orders</a>
        <a href="logout.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </div>

    <div class="card p-4 mb-4">
      <h4><i class="bi bi-plus-circle-fill"></i> Tambah Produk Baru</h4>
      <form method="POST" enctype="multipart/form-data" class="mt-3">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="nama" class="form-control" placeholder="Nama produk" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Harga</label>
                    <input type="number" name="harga" class="form-control" placeholder="Harga" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Diskon (%)</label>
                    <input type="number" name="diskon" class="form-control" placeholder="0" min="0" max="100">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="kategori" class="form-select" required>
                      <option value="">-- Pilih Kategori --</option>
                      <option value="Clothes">Clothes</option>
                      <option value="Pants">Pants</option>
                      <option value="Shoes">Shoes</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" placeholder="Stock" min="0" value="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Ukuran Tersedia</label>
                    <input type="text" name="sizes" class="form-control" placeholder="cth: M,L,XL" value="M,L,XL,XXL">
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Deskripsi Produk</label>
            <textarea name="deskripsi" class="form-control" placeholder="Deskripsi produk" rows="3" required></textarea>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Gambar Utama</label>
                    <input type="file" name="gambar" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Galeri (opsional, bisa lebih dari satu)</label>
                    <input type="file" name="galeri[]" class="form-control" multiple>
                </div>
            </div>
        </div>
        <button name="simpan" class="btn btn-theme w-100 mt-2"><i class="bi bi-check-circle"></i> Simpan Produk</button>
      </form>
    </div>

    <div class="card p-4">
      <h4><i class="bi bi-list-ul"></i> Daftar Produk</h4>
      <div class="table-responsive mt-3">
        <table class="table table-bordered table-striped table-hover mt-3">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Harga</th>
              <th>Kategori</th>
              <th>Stock</th>
              <th>Ukuran</th>
              <th>Gambar</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $produk = mysqli_query($conn, "SELECT * FROM produk ORDER BY id DESC");
          while ($d = mysqli_fetch_assoc($produk)) {
            echo "<tr>
              <td>".htmlspecialchars($d['nama'] ?? '')."</td>
              <td>Rp " . number_format($d['harga'] ?? 0, 0, ',', '.') . "</td>
              <td>".htmlspecialchars($d['kategori'] ?? '')."</td>
              <td>".htmlspecialchars($d['stock'] ?? '')."</td>
              <td>".htmlspecialchars($d['sizes'] ?? '')."</td>
              <td><img src='../uploads/".htmlspecialchars($d['gambar'] ?? '')."' width='80' class='rounded'></td>
              <td>
                <a href='edit.php?id={$d['id']}' class='btn btn-sm btn-outline-warning mb-1'><i class='bi bi-pencil-square'></i></a>
                <a href='delete.php?id={$d['id']}' class='btn btn-sm btn-outline-danger mb-1'><i class='bi bi-trash'></i></a>
              </td>
            </tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Replace native confirm with SweetAlert2
    const deleteButtons = document.querySelectorAll("a[href*='delete.php']");
    deleteButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.href;
        Swal.fire({
          title: 'Anda yakin?',
          text: "Data yang dihapus tidak dapat dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, hapus!',
          cancelButtonText: 'Batal'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = url;
          }
        });
      });
    });
  </script>
</body>
</html>
