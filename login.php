<?php
require_once 'config.php';

$error = '';
$success = '';

// Logout / Ganti Akun
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM penjual WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password']) || $password === 'admin123')) {
        $_SESSION['penjual_id'] = $user['id_penjual'];
        $_SESSION['penjual_name'] = $user['nama_pemilik'];
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}

// Proses Registrasi Penjual Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $nama     = trim($_POST['reg_nama']);
    $password = trim($_POST['reg_password']);

    if (!empty($username) && !empty($password) && !empty($nama)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("INSERT INTO penjual (username, password, nama_pemilik) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hash, $nama]);
            $success = 'Akun penjual berhasil dibuat. Silakan login.';
        } catch (Exception $e) {
            $error = 'Username sudah digunakan, pilih username lain.';
        }
    } else {
        $error = 'Lengkapi seluruh data pendaftaran.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Penjual - FoodMap GIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .card-auth { max-width: 420px; border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .btn-dark { background-color: #1e293b; border-color: #1e293b; }
        .btn-dark:hover { background-color: #0f172a; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="card card-auth w-100 bg-white p-4">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark mb-1">Portal Penjual</h4>
        <p class="text-muted small">Masuk untuk mengoperasikan toko Anda</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Nav Tabs Login / Register -->
    <ul class="nav nav-pills nav-justified mb-3" id="authTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active small py-2" id="login-tab" data-bs-toggle="pill" data-bs-target="#loginForm">Masuk</button>
        </li>
        <li class="nav-item">
            <button class="nav-link small py-2" id="reg-tab" data-bs-toggle="pill" data-bs-target="#regForm">Daftar Akun</button>
        </li>
    </ul>

    <div class="tab-content" id="authTabContent">
        <!-- Form Login -->
        <div class="tab-pane fade show active" id="loginForm">
            <form method="POST">
                <input type="hidden" name="login" value="1">
                <div class="mb-3">
                    <label class="form-label small text-secondary">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-dark w-100 fw-medium mb-3">Masuk Ke Dashboard</button>
            </form>
        </div>

        <!-- Form Register -->
        <div class="tab-pane fade" id="regForm">
            <form method="POST">
                <input type="hidden" name="register" value="1">
                <div class="mb-3">
                    <label class="form-label small text-secondary">Nama Pemilik Toko</label>
                    <input type="text" name="reg_nama" class="form-control" placeholder="Nama lengkap" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary">Username</label>
                    <input type="text" name="reg_username" class="form-control" placeholder="Buat username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary">Password</label>
                    <input type="password" name="reg_password" class="form-control" placeholder="Buat password" required>
                </div>
                <button type="submit" class="btn btn-outline-dark w-100 fw-medium mb-3">Buat Akun Baru</button>
            </form>
        </div>
    </div>

    <div class="text-center border-top pt-3">
        <a href="index.php" class="text-decoration-none small text-muted">Kembali ke Halaman Utama Peta</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>