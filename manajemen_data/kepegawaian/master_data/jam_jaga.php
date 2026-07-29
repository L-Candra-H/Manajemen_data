<?php
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// cek hak akses
if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Jam Jaga Departemen.</div>";
    exit;
}

$conn = bukakoneksi();

// handler insert/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode   = $_POST['dep_id'] ?? null;
    $shift  = $_POST['shift'] ?? null;
    $masuk  = $_POST['jam_masuk'] ?? null;
    $pulang = $_POST['jam_pulang'] ?? null;

    if (isset($_POST['mode']) && $_POST['mode'] === 'insert') {
        $stmt = $conn->prepare("INSERT INTO jam_jaga (dep_id, shift, jam_masuk, jam_pulang) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $kode, $shift, $masuk, $pulang);
        $stmt->execute();
    } elseif (isset($_POST['mode']) && $_POST['mode'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM jam_jaga WHERE no_id=?");
        $stmt->bind_param("i", $_POST['no_id']);
        $stmt->execute();
    }
}

// pagination setup
$limit  = 6;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// filter departemen
$filterDep = $_GET['dep_id'] ?? '';
$where = ($filterDep && $filterDep !== 'ALL')
    ? "WHERE j.dep_id = '" . mysqli_real_escape_string($conn, $filterDep) . "'"
    : "";

// hitung total data
if ($filterDep === '') {
    $totalData  = 0;
    $totalPages = 0;
    $totalRows  = 0;
    $result     = false;
} else {
    $totalSql    = "SELECT COUNT(*) AS total FROM jam_jaga j $where";
    $totalResult = mysqli_query($conn, $totalSql);
    $totalRow    = $totalResult ? mysqli_fetch_assoc($totalResult) : ['total' => 0];
    $totalData   = $totalRow['total'] ?? 0;
    $totalPages  = $totalData > 0 ? ceil($totalData / $limit) : 0;
    $totalRows   = $totalData;
}

// ambil data sesuai halaman + filter
$result = false;
if ($filterDep !== '') {
    $sql = "SELECT j.no_id, j.dep_id, d.nama, j.shift, j.jam_masuk, j.jam_pulang
            FROM jam_jaga j
            JOIN departemen d ON j.dep_id = d.dep_id
            $where
            ORDER BY j.dep_id, j.shift
            LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $sql);
}

// daftar departemen untuk dropdown
$depList      = $conn->query("SELECT dep_id, nama FROM departemen ORDER BY dep_id ASC");
$depListArray = $depList->fetch_all(MYSQLI_ASSOC);

// daftar shift
$shiftArr = [
    'Pagi','Pagi2','Pagi3','Pagi4','Pagi5','Pagi6','Pagi7','Pagi8','Pagi9','Pagi10',
    'Siang','Siang2','Siang3','Siang4','Siang5','Siang6','Siang7','Siang8','Siang9','Siang10',
    'Malam','Malam2','Malam3','Malam4','Malam5','Malam6','Malam7','Malam8','Malam9','Malam10',
    'Midle Pagi1','Midle Pagi2','Midle Pagi3','Midle Pagi4','Midle Pagi5','Midle Pagi6','Midle Pagi7','Midle Pagi8','Midle Pagi9','Midle Pagi10',
    'Midle Siang1','Midle Siang2','Midle Siang3','Midle Siang4','Midle Siang5','Midle Siang6','Midle Siang7','Midle Siang8','Midle Siang9','Midle Siang10',
    'Midle Malam1','Midle Malam2','Midle Malam3','Midle Malam4','Midle Malam5','Midle Malam6','Midle Malam7','Midle Malam8','Midle Malam9','Midle Malam10'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Jam Jaga Departemen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <!-- Header -->
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Jam Jaga Departemen</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <!-- Body -->
      <div class="card-body p-3">
        <!-- Filter -->
        <form method="get" class="mb-3">
          <label for="dep_id" class="form-label">Filter Departemen :</label>
          <select name="dep_id" id="dep_id" class="form-select form-select-sm" style="max-width:220px; display:inline-block;">
            <option value="">-- Pilih Departemen --</option>
            <option value="ALL" <?= $filterDep === 'ALL' ? 'selected' : '' ?>>Pilih Semua</option>
            <?php foreach($depListArray as $d): ?>
              <option value="<?= htmlspecialchars($d['dep_id']) ?>" <?= $filterDep === $d['dep_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['dep_id'].' - '.$d['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

        <!-- Tabel -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-master align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>Kode</th>
                <th>Departemen</th>
                <th>Shift</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['dep_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['nama'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['shift'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['jam_masuk'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['jam_pulang'] ?? '') ?></td>
                    <td class="text-center">
                      <form action="" method="post" style="display:inline">
                        <input type="hidden" name="mode" value="delete">
                        <input type="hidden" name="no_id" value="<?= htmlspecialchars($row['no_id'] ?? '') ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center">Silakan pilih departemen untuk menampilkan data</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
          <div class="mt-2 small text-start text-muted">
            Data : <?= $totalRows ?? 0 ?>,
          </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-3">
          <ul class="pagination justify-content-center">
            <!-- Tombol Prev -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">« Prev</a>
            </li>

            <!-- Nomor Halaman -->
            <?php
              $start = max(1, $page - 1);
              $end   = min($totalPages, $page + 1);
              for ($i = $start; $i <= $end; $i++):
            ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <!-- Tombol Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next »</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Tambah Jam Jaga</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Departemen</label>
            <select name="dep_id" class="form-select" required>
              <option value="">-- Pilih Departemen --</option>
              <?php foreach($depListArray as $d): ?>
                <option value="<?= $d['dep_id'] ?>"><?= $d['dep_id'].' - '.$d['nama'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Shift</label>
            <select name="shift" class="form-select" required>
              <option value="">-- Pilih Shift --</option>
              <?php foreach($shiftArr as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Jam Masuk</label>
            <input type="time" name="jam_masuk" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Jam Pulang</label>
            <input type="time" name="jam_pulang" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
