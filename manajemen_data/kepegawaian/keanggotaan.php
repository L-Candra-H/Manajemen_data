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

// proses update / insert jika belum ada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'update') {
    $id        = (int)($_POST['id'] ?? 0);
    $koperasi  = $_POST['koperasi'] ?? '';
    $jamsostek = $_POST['jamsostek'] ?? '';
    $bpjs      = $_POST['bpjs'] ?? '';

    $cek = $conn->prepare("SELECT COUNT(*) FROM keanggotaan WHERE id=?");
    $cek->bind_param("i", $id);
    $cek->execute();
    $cek->bind_result($jumlah);
    $cek->fetch();
    $cek->close();

    if ($jumlah > 0) {
        $stmt = $conn->prepare("UPDATE keanggotaan SET koperasi=?, jamsostek=?, bpjs=? WHERE id=?");
        $stmt->bind_param("sssi", $koperasi, $jamsostek, $bpjs, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO keanggotaan (id, koperasi, jamsostek, bpjs) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $id, $koperasi, $jamsostek, $bpjs);
        $stmt->execute();
        $stmt->close();
    }
}

// pagination
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 6;
$offset = ($page - 1) * $limit;

// filter
$filter = $_GET['filter'] ?? '';

// ambil daftar pegawai aktif untuk dropdown
$listPegawai = mysqli_query($conn, "SELECT nik, nama FROM pegawai WHERE stts_aktif='AKTIF' ORDER BY nik");

// hitung total data sesuai filter
if ($filter === 'ALL') {
    $countSql = "SELECT COUNT(*) AS total 
                 FROM pegawai p 
                 LEFT JOIN keanggotaan k ON k.id = p.id 
                 WHERE p.stts_aktif = 'AKTIF'";
} elseif ($filter !== '') {
    $countSql = "SELECT COUNT(*) AS total 
                 FROM pegawai p 
                 LEFT JOIN keanggotaan k ON k.id = p.id 
                 WHERE p.stts_aktif = 'AKTIF' AND p.nik='".mysqli_real_escape_string($conn,$filter)."'";
} else {
    $countSql = "SELECT COUNT(*) AS total FROM pegawai WHERE 1=0";
}
$countResult = mysqli_query($conn, $countSql);
$totalRows = $countResult ? (int) mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $limit) : 1;

// ambil data sesuai filter
if ($filter === 'ALL') {
    $sql = "SELECT p.id, p.nik, p.nama, k.koperasi, k.jamsostek, k.bpjs
            FROM pegawai p
            LEFT JOIN keanggotaan k ON k.id = p.id
            WHERE p.stts_aktif = 'AKTIF'
            ORDER BY p.nik
            LIMIT $limit OFFSET $offset";
} elseif ($filter !== '') {
    $sql = "SELECT p.id, p.nik, p.nama, k.koperasi, k.jamsostek, k.bpjs
            FROM pegawai p
            LEFT JOIN keanggotaan k ON k.id = p.id
            WHERE p.stts_aktif = 'AKTIF' AND p.nik='".mysqli_real_escape_string($conn,$filter)."'
            ORDER BY p.nik";
} else {
    $sql = "SELECT p.id, p.nik, p.nama, k.koperasi, k.jamsostek, k.bpjs
            FROM pegawai p
            LEFT JOIN keanggotaan k ON k.id = p.id
            WHERE 1=0";
}
$result = mysqli_query($conn, $sql);

// dropdown sumber
$koperasiArr = [];
$res = mysqli_query($conn, "SELECT stts FROM koperasi ORDER BY stts");
while($row = mysqli_fetch_assoc($res)) { $koperasiArr[] = $row['stts']; }

$jamsostekArr = [];
$res = mysqli_query($conn, "SELECT stts FROM jamsostek ORDER BY stts");
while($row = mysqli_fetch_assoc($res)) { $jamsostekArr[] = $row['stts']; }

