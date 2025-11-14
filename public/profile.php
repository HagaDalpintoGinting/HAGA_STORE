<?php
session_start();
include '../admin/config.php';

// Require login
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$uid = (int)($_SESSION['user_id']);
$info = null;
$msg = '';
$err = '';

// Ensure upload dir exists for avatars
$uploadDir = realpath(__DIR__ . '/../uploads');
if ($uploadDir === false) { $uploadDir = __DIR__ . '/../uploads'; }
$avatarDir = $uploadDir . '/avatars';
if (!is_dir($avatarDir)) {
  @mkdir($avatarDir, 0777, true);
}

// Helpers
function fetch_user($conn, $uid) {
  $result = null;
  if ($stmt = mysqli_prepare($conn, 'SELECT id, name, email, role, created_at, dob, gender, phone, avatar FROM users WHERE id = ? LIMIT 1')) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $name, $email, $role, $created, $dob, $gender, $phone, $avatar);
    if (mysqli_stmt_fetch($stmt)) {
      $result = [ 'id' => $id, 'name' => $name, 'email' => $email, 'role' => $role, 'created_at' => $created, 'dob' => $dob, 'gender' => $gender, 'phone' => $phone, 'avatar' => $avatar ];
    }
    mysqli_stmt_close($stmt);
  }
  return $result;
}

function fetch_addresses($conn, $uid) {
  $rows = [];
  if ($stmt = mysqli_prepare($conn, 'SELECT id, label, recipient_name, phone, address_text, city, postal_code, is_default FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC')) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
      while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
    }
    mysqli_stmt_close($stmt);
  }
  return $rows;
}

function fetch_payment($conn, $uid) {
  $row = null;
  if ($stmt = mysqli_prepare($conn, 'SELECT id, method, provider, account_name, account_number FROM user_payments WHERE user_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1')) {
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $method, $provider, $acc_name, $acc_number);
    if (mysqli_stmt_fetch($stmt)) {
      $row = ['id' => $id, 'method' => $method, 'provider' => $provider, 'account_name' => $acc_name, 'account_number' => $acc_number ];
    }
    mysqli_stmt_close($stmt);
  }
  return $row;
}

