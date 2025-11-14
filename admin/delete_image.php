<?php
session_start();
include 'config.php';
if (!isset($_SESSION['login'])) { header('Location: login.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
$pid = (int)($_GET['pid'] ?? 0);
if ($id>0) {
  $res = mysqli_query($conn, "SELECT filename FROM produk_images WHERE id=$id");
  if ($row = mysqli_fetch_assoc($res)) {
    @unlink('../uploads/'.$row['filename']);
  }
  mysqli_query($conn, "DELETE FROM produk_images WHERE id=$id");
}
header('Location: edit.php?id='.$pid);
exit;
