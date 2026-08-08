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

// === PROSES CRUD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $tanggal_hari = $_POST['tanggal_hari'] ?? '';
    $tanggal_jam  = $_POST['tanggal_jam'] ?? '';
    $tanggal      = $tanggal_hari . ' ' . $tanggal_jam;

    $audit1 = $_POST['audit1'] ?? '';
    $audit2 = $_POST['audit2'] ?? '';
    $audit3 = $_POST['audit3'] ?? '';
    $audit4 = $_POST['audit4'] ?? '';
    $audit5 = $_POST['audit5'] ?? '';
    $audit6 = $_POST['audit6'] ?? '';
    $audit7 = $_POST['audit7'] ?? '';
    $audit8 = $_POST['audit8'] ?? '';

    if ($aksi === 'tambah') {
        $sql = "INSERT INTO audit_kamar_jenazah
                (tanggal,audit1,audit2,audit3,audit4,audit5,audit6,audit7,audit8)
                VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss",$tanggal,$audit1,$audit2,$audit3,$audit4,$audit5,$audit6,$audit7,$audit8);
        $stmt->execute();
    }

    if ($aksi === 'update') {
        $old_tanggal  = $_POST['old_tanggal'];
        $sql = "UPDATE audit_kamar_jenazah SET
                tanggal=?,audit1=?,audit2=?,audit3=?,audit4=?,
                audit5=?,audit6=?,audit7=?,audit8=?
                WHERE tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss",$tanggal,$audit1,$audit2,$audit3,$audit4,$audit5,$audit6,$audit7,$audit8,$old_tanggal);
        $stmt->execute();
    }

    if ($aksi === 'hapus') {
        $tanggal  = $_POST['tanggal'];
        $sql = "DELETE FROM audit_kamar_jenazah WHERE tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$tanggal);
        $stmt->execute();
        header("Location: audit_kamar_jenazah.php");
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
    $where   = "WHERE tanggal BETWEEN ? AND ?";
}

// === PAGINATION ===
$limit  = 5;
$page   = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// === QUERY DATA AUDIT ===
$sqlData = "SELECT * FROM audit_kamar_jenazah $where ORDER BY tanggal ASC LIMIT ?,?";
$stmt = $conn->prepare($sqlData);
if ($awalForm && $akhirForm) {
    $stmt->bind_param("ssii",$awalDb,$akhirDb,$offset,$limit);
} else {
    $stmt->bind_param("ii",$offset,$limit);
}
$stmt->execute();
$result = $stmt->get_result();

// === HITUNG TOTAL DATA ===
$sqlCount = "SELECT COUNT(*) AS total FROM audit_kamar_jenazah ".($awalForm&&$akhirForm?"WHERE tanggal BETWEEN ? AND ?":"WHERE 1=0");
$stmtCount = $conn->prepare($sqlCount);
if ($awalForm && $akhirForm) {
    $stmtCount->bind_param("ss",$awalDb,$akhirDb);
}
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// === QUERY REKAP YA/TIDAK/RATA-RATA ===
$sqlRekap = "SELECT
    SUM(audit1='Ya') AS audit1_ya,
    SUM(audit2='Ya') AS audit2_ya,
    SUM(audit3='Ya') AS audit3_ya,
    SUM(audit4='Ya') AS audit4_ya,
    SUM(audit5='Ya') AS audit5_ya,
    SUM(audit6='Ya') AS audit6_ya,
    SUM(audit7='Ya') AS audit7_ya,
    SUM(audit8='Ya') AS audit8_ya,
    COUNT(*) AS total
  FROM audit_kamar_jenazah ".($awalForm&&$akhirForm?"WHERE tanggal BETWEEN ? AND ?":"WHERE 1=0");
$stmtRekap = $conn->prepare($sqlRekap);
if ($awalForm && $akhirForm) {
    $stmtRekap->bind_param("ss",$awalDb,$akhirDb);
}
$stmtRekap->execute();
$rekap = $stmtRekap->get_result()->fetch_assoc();

// === HITUNG TTL ===
$totalYa = $rekap['audit1_ya']+$rekap['audit2_ya']+$rekap['audit3_ya']+$rekap['audit4_ya']+$rekap['audit5_ya']+$rekap['audit6_ya']+$rekap['audit7_ya']+$rekap['audit8_ya'];
$totalItem = $rekap['total']*8;
$totalTidak = $totalItem-$totalYa;

