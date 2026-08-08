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

// === AMBIL DATA RUANG AKTIF ===
$qRuangTambah = $conn->query("SELECT id_ruang, nama_ruang FROM ruang_audit_kepatuhan ORDER BY id_ruang ASC");
$qRuangEdit   = $conn->query("SELECT id_ruang, nama_ruang FROM ruang_audit_kepatuhan ORDER BY id_ruang ASC");

// === PROSES CRUD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $tanggal_hari = $_POST['tanggal_hari'] ?? '';
    $tanggal_jam  = $_POST['tanggal_jam'] ?? '';
    $tanggal      = $tanggal_hari . ' ' . $tanggal_jam;
    $id_ruang     = $_POST['id_ruang'] ?? '';

    $apd        = $_POST['menggunakan_apd_waktu_membuang_darah'] ?? '';
    $lantai     = $_POST['komponen_darah_tidak_ada_dilantai'] ?? '';
    $tempat     = $_POST['membuang_darah_pada_tempat_ditentukan'] ?? '';
    $pembersih  = $_POST['pembersihan_areal_tumbahan_darah'] ?? '';
    $limbah     = $_POST['apd_dibuang_di_limbah_infeksius'] ?? '';
    $cuci_tangan= $_POST['melakukan_kebersihan_tangan_setelah_prosedur'] ?? '';

    if ($aksi === 'tambah') {
        $sql = "INSERT INTO audit_penanganan_darah
                (tanggal, id_ruang,
                 menggunakan_apd_waktu_membuang_darah,
                 komponen_darah_tidak_ada_dilantai,
                 membuang_darah_pada_tempat_ditentukan,
                 pembersihan_areal_tumbahan_darah,
                 apd_dibuang_di_limbah_infeksius,
                 melakukan_kebersihan_tangan_setelah_prosedur)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $tanggal, $id_ruang, $apd, $lantai, $tempat, $pembersih, $limbah, $cuci_tangan);
        $stmt->execute();
    }

    if ($aksi === 'update') {
        $old_id_ruang = $_POST['old_id_ruang'];
        $old_tanggal  = $_POST['old_tanggal'];
        $sql = "UPDATE audit_penanganan_darah SET
                tanggal=?, id_ruang=?,
                menggunakan_apd_waktu_membuang_darah=?,
                komponen_darah_tidak_ada_dilantai=?,
                membuang_darah_pada_tempat_ditentukan=?,
                pembersihan_areal_tumbahan_darah=?,
                apd_dibuang_di_limbah_infeksius=?,
                melakukan_kebersihan_tangan_setelah_prosedur=?
                WHERE id_ruang=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss", $tanggal, $id_ruang, $apd, $lantai, $tempat, $pembersih, $limbah, $cuci_tangan, $old_id_ruang, $old_tanggal);
        $stmt->execute();
    }

    if ($aksi === 'hapus') {
        $id_ruang = $_POST['id_ruang'];
        $tanggal  = $_POST['tanggal'];
        $sql = "DELETE FROM audit_penanganan_darah WHERE id_ruang=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $id_ruang, $tanggal);
        $stmt->execute();
        header("Location: audit_penanganan_darah.php");
        exit;
    }
}

// === FILTER RENTANG TANGGAL ===
$awalForm  = $_GET['awal'] ?? '';
$akhirForm = $_GET['akhir'] ?? '';
$where  = "WHERE 1=0"; 
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
$sqlData = "SELECT a.*, r.nama_ruang
            FROM audit_penanganan_darah a 
            LEFT JOIN ruang_audit_kepatuhan r ON a.id_ruang = r.id_ruang
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
$sqlCount = "SELECT COUNT(*) AS total FROM audit_penanganan_darah " . ($awalForm && $akhirForm ? "WHERE tanggal BETWEEN ? AND ?" : "WHERE 1=0");
$stmtCount = $conn->prepare($sqlCount);
if ($awalForm && $akhirForm) {
    $stmtCount->bind_param("ss", $awalDb, $akhirDb);
}
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// === QUERY REKAP YA/TIDAK/RATA-RATA ===
$sqlRekap = "SELECT
    SUM(menggunakan_apd_waktu_membuang_darah='Ya') AS apd_ya,
    SUM(komponen_darah_tidak_ada_dilantai='Ya') AS lantai_ya,
    SUM(membuang_darah_pada_tempat_ditentukan='Ya') AS tempat_ya,
    SUM(pembersihan_areal_tumbahan_darah='Ya') AS pembersih_ya,
    SUM(apd_dibuang_di_limbah_infeksius='Ya') AS limbah_ya,
    SUM(melakukan_kebersihan_tangan_setelah_prosedur='Ya') AS cuci_tangan_ya,
    COUNT(*) AS total
  FROM audit_penanganan_darah
  " . ($awalForm && $akhirForm ? "WHERE tanggal BETWEEN ? AND ?" : "WHERE 1=0");
