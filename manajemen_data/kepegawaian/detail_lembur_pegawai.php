<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

if(!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = bukakoneksi();

// daftar bulan untuk label
$bulanArr = [
  1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
];

// ambil parameter dari lembur_pegawai.php
$idPegawai   = $_GET['id'] ?? '';
$tahun       = $_GET['tahun'] ?? '';
$bulan       = $_GET['bulan'] ?? '';

// ambil data pegawai
$pegawai = null;
if ($idPegawai) {
    $stmt = $conn->prepare("SELECT id, nik, nama FROM pegawai WHERE id=?");
    $stmt->bind_param("i", $idPegawai);
    $stmt->execute();
    $pegawai = $stmt->get_result()->fetch_assoc();
}

// ambil semua tanggal existing untuk pegawai ini
$existingDates = [];
if ($idPegawai && $tahun && $bulan) {
    $q = $conn->prepare("SELECT tgl FROM presensi WHERE id=? AND YEAR(tgl)=? AND MONTH(tgl)=?");
    $q->bind_param("iii", $idPegawai, $tahun, $bulan);
    $q->execute();
    $res = $q->get_result();
    while($r = $res->fetch_assoc()) {
        $existingDates[] = $r['tgl'];
    }
}

// handler insert/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['mode'] === 'insert') {
        $tgl    = $_POST['tgl'] ?? null;
        $jns    = $_POST['jns'] ?? null;
        $lembur = $_POST['lembur'] ?? 0;

        // cek duplikat
        $cek = $conn->prepare("SELECT COUNT(*) AS ada FROM presensi WHERE id=? AND tgl=?");
        $cek->bind_param("is", $idPegawai, $tgl);
        $cek->execute();
        $cekRes = $cek->get_result()->fetch_assoc();

        if ($cekRes['ada'] > 0) {
            $_SESSION['error'] = "Data lembur untuk tanggal $tgl sudah ada!";
        } else {
            $stmt = $conn->prepare("INSERT INTO presensi (tgl, id, jns, lembur) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sisi", $tgl, $idPegawai, $jns, $lembur);
            $stmt->execute();
        }
    } elseif ($_POST['mode'] === 'delete') {
        $idPegawai = $_POST['id'] ?? null;
        $tgl       = $_POST['tgl'] ?? null;
        $stmt = $conn->prepare("DELETE FROM presensi WHERE id=? AND tgl=?");
        $stmt->bind_param("is", $idPegawai, $tgl);
        $stmt->execute();
    }
}

// ambil detail lembur sesuai filter
$result = false;
$totalPages = 0;
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

