<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodMap GIS - Pencarian Penyedia Makanan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet & Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        .navbar-brand { font-weight: 600; letter-spacing: -0.5px; }
        #map { height: 78vh; width: 100%; border-radius: 8px; border: 1px solid #e2e8f0; z-index: 1; }
        .sidebar { height: 78vh; overflow-y: auto; }
        .provider-card { cursor: pointer; border: 1px solid #e2e8f0; border-radius: 6px; transition: all 0.2s ease; }
        .provider-card:hover { border-color: #94a3b8; background-color: #ffffff; transform: translateY(-1px); }
        .leaflet-routing-container { display: none !important; }
        .btn-dark-custom { background-color: #0f172a; color: #ffffff; border: none; }
        .btn-dark-custom:hover { background-color: #1e293b; color: #ffffff; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand text-white" href="index.php">FoodMap GIS</a>
        <div class="ms-auto">
            <a href="admin.php" class="btn btn-outline-light btn-sm px-3">Portal Penjual</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 my-3">
    <!-- Search & Filter Controls -->
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cari nama tempat makan atau alamat...">
        </div>
        <div class="col-md-3">
            <select id="kategoriSelect" class="form-select form-select-sm">
                <option value="">Semua Kategori</option>
                <option value="1">Restoran</option>
                <option value="2">Kafe</option>
                <option value="3">Warung Makan</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-dark-custom btn-sm w-100" onclick="getUserLocation()">Gunakan Lokasi Saya</button>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary btn-sm w-100" onclick="resetFilter()">Reset Peta & Filter</button>
        </div>
    </div>

    <div class="row g-3">
        <!-- Sidebar Tempat Makan -->
        <div class="col-md-4">
            <div class="card p-2 sidebar border-0 bg-transparent" id="providerList">
                <p class="text-muted text-center small mt-4">Memuat data tempat makan...</p>
            </div>
        </div>

        <!-- Canvas Peta Utama -->
        <div class="col-md-8">
            <div id="map"></div>
        </div>
    </div>
</div>

<!-- Modal Detail Toko & Rute In-App -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold" id="modalNama">Detail Tempat Makan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <img id="modalFoto" src="" class="img-fluid rounded mb-3 w-100" style="max-height: 180px; object-fit: cover;">
                <p class="mb-1 small"><strong>Alamat:</strong> <span id="modalAlamat" class="text-secondary"></span></p>
                <p class="mb-1 small"><strong>Jam Operasional:</strong> <span id="modalJam" class="text-secondary"></span></p>
                <p class="mb-1 small"><strong>Telepon:</strong> <span id="modalTelp" class="text-secondary"></span></p>
                <hr class="my-2">
                <h6 class="fw-semibold small mb-2">Daftar Menu</h6>
                <ul id="modalMenu" class="list-group list-group-flush mb-3"></ul>
                
                <button id="btnRouteMap" class="btn btn-dark-custom btn-sm w-100 fw-medium">Tampilkan Rute Ke Toko Ini</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script>
    let map;
    let markersLayer = L.layerGroup();
    let userMarker = null;
    let userPos = null;
    let routingControl = null;

    function initMap() {
        map = L.map('map').setView([-6.917464, 107.619123], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        markersLayer.addTo(map);
        loadData();
    }

    function getUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userPos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    if (userMarker) map.removeLayer(userMarker);

                    userMarker = L.circleMarker([userPos.lat, userPos.lng], {
                        radius: 7,
                        fillColor: "#0f172a",
                        color: "#ffffff",
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.9
                    }).addTo(map).bindPopup("Lokasi Saya").openPopup();

                    map.setView([userPos.lat, userPos.lng], 14);
                    loadData();
                },
                () => alert("Gagal mengambil posisi lokasi browser.")
            );
        } else {
            alert("Browser tidak mendukung geolokalisa.");
        }
    }

    function resetFilter() {
        document.getElementById('searchInput').value = '';
        document.getElementById('kategoriSelect').value = '';
        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }
        loadData();
    }

    function loadData() {
        const search = document.getElementById('searchInput').value;
        const kategori = document.getElementById('kategoriSelect').value;
        
        let url = `api.php?search=${encodeURIComponent(search)}&kategori=${kategori}`;
        if (userPos) {
            url += `&lat=${userPos.lat}&lng=${userPos.lng}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                markersLayer.clearLayers();
                const listContainer = document.getElementById('providerList');
                listContainer.innerHTML = '';

                if (data.length === 0) {
                    listContainer.innerHTML = '<p class="text-center text-muted small mt-4">Penyedia makanan tidak ditemukan.</p>';
                    return;
                }

                data.forEach((p) => {
                    const lat = parseFloat(p.latitude);
                    const lng = parseFloat(p.longitude);

                    const marker = L.marker([lat, lng]);
                    marker.bindTooltip(p.nama_penyedia, { permanent: false, direction: 'top' });
                    marker.on('click', () => showDetailModal(p));
                    markersLayer.addLayer(marker);

                    const distanceBadge = p.jarak_km !== null ? `<span class="badge bg-secondary font-monospace">${p.jarak_km} km</span>` : '';
                    const card = document.createElement('div');
                    card.className = 'card mb-2 provider-card p-3 bg-white';
                    card.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="fw-bold mb-1 text-dark">${p.nama_penyedia}</h6>
                            ${distanceBadge}
                        </div>
                        <small class="text-muted d-block mb-1">${p.nama_kategori} • ${p.jam_buka} - ${p.jam_tutup}</small>
                        <small class="text-secondary text-truncate d-block">${p.alamat}</small>
                    `;
                    card.onclick = () => {
                        map.setView([lat, lng], 16);
                        showDetailModal(p);
                    };
                    listContainer.appendChild(card);
                });
            });
    }

    function showDetailModal(p) {
        document.getElementById('modalNama').innerText = p.nama_penyedia;
        document.getElementById('modalFoto').src = p.foto;
        document.getElementById('modalAlamat').innerText = p.alamat;
        document.getElementById('modalJam').innerText = `${p.jam_buka} - ${p.jam_tutup}`;
        document.getElementById('modalTelp').innerText = p.no_telepon || '-';

        const menuList = document.getElementById('modalMenu');
        menuList.innerHTML = '';
        p.menu.forEach(m => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center px-0 py-1 small border-0';
            li.innerHTML = `<span>${m.nama_menu}</span> <strong class="text-dark">Rp ${parseInt(m.harga).toLocaleString('id-ID')}</strong>`;
            menuList.appendChild(li);
        });

        // Rute Langsung di Dalam Peta Aplikasi
        document.getElementById('btnRouteMap').onclick = () => {
            if (!userPos) {
                alert("Silakan klik tombol 'Gunakan Lokasi Saya' terlebih dahulu untuk menentukan titik awal rute Anda!");
                return;
            }

            if (routingControl) map.removeControl(routingControl);

            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(userPos.lat, userPos.lng),
                    L.latLng(parseFloat(p.latitude), parseFloat(p.longitude))
                ],
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1'
                }),
                lineOptions: {
                    styles: [{ color: '#0f172a', weight: 4, opacity: 0.8 }]
                },
                createMarker: () => null
            }).addTo(map);

            bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
        };

        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        modal.show();
    }

    document.getElementById('searchInput').addEventListener('input', loadData);
    document.getElementById('kategoriSelect').addEventListener('change', loadData);

    window.onload = initMap;
</script>
</body>
</html>