$stmtRekap = $conn->prepare($sqlRekap);
if ($awalForm && $akhirForm) {
    $stmtRekap->bind_param("ss", $awalDb, $akhirDb);
}
$stmtRekap->execute();
$rekap = $stmtRekap->get_result()->fetch_assoc();

// === HITUNG TTL ===
$totalYa = $rekap['apd_ya'] + $rekap['lantai_ya'] + $rekap['tempat_ya'] +
           $rekap['pembersih_ya'] + $rekap['limbah_ya'] + $rekap['cuci_tangan_ya'];

$totalItem = $rekap['total'] * 6; // 6 indikator per baris audit
$totalTidak = $totalItem - $totalYa;

// Persentase rata-rata keseluruhan
$ttlYaPersen    = $totalItem > 0 ? round(($totalYa / $totalItem) * 100) : 0;
$ttlTidakPersen = $totalItem > 0 ? round(($totalTidak / $totalItem) * 100) : 0;
$ttlRataPersen  = $ttlYaPersen;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Audit Penanganan Darah</title>
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
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Audit Penanganan Darah</h5>
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
                  <th>ID Ruang</th>
                  <th>Ruang/Unit</th>
                  <th>1. Menggunakan APD Waktu Membuang Darah/Komponen Darah</th>
                  <th>2. Komponen Darah Tidak Ada Di Lantai</th>
                  <th>3. Membuang Darah/Komponen Darah Pada Tempat Yang Ditentukan</th>
                  <th>4. Pembersihan Areal Tumpah Darah Dengan Clorin/Spil Kit</th>
                  <th>5. APD Yang Digunakan Dibuang Di Limbah Infeksius</th>
                  <th>6. Melakukan Kebersihan Tangan Setelah Prosedur Tersebut</th>
                  <th>Ttl. Nilai (%)</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($result->num_rows == 0): ?>
                  <tr><td colspan="11" class="text-center text-muted">Silakan pilih periode untuk menampilkan data</td></tr>
                <?php else: ?>
                  <?php while($row = $result->fetch_assoc()):
                    $items = [
                      'menggunakan_apd_waktu_membuang_darah',
                      'komponen_darah_tidak_ada_dilantai',
                      'membuang_darah_pada_tempat_ditentukan',
                      'pembersihan_areal_tumbahan_darah',
                      'apd_dibuang_di_limbah_infeksius',
                      'melakukan_kebersihan_tangan_setelah_prosedur'
                    ];
                    $yaCount = 0;
                    foreach($items as $i){ if($row[$i] === 'Ya') $yaCount++; }
                    $ttlNilai = round(($yaCount / count($items)) * 100);
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['id_ruang']) ?></td>
                    <td><?= htmlspecialchars($row['nama_ruang']) ?></td>
                    <td><?= $row['menggunakan_apd_waktu_membuang_darah'] ?></td>
                    <td><?= $row['komponen_darah_tidak_ada_dilantai'] ?></td>
                    <td><?= $row['membuang_darah_pada_tempat_ditentukan'] ?></td>
                    <td><?= $row['pembersihan_areal_tumbahan_darah'] ?></td>
                    <td><?= $row['apd_dibuang_di_limbah_infeksius'] ?></td>
                    <td><?= $row['melakukan_kebersihan_tangan_setelah_prosedur'] ?></td>
                    <td><?= $ttlNilai ?>%</td>
                    <td class="text-center">
                      <!-- Tombol Edit -->
                      <button type="button" class="btn btn-warning btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                        data-id_ruang="<?= $row['id_ruang'] ?>"
                        data-nama_ruang="<?= $row['nama_ruang'] ?>"
                        data-tanggal="<?= $row['tanggal'] ?>"
                        data-apd="<?= $row['menggunakan_apd_waktu_membuang_darah'] ?>"
                        data-lantai="<?= $row['komponen_darah_tidak_ada_dilantai'] ?>"
                        data-tempat="<?= $row['membuang_darah_pada_tempat_ditentukan'] ?>"
                        data-pembersih="<?= $row['pembersihan_areal_tumbahan_darah'] ?>"
                        data-limbah="<?= $row['apd_dibuang_di_limbah_infeksius'] ?>"
                        data-cuci="<?= $row['melakukan_kebersihan_tangan_setelah_prosedur'] ?>">
                        ✏️ Edit
                      </button>
                      <!-- Tombol Hapus -->
                      <form method="post" action="" style="display:inline">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id_ruang" value="<?= $row['id_ruang'] ?>">
                        <input type="hidden" name="tanggal" value="<?= $row['tanggal'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
                      </form>
                    </td>
                  </tr>
                  <?php endwhile; ?>

                  <!-- Rekap -->
                  <?php if ($page == $totalPages): ?>
                    <tr class="table-success">
                      <td colspan="3">Ya</td>
                      <td><?= $rekap['apd_ya'] ?></td>
                      <td><?= $rekap['lantai_ya'] ?></td>
                      <td><?= $rekap['tempat_ya'] ?></td>
                      <td><?= $rekap['pembersih_ya'] ?></td>
                      <td><?= $rekap['limbah_ya'] ?></td>
                      <td><?= $rekap['cuci_tangan_ya'] ?></td>
                      <td><?= $totalYa ?></td>
                      <td></td>
                    </tr>
                    <tr class="table-danger">
                      <td colspan="3">Tidak</td>
                      <td><?= $rekap['total'] - $rekap['apd_ya'] ?></td>
                      <td><?= $rekap['total'] - $rekap['lantai_ya'] ?></td>
                      <td><?= $rekap['total'] - $rekap['tempat_ya'] ?></td>
                      <td><?= $rekap['total'] - $rekap['pembersih_ya'] ?></td>
                      <td><?= $rekap['total'] - $rekap['limbah_ya'] ?></td>
                      <td><?= $rekap['total'] - $rekap['cuci_tangan_ya'] ?></td>
                      <td><?= $totalTidak ?></td>
                      <td></td>
                    </tr>
                    <tr class="table-info">>
                      <td colspan="3">Rata-rata</td>
                      <td><?= round(($rekap['apd_ya']/$rekap['total'])*100) ?>%</td>
                      <td><?= round(($rekap['lantai_ya']/$rekap['total'])*100) ?>%</td>
                      <td><?= round(($rekap['tempat_ya']/$rekap['total'])*100) ?>%</td>
                      <td><?= round(($rekap['pembersih_ya']/$rekap['total'])*100) ?>%</td>
                      <td><?= round(($rekap['limbah_ya']/$rekap['total'])*100) ?>%</td>
                      <td><?= round(($rekap['cuci_tangan_ya']/$rekap['total'])*100) ?>%</td>
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
              $endPage = min($totalPages, $page + 1);
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
            <h5 class="modal-title">Tambah Audit Penanganan Darah</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex mb-3 align-items-center gap-3 flex-wrap">
              <label>Tanggal :</label>
              <input type="date" name="tanggal_hari" class="form-control" style="width:120px" required>
              <input type="time" name="tanggal_jam" class="form-control" style="width:80px" required>
              <label>Ruang/Unit :</label>
              <select id="ruangSelect" name="id_ruang" class="form-select" style="width:170px" required>
                <option value="">-- Pilih --</option>
                <?php while($r = $qRuangTambah->fetch_assoc()): ?>
                  <option value="<?= $r['id_ruang'] ?>" data-nama="<?= $r['nama_ruang'] ?>">
                    <?= $r['id_ruang'] ?> - <?= $r['nama_ruang'] ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <input type="text" name="nama" id="nama_ruang" class="form-control bg-secondary text-white me-2" readonly style="width:150px">
            </div>

            <!-- Item Audit -->
            <div class="item-audit">
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">1. Menggunakan APD Waktu Membuang Darah/Komponen Darah</label>
                <select name="menggunakan_apd_waktu_membuang_darah" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">2. Komponen Darah Tidak Ada Di Lantai</label>
                <select name="komponen_darah_tidak_ada_dilantai" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">3. Membuang Darah/Komponen Darah Pada Tempat Yang Ditentukan</label>
                <select name="membuang_darah_pada_tempat_ditentukan" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">4. Pembersihan Areal Tumpah Darah Dengan Clorin/Spil Kit</label>
                <select name="pembersihan_areal_tumbahan_darah" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">5. APD Yang Digunakan Dibuang Di Limbah Infeksius</label>
                <select name="apd_dibuang_di_limbah_infeksius" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">6. Melakukan Kebersihan Tangan Setelah Prosedur Tersebut</label>
                <select name="melakukan_kebersihan_tangan_setelah_prosedur" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
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
          <input type="hidden" name="old_id_ruang" id="old_id_ruang">
          <input type="hidden" name="old_tanggal" id="old_tanggal">

          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Edit Audit Penanganan Darah</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex mb-3 align-items-center gap-3 flex-wrap">
              <label>Tanggal :</label>
              <input type="date" name="tanggal_hari" id="edit_tanggal_hari" class="form-control" style="width:120px" required>
              <input type="time" name="tanggal_jam" id="edit_tanggal_jam" class="form-control" style="width:80px" required>
              <label>Ruang/Unit :</label>
              <select name="id_ruang" id="edit_id_ruang" class="form-select" style="width:170px" required>
                <option value="">-- Pilih --</option>
                <?php while($r = $qRuangEdit->fetch_assoc()): ?>
                  <option value="<?= $r['id_ruang'] ?>"><?= $r['id_ruang'] ?> - <?= $r['nama_ruang'] ?></option>
                <?php endwhile; ?>
              </select>
              <input type="text" id="edit_nama_ruang" class="form-control bg-secondary text-white me-2" readonly style="width:150px">
            </div>

            <!-- Item Audit -->
            <div class="item-audit">
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">1. Menggunakan APD Waktu Membuang Darah/Komponen Darah</label>
                <select name="menggunakan_apd_waktu_membuang_darah" id="edit_apd" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">2. Komponen Darah Tidak Ada Di Lantai</label>
                <select name="komponen_darah_tidak_ada_dilantai" id="edit_lantai" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">3. Membuang Darah/Komponen Darah Pada Tempat Yang Ditentukan</label>
                <select name="membuang_darah_pada_tempat_ditentukan" id="edit_tempat" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">4. Pembersihan Areal Tumpah Darah Dengan Clorin/Spil Kit</label>
                <select name="pembersihan_areal_tumbahan_darah" id="edit_pembersih" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">5. APD Yang Digunakan Dibuang Di Limbah Infeksius</label>
                <select name="apd_dibuang_di_limbah_infeksius" id="edit_limbah" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">6. Melakukan Kebersihan Tangan Setelah Prosedur Tersebut</label>
                <select name="melakukan_kebersihan_tangan_setelah_prosedur" id="edit_cuci" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-warning">🔄 Update</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", function() {
    const ruangSelect = document.getElementById("ruangSelect");
    const namaRuangField = document.getElementById("nama_ruang");

    ruangSelect.addEventListener("change", function() {
      const selectedOption = ruangSelect.options[ruangSelect.selectedIndex];
      const namaRuang = selectedOption.getAttribute("data-nama") || "";
      namaRuangField.value = namaRuang;
    });
  });

  document.addEventListener("DOMContentLoaded", function(){
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;

      var id_ruang   = button.getAttribute('data-id_ruang');
      var nama_ruang = button.getAttribute('data-nama_ruang');
      var tanggal    = button.getAttribute('data-tanggal');
      var apd        = button.getAttribute('data-apd');
      var lantai     = button.getAttribute('data-lantai');
      var tempat     = button.getAttribute('data-tempat');
      var pembersih  = button.getAttribute('data-pembersih');
      var limbah     = button.getAttribute('data-limbah');
      var cuci       = button.getAttribute('data-cuci');

      // Pisahkan tanggal jadi hari + jam
      var dt = new Date(tanggal);
      document.getElementById('edit_tanggal_hari').value = dt.toISOString().slice(0,10);
      document.getElementById('edit_tanggal_jam').value  = dt.toTimeString().slice(0,5);

      // Isi field modal
      document.getElementById('edit_id_ruang').value   = id_ruang;
      document.getElementById('edit_nama_ruang').value = nama_ruang;
      document.getElementById('edit_apd').value        = apd;
      document.getElementById('edit_lantai').value     = lantai;
      document.getElementById('edit_tempat').value     = tempat;
      document.getElementById('edit_pembersih').value  = pembersih;
      document.getElementById('edit_limbah').value     = limbah;
      document.getElementById('edit_cuci').value       = cuci;

      // Simpan PK lama
      document.getElementById('old_id_ruang').value = id_ruang;
      document.getElementById('old_tanggal').value  = tanggal;
    });
  });
  </script>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
