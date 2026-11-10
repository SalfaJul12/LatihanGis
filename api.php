<?php
header('Content-Type: application/json');
require_once 'config.php';

$userLat  = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$userLng  = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search   = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT p.*, k.nama_kategori 
        FROM penyedia_makanan p 
        JOIN kategori_makanan k ON p.id_kategori = k.id_kategori 
        WHERE 1=1";
$params = [];

if (!empty($kategori)) {
    $sql .= " AND p.id_kategori = ?";
    $params[] = $kategori;
}

if (!empty($search)) {
    $sql .= " AND (p.nama_penyedia LIKE ? OR p.alamat LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$penyediaList = $stmt->fetchAll();

$response = [];

foreach ($penyediaList as $p) {
    $stmtMenu = $pdo->prepare("SELECT nama_menu, harga FROM menu_makanan WHERE id_penyedia = ?");
    $stmtMenu->execute([$p['id_penyedia']]);
    $menuList = $stmtMenu->fetchAll();

    $distance = null;
    if ($userLat !== null && $userLng !== null) {
        $earthRadius = 6371;
        $dLat = deg2rad($p['latitude'] - $userLat);
        $dLng = deg2rad($p['longitude'] - $userLng);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($userLat)) * cos(deg2rad($p['latitude'])) *
             sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = round($earthRadius * $c, 2);
    }

    $p['menu'] = $menuList;
    $p['jarak_km'] = $distance;
    $response[] = $p;
}

if ($userLat !== null && $userLng !== null) {
    usort($response, function($a, $b) {
        return $a['jarak_km'] <=> $b['jarak_km'];
    });
}

echo json_encode($response);
?>