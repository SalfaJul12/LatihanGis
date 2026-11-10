<?php
require_once 'config.php';

if (!isset($_SESSION['penjual_id'])) {
    header('Location: login.php');
    exit;
}

$id_penjual   = $_SESSION['penjual_id'];
$nama_pemilik = $_SESSION['penjual_name'];
$msg = '';
$error = '';

// Ambil Toko Milik Penjual
$stmt = $pdo->prepare("SELECT * FROM penyedia_makanan WHERE id_penjual = ?");
$stmt->execute([$id_penjual]);
$toko = $stmt->fetch();

// Action: Tambah / Edit Data Toko
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_toko'])) {
    $nama_penyedia = trim($_POST['nama_penyedia']);
    $id_kategori   = intval($_POST['id_kategori']);
    $alamat        = trim($_POST['alamat']);
    $latitude      = trim($_POST['latitude']);
    $longitude     = trim($_POST['longitude']);
    $jam_buka      = $_POST['jam_buka'];
    $jam_tutup     = $_POST['jam_tutup'];
    $no_telepon    = trim($_POST['no_telepon']);
    $foto          = trim($_POST['foto']) ?: 'https://picsum.photos/300/200';

    if ($toko) {
        // Update Toko
        $sql = "UPDATE penyedia_makanan SET id_kategori=?, nama_penyedia=?, alamat=?, latitude=?, longitude=?, jam_buka=?, jam_tutup=?, no_telepon=?, foto=? WHERE id_penyedia=? AND id_penjual=?";
        $pdo->prepare($sql)->execute([$id_kategori, $nama_penyedia, $alamat, $latitude, $longitude, $jam_buka, $jam_tutup, $no_telepon, $foto, $toko['id_penyedia'], $id_penjual]);
        $msg = 'Informasi toko berhasil diperbarui.';
    } else {
        // Buat Toko Baru
        $sql = "INSERT INTO penyedia_makanan (id_penjual, id_kategori, nama_penyedia, alamat, latitude, longitude, jam_buka, jam_tutup, no_telepon, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$id_penjual, $id_kategori, $nama_penyedia, $alamat, $latitude, $longitude, $jam_buka, $jam_tutup, $no_telepon, $foto]);
        $msg = 'Toko baru berhasil terdaftar.';
    }

    // Refresh Data Toko
    $stmt->execute([$id_penjual]);
    $toko = $stmt->fetch();
}

// Action: Tambah Menu Makanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_menu']) && $toko) {
    $nama_menu = trim($_POST['nama_menu']);
    $harga     = floatval($_POST['harga']);

    if (!empty($nama_menu) && $harga > 0) {
        $stmtMenu = $pdo->prepare("INSERT INTO menu_makanan (id_penyedia, nama_menu, harga) VALUES (?, ?, ?)");
        $stmtMenu->execute([$toko['id_penyedia'], $nama_menu, $harga]);
        $msg = 'Menu makanan berhasil ditambahkan.';
    }
}

// Action: Hapus Menu Makanan
if (isset($_GET['delete_menu']) && $toko) {
    $id_menu = intval($_GET['delete_menu']);
    $pdo->prepare("DELETE FROM menu_makanan WHERE id_menu = ? AND id_penyedia = ?")->execute([$id_menu, $toko['id_penyedia']]);
    header('Location: admin.php');
    exit;
}

