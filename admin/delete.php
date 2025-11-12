<?php
include 'config.php';
$id = $_GET['id'];

// Ambil gambar sebelum dihapus
$data = mysqli_query($conn, "SELECT gambar FROM produk WHERE id=$id");
$produk = mysqli_fetch_assoc($data);

if ($produk && file_exists("../uploads/" . $produk['gambar'])) {
    unlink("../uploads/" . $produk['gambar']);
}

mysqli_query($conn, "DELETE FROM produk WHERE id=$id");
header("Location: index.php");
exit;
?>
