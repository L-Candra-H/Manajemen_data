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
        $tindakan     = $_POST['tindakan'];
        $nik          = $_POST['nik'];
        $topi         = $_POST['topi'];
        $masker       = $_POST['masker'];
        $kacamata     = $_POST['kacamata'];
        $sarungtangan = $_POST['sarungtangan'];
        $apron        = $_POST['apron'];
        $sepatu       = $_POST['sepatu'];

        $sql = "INSERT INTO audit_kepatuhan_apd 
                (tanggal, tindakan, nik, topi, masker, kacamata, sarungtangan, apron, sepatu)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $tanggal, $tindakan, $nik,
                          $topi, $masker, $kacamata, $sarungtangan, $apron, $sepatu);
        $stmt->execute();
    }

    if ($aksi === 'update') {
        $tanggal_hari = $_POST['tanggal_hari'];
        $tanggal_jam  = $_POST['tanggal_jam'];
        $tanggal      = $tanggal_hari . ' ' . $tanggal_jam;
        $tindakan     = $_POST['tindakan'];
        $nik          = $_POST['nik'];
        $topi         = $_POST['topi'];
        $masker       = $_POST['masker'];
        $kacamata     = $_POST['kacamata'];
        $sarungtangan = $_POST['sarungtangan'];
        $apron        = $_POST['apron'];
        $sepatu       = $_POST['sepatu'];

        // karena PK = nik+tindakan+tanggal, update harus pakai kondisi lama
        $old_nik      = $_POST['old_nik'];
        $old_tindakan = $_POST['old_tindakan'];
        $old_tanggal  = $_POST['old_tanggal'];

        $sql = "UPDATE audit_kepatuhan_apd SET 
                    tanggal=?, tindakan=?, nik=?, 
                    topi=?, masker=?, kacamata=?, sarungtangan=?, apron=?, sepatu=? 
                WHERE nik=? AND tindakan=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssss", $tanggal, $tindakan, $nik,
                          $topi, $masker, $kacamata, $sarungtangan, $apron, $sepatu,
                          $old_nik, $old_tindakan, $old_tanggal);
        $stmt->execute();
    }

    if ($aksi === 'hapus') {
        $nik      = $_POST['nik'];
        $tindakan = $_POST['tindakan'];
        $tanggal  = $_POST['tanggal'];

        $sql = "DELETE FROM audit_kepatuhan_apd WHERE nik=? AND tindakan=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nik, $tindakan, $tanggal);
        $stmt->execute();

        header("Location: audit_kepatuhan.php");
        exit;
    }
}

// === FILTER RENTANG TANGGAL ===
$awalForm  = $_GET['awal'] ?? '';
$akhirForm = $_GET['akhir'] ?? '';

$where  = "WHERE 1=0"; // default kosong
$awalDb = $akhirDb = null;

if ($awalForm && $akhirForm) {
    $awalDb  = $awalForm . " 00:00:00";
    $akhirDb = $akhirForm . " 23:59:59";
    $where   = "WHERE a.tanggal BETWEEN ? AND ?";
}

// === PAGINATION ===
$limit  = 5;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// === QUERY DATA AUDIT ===
$sqlData = "SELECT a.*, p.nama, p.jbtn
            FROM audit_kepatuhan_apd a 
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
$sqlCount = "SELECT COUNT(*) AS total FROM audit_kepatuhan_apd " . ($awalForm && $akhirForm ? "WHERE tanggal BETWEEN ? AND ?" : "WHERE 1=0");
$stmtCount = $conn->prepare($sqlCount);
if ($awalForm && $akhirForm) {
    $stmtCount->bind_param("ss", $awalDb, $akhirDb);
}
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// === QUERY REKAP YA/TIDAK/RATA-RATA ===
$sqlRekap = "SELECT 
    SUM(topi='Ya') AS topi_ya, SUM(topi='Tidak') AS topi_tidak,
    SUM(masker='Ya') AS masker_ya, SUM(masker='Tidak') AS masker_tidak,
    SUM(kacamata='Ya') AS kacamata_ya, SUM(kacamata='Tidak') AS kacamata_tidak,
    SUM(sarungtangan='Ya') AS sarungtangan_ya, SUM(sarungtangan='Tidak') AS sarungtangan_tidak,
    SUM(apron='Ya') AS apron_ya, SUM(apron='Tidak') AS apron_tidak,
    SUM(sepatu='Ya') AS sepatu_ya, SUM(sepatu='Tidak') AS sepatu_tidak,
    COUNT(*) AS total
  FROM audit_kepatuhan_apd
  " . ($awalForm && $akhirForm ? "WHERE tanggal BETWEEN ? AND ?" : "WHERE 1=0");

