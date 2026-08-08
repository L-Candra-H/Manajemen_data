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

// === AMBIL DATA RUANG ===
$qRuangTambah = $conn->query("SELECT id_ruang, nama_ruang 
                              FROM ruang_audit_kepatuhan 
                              ORDER BY id_ruang ASC");
$qRuangEdit   = $conn->query("SELECT id_ruang, nama_ruang 
                              FROM ruang_audit_kepatuhan 
                              ORDER BY id_ruang ASC");

// === PROSES CRUD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah') {
        $tanggal_hari = $_POST['tanggal_hari'];
        $tanggal_jam  = $_POST['tanggal_jam'];
        $tanggal      = $tanggal_hari . ' ' . $tanggal_jam;
        $id_ruang     = $_POST['id_ruang'];
        $audit1 = $_POST['audit1'];
        $audit2 = $_POST['audit2'];
        $audit3 = $_POST['audit3'];
        $audit4 = $_POST['audit4'];
        $audit5 = $_POST['audit5'];
        $audit6 = $_POST['audit6'];

        $sql = "INSERT INTO audit_pembuangan_limbah_cair_infeksius
                (tanggal, id_ruang, audit1, audit2, audit3, audit4, 
                audit5, audit6)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $tanggal, $id_ruang, $audit1, $audit2, $audit3, $audit4, $audit5, $audit6);
        $stmt->execute();
    }

    if ($aksi === 'update') {
        $tanggal_hari = $_POST['tanggal_hari'];
        $tanggal_jam  = $_POST['tanggal_jam'];
        $tanggal      = $tanggal_hari . ' ' . $tanggal_jam;
        $id_ruang     = $_POST['id_ruang'];
        $audit1 = $_POST['audit1'];
        $audit2 = $_POST['audit2'];
        $audit3 = $_POST['audit3'];
        $audit4 = $_POST['audit4'];
        $audit5 = $_POST['audit5'];
        $audit6 = $_POST['audit6'];

        // PK = tanggal+ruang
        $old_id_ruang = $_POST['old_id_ruang'];
        $old_tanggal  = $_POST['old_tanggal'];

        $sql = "UPDATE audit_pembuangan_limbah_cair_infeksius SET
                tanggal=?, id_ruang=?, audit1=?, audit2=?, 
                audit3=?, audit4=?, audit5=?, 
                audit6=?
                WHERE id_ruang=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss", $tanggal, $id_ruang, 
                          $audit1, $audit2, $audit3, $audit4, $audit5, $audit6, $old_id_ruang, $old_tanggal);
        $stmt->execute();
    }

    if ($aksi === 'hapus') {
        $id_ruang = $_POST['id_ruang'];
        $tanggal  = $_POST['tanggal'];

        $sql = "DELETE FROM audit_pembuangan_limbah_cair_infeksius WHERE id_ruang=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $id_ruang, $tanggal);
        $stmt->execute();

        header("Location: audit_pembuangan_limbah_cair_infeksius.php");
        exit;
    }
}

// === FILTER RENTANG TANGGAL ===
$awalForm  = $_GET['awal'] ?? '';
$akhirForm = $_GET['akhir'] ?? '';

$where     = "WHERE 1=0";
$awalDb = $akhirDb = null;

if ($awalForm && $akhirForm) {
    $awalDb = $awalForm . " 00:00:00";
    $akhirDb = $akhirForm . " 23:59:59";
    $where = "WHERE a.tanggal BETWEEN ? AND ?";
}

// === PAGINATION ===
$limit = 5;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// === QUERY DATA AUDIT ===
$sqlData = "SELECT a.*, r.nama_ruang
            FROM audit_pembuangan_limbah_cair_infeksius a
            LEFT JOIN ruang_audit_kepatuhan r ON a.id_ruang=r.id_ruang
            $where
            ORDER BY a.tanggal ASC
            LIMIT ?,?";
$stmt = $conn->prepare($sqlData);

