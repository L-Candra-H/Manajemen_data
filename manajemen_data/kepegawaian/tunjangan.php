<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

if (!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = bukakoneksi();

// pagination setup
$limit  = 4;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// ambil filter dari GET
$filter = $_GET['filter'] ?? '';

// query daftar pegawai untuk dropdown filter
$listPegawai = mysqli_query($conn, "
    SELECT nik, nama 
    FROM pegawai 
    ORDER BY nik ASC
");

// kondisi filter
$where = "";
if ($filter && $filter !== 'ALL') {
    $where = "WHERE p.nik = '".mysqli_real_escape_string($conn, $filter)."'";
}

// fungsi hitung jumlah data
function hitungJumlah($conn, $filter, $where) {
    $jmlData = 0; // default 0
    if ($filter) {
        if ($filter === 'ALL') {
            $sqlCount = "SELECT COUNT(*) AS total FROM pegawai p";
        } else {
            $sqlCount = "SELECT COUNT(*) AS total FROM pegawai p $where";
        }
        $resCount = mysqli_query($conn, $sqlCount);
        if ($resCount) {
            $rowCount = mysqli_fetch_assoc($resCount);
            $jmlData  = $rowCount['total'];
        }
    }
    return $jmlData;
}

// hitung jumlah data sesuai filter
$jmlData = hitungJumlah($conn, $filter, $where);

// pagination setup berdasarkan jumlah data
$totalPages = $jmlData > 0 ? ceil($jmlData / $limit) : 1;

// ambil data pegawai + tunjangan (gabung per pegawai)
$result = null;
if ($filter) {
    $sql = "SELECT p.id, p.nik, p.nama, d.nama AS departemen,
                   GROUP_CONCAT(DISTINCT mb.nama ORDER BY mb.nama ASC SEPARATOR '|') AS tnj_bulanan,
                   GROUP_CONCAT(DISTINCT mh.nama ORDER BY mh.nama ASC SEPARATOR '|') AS tnj_harian
            FROM pegawai p
            LEFT JOIN departemen d ON p.departemen = d.dep_id
            LEFT JOIN pnm_tnj_bulanan pb ON pb.id = p.id
            LEFT JOIN master_tunjangan_bulanan mb ON pb.id_tnj = mb.id
            LEFT JOIN pnm_tnj_harian ph ON ph.id = p.id
            LEFT JOIN master_tunjangan_harian mh ON ph.id_tnj = mh.id
            $where
            GROUP BY p.id, p.nik, p.nama, d.nama
            ORDER BY p.nik ASC
            LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $sql);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tunjangan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <!-- HEADER -->
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase text-center flex-grow-1">Tunjangan</h5>
        <div class="d-flex gap-2">
          <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">

        <!-- Filter Pegawai -->
        <form method="get" class="mb-3">
          <label for="filter" class="form-label">Filter Pegawai:</label>
          <select name="filter" id="filter" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Pegawai --</option>
            <option value="ALL" <?= $filter==='ALL' ? 'selected' : '' ?>>Pilih Semua</option>
            <?php while($peg = mysqli_fetch_assoc($listPegawai)): ?>
              <option value="<?= $peg['nik'] ?>" <?= ($filter==$peg['nik'])?'selected':'' ?>>
                <?= $peg['nik'] ?> - <?= $peg['nama'] ?>
              </option>
            <?php endwhile; ?>
          </select>
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

        <!-- Tabel Pegawai -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-tunjangan align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Departemen</th>
                <th>Tnj. Bulanan Diterima</th>
                <th>Aksi</th>
                <th>Tnj. Harian Diterima</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['departemen'] ?? '') ?></td>

                    <!-- Kolom Bulanan -->
                    <td>
                      <table class="table table-sm table-bordered mb-0">
                        <thead class="table-secondary text-center">
                          <tr>
                            <th>No</th>
                            <th>Nama Tunjangan</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                            $bulanan = $row['tnj_bulanan'] ? explode('|', $row['tnj_bulanan']) : [];
                            $no = 1;
                            foreach ($bulanan as $val): ?>
                            <tr>
                              <td class="text-center"><?= $no++ ?></td>
                              <td><?= htmlspecialchars($val ?: '-') ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </td>
                    <td class="text-center">
                      <a href="detail_tunjangan_bulanan.php?nik=<?= urlencode($row['nik']) ?>&nama=<?= urlencode($row['nama']) ?>"
                         class="btn btn-info btn-sm">
                        Update
                      </a>
                    </td>
                    <!-- Kolom Harian -->
                    <td>
                      <table class="table table-sm table-bordered mb-0">
                        <thead class="table-secondary text-center">
                          <tr>
                            <th>No</th>
                            <th>Nama Tunjangan</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                            $harian = $row['tnj_harian'] ? explode('|', $row['tnj_harian']) : [];
                            $no = 1;
                            foreach ($harian as $val): ?>
                            <tr>
                              <td class="text-center"><?= $no++ ?></td>
                              <td><?= htmlspecialchars($val ?: '-') ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </td>
                    <td class="text-center">
                      <a href="detail_tunjangan_harian.php?nik=<?= urlencode($row['nik']) ?>&nama=<?= urlencode($row['nama']) ?>"
                         class="btn btn-info btn-sm">
                        Update
                      </a>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted">Silakan pilih pegawai terlebih dahulu</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Info Data -->
        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlData ?>,
        </div>

        <!-- Pagination -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&filter=<?= urlencode($filter) ?>">« Prev</a>
              </li>
              <?php
                $start = max(1, $page - 1);
                $end   = min($totalPages, $page + 1);
                for ($i = $start; $i <= $end; $i++):
              ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&filter=<?= urlencode($filter) ?>">Next »</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