$stmtRekap = $conn->prepare($sqlRekap);
if ($awalForm && $akhirForm) {
    $stmtRekap->bind_param("ss", $awalDb, $akhirDb);
}
$stmtRekap->execute();
$rekap = $stmtRekap->get_result()->fetch_assoc();

// === HITUNG TTL ===
$totalYa = $rekap['topi_ya'] + $rekap['masker_ya'] + $rekap['kacamata_ya'] +
           $rekap['sarungtangan_ya'] + $rekap['apron_ya'] + $rekap['sepatu_ya'];

$totalTidak = $rekap['topi_tidak'] + $rekap['masker_tidak'] + $rekap['kacamata_tidak'] +
              $rekap['sarungtangan_tidak'] + $rekap['apron_tidak'] + $rekap['sepatu_tidak'];

$totalItem = $rekap['total'] * 6; // 6 jenis APD per baris

$ttlYaPersen    = $totalItem > 0 ? round(($totalYa / $totalItem) * 100) : 0;
$ttlTidakPersen = $totalItem > 0 ? round(($totalTidak / $totalItem) * 100) : 0;
$ttlRataPersen  = $totalItem > 0 ? round(($totalYa / $totalItem) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Audit Kepatuhan APD</title>
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
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Audit Kepatuhan APD</h5>
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
              <label class="form-label">Tanggal:</label>
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
                  <th>Tindakan</th>
                  <th>NIP/Kode</th>
                  <th>Dokter/Paramedis</th>
                  <th>Profesi/Jabatan</th>
                  <th>Topi</th>
                  <th>Masker</th>
                  <th>Kacamata</th>
                  <th>Sarung Tangan</th>
                  <th>Apron</th>
                  <th>Sepatu</th>
                  <th>Ttl.Nilai (%)</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($result->num_rows == 0): ?>
                  <tr><td colspan="13" class="text-center text-muted">Silakan pilih periode untuk menampilkan data</td></tr>
                <?php else: ?>
                  <?php while($row = $result->fetch_assoc()): 
                    // hitung total nilai %
                    $items = ['topi','masker','kacamata','sarungtangan','apron','sepatu'];
                    $yaCount = 0;
                    foreach($items as $i){ if($row[$i]==='Ya') $yaCount++; }
                    $ttlNilai = round(($yaCount / count($items)) * 100);
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['tindakan']) ?></td>
                    <td><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['jbtn']) ?></td>
                    <td><?= $row['topi'] ?></td>
                    <td><?= $row['masker'] ?></td>
                    <td><?= $row['kacamata'] ?></td>
                    <td><?= $row['sarungtangan'] ?></td>
                    <td><?= $row['apron'] ?></td>
                    <td><?= $row['sepatu'] ?></td>
                    <td><?= $ttlNilai ?>%</td>
                    <td class="text-center">
                      <button type="button" class="btn btn-warning btn-sm" 
                              data-bs-toggle="modal" data-bs-target="#modalEdit"
                              data-nik="<?= $row['nik'] ?>" 
                              data-nama="<?= $row['nama'] ?>" 
                              data-jbtn="<?= $row['jbtn'] ?>" 
                              data-tindakan="<?= $row['tindakan'] ?>" 
                              data-tanggal="<?= $row['tanggal'] ?>"
                              data-topi="<?= $row['topi'] ?>"
                              data-masker="<?= $row['masker'] ?>"
                              data-kacamata="<?= $row['kacamata'] ?>"
                              data-sarungtangan="<?= $row['sarungtangan'] ?>"
                              data-apron="<?= $row['apron'] ?>"
                              data-sepatu="<?= $row['sepatu'] ?>">
                        ✏️ Edit
                      </button>
                      <form method="post" action="" style="display:inline">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="nik" value="<?= $row['nik'] ?>">
                        <input type="hidden" name="tindakan" value="<?= $row['tindakan'] ?>">
                        <input type="hidden" name="tanggal" value="<?= $row['tanggal'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
                      </form>
                    </td>
                  </tr>
                  <?php endwhile; ?>

                  <?php if ($page == $totalPages): ?>
                  <tr class="table-success">
                    <td colspan="5">Ya</td>
                    <td><?= $rekap['topi_ya'] ?></td>
                    <td><?= $rekap['masker_ya'] ?></td>
                    <td><?= $rekap['kacamata_ya'] ?></td>
                    <td><?= $rekap['sarungtangan_ya'] ?></td>
                    <td><?= $rekap['apron_ya'] ?></td>
                    <td><?= $rekap['sepatu_ya'] ?></td>
                    <td><?= $totalYa ?></td>
                    <td></td>
                  </tr>
                  <tr class="table-danger">
                    <td colspan="5">Tidak</td>
                    <td><?= $rekap['topi_tidak'] ?></td>
                    <td><?= $rekap['masker_tidak'] ?></td>
                    <td><?= $rekap['kacamata_tidak'] ?></td>
                    <td><?= $rekap['sarungtangan_tidak'] ?></td>
                    <td><?= $rekap['apron_tidak'] ?></td>
                    <td><?= $rekap['sepatu_tidak'] ?></td>
                    <td><?= $totalTidak ?></td>
                    <td></td>
                  </tr>
                  <tr class="table-info">>
                    <td colspan="5">Rata-rata</td>
                    <td><?= round(($rekap['topi_ya']/$rekap['total'])*100) ?>%</td>
                    <td><?= round(($rekap['masker_ya']/$rekap['total'])*100) ?>%</td>
                    <td><?= round(($rekap['kacamata_ya']/$rekap['total'])*100) ?>%</td>
                    <td><?= round(($rekap['sarungtangan_ya']/$rekap['total'])*100) ?>%</td>
                    <td><?= round(($rekap['apron_ya']/$rekap['total'])*100) ?>%</td>
                    <td><?= round(($rekap['sepatu_ya']/$rekap['total'])*100) ?>%</td>
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
            <h5 class="modal-title">Tambah Audit Kepatuhan APD</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Tanggal Audit & Tindakan -->
            <div class="d-flex mb-3 align-items-center">
              <label class="me-2">Tanggal Audit:</label>
              <input type="date" name="tanggal_hari" class="form-control me-2" style="width:160px" required>
              <input type="time" name="tanggal_jam" class="form-control me-4" style="width:120px" required>
              <label class="me-2">Tindakan:</label>
              <input type="text" name="tindakan" class="form-control" style="flex:1" required>
            </div>

            <!-- Dokter/Paramedis -->
            <div class="d-flex mb-3 align-items-center">
              <label class="me-2">Dokter/Paramedis:</label>
              <select name="nik" id="nikSelect" class="form-select me-2" style="width:200px" required>
                <option value="">-- Pilih --</option>
                <?php while($p = $qPegawaiTambah->fetch_assoc()): ?>
                  <option value="<?= $p['nik'] ?>" data-nama="<?= $p['nama'] ?>" data-jbtn="<?= $p['jbtn'] ?>">
                    <?= $p['nik'] ?> - <?= $p['nama'] ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <input type="text" name="nama" id="namaField" class="form-control bg-secondary text-white me-2" readonly style="width:200px">
              <input type="text" name="jbtn" id="jbtnField" class="form-control bg-secondary text-white" readonly style="width:200px">
            </div>

            <!-- Item APD -->
            <h6 class="mt-3 mb-2">Item APD :</h6>
            <div class="item-audit">

            <!-- Baris pertama -->
            <div class="row g-4 mb-2">
              <div class="col-md-4 d-flex align-items-center gap-2">
                <label class="flex-grow-1">1. Topi/Pelindung Kepala</label>
                <select name="topi" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center gap-2">
                <label class="flex-grow-1">3. Kaca Mata Google</label>
                <select name="kacamata" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center gap-2">
                <label class="flex-grow-1">5. Apron
                </label>
                <select name="apron" class="form-select w-auto">
                  <option value="Ya">Ya</option><option value="Tidak">Tidak</option>
                </select>
              </div>
            </div>

            <!-- Baris kedua -->
            <div class="row g-4 mb-2">
              <div class="col-md-4 d-flex align-items-center gap-2">
                <label class="flex-grow-1">2. Masker</label>
                <select name="masker" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center gap-2">
                <label class="flex-grow-1">4. Sarung Tangan</label>
                <select name="sarungtangan" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center gap-2">
                <label class="flex-grow-1">6. Sepatu</label>
                <select name="sepatu" class="form-select w-auto">
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
          <!-- hidden PK lama -->
          <input type="hidden" name="old_nik" id="old_nik">
          <input type="hidden" name="old_tindakan" id="old_tindakan">
          <input type="hidden" name="old_tanggal" id="old_tanggal">

          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Edit Audit Kepatuhan APD</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Tanggal Audit & Tindakan -->
            <div class="d-flex mb-3 align-items-center">
              <label class="me-2">Tanggal Audit:</label>
              <input type="date" name="tanggal_hari" id="edit_tanggal_hari" class="form-control me-2" style="width:160px" required>
              <input type="time" name="tanggal_jam" id="edit_tanggal_jam" class="form-control me-4" style="width:120px" required>
              <label class="me-2">Tindakan:</label>
              <input type="text" name="tindakan" id="edit_tindakan" class="form-control" style="flex:1" required>
            </div>

            <!-- Dokter/Paramedis -->
            <div class="d-flex mb-3 align-items-center">
              <label class="me-2">Dokter/Paramedis:</label>
              <select name="nik" id="edit_nik" class="form-select me-2" style="width:200px" required>
                <option value="">-- Pilih --</option>
                <?php while($p = $qPegawaiEdit->fetch_assoc()): ?>
                  <option value="<?= $p['nik'] ?>" data-nama="<?= $p['nama'] ?>" data-jbtn="<?= $p['jbtn'] ?>">
                    <?= $p['nik'] ?> - <?= $p['nama'] ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <input type="text" id="edit_nama" class="form-control bg-secondary text-white me-2" readonly style="width:200px">
              <input type="text" id="edit_jbtn" class="form-control bg-secondary text-white" readonly style="width:200px">
            </div>

            <!-- Item APD -->
            <h6 class="mt-3 mb-2">Item APD :</h6>
            <div class="item-audit">

            <!-- Baris pertama -->
            <div class="row g-4 mb-2">
              <div class="col-md-4 d-flex align-items-center">
                <label class="flex-grow-1">1. Topi/Pelindung Kepala</label>
                <select name="topi" id="edit_topi" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center">
                <label class="flex-grow-1">3. Kaca Mata Google</label>
                <select name="kacamata" id="edit_kacamata" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center">
                <label class="flex-grow-1">5. Apron</label>
                <select name="apron" id="edit_apron" class="form-select w-auto">
                  <option value="Ya">Ya</option><option value="Tidak">Tidak</option>
                </select>
              </div>
            </div>

            <div class="row mb-2">
              <div class="col-md-4 d-flex align-items-center">
                <label class="flex-grow-1">2. Masker</label>
                <select name="masker" id="edit_masker" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center">
                <label class="flex-grow-1">4. Sarung Tangan</label>
                <select name="sarungtangan" id="edit_sarungtangan" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-center">
                <label class="flex-grow-1">6. Sepatu</label>
                <select name="sepatu" id="edit_sepatu" class="form-select w-auto">
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
    // === Modal Tambah: Autofill Nama & Jabatan ketika NIK dipilih ===
    var nikSelect = document.getElementById('nikSelect');
    if (nikSelect) {
      nikSelect.addEventListener('change', function() {
        var selected = this.options[this.selectedIndex];
        document.getElementById('namaField').value = selected.getAttribute('data-nama') || '';
        document.getElementById('jbtnField').value = selected.getAttribute('data-jbtn') || '';
      });
    }

    // === Modal Edit: Isi field dengan data dari tombol Edit ===
    var modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
      modalEdit.addEventListener('show.bs.modal', function(event) {
        var button   = event.relatedTarget;
        var nik      = button.getAttribute('data-nik');
        var nama     = button.getAttribute('data-nama');
        var jbtn     = button.getAttribute('data-jbtn');
        var tindakan = button.getAttribute('data-tindakan');
        var tanggal  = button.getAttribute('data-tanggal');

        // Item APD
        var topi         = button.getAttribute('data-topi');
        var masker       = button.getAttribute('data-masker');
        var kacamata     = button.getAttribute('data-kacamata');
        var sarungtangan = button.getAttribute('data-sarungtangan');
        var apron        = button.getAttribute('data-apron');
        var sepatu       = button.getAttribute('data-sepatu');

        // Hidden old PK
        document.getElementById('old_nik').value      = nik;
        document.getElementById('old_tindakan').value = tindakan;
        document.getElementById('old_tanggal').value  = tanggal;

        // Isi field edit
        document.getElementById('edit_nik').value      = nik;
        document.getElementById('edit_tindakan').value = tindakan;
        document.getElementById('edit_nama').value     = nama;
        document.getElementById('edit_jbtn').value     = jbtn;

        // Pecah tanggal jadi date + time
        var tanggal = button.getAttribute('data-tanggal'); // contoh: "2026-08-01 00:00:00"
        var parts = tanggal.split(' '); // ["2026-08-01", "00:00:00"]

        if (parts.length >= 2) {
          document.getElementById('edit_tanggal_hari').value = parts[0];          // "2026-08-01"
          document.getElementById('edit_tanggal_jam').value  = parts[1].slice(0,5); // "00:00"
        }

        // Isi item APD
        document.getElementById('edit_topi').value         = topi;
        document.getElementById('edit_masker').value       = masker;
        document.getElementById('edit_kacamata').value     = kacamata;
        document.getElementById('edit_sarungtangan').value = sarungtangan;
        document.getElementById('edit_apron').value        = apron;
        document.getElementById('edit_sepatu').value       = sepatu;
      });
    }

    // === Modal Edit: Autofill Nama & Jabatan ketika NIK dipilih ===
    var editNik = document.getElementById('edit_nik');
    if (editNik) {
      editNik.addEventListener('change', function() {
        var selected = this.options[this.selectedIndex];
        document.getElementById('edit_nama').value = selected.getAttribute('data-nama') || '';
        document.getElementById('edit_jbtn').value = selected.getAttribute('data-jbtn') || '';
      });
    }
  });
  
  </script>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>