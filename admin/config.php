<?php
$conn = mysqli_connect("db", "root", "root", "db_produk");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
