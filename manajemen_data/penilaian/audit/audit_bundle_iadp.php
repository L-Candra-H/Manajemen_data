<?php
session_start();
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

if (!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

$conn = bukakoneksi();

// === AMBIL DATA PEGAWAI AKTIF ===
$qPegawaiTambah = $conn->query("SELECT nik, nama, jbtn 
                                FROM pegawai 
                                WHERE stts_aktif='AKTIF' 
                                ORDER BY nik ASC");
$qPegawaiEdit   = $conn->query("SELECT nik, nama, jbtn 
                                FROM pegawai 
                                WHERE stts_aktif='AKTIF' 
                                ORDER BY nik ASC");

// === PROSES CRUD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah') {
        $tanggal_hari = $_POST['tanggal_hari'];
        $tanggal_jam  = $_POST['tanggal_jam'];
        $tanggal      = $tanggal_hari . ' ' . $tanggal_jam;
        $nik          = $_POST['nik'];
        $handhygiene  = $_POST['handhygiene'];
        $apd          = $_POST['apd'];
        $skin         = $_POST['skin_antiseptik'];
        $lokasi       = $_POST['lokasi_iv'];
        $perawatan    = $_POST['perawatan_rutin'];

        $sql = "INSERT INTO audit_bundle_iadp 
                (tanggal, nik, handhygiene, apd, skin_antiseptik, 
                  lokasi_iv, perawatan_rutin) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $tanggal, $nik, $handhygiene, $apd, $skin, $lokasi, $perawatan);
        $stmt->execute();
    }

    if ($aksi === 'update') {
        $tanggal_hari = $_POST['tanggal_hari'];
        $tanggal_jam  = $_POST['tanggal_jam'];
        $tanggal      = $tanggal_hari . ' ' . $tanggal_jam;
        $nik          = $_POST['nik'];
        $handhygiene  = $_POST['handhygiene'];
        $apd          = $_POST['apd'];
        $skin         = $_POST['skin_antiseptik'];
        $lokasi       = $_POST['lokasi_iv'];
        $perawatan    = $_POST['perawatan_rutin'];

        // PK = tanggal+nik
        $old_nik      = $_POST['old_nik'];
        $old_tanggal  = $_POST['old_tanggal'];

        $sql = "UPDATE audit_bundle_iadp SET 
                    tanggal=?, nik=?, 
                    handhygiene=?, apd=?, skin_antiseptik=?,
                    lokasi_iv=?, perawatan_rutin=?  
                WHERE nik=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $tanggal, $nik, $handhygiene, $apd, $skin, $lokasi, 
                          $perawatan, $old_nik, $old_tanggal);
        $stmt->execute();
    }

    if ($aksi === 'hapus') {
        $nik      = $_POST['nik'];
        $tanggal  = $_POST['tanggal'];

        $sql = "DELETE FROM audit_bundle_iadp WHERE nik=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nik, $tanggal);
        $stmt->execute();

        header("Location: audit_bundle_iadp.php");
        exit;
    }
}

// === FILTER RENTANG TANGGAL ===
$awalForm = $_GET['awal'] ?? '';
$akhirForm = $_GET['akhir'] ?? '';

$where = "WHERE 1=0";
$awalDb = $akhirDb = null;

if ($awalForm && $akhirForm) {
    $awalDb = $awalForm . " 00:00:00";
    $akhirDb = $akhirForm . " 23:59:59";
    $where = "WHERE a.tanggal BETWEEN ? AND ?";
}

// === PAGINATION ===
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// === QUERY DATA AUDIT ===
$sqlData = "SELECT a.*, p.nama, p.jbtn 
            FROM audit_bundle_iadp a 
            LEFT JOIN pegawai p ON a.nik = p.nik 
            $where 
            ORDER BY a.tanggal ASC 
            LIMIT ?, ?";
$stmt = $conn->prepare($sqlData);