// Ambil Menu Toko Ini
$menuList = [];
if ($toko) {
    $stmtM = $pdo->prepare("SELECT * FROM menu_makanan WHERE id_penyedia = ?");
    $stmtM->execute([$toko['id_penyedia']]);
    $menuList = $stmtM->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Operasional Toko - FoodMap GIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { background-color: #f8fafc; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .navbar-custom { background-color: #0f172a; }
        #pickerMap { height: 280px; width: 100%; border-radius: 6px; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-2">
    <div class="container">
        <a class="navbar-brand fw-semibold text-white" href="index.php">FoodMap GIS</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-slate-300 small text-light">Pemilik: <?= htmlspecialchars($nama_pemilik) ?></span>
            <a href="login.php?action=logout" class="btn btn-outline-light btn-sm">Ganti Akun / Logout</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <?php if ($msg): ?>
        <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Profil Toko -->
        <div class="col-lg-7">
            <div class="card p-3">
                <h5 class="fw-bold mb-3"><?= $toko ? 'Edit Informasi Toko' : 'Pendaftaran Toko Baru' ?></h5>
                <form method="POST">
                    <input type="hidden" name="save_toko" value="1">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small text-secondary">Nama Tempat Makan</label>
                            <input type="text" name="nama_penyedia" class="form-control form-control-sm" value="<?= htmlspecialchars($toko['nama_penyedia'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">Kategori</label>
                            <select name="id_kategori" class="form-select form-select-sm" required>
                                <option value="1" <?= ($toko['id_kategori'] ?? 0) == 1 ? 'selected' : '' ?>>Restoran</option>
                                <option value="2" <?= ($toko['id_kategori'] ?? 0) == 2 ? 'selected' : '' ?>>Kafe</option>
                                <option value="3" <?= ($toko['id_kategori'] ?? 0) == 3 ? 'selected' : '' ?>>Warung Makan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-secondary">Alamat Lengkap</label>
                            <input type="text" name="alamat" class="form-control form-control-sm" value="<?= htmlspecialchars($toko['alamat'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">Jam Buka</label>
                            <input type="time" name="jam_buka" class="form-control form-control-sm" value="<?= $toko['jam_buka'] ?? '08:00' ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">Jam Tutup</label>
                            <input type="time" name="jam_tutup" class="form-control form-control-sm" value="<?= $toko['jam_tutup'] ?? '21:00' ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">No. Telepon</label>
                            <input type="text" name="no_telepon" class="form-control form-control-sm" value="<?= htmlspecialchars($toko['no_telepon'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-secondary">URL Foto Tempat Makan</label>
                            <input type="url" name="foto" class="form-control form-control-sm" value="<?= htmlspecialchars($toko['foto'] ?? 'https://picsum.photos/300/200') ?>">
                        </div>
                        
                        <!-- Map Picker -->
                        <div class="col-12">
                            <label class="form-label small text-secondary">Tentukan Lokasi Koordinat (Klik pada Peta)</label>
                            <div id="pickerMap"></div>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="latitude" name="latitude" class="form-control form-control-sm bg-light" value="<?= $toko['latitude'] ?? '-6.917464' ?>" readonly required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="longitude" name="longitude" class="form-control form-control-sm bg-light" value="<?= $toko['longitude'] ?? '107.619123' ?>" readonly required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-dark btn-sm fw-medium w-100">Simpan Data Toko</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kelola Menu Toko -->
        <div class="col-lg-5">
            <div class="card p-3 mb-3">
                <h5 class="fw-bold mb-3">Tambah Menu Makanan</h5>
                <?php if ($toko): ?>
                    <form method="POST">
                        <input type="hidden" name="add_menu" value="1">
                        <div class="mb-2">
                            <input type="text" name="nama_menu" class="form-control form-control-sm" placeholder="Nama Menu" required>
                        </div>
                        <div class="mb-2">
                            <input type="number" name="harga" class="form-control form-control-sm" placeholder="Harga (Rp)" required>
                        </div>
                        <button type="submit" class="btn btn-outline-dark btn-sm w-100">Tambah Menu</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">Simpan informasi toko terlebih dahulu sebelum menambahkan daftar menu.</p>
                <?php endif; ?>
            </div>

            <div class="card p-3">
                <h6 class="fw-bold mb-2">Daftar Menu Saat Ini</h6>
                <?php if (!empty($menuList)): ?>
                    <ul class="list-group list-group-flush border-top">
                        <?php foreach ($menuList as $m): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 small">
                                <div>
                                    <span class="fw-medium text-dark"><?= htmlspecialchars($m['nama_menu']) ?></span>
                                    <div class="text-muted">Rp <?= number_format($m['harga'], 0, ',', '.') ?></div>
                                </div>
                                <a href="admin.php?delete_menu=<?= $m['id_menu'] ?>" class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="return confirm('Hapus menu ini?')">Hapus</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-0">Belum ada menu yang terdaftar.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let pickerMap;
    let pickerMarker;

    function initPickerMap() {
        const initLat = parseFloat(document.getElementById('latitude').value);
        const initLng = parseFloat(document.getElementById('longitude').value);

        pickerMap = L.map('pickerMap').setView([initLat, initLng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(pickerMap);

        pickerMarker = L.marker([initLat, initLng]).addTo(pickerMap);

        pickerMap.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(8);
            const lng = e.latlng.lng.toFixed(8);

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            if (pickerMarker) pickerMap.removeLayer(pickerMarker);
            pickerMarker = L.marker([lat, lng]).addTo(pickerMap);
        });
    }

    window.onload = initPickerMap;
</script>
</body>
</html>