$activeTab = $_GET['tab'] ?? 'biodata';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'update_biodata') {
    // Load current values to support partial updates (e.g., avatar-only)
    $current = fetch_user($conn, $uid);
    $name = trim($_POST['name'] ?? ($current['name'] ?? ''));
    $email = trim($_POST['email'] ?? ($current['email'] ?? ''));
    $dob = $_POST['dob'] ?? ($current['dob'] ?? null);
    $gender = $_POST['gender'] ?? ($current['gender'] ?? null);
    $phone = trim($_POST['phone'] ?? ($current['phone'] ?? ''));
    $newpwd = $_POST['new_password'] ?? '';
    $confpwd = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '') {
      $err = 'Nama dan email wajib diisi.';
      $activeTab = 'biodata';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $err = 'Format email tidak valid.';
      $activeTab = 'biodata';
    } else {
      // Handle avatar upload if any
      $avatarFileName = null;
      if (!empty($_FILES['avatar']['name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
        $allowed = ['jpg','jpeg','png'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
          $err = 'Ekstensi file harus JPG, JPEG, atau PNG.';
          $activeTab = 'biodata';
        } elseif ($_FILES['avatar']['size'] > $maxSize) {
          $err = 'Ukuran file maksimal 2MB.';
          $activeTab = 'biodata';
        } else {
          $avatarFileName = 'avt_' . $uid . '_' . time() . '.' . $ext;
          @move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarDir . '/' . $avatarFileName);
        }
      }

      if ($err === '') {
        $sql = 'UPDATE users SET name = ?, email = ?, dob = ?, gender = ?, phone = ?' . ($avatarFileName ? ', avatar = ?' : '') . ' WHERE id = ?';
        if ($stmt = mysqli_prepare($conn, $sql)) {
          if ($avatarFileName) {
            mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $email, $dob, $gender, $phone, $avatarFileName, $uid);
          } else {
            mysqli_stmt_bind_param($stmt, 'sssssi', $name, $email, $dob, $gender, $phone, $uid);
          }
          if (mysqli_stmt_execute($stmt)) {
            if ($newpwd !== '' || $confpwd !== '') {
              if (strlen($newpwd) < 6) {
                $err = 'Password baru minimal 6 karakter.';
              } elseif ($newpwd !== $confpwd) {
                $err = 'Konfirmasi password baru tidak sama.';
              } else {
                $hash = password_hash($newpwd, PASSWORD_DEFAULT);
                if ($pstmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?')) {
                  mysqli_stmt_bind_param($pstmt, 'si', $hash, $uid);
                  if (!mysqli_stmt_execute($pstmt)) { $err = 'Gagal memperbarui password.'; }
                  mysqli_stmt_close($pstmt);
                }
              }
            }
            if ($err === '') {
              $msg = 'Biodata berhasil diperbarui.';
              $_SESSION['user_name'] = $name;
              $_SESSION['user_email'] = $email;
            }
          } else {
            if (mysqli_errno($conn) == 1062) { $err = 'Email sudah digunakan akun lain.'; }
            else { $err = 'Gagal menyimpan biodata.'; }
          }
          mysqli_stmt_close($stmt);
        } else { $err = 'Terjadi kesalahan server saat menyimpan biodata.'; }
      }
      $activeTab = 'biodata';
    }
  } elseif ($action === 'add_address') {
    $label = trim($_POST['label'] ?? '');
    $recipient = trim($_POST['recipient_name'] ?? '');
    $phone = trim($_POST['addr_phone'] ?? '');
    $address = trim($_POST['address_text'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');
    $is_def = isset($_POST['is_default']) ? 1 : 0;

    if ($recipient === '' || $phone === '' || $address === '' || $city === '' || $postal === '') {
      $err = 'Semua field alamat wajib diisi.';
    } else {
      if ($is_def) { @mysqli_query($conn, 'UPDATE user_addresses SET is_default = 0 WHERE user_id = ' . (int)$uid); }
      if ($stmt = mysqli_prepare($conn, 'INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_text, city, postal_code, is_default) VALUES (?,?,?,?,?,?,?,?)')) {
        mysqli_stmt_bind_param($stmt, 'issssssi', $uid, $label, $recipient, $phone, $address, $city, $postal, $is_def);
        if (mysqli_stmt_execute($stmt)) { $msg = 'Alamat berhasil ditambahkan.'; } else { $err = 'Gagal menambahkan alamat.'; }
        mysqli_stmt_close($stmt);
      } else { $err = 'Terjadi kesalahan server saat menambah alamat.'; }
    }
    $activeTab = 'alamat';
  } elseif ($action === 'delete_address') {
    $addrId = (int)($_POST['addr_id'] ?? 0);
    if ($addrId > 0) {
      if ($stmt = mysqli_prepare($conn, 'DELETE FROM user_addresses WHERE id = ? AND user_id = ?')) {
        mysqli_stmt_bind_param($stmt, 'ii', $addrId, $uid);
        if (mysqli_stmt_execute($stmt)) { $msg = 'Alamat dihapus.'; } else { $err = 'Gagal menghapus alamat.'; }
        mysqli_stmt_close($stmt);
      }
    }
    $activeTab = 'alamat';
  } elseif ($action === 'set_default_address') {
    $addrId = (int)($_POST['addr_id'] ?? 0);
    if ($addrId > 0) {
      @mysqli_query($conn, 'UPDATE user_addresses SET is_default = 0 WHERE user_id = ' . (int)$uid);
      if ($stmt = mysqli_prepare($conn, 'UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?')) {
        mysqli_stmt_bind_param($stmt, 'ii', $addrId, $uid);
        if (mysqli_stmt_execute($stmt)) { $msg = 'Alamat utama diperbarui.'; } else { $err = 'Gagal memperbarui alamat utama.'; }
        mysqli_stmt_close($stmt);
      }
    }
    $activeTab = 'alamat';
  } elseif ($action === 'save_payment') {
    $method = $_POST['method'] ?? 'cod';
    $provider = trim($_POST['provider'] ?? '');
    $acc_name = trim($_POST['account_name'] ?? '');
    $acc_number = trim($_POST['account_number'] ?? '');

    // Upsert: if exists update else insert
    $exist = fetch_payment($conn, $uid);
    if ($exist) {
      if ($stmt = mysqli_prepare($conn, 'UPDATE user_payments SET method = ?, provider = ?, account_name = ?, account_number = ? WHERE id = ? AND user_id = ?')) {
        $pid = (int)$exist['id'];
        mysqli_stmt_bind_param($stmt, 'ssssii', $method, $provider, $acc_name, $acc_number, $pid, $uid);
        if (mysqli_stmt_execute($stmt)) { $msg = 'Preferensi pembayaran disimpan.'; } else { $err = 'Gagal menyimpan preferensi pembayaran.'; }
        mysqli_stmt_close($stmt);
      }
    } else {
      if ($stmt = mysqli_prepare($conn, 'INSERT INTO user_payments (user_id, method, provider, account_name, account_number, is_primary) VALUES (?,?,?,?,?,1)')) {
        mysqli_stmt_bind_param($stmt, 'issss', $uid, $method, $provider, $acc_name, $acc_number);
        if (mysqli_stmt_execute($stmt)) { $msg = 'Preferensi pembayaran disimpan.'; } else { $err = 'Gagal menyimpan preferensi pembayaran.'; }
        mysqli_stmt_close($stmt);
      }
    }
    $activeTab = 'pembayaran';
  }
}

$info = fetch_user($conn, $uid);
$addresses = fetch_addresses($conn, $uid);
$payment = fetch_payment($conn, $uid);
if (!$info) { $err = $err ?: 'Data profil tidak ditemukan.'; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profil | HAGA STORE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="theme.css?v=1763094445" rel="stylesheet">
  <style>
    body { background: linear-gradient(145deg, #1a1a1a 0%, #2c0a05 100%); font-family: 'Quicksand', sans-serif; color: #eee; }
    .brand-title { font-family: 'Anton', sans-serif; letter-spacing: 2px; color: #ff3c00; text-shadow: 1px 1px 3px #000; }
    .card-glass { background: linear-gradient(135deg, rgba(26,26,26,0.5) 0%, rgba(44,10,5,0.5) 100%); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; box-shadow: 0 10px 30px rgba(255,60,0,0.1); backdrop-filter: blur(2px); }
    .form-control.bg-glass { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.2); }
    .form-control.bg-glass:focus { background: transparent; color: #fff; border-color: #ff3c00; box-shadow: 0 0 0 .25rem rgba(255,60,0,.15); }
    .form-control.bg-glass::placeholder { color: rgba(255,255,255,0.85); opacity: 1; }
    textarea.form-control.bg-glass::placeholder { color: rgba(255,255,255,0.85); }
    /* Themed select to match dark glass style */
    .form-select.bg-glass {
      background-color: transparent;
      color: #fff;
      border: 1px solid rgba(255,255,255,0.2);
      /* White chevron */
      --bs-form-select-bg-img: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='white' d='M3.204 5.096a.5.5 0 0 1 .707 0L8 9.185l4.09-4.09a.5.5 0 1 1 .707.707l-4.243 4.243a1 1 0 0 1-1.414 0L3.204 5.803a.5.5 0 0 1 0-.707z'/%3e%3c/svg%3e");
      background-image: var(--bs-form-select-bg-img), var(--bs-form-select-bg-icon, none);
    }
    .form-select.bg-glass:focus { background-color: transparent; color: #fff; border-color: #ff3c00; box-shadow: 0 0 0 .25rem rgba(255,60,0,.15); }
    .form-select.bg-glass option { background-color: #1e1e1e; color: #fff; }
    /* Buttons use global theme.css */
    a.link-light-orange { color: #ff7a52; text-decoration: none; }
    a.link-light-orange:hover { color: #ffa284; }
    .nav-tabs .nav-link { color: #ddd; }
    .nav-tabs .nav-link.active { background-color: rgba(255,255,255,0.06); color: #fff; border-color: rgba(255,255,255,0.1) rgba(255,255,255,0.1) transparent; }
    .list-tile { border-bottom: 1px solid rgba(255,255,255,0.08); padding: .6rem 0; display:flex; justify-content:space-between; gap:1rem; }
    .avatar { width: 160px; height: 160px; border-radius: 16px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); }
    .badge-verified { background: #1f8f4a; }
  </style>
</head>
<body style="padding-top: 2rem; padding-bottom: 2rem;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-10">
        <div class="p-3 p-md-4 card-glass">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
              <i class="bi bi-person-circle text-light me-2" style="font-size:1.6rem;"></i>
              <h1 class="h5 m-0 brand-title">Profil Saya</h1>
            </div>
            <a href="index.php" class="btn btn-sm btn-outline-theme"><i class="bi bi-house"></i> Kembali ke Home</a>
          </div>

          <?php if ($msg): ?>
            <div class="alert alert-success py-2" role="alert"><?= htmlspecialchars($msg) ?></div>
          <?php endif; ?>
          <?php if ($err): ?>
            <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($err) ?></div>
          <?php endif; ?>

          <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link <?= $activeTab==='biodata' ? 'active' : '' ?>" href="?tab=biodata">Biodata Diri</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link <?= $activeTab==='alamat' ? 'active' : '' ?>" href="?tab=alamat">Alamat Saya</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link <?= $activeTab==='pembayaran' ? 'active' : '' ?>" href="?tab=pembayaran">Pembayaran</a>
            </li>
          </ul>

          <?php if ($activeTab === 'biodata'): ?>
          <div class="row g-4">
            <div class="col-12 col-md-4">
              <div class="text-center">
                <?php
                  // Build offline-safe default avatar (SVG data URI) when no uploaded avatar
                  $defaultAvatarSvg = 'data:image/svg+xml;utf8,' . rawurlencode(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">'
                    . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#2c0a05"/><stop offset="100%" stop-color="#1a1a1a"/></linearGradient></defs>'
                    . '<rect width="300" height="300" rx="24" fill="url(#g)"/>'
                    . '<circle cx="150" cy="120" r="60" fill="#444" stroke="#666" stroke-width="4"/>'
                    . '<rect x="65" y="190" width="170" height="70" rx="20" fill="#444" stroke="#666" stroke-width="4"/>'
                    . '<text x="150" y="285" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" fill="#bbb">Avatar</text>'
                    . '</svg>'
                  );
                  $avatarRel = !empty($info['avatar']) ? ('../uploads/avatars/' . htmlspecialchars($info['avatar'])) : '';
                  $avatarAbs = (!empty($info['avatar'])) ? ($avatarDir . '/' . $info['avatar']) : '';
                  $avatarPath = ($avatarRel && is_file($avatarAbs)) ? $avatarRel : $defaultAvatarSvg;
                ?>
                <img src="<?= $avatarPath ?>" alt="Avatar" class="avatar mb-3">
                <form method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="update_biodata">
                  <div class="input-group">
                    <input class="form-control bg-glass" type="file" name="avatar" accept="image/png,image/jpeg">
                    <button class="btn btn-theme" type="submit">Pilih Foto</button>
                  </div>
                  <div class="form-text text-light mt-2">Maks 2MB. JPG, JPEG, PNG.</div>
                </form>
              </div>
            </div>
            <div class="col-12 col-md-8">
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_biodata">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control bg-glass" name="name" value="<?= htmlspecialchars($info['name'] ?? '') ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control bg-glass" name="email" value="<?= htmlspecialchars($info['email'] ?? '') ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" class="form-control bg-glass" name="dob" value="<?= htmlspecialchars($info['dob'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Jenis Kelamin</label>
                    <select class="form-select bg-glass" name="gender">
                      <?php $g = $info['gender'] ?? ''; ?>
                      <option value="" <?= $g===''?'selected':'' ?>>Pilih</option>
                      <option value="male" <?= $g==='male'?'selected':'' ?>>Laki-laki</option>
                      <option value="female" <?= $g==='female'?'selected':'' ?>>Perempuan</option>
                      <option value="other" <?= $g==='other'?'selected':'' ?>>Lainnya</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Nomor HP</label>
                    <input type="text" class="form-control bg-glass" name="phone" value="<?= htmlspecialchars($info['phone'] ?? '') ?>">
                  </div>
                  <div class="col-md-6"></div>
                  <div class="col-md-6">
                    <label class="form-label">Password Baru (opsional)</label>
                    <input type="password" class="form-control bg-glass" name="new_password" placeholder="********">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control bg-glass" name="confirm_password" placeholder="********">
                  </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                  <button type="submit" class="btn btn-theme">Simpan Perubahan</button>
                </div>
              </form>
              <div class="mt-4 small text-secondary">
                <div>Role: <?= htmlspecialchars($info['role'] ?? '-') ?></div>
                <div>Bergabung: <?= htmlspecialchars($info['created_at'] ?? '-') ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($activeTab === 'alamat'): ?>
          <div class="row g-4">
            <div class="col-12 col-lg-6">
              <h6 class="mb-3">Daftar Alamat</h6>
              <?php if (empty($addresses)): ?>
                <div class="text-secondary">Belum ada alamat. Tambahkan alamat baru di samping.</div>
              <?php else: foreach ($addresses as $a): ?>
                <div class="list-tile">
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars($a['recipient_name']) ?> <?= $a['is_default']?'<span class="badge bg-success ms-2">Utama</span>':'' ?></div>
                    <div class="small text-secondary"><?= htmlspecialchars($a['address_text']) ?>, <?= htmlspecialchars($a['city']) ?> <?= htmlspecialchars($a['postal_code']) ?> | <?= htmlspecialchars($a['phone']) ?></div>
                  </div>
                  <div class="d-flex gap-2">
                    <?php if (!$a['is_default']): ?>
                    <form method="POST">
                      <input type="hidden" name="action" value="set_default_address">
                      <input type="hidden" name="addr_id" value="<?= (int)$a['id'] ?>">
                      <button class="btn btn-sm btn-outline-theme" type="submit">Jadikan Utama</button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" class="form-hapus-alamat">
                      <input type="hidden" name="action" value="delete_address">
                      <input type="hidden" name="addr_id" value="<?= (int)$a['id'] ?>">
                      <button class="btn btn-sm btn-outline-theme" type="submit">Hapus</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; endif; ?>
            </div>
            <div class="col-12 col-lg-6">
              <h6 class="mb-3">Tambah Alamat Baru</h6>
              <form method="POST">
                <input type="hidden" name="action" value="add_address">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Label (opsional)</label>
                    <input type="text" class="form-control bg-glass" name="label" placeholder="Rumah/Kantor">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Nama Penerima</label>
                    <input type="text" class="form-control bg-glass" name="recipient_name" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Nomor HP</label>
                    <input type="text" class="form-control bg-glass" name="addr_phone" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Kota/Kabupaten</label>
                    <input type="text" class="form-control bg-glass" name="city" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea class="form-control bg-glass" name="address_text" rows="3" required></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" class="form-control bg-glass" name="postal_code" required>
                  </div>
                  <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                      <label class="form-check-label" for="is_default">Jadikan sebagai alamat utama</label>
                    </div>
                  </div>
                </div>
                <div class="text-end mt-3">
                  <button type="submit" class="btn btn-theme">Simpan Alamat</button>
                </div>
              </form>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($activeTab === 'pembayaran'): ?>
          <div class="row g-4">
            <div class="col-12 col-lg-7">
              <h6 class="mb-3">Preferensi Pembayaran</h6>
              <form method="POST">
                <input type="hidden" name="action" value="save_payment">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Metode</label>
                    <?php $m = $payment['method'] ?? 'cod'; ?>
                    <select class="form-select bg-glass" name="method">
                      <option value="cod" <?= $m==='cod'?'selected':'' ?>>COD (Bayar di tempat)</option>
                      <option value="bank" <?= $m==='bank'?'selected':'' ?>>Transfer Bank</option>
                      <option value="ewallet" <?= $m==='ewallet'?'selected':'' ?>>E-Wallet</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Penyedia</label>
                    <input type="text" class="form-control bg-glass" name="provider" value="<?= htmlspecialchars($payment['provider'] ?? '') ?>" placeholder="BCA/OVO/Gopay dsb.">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Nama Akun</label>
                    <input type="text" class="form-control bg-glass" name="account_name" value="<?= htmlspecialchars($payment['account_name'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Nomor Akun</label>
                    <input type="text" class="form-control bg-glass" name="account_number" value="<?= htmlspecialchars($payment['account_number'] ?? '') ?>">
                  </div>
                </div>
                <div class="text-end mt-3">
                  <button type="submit" class="btn btn-theme">Simpan</button>
                </div>
              </form>
            </div>
            <div class="col-12 col-lg-5">
              <div class="small text-secondary">Data ini akan digunakan untuk mempermudah proses pembayaran pada pesanan Anda.</div>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.querySelectorAll('.form-hapus-alamat').forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        
        Swal.fire({
          title: 'Hapus Alamat?',
          text: "Alamat ini akan dihapus secara permanen.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, Hapus!',
          cancelButtonText: 'Batal',
          customClass: {
            popup: 'swal2-popup',
            confirmButton: 'swal2-confirm',
            cancelButton: 'swal2-cancel'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            this.submit();
          }
        })
      });
    });
  </script>
</body>
</html>