if ($awalForm && $akhirForm) {
    $stmt->bind_param("ssii", $awalDb, $akhirDb, $offset, $limit);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

// === HITUNG TOTAL DATA ===
$sqlCount = "SELECT COUNT(*) AS total FROM audit_bundle_iadp " . ($awalForm && $akhirForm ? "WHERE tanggal BETWEEN ? AND ?" : "WHERE 1=0");
$stmtCount = $conn->prepare($sqlCount);
if ($awalForm && $akhirForm) {
    $stmtCount->bind_param("ss", $awalDb, $akhirDb);
}
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// === QUERY REKAP YA/TIDAK/RATA-RATA ===
$sqlRekap = "SELECT
    SUM(handhygiene='Ya') AS hh_ya,
    SUM(handhygiene='Tidak') AS hh_tidak,
    SUM(apd='Ya') AS apd_ya,
    SUM(apd='Tidak') AS apd_tidak,
    SUM(skin_antiseptik='Ya') AS skin_ya,
    SUM(skin_antiseptik='Tidak') AS skin_tidak,
    SUM(lokasi_iv='Ya') AS lokasi_ya,
    SUM(lokasi_iv='Tidak') AS lokasi_tidak,
    SUM(perawatan_rutin='Ya') AS perawatan_ya,
    SUM(perawatan_rutin='Tidak') AS perawatan_tidak,
    COUNT(*) AS total
    FROM audit_bundle_iadp 
    " . ($awalForm && $akhirForm ? "WHERE tanggal BETWEEN ? AND ?" : "WHERE 1=0");

$stmtRekap = $conn->prepare($sqlRekap);
if ($awalForm && $akhirForm) {
    $stmtRekap->bind_param("ss", $awalDb, $akhirDb);
}
$stmtRekap->execute();
$rekap = $stmtRekap->get_result()->fetch_assoc();

// === HITUNG TTL ===
$totalYa = $rekap['hh_ya'] + $rekap['apd_ya'] + $rekap['skin_ya'] + $rekap['lokasi_ya'] + $rekap['perawatan_ya'];
$totalTidak = $rekap['hh_tidak'] + $rekap['apd_tidak'] + $rekap['skin_tidak'] + $rekap['lokasi_tidak'] + $rekap['perawatan_tidak'];
$totalItem = $rekap['total'] * 5;

$ttlYaPersen    = $totalItem > 0 ? round(($totalYa / $totalItem) * 100) : 0;
$ttlTidakPersen = $totalItem > 0 ? round(($totalTidak / $totalItem) * 100) : 0;
$ttlRataPersen = $totalItem > 0 ? round(($totalYa / $totalItem) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Audit Bundle IADP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="audit.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="main-content">
    <div class="container-fluid mt-4">
      <div class="card shadow">
        <!-- Header -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Audit Bundle IADP</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
            <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <!-- Body -->
        <div class="card-body p-3">
          <!-- Filter Rentang Tanggal -->
          <form method="get" class="row g-2 mb-3">
            <div class="col-auto">
              <label class="form-label">Tanggal :</label>
              <input type="date" name="awal" value="<?= htmlspecialchars($awalForm) ?>" class="form-control form-control-sm d-inline w-auto">
              <input type="date" name="akhir" value="<?= htmlspecialchars($akhirForm) ?>" class="form-control form-control-sm d-inline w-auto">
              <button type="submit" class="btn btn-success btn-sm">Terapkan</button>
            </div>
          </form>

          <!-- Tabel -->
          <div class="table-wrapper">
            <table class="table table-striped table-bordered table-master_audit align-middle">
              <thead class="table-dark text-center">
                <tr>
                  <th>Tanggal Audit</th>
                  <th>NIP/Kode</th>
                  <th>Dokter/Paramedis</th>
                  <th>1. Handhygiene</th>
                  <th>2. APD</th>
                  <th>3. Skin Antiseptik</th>
                  <th>4. Lokasi IV</th>
                  <th>5. Perawatan Rutin</th>
                  <th>Ttl. Nilai (%)</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($result->num_rows == 0): ?>
                  <tr>
                    <td colspan="10" class="text-center text-muted">Silakan pilih periode untuk menampilkan data</td>
                  </tr>
                <?php else: ?>
                  <?php while($row = $result->fetch_assoc()):
                    $items = [
                      'handhygiene',
                      'apd',
                      'skin_antiseptik',
                      'lokasi_iv',
                      'perawatan_rutin'
                    ];
                    $yaCount = 0;
                    foreach($items as $i){ if($row[$i] === 'Ya') $yaCount++; }
                    $ttlNilai = round(($yaCount / count($items)) * 100);
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?> (<?= htmlspecialchars($row['jbtn']) ?>)</td>
                    <td><?= $row['handhygiene'] ?></td>
                    <td><?= $row['apd'] ?></td>
                    <td><?= $row['skin_antiseptik'] ?></td>
                    <td><?= $row['lokasi_iv'] ?></td>
                    <td><?= $row['perawatan_rutin'] ?></td>
                    <td><?= $ttlNilai ?>%</td>
                    <td class="text-center">
                      <!-- Tombol Edit -->
                      <button type="button" class="btn btn-warning btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                        data-nik="<?= $row['nik'] ?>"
                        data-nama="<?= $row['nama'] ?>"
                        data-jbtn="<?= $row['jbtn'] ?>"
                        data-tanggal="<?= $row['tanggal'] ?>"
                        data-handhygiene="<?= $row['handhygiene'] ?>"
                        data-apd="<?= $row['apd'] ?>"
                        data-skin="<?= $row['skin_antiseptik'] ?>"
                        data-lokasi="<?= $row['lokasi_iv'] ?>"
                        data-perawatan="<?= $row['perawatan_rutin'] ?>">
                        ✏️ Edit
                      </button>
                      <!-- Tombol Hapus -->
                      <form method="post" action="" style="display:inline">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="nik" value="<?= $row['nik'] ?>">
                        <input type="hidden" name="tanggal" value="<?= $row['tanggal'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
                      </form>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                  <?php if ($page == $totalPages): ?>
                    <tr class="table-success">
                      <td colspan="3">Ya</td>
                      <td><?= $rekap['hh_ya'] ?></td>
                      <td><?= $rekap['apd_ya'] ?></td>
                      <td><?= $rekap['skin_ya'] ?></td>
                      <td><?= $rekap['lokasi_ya'] ?></td>
                      <td><?= $rekap['perawatan_ya'] ?></td>
                      <td><?= $totalYa ?></td>
                      <td></td>
                    </tr>
                    <tr class="table-danger">
                      <td colspan="3">Tidak</td>
                      <td><?= $rekap['hh_tidak'] ?></td>
                      <td><?= $rekap['apd_tidak'] ?></td>
                      <td><?= $rekap['skin_tidak'] ?></td>
                      <td><?= $rekap['lokasi_tidak'] ?></td>
                      <td><?= $rekap['perawatan_tidak'] ?></td>
                      <td><?= $totalTidak ?></td>
                      <td></td>
                    </tr>
                    <tr class="table-info">
                      <td colspan="3">Rata-rata (%)</td>
                      <td><?= $rekap['total']>0 ? round(($rekap['hh_ya']/$rekap['total'])*100) : 0 ?>%</td>
                      <td><?= $rekap['total']>0 ? round(($rekap['apd_ya']/$rekap['total'])*100) : 0 ?>%</td>
                      <td><?= $rekap['total']>0 ? round(($rekap['skin_ya']/$rekap['total'])*100) : 0 ?>%</td>
                      <td><?= $rekap['total']>0 ? round(($rekap['lokasi_ya']/$rekap['total'])*100) : 0 ?>%</td>
                      <td><?= $rekap['total']>0 ? round(($rekap['perawatan_ya']/$rekap['total'])*100) : 0 ?>%</td>
                      <td><?= $ttlRataPersen ?>%</td>
                      <td></td>
                    </tr>
                  <?php endif; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&awal=<?= urlencode($awalForm) ?>&akhir=<?= urlencode($akhirForm) ?>">« Prev</a>
              </li>
              <?php
                $startPage = max(1, $page - 1);
                $endPage   = min($totalPages, $page + 1);
                for ($i = $startPage; $i <= $endPage; $i++):
              ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&awal=<?= urlencode($awalForm) ?>&akhir=<?= urlencode($akhirForm) ?>"><?= $i ?></a>
              </li>
              <?php endfor; ?>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&awal=<?= urlencode($awalForm) ?>&akhir=<?= urlencode($akhirForm) ?>">Next »</a>
              </li>
            </ul>
          </nav>

        </div>
      </div>
    </div>
  </main>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="tambah">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Tambah Audit Bundle IADP</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Tanggal Audit & Petugas -->
            <div class="d-flex mb-3 align-items-center gap-3 flex-wrap">
              <label>Tanggal:</label>
              <input type="date" name="tanggal_hari" class="form-control" style="width:120px" required>
              <input type="time" name="tanggal_jam" class="form-control" style="width:80px" required>

              <label>Petugas:</label>
              <select name="nik" id="nikSelect" class="form-select" style="width:170px" required>
                <option value="">-- Pilih --</option>
                <?php while($p=$qPegawaiTambah->fetch_assoc()): ?>
                  <option value="<?= $p['nik'] ?>" data-nama="<?= $p['nama'] ?>" data-jbtn="<?= $p['jbtn'] ?>">
                    <?= $p['nik'] ?> - <?= $p['nama'] ?>
                  </option>
                <?php endwhile; ?>
              </select>

              <input type="text" name="nama" id="namaField" 
                     class="form-control bg-secondary text-white me-2" 
                     readonly style="width:180px">
            </div>

            <!-- Bundles -->
            <h6 class="mt-3 mb-2">BUNDLES :</h6>
            <div class="item-audit">

            <div class="row g-3 mb-2">
              <div class="col-md-4 d-flex align-items-center gap-3">
                <label class="flex-grow-1">1. Handhygiene</label>
                <select name="handhygiene" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center gap-3">
                <label class="flex-grow-1">2. APD</label>
                <select name="apd" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center gap-3">
                <label class="flex-grow-1">3. Skin Antiseptik</label>
                <select name="skin_antiseptik" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
            </div>
            <div class="row g-3 mb-2">
              <div class="col-md-4 d-flex align-items-center gap-3">
                <label class="flex-grow-1">4. Lokasi IV</label>
                <select name="lokasi_iv" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center gap-3">
                <label class="flex-grow-1">5. Perawatan Rutin</label>
                <select name="perawatan_rutin" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
            </div>
          </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="update">
          <input type="hidden" name="old_nik" id="old_nik">
          <input type="hidden" name="old_tanggal" id="old_tanggal">

          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Edit Audit Bundle IADP</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Tanggal Audit & Petugas -->
            <div class="d-flex mb-3 align-items-center gap-3 flex-wrap">
              <label>Tanggal :</label>
              <input type="date" name="tanggal_hari" id="edit_tanggal_hari" class="form-control" style="width:120px" required>
              <input type="time" name="tanggal_jam" id="edit_tanggal_jam" class="form-control" style="width:80px" required>
              <label>Petugas :</label>
              <select name="nik" id="edit_nik" class="form-select" style="width:170px" required>
                <option value="">-- Pilih --</option>
                <?php while($p=$qPegawaiEdit->fetch_assoc()): ?>
                <option value="<?= $p['nik'] ?>" data-nama="<?= $p['nama'] ?>" data-jbtn="<?= $p['jbtn'] ?>">
                  <?= $p['nik'] ?> - <?= $p['nama'] ?>
                </option>
                <?php endwhile; ?>
              </select>
              <input type="text" id="edit_nama" class="form-control bg-secondary text-white me-2" readonly style="width:180px">

            </div>

            <!-- Bundles -->
            <h6 class="mt-3 mb-2">BUNDLES :</h6>
            <div class="item-audit">
            <div class="row g-3 mb-2">
              <div class="col-md-4 d-flex align-items-center gap-3">
                <label class="flex-grow-1">1. Handhygiene</label>
                <select name="handhygiene" id="edit_handhygiene" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
               </div>
              <div class="col-md-4 d-flex align-items-center gap-3">
                <label class="flex-grow-1">2. APD</label>
                <select name="apd" id="edit_apd" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center gap-3">
                <label class="flex-grow-1">3. Skin Antiseptik</label>
                <select name="skin_antiseptik" id="edit_skin" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                  </select>
                </div>
              </div>
              <div class="row g-3 mb-2">
                <div class="col-md-4 d-flex align-items-center gap-3">
                  <label class="flex-grow-1">4. Lokasi IV</label>
                  <select name="lokasi_iv" id="edit_lokasi" class="form-select w-auto">
                    <option value="Ya">Ya</option>
                    <option value="Tidak">Tidak</option>
                  </select>
                </div>
                <div class="col-md-4 d-flex align-items-center gap-3">
                  <label class="flex-grow-1">5. Perawatan Rutin</label>
                  <select name="perawatan_rutin" id="edit_perawatan" class="form-select w-auto">
                    <option value="Ya">Ya</option>
                    <option value="Tidak">Tidak</option>
                </select>
              </div>
            </div>
          </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-warning">💾 Update</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", function() {
    const nikSelect = document.getElementById("nikSelect");
    const namaField = document.getElementById("namaField");
    if(nikSelect){
      nikSelect.addEventListener("change", function() {
        const selectedOption = nikSelect.options[nikSelect.selectedIndex];
        const nama = selectedOption.getAttribute("data-nama") || "";
        namaField.value = nama;
      });
    }
  });

  document.addEventListener("DOMContentLoaded", function(){
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;

      // Ambil data dari atribut tombol Edit
      var nik = button.getAttribute('data-nik');
      var nama = button.getAttribute('data-nama');
      var tanggal = button.getAttribute('data-tanggal');
      var handhygiene = button.getAttribute('data-handhygiene');
      var apd = button.getAttribute('data-apd');
      var skin = button.getAttribute('data-skin');
      var lokasi = button.getAttribute('data-lokasi');
      var perawatan = button.getAttribute('data-perawatan');

      // Pisahkan tanggal jadi hari + jam (format: YYYY-MM-DD HH:MM:SS)
      var parts = tanggal.split(' ');
      var hari = parts[0];
      var jam = parts[1];

      // Isi field modal
      document.getElementById('edit_tanggal_hari').value = hari;
      document.getElementById('edit_tanggal_jam').value = jam;
      document.getElementById('edit_nik').value = nik;
      document.getElementById('edit_nama').value = nama;

      document.getElementById('edit_handhygiene').value = handhygiene;
      document.getElementById('edit_apd').value = apd;
      document.getElementById('edit_skin').value = skin;
      document.getElementById('edit_lokasi').value = lokasi;
      document.getElementById('edit_perawatan').value = perawatan;

      // Simpan PK lama
      document.getElementById('old_nik').value = nik;
      document.getElementById('old_tanggal').value = tanggal;
    });
  });
  </script>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