if ($idPegawai && $tahun && $bulan) {
    // hitung total data
    $countSql = "SELECT COUNT(*) AS total FROM presensi WHERE id=? AND YEAR(tgl)=? AND MONTH(tgl)=?";
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param("iii", $idPegawai, $tahun, $bulan);
    $stmt->execute();
    $countRes = $stmt->get_result()->fetch_assoc();
    $totalData = $countRes['total'] ?? 0;
    $totalPages = $totalData > 0 ? ceil($totalData / $limit) : 0;

    $sql = "SELECT id, tgl, jns, lembur
            FROM presensi
            WHERE id=? AND YEAR(tgl)=? AND MONTH(tgl)=?
            ORDER BY tgl ASC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $idPegawai, $tahun, $bulan, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

$totalLemburHB = 0;
$totalLemburHR = 0;

if ($idPegawai && $tahun && $bulan) {
    $sumSql = "SELECT 
                  SUM(CASE WHEN jns='HB' THEN lembur ELSE 0 END) AS ttl_hb,
                  SUM(CASE WHEN jns='HR' THEN lembur ELSE 0 END) AS ttl_hr
               FROM presensi
               WHERE id=? AND YEAR(tgl)=? AND MONTH(tgl)=?";
    $stmt = $conn->prepare($sumSql);
    $stmt->bind_param("iii", $idPegawai, $tahun, $bulan);
    $stmt->execute();
    $sumRes = $stmt->get_result()->fetch_assoc();
    $totalLemburHB = $sumRes['ttl_hb'] ?? 0;
    $totalLemburHR = $sumRes['ttl_hr'] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Lembur Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="main-content">
    <div class="container-fluid mt-4">
      <div class="card shadow">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">
            Detail Lembur Pegawai - <?= htmlspecialchars($pegawai['nik'] ?? '') ?> | <?= htmlspecialchars($pegawai['nama'] ?? '') ?> | Periode: <?= $tahun.' - '.$bulanArr[(int)$bulan] ?>
          </h5>
          <div class="d-flex gap-2">
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
            <a href="lembur_pegawai.php?tahunHitung=<?= $tahun.'-'.$bulan ?>" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <div class="card-body p-3">
          <div class="table-wrapper">
            <table class="table table-bordered table-striped align-middle table-lembur">
              <thead class="table-dark text-center">
                <tr>
                  <th>Tgl Lembur</th>
                  <th>Jns Lembur</th>
                  <th>Lembur</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['tgl']) ?></td>
                      <td><?= htmlspecialchars($row['jns']) ?></td>
                      <td class="text-center"><?= $row['lembur'] ?> Jam</td>
                      <td class="text-center">
                        <form action="" method="post" style="display:inline">
                          <input type="hidden" name="mode" value="delete">
                          <input type="hidden" name="id" value="<?= $row['id'] ?>">
                          <input type="hidden" name="tgl" value="<?= $row['tgl'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
                        </form>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="4" class="text-center">Belum ada data lembur untuk periode ini</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

            <div class="mt-2 small text-start text-muted">
              Data : <?= $totalData ?> record,
              Ttl Lembur HB : <?= $totalLemburHB ?>,
              Ttl Lembur HR : <?= $totalLemburHR ?>
            </div>

          </div>

          <!-- Pagination -->
          <?php if ($totalPages >= 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
              <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="?id=<?= $idPegawai ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&page=<?= max(1, $page - 1) ?>">« Prev</a>
                </li>
                <?php
                  $start = max(1, $page - 1);
                  $end   = min($totalPages, $page + 1);
                  for ($i = $start; $i <= $end; $i++):
                ?>
                  <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?id=<?= $idPegawai ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&page=<?= $i ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="?id=<?= $idPegawai ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&page=<?= min($totalPages, $page + 1) ?>">Next »</a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Tambah Lembur Pegawai</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">NIP</label>
            <input type="text" class="form-control bg-danger text-white fw-bold" 
                   value="<?= htmlspecialchars($pegawai['nik'] ?? '') ?>" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control bg-danger text-white fw-bold" 
                   value="<?= htmlspecialchars($pegawai['nama'] ?? '') ?>" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal</label>
            <select name="tgl" class="form-select" required>
              <option value="">-- Pilih Tanggal --</option>
              <?php
              if ($tahun && $bulan) {
                  $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
                  for ($d=1; $d<=$daysInMonth; $d++) {
                      $tglVal = sprintf("%04d-%02d-%02d", $tahun, $bulan, $d);
                      $disabled = in_array($tglVal, $existingDates) ? 'disabled' : '';
                      echo "<option value=\"$tglVal\" $disabled>$d</option>";
                  }
              }
              ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Jenis Lembur</label>
            <select name="jns" class="form-select" required>
              <option value="">-- Pilih Jenis --</option>
              <option value="HR">HR</option>
              <option value="HB">HB</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Lembur</label>
            <div class="input-group">
              <input type="number" name="lembur" class="form-control" min="0" value="0" required>
              <span class="input-group-text">Jam</span>
            </div>
            <small class="text-muted">Jika jenis HR, isi dengan 1.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?php if(isset($_SESSION['error'])): ?>
  <div id="popup" class="popup" style="display:flex;">
    <div class="popup-content">
      <p>⚠️ <?= $_SESSION['error'] ?></p>
      <button onclick="closePopup()">Tutup</button>
    </div>
  </div>
  <script>
    function closePopup() {
      const popup = document.getElementById('popup');
      popup.classList.add('fade-out');
      setTimeout(() => { popup.style.display = 'none'; }, 1000);
    }
    setTimeout(closePopup, 5000);
  </script>
  <?php unset($_SESSION['error']); endif; ?>

</body>
</html>