// Persentase rata-rata keseluruhan
$ttlYaPersen    = $totalItem>0 ? round(($totalYa/$totalItem)*100) : 0;
$ttlTidakPersen = $totalItem>0 ? round(($totalTidak/$totalItem)*100) : 0;
$ttlRataPersen  = $ttlYaPersen;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Audit Kamar Jenazah</title>
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
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Audit Kamar Jenazah</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
            <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <!-- Body -->
        <div class="card-body p-3">
          <!-- Filter tanggal -->
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
                  <th>1. Tersedia Tempat Limbah Infeksius Dan Limbah Non Infeksius</th>
                  <th>2. Tersedia Tempat Linen Infeksius Dan Linen Non Infeksius</th>
                  <th>3. Tersedia APD Yang Lengkap (Masker, Sarung Tangan, Apron, Sepatu Boot, Kacamata Google, Tutup Kepala)</th>
                  <th>4. Petugas Menggunakan APD Sesuai Indikasi (Jenazah Infeksius Dan Non Infeksius)</th>
                  <th>5. Tersedia Wastafel Cuci Tangan, Dan Dilengkapi Dengan Tisu, Tempat Sampah Dan Sabun</th>
                  <th>6. Tersedia Handrub</th>
                  <th>7. Tersedia Peralatan Memandikan Jenazah Dan Perawatan Jenazah (Sabun, Minyak Wangi, Kapas, Dll)</th>
                  <th>8. Keranda Dalam Keadaan Bersih, Tidak Berdebu Dan Dibersihkan Setiap Hari/Setiap Selesai Digunakan</th>
                  <th>Ttl. Nilai (%)</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($result->num_rows == 0): ?>
                  <tr><td colspan="11" class="text-center text-muted">Silakan pilih periode untuk menampilkan data</td></tr>
                <?php else: ?>
                  <?php while($row = $result->fetch_assoc()):
                    $items = ['audit1','audit2','audit3','audit4','audit5','audit6','audit7','audit8'];
                    $yaCount = 0;
                    foreach($items as $i){ if($row[$i] === 'Ya') $yaCount++; }
                    $ttlNilai = round(($yaCount / count($items)) * 100);
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <?php for($i=1;$i<=8;$i++): ?>
                      <td><?= $row['audit'.$i] ?></td>
                    <?php endfor; ?>
                    <td><?= $ttlNilai ?>%</td>
                    <td class="text-center">
                      <!-- Tombol Edit -->
                      <button type="button" class="btn btn-warning btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                        data-tanggal="<?= $row['tanggal'] ?>"
                        <?php for($i=1;$i<=8;$i++): ?>
                        data-audit<?= $i ?>="<?= $row['audit'.$i] ?>"
                        <?php endfor; ?>
                      >✏️ Edit</button>
                      <!-- Tombol Hapus -->
                      <form method="post" action="" style="display:inline">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="tanggal" value="<?= $row['tanggal'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
                      </form>
                    </td>
                  </tr>
                  <?php endwhile; ?>

                  <!-- Rekap -->
                  <?php if ($page == $totalPages && $rekap['total'] > 0): ?>
                    <tr class="table-success">
                      <td>Ya</td>
                      <?php for($i=1;$i<=8;$i++): ?>
                        <td><?= $rekap['audit'.$i.'_ya'] ?></td>
                      <?php endfor; ?>
                      <td><?= $totalYa ?></td>
                      <td></td>
                    </tr>
                    <tr class="table-danger">
                      <td>Tidak</td>
                      <?php for($i=1;$i<=8;$i++): ?>
                        <td><?= $rekap['total'] - $rekap['audit'.$i.'_ya'] ?></td>
                      <?php endfor; ?>
                      <td><?= $totalTidak ?></td>
                      <td></td>
                    </tr>
                    <tr class="table-info">
                      <td>Rata-rata (%)</td>
                      <?php for($i=1;$i<=8;$i++): ?>
                        <td><?= $rekap['total']>0 ? round(($rekap['audit'.$i.'_ya']/$rekap['total'])*100) : 0 ?>%</td>
                      <?php endfor; ?>
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
    <div class="modal-dialog modal-lg-custom">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="tambah">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Tambah Audit Kamar Jenazah</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Tanggal Audit -->
            <div class="d-flex mb-3 align-items-center gap-3 flex-wrap">
              <label>Tanggal :</label>
              <input type="date" name="tanggal_hari" class="form-control" style="width:150px" required>
              <input type="time" name="tanggal_jam" class="form-control" style="width:100px" required>
            </div>

            <!-- Item Audit -->
            <h6 class="mt-3 mb-2">AUDIT :</h6>
            <div class="item-audit">
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">1. Tersedia Tempat Limbah Infeksius Dan Limbah Non Infeksius</label>
                <select name="audit1" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">2. Tersedia Tempat Linen Infeksius Dan Linen Non Infeksius</label>
                <select name="audit2" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">3. Tersedia APD Yang Lengkap (Masker, Sarung Tangan, Apron, Sepatu Boot, Kacamata Google, Tutup Kepala)</label>
                <select name="audit3" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">4. Petugas Menggunakan APD Sesuai Indikasi (Jenazah Infeksius Dan Non Infeksius)</label>
                <select name="audit4" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">5. Tersedia Wastafel Cuci Tangan, Dan Dilengkapi Dengan Tisu, Tempat Sampah Dan Sabun</label>
                <select name="audit5" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">6. Tersedia Handrub</label>
                <select name="audit6" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">7. Tersedia Peralatan Memandikan Jenazah Dan Perawatan Jenazah (Sabun, Minyak Wangi, Kapas, Dll)</label>
                <select name="audit7" class="form-select" style="width:150px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">8. Keranda Dalam Keadaan Bersih, Tidak Berdebu Dan Dibersihkan Setiap Hari/Setiap Selesai Digunakan</label>
                <select name="audit8" class="form-select" style="width:150px">
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
    <div class="modal-dialog modal-lg-custom2">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="update">
          <input type="hidden" name="old_tanggal" id="old_tanggal">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Edit Audit Kamar Jenazah</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Tanggal Audit -->
            <div class="d-flex mb-3 align-items-center gap-3 flex-wrap">
              <label>Tanggal :</label>
              <input type="date" name="tanggal_hari" id="edit_tanggal_hari" class="form-control" style="width:150px" required>
              <input type="time" name="tanggal_jam" id="edit_tanggal_jam" class="form-control" style="width:100px" required>
            </div>

            <!-- Item Audit -->
            <h6 class="mt-3 mb-2">AUDIT :</h6>
            <div class="item-audit">
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">1. Tersedia Tempat Limbah Infeksius Dan Limbah Non Infeksius</label>
                <select name="audit1" id="edit_audit1" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">2. Tersedia Tempat Linen Infeksius Dan Linen Non Infeksius</label>
                <select name="audit2" id="edit_audit2" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">3. Tersedia APD Yang Lengkap (Masker, Sarung Tangan, Apron, Sepatu Boot, Kacamata Google, Tutup Kepala)</label>
                <select name="audit3" id="edit_audit3" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">4. Petugas Menggunakan APD Sesuai Indikasi (Jenazah Infeksius Dan Non Infeksius)</label>
                <select name="audit4" id="edit_audit4" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">5. Tersedia Wastafel Cuci Tangan, Dan Dilengkapi Dengan Tisu, Tempat Sampah Dan Sabun</label>
                <select name="audit5" id="edit_audit5" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">6. Tersedia Handrub</label>
                <select name="audit6" id="edit_audit6" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">7. Tersedia Peralatan Memandikan Jenazah Dan Perawatan Jenazah (Sabun, Minyak Wangi, Kapas, Dll)</label>
                <select name="audit7" id="edit_audit7" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>
              <div class="col-md-12 d-flex align-items-center gap-3 mb-2">
                <label class="flex-grow-1">8. Keranda Dalam Keadaan Bersih, Tidak Berdebu Dan Dibersihkan Setiap Hari/Setiap Selesai Digunakan</label>
                <select name="audit8" id="edit_audit8" class="form-select" style="width:200px">
                  <option value="Ya">Ya</option>
                  <option value="Tidak">Tidak</option>
                </select>
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
    // Untuk Modal Edit: isi otomatis field dari data-* attribute
    const modalEdit = document.getElementById("modalEdit");
    if (modalEdit) {
      modalEdit.addEventListener("show.bs.modal", function(event) {
        const button = event.relatedTarget;
        if (!button) return;

        const tanggal = button.getAttribute("data-tanggal") || "";

        // parsing tanggal aman
        if (tanggal) {
          const dt = new Date(tanggal);
          if (!isNaN(dt)) {
            document.getElementById("edit_tanggal_hari").value = dt.toISOString().slice(0,10);
            document.getElementById("edit_tanggal_jam").value = dt.toTimeString().slice(0,5);
          }
        }

        // isi nilai audit 1–8
        for (let i = 1; i <= 8; i++) {
          const val = button.getAttribute("data-audit" + i);
          if (val) {
            document.getElementById("edit_audit" + i).value = val;
          }
        }

        // simpan tanggal lama untuk identifikasi update
        document.getElementById("old_tanggal").value = tanggal;
      });
    }
  });
  </script>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