if ($awalForm && $akhirForm) {
    $stmt->bind_param("ssii", $awalDb, $akhirDb, $offset, $limit);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

// === HITUNG TOTAL DATA ===
$sqlCount = "SELECT COUNT(*) AS total FROM audit_pembuangan_limbah_cair_infeksius " . ($awalForm && $akhirForm ? "WHERE tanggal BETWEEN ? AND ?" : "WHERE 1=0");
$stmtCount = $conn->prepare($sqlCount);
if ($awalForm && $akhirForm) {
    $stmtCount->bind_param("ss", $awalDb, $akhirDb);
}
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// === QUERY REKAP YA/TIDAK/RATA-RATA ===
$sqlRekap = "SELECT
    SUM(audit1='Ya') AS a1_ya, SUM(audit1='Tidak') AS a1_tidak,
    SUM(audit2='Ya') AS a2_ya, SUM(audit2='Tidak') AS a2_tidak,
    SUM(audit3='Ya') AS a3_ya, SUM(audit3='Tidak') AS a3_tidak,
    SUM(audit4='Ya') AS a4_ya, SUM(audit4='Tidak') AS a4_tidak,
    SUM(audit5='Ya') AS a5_ya, SUM(audit5='Tidak') AS a5_tidak,
    SUM(audit6='Ya') AS a6_ya, SUM(audit6='Tidak') AS a6_tidak,
    COUNT(*) AS total
    FROM audit_pembuangan_limbah_cair_infeksius
    " . ($awalForm && $akhirForm ? "WHERE tanggal BETWEEN ? AND ?" : "WHERE 1=0");

$stmtRekap = $conn->prepare($sqlRekap);
if ($awalForm && $akhirForm) {
    $stmtRekap->bind_param("ss", $awalDb, $akhirDb);
}
$stmtRekap->execute();
$rekap = $stmtRekap->get_result()->fetch_assoc();

// === HITUNG TTL ===
$totalYa    = $rekap['a1_ya'] + $rekap['a2_ya'] + $rekap['a3_ya'] + $rekap['a4_ya'] + $rekap['a5_ya'] + $rekap['a6_ya'];
$totalTidak = $rekap['a1_tidak'] + $rekap['a2_tidak'] + $rekap['a3_tidak'] + $rekap['a4_tidak'] + $rekap['a5_tidak'] + $rekap['a6_tidak'];
$totalItem  = $rekap['total'] * 6;

$ttlYaPersen    = $totalItem > 0 ? round(($totalYa / $totalItem) * 100) : 0;
$ttlTidakPersen = $totalItem > 0 ? round(($totalTidak / $totalItem) * 100) : 0;
$ttlRataPersen  = $ttlYaPersen;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Audit Pembuangan Limbah Cair Infeksius</title>
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
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Audit Pembuangan Limbah Cair Infeksius</h5>
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
                  <th>1. Petugas Melakukan Kebersihan Tangan Sebelum Membuang Limbah Cair Infeksius</th>
                  <th>2. Menggunakan APD Minimal Sarung Tangan Dan Masker Sebelum Membuang Limbah Cair Infeksius</th>
                  <th>3. Limbah Cair Medis Infeksius Dibuang Dalam Spoelhock Atau WC Di Ruang Perawatan</th>
                  <th>4. Limbah Cair Infeksius Disiram Menggunakan Air Mengalir Dan Banyak</th>
                  <th>5. APD Yang Sudah Dipakai Dilepaskan Sesuai Prosedur Setelah Pembuangan Limbah Cair Infeksius</th>
                  <th>6. Melakukan Kebersihan Tangan Sesuai Prosedur Setelah Pembuangan Limbah Cair Infeksius</th>
                  <th>Ttl. Nilai (%)</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if($result->num_rows == 0): ?>
                  <tr>
                    <td colspan="11" class="text-center text-muted">Silakan pilih periode untuk menampilkan data</td>
                  </tr>
                <?php else: ?>
                  <?php while($row=$result->fetch_assoc()):
                    $items=[
                      'audit1','audit2','audit3',
                      'audit4','audit5','audit6'
                    ];
                    $yaCount=0;
                    foreach($items as $i){ if($row[$i]==='Ya') $yaCount++; }
                    $ttlNilai=round(($yaCount/count($items))*100);
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['id_ruang']) ?></td>
                    <td><?= htmlspecialchars($row['nama_ruang']) ?></td>
                    <td><?= $row['audit1'] ?></td>
                    <td><?= $row['audit2'] ?></td>
                    <td><?= $row['audit3'] ?></td>
                    <td><?= $row['audit4'] ?></td>
                    <td><?= $row['audit5'] ?></td>
                    <td><?= $row['audit6'] ?></td>
                    <td><?= $ttlNilai ?>%</td>
                    <td class="text-center">
                      <!-- Tombol Edit -->
                      <button type="button" class="btn btn-warning btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                        data-id_ruang="<?= $row['id_ruang'] ?>"
                        data-nama_ruang="<?= $row['nama_ruang'] ?>"
                        data-tanggal="<?= $row['tanggal'] ?>"
                        data-audit1="<?= $row['audit1'] ?>"
                        data-audit2="<?= $row['audit2'] ?>"
                        data-audit3="<?= $row['audit3'] ?>"
                        data-audit4="<?= $row['audit4'] ?>"
                        data-audit5="<?= $row['audit5'] ?>"
                        data-audit6="<?= $row['audit6'] ?>">
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
                  <?php if($page==$totalPages): ?>
                    <tr class="table-success">
                      <td colspan="3">Ya</td>
                      <td><?= $rekap['a1_ya'] ?></td>
                      <td><?= $rekap['a2_ya'] ?></td>
                      <td><?= $rekap['a3_ya'] ?></td>
                      <td><?= $rekap['a4_ya'] ?></td>
                      <td><?= $rekap['a5_ya'] ?></td>
                      <td><?= $rekap['a6_ya'] ?></td>
                      <td><?= $totalYa ?></td>
                      <td></td>
                    </tr>
                    <tr class="table-danger">
                      <td colspan="3">Tidak</td>
                      <td><?= $rekap['a1_tidak'] ?></td>
                      <td><?= $rekap['a2_tidak'] ?></td>
                      <td><?= $rekap['a3_tidak'] ?></td>
                      <td><?= $rekap['a4_tidak'] ?></td>
                      <td><?= $rekap['a5_tidak'] ?></td>
                      <td><?= $rekap['a6_tidak'] ?></td>
                      <td><?= $totalTidak ?></td>
                      <td></td>
                    </tr>
                    <tr class="table-info">
                      <td colspan="3">Rata-rata (%)</td>
                      <td><?= $rekap['total']>0?round(($rekap['a1_ya']/$rekap['total'])*100):0 ?>%</td>
                      <td><?= $rekap['total']>0?round(($rekap['a2_ya']/$rekap['total'])*100):0 ?>%</td>
                      <td><?= $rekap['total']>0?round(($rekap['a3_ya']/$rekap['total'])*100):0 ?>%</td>
                      <td><?= $rekap['total']>0?round(($rekap['a4_ya']/$rekap['total'])*100):0 ?>%</td>
                      <td><?= $rekap['total']>0?round(($rekap['a5_ya']/$rekap['total'])*100):0 ?>%</td>
                      <td><?= $rekap['total']>0?round(($rekap['a6_ya']/$rekap['total'])*100):0 ?>%</td>
                      <td><?= $ttlRataPersen ?>%
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
    <div class="modal-dialog modal-lg-custom">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="tambah">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Tambah Audit Pembuangan Limbah Cair Infeksius</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Tanggal Audit & Ruang -->
            <div class="d-flex mb-3 align-items-center gap-3 flex-wrap">
              <label>Tanggal :</label>
              <input type="date" name="tanggal_hari" class="form-control" style="width:120px" required>
              <input type="time" name="tanggal_jam" class="form-control" style="width:80px" required>

              <label>Ruang/Unit Diaudit :</label>
              <select name="id_ruang" id="ruangSelect" class="form-select" style="width:170px" required>
                <option value="">-- Pilih --</option>
                <?php while($r=$qRuangTambah->fetch_assoc()): ?>
                  <option value="<?= $r['id_ruang'] ?>" data-nama="<?= $r['nama_ruang'] ?>"><?= $r['id_ruang'] ?> - <?= $r['nama_ruang'] ?></option>
                <?php endwhile; ?>
              </select>

              <input type="text" name="nama_ruang" id="ruangField" 
                        class="form-control bg-secondary text-white me-2" 
                        readonly style="width:180px">
            </div>

            <!-- Bundles -->
            <h6 class="mt-3 mb-2">AUDIT :</h6>
            <div class="item-audit">

              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">1. Petugas Melakukan Kebersihan Tangan Sebelum Membuang Limbah Cair Infeksius</label>
                <select name="audit1" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">2. Menggunakan APD Minimal Sarung Tangan Dan Masker Sebelum Membuang Limbah Cair Infeksius</label>
                <select name="audit2" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">3. Limbah Cair Medis Infeksius Dibuang Dalam Spoelhock Atau WC Di Ruang Perawatan</label>
                <select name="audit3" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">4. Limbah Cair Infeksius Disiram Menggunakan Air Mengalir Dan Banyak</label>
                <select name="audit4" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">5. APD Yang Sudah Dipakai Dilepaskan Sesuai Prosedur Setelah Pembuangan Limbah Cair Infeksius</label>
                <select name="audit5" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">6. Melakukan Kebersihan Tangan Sesuai Prosedur Setelah Pembuangan Limbah Cair Infeksius</label>
                <select name="audit6" class="form-select w-auto">
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
    <div class="modal-dialog modal-lg-custom">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="update">
          <input type="hidden" name="old_id_ruang" id="old_id_ruang">
          <input type="hidden" name="old_tanggal" id="old_tanggal">

          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Edit Audit Pembuangan Limbah Cair Infeksius</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Tanggal Audit & Ruang -->
            <div class="d-flex mb-3 align-items-center gap-3 flex-wrap">
              <label>Tanggal :</label>
              <input type="date" name="tanggal_hari" id="edit_tanggal_hari" class="form-control" style="width:120px" required>
              <input type="time" name="tanggal_jam" id="edit_tanggal_jam" class="form-control" style="width:80px" required>
              <label>Ruang/Unit Diaudit :</label>
              <select name="id_ruang" id="edit_id_ruang" class="form-select" style="width:170px" required>
                <option value="">-- Pilih --</option>
                <?php while($r=$qRuangEdit->fetch_assoc()): ?>
                  <option value="<?= $r['id_ruang'] ?>" data-nama="<?= $r['nama_ruang'] ?>"><?= $r['id_ruang'] ?> - <?= $r['nama_ruang'] ?></option>
                <?php endwhile; ?>
              </select>
              <input type="text" id="edit_nama_ruang" class="form-control bg-secondary text-white me-2" readonly style="width:180px">
            
            </div>

            <!-- Bundles -->
            <h6 class="mt-3 mb-2">AUDIT :</h6>
            <div class="item-audit">
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">1. Petugas Melakukan Kebersihan Tangan Sebelum Membuang Limbah Cair Infeksius</label>
                <select name="audit1" id="edit_audit1" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">2. Menggunakan APD Minimal Sarung Tangan Dan Masker Sebelum Membuang Limbah Cair Infeksius</label>
                <select name="audit2" id="edit_audit2" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">3. Limbah Cair Medis Infeksius Dibuang Dalam Spoelhock Atau WC Di Ruang Perawatan</label>
                <select name="audit3" id="edit_audit3" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">4. Limbah Cair Infeksius Disiram Menggunakan Air Mengalir Dan Banyak</label>
                <select name="audit4" id="edit_audit4" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">5. APD Yang Sudah Dipakai Dilepaskan Sesuai Prosedur Setelah Pembuangan Limbah Cair Infeksius</label>
                <select name="audit5" id="edit_audit5" class="form-select w-auto">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3">
                <label class="flex-grow-1">6. Melakukan Kebersihan Tangan Sesuai Prosedur Setelah Pembuangan Limbah Cair Infeksius</label>
                <select name="audit6" id="edit_audit6" class="form-select w-auto">
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
    const ruangField = document.getElementById("ruangField");
    if(ruangSelect) {
      ruangSelect.addEventListener("change", function() {
        const selectedOption = ruangSelect.options[ruangSelect.selectedIndex];
        const nama = selectedOption.getAttribute("data-nama");
        ruangField.value = nama;
      });
    }
  });

  document.addEventListener("DOMContentLoaded", function(){
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;

      // Ambil data dari atribut tombol Edit
      var id_ruang   = button.getAttribute('data-id_ruang');
      var nama_ruang = button.getAttribute('data-nama_ruang');
      var tanggal    = button.getAttribute('data-tanggal');
      var audit1     = button.getAttribute('data-audit1');
      var audit2     = button.getAttribute('data-audit2');
      var audit3     = button.getAttribute('data-audit3');
      var audit4     = button.getAttribute('data-audit4');
      var audit5     = button.getAttribute('data-audit5');
      var audit6     = button.getAttribute('data-audit6');

      // Pisahkan tanggal jadi hari + jam (format: YYYY-MM-DD HH:MM:SS)
      var parts = tanggal.split(' ');
      var hari = parts[0];
      var jam  = parts[1];

      // Isi field modal
      document.getElementById('edit_tanggal_hari').value = hari;
      document.getElementById('edit_tanggal_jam').value  = jam;
      document.getElementById('edit_id_ruang').value     = id_ruang;
      document.getElementById('edit_nama_ruang').value   = nama_ruang;
    
      document.getElementById('edit_audit1').value       = audit1;
      document.getElementById('edit_audit2').value       = audit2;
      document.getElementById('edit_audit3').value       = audit3;
      document.getElementById('edit_audit4').value       = audit4;
      document.getElementById('edit_audit5').value       = audit5;
      document.getElementById('edit_audit6').value       = audit6;

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