$bpjsArr = [];
$res = mysqli_query($conn, "SELECT stts FROM bpjs ORDER BY stts");
while($row = mysqli_fetch_assoc($res)) { $bpjsArr[] = $row['stts']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Keanggotaan Pegawai</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
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
          <h5 class="mb-0 text-uppercase text-center flex-grow-1">Keanggotaan Pegawai</h5>
          <div class="d-flex gap-2">
            <a href="../index.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <!-- FILTER -->
        <div class="card-body">
          <form method="get" class="mb-3">
            <label for="filter" class="form-label">Filter Pegawai:</label>
            <select name="filter" id="filter" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
              <option value="">-- Pilih Pegawai --</option>
              <option value="ALL" <?= $filter==='ALL' ? 'selected' : '' ?>>Pilih Semua</option>
              <?php while($peg = mysqli_fetch_assoc($listPegawai)): ?>
                <option value="<?= $peg['nik'] ?>" <?= ($filter==$peg['nik'])?'selected':'' ?>>
                  <?= htmlspecialchars($peg['nik']) ?> - <?= htmlspecialchars($peg['nama']) ?>
                </option>
              <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
          </form>
        </div>

        <!-- TABEL -->
        <div class="card-body p-3">
          <div class="table-wrapper">
            <table class="table table-bordered table-striped align-middle table-keanggotaan">
              <thead class="table-dark text-center">
                <tr>
                  <th>NIP</th>
                  <th>Nama</th>
                  <th>Anggota Koperasi</th>
                  <th>Anggota BPJS Ketenagakerjaan</th>
                  <th>Anggota BPJS Kesehatan</th>
                  <th>Proses</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($result) === 0): ?>
                  <tr><td colspan="6" class="text-center text-muted">Tidak ada data keanggotaan</td></tr>
                <?php else: ?>
                  <?php while($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['nik'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['nama'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['koperasi'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['jamsostek'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['bpjs'] ?? '') ?></td>
                    <td class="text-center">
                      <button class="btn btn-warning btn-sm"
                              data-bs-toggle="modal"
                              data-bs-target="#modalEdit"
                              data-id="<?= htmlspecialchars($row['id'] ?? '') ?>"
                              data-nik="<?= htmlspecialchars($row['nik'] ?? '') ?>"
                              data-nama="<?= htmlspecialchars($row['nama'] ?? '') ?>"
                              data-koperasi="<?= htmlspecialchars($row['koperasi'] ?? '') ?>"
                              data-jamsostek="<?= htmlspecialchars($row['jamsostek'] ?? '') ?>"
                              data-bpjs="<?= htmlspecialchars($row['bpjs'] ?? '') ?>">
                        ✏️ Update
                      </button>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if ($totalPages >= 1): ?>
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&filter=<?= urlencode($filter) ?>">Prev</a>
              </li>

              <?php
              $start = max(1, $page - 1);
              $end   = min($totalPages, $page + 1);
              for ($i = $start; $i <= $end; $i++):
              ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&filter=<?= urlencode($filter) ?>">Next</a>
              </li>
            </ul>
          </nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>

  <!-- Modal Update -->
  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="update">
        <input type="hidden" name="id" id="editId">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">Update Keanggotaan Pegawai</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">NIP</label>
            <input type="text" id="editNik" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" id="editNama" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Anggota Koperasi</label>
            <select name="koperasi" id="editKoperasi" class="form-select">
              <option value="">-- Pilih --</option>
              <?php foreach($koperasiArr as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Anggota Jamsostek</label>
            <select name="jamsostek" id="editJamsostek" class="form-select">
              <option value="">-- Pilih --</option>
              <?php foreach($jamsostekArr as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Anggota BPJS</label>
            <select name="bpjs" id="editBpjs" class="form-select">
              <option value="">-- Pilih --</option>
              <?php foreach($bpjsArr as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">🔄 Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script>
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      document.getElementById('editId').value       = button.getAttribute('data-id') || '';
      document.getElementById('editNik').value      = button.getAttribute('data-nik') || '';
      document.getElementById('editNama').value     = button.getAttribute('data-nama') || '';
      document.getElementById('editKoperasi').value = button.getAttribute('data-koperasi') || '';
      document.getElementById('editJamsostek').value= button.getAttribute('data-jamsostek') || '';
      document.getElementById('editBpjs').value     = button.getAttribute('data-bpjs') || '';
    });
  </script>
</body>
</html>
