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
$limit  = 5;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// ambil filter dari GET
$filterPegawai = $_GET['filterPegawai'] ?? '';
$tahunHitung   = $_GET['tahunHitung'] ?? ''; // format: YYYY-MM

// pecah tahun-bulan
$tahunFilter = '';
$bulanFilter = '';
if (!empty($tahunHitung)) {
    [$tahunFilter, $bulanFilter] = explode('-', $tahunHitung);
}

// query daftar pegawai untuk dropdown filter
$listPegawai = mysqli_query($conn, "SELECT nik, nama FROM pegawai ORDER BY nik ASC");

// query daftar tahun-bulan dari set_tahun
$listTahun = mysqli_query($conn, "
    SELECT tahun, bulan 
    FROM set_tahun 
    ORDER BY tahun DESC, bulan DESC
");

// array bulan untuk label
$bulanArr = [
  1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April",
  5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus",
  9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"
];

// default nilai
$jmlData = 0;
$ttlJasa = 0;
$result  = false;
$totalPages = 0;

// hanya jalankan query kalau filter aktif
if ($filterPegawai || $tahunHitung) {
    // kondisi filter
    $where = "WHERE 1=1";
    if ($filterPegawai && $filterPegawai !== 'ALL') {
        $where .= " AND p.nik = '".mysqli_real_escape_string($conn, $filterPegawai)."'";
    }
    if ($tahunFilter) {
        $where .= " AND jl.thn = '".mysqli_real_escape_string($conn, $tahunFilter)."'";
    }
    if ($bulanFilter) {
        $where .= " AND jl.bln = '".mysqli_real_escape_string($conn, $bulanFilter)."'";
    }

    // hitung jumlah data
    $sqlCount = "SELECT COUNT(*) AS total
                 FROM pegawai p
                 LEFT JOIN departemen d ON p.departemen = d.dep_id
                 WHERE 1=1
                 ".($filterPegawai && $filterPegawai!=='ALL' ? " AND p.nik='".mysqli_real_escape_string($conn,$filterPegawai)."'" : "");
    $resCount = mysqli_query($conn, $sqlCount);
    $rowCount = mysqli_fetch_assoc($resCount);
    $jmlData  = $rowCount['total'] ?? 0;

    // total jasa lain (rekap)
    $sqlTotalJasa = "SELECT SUM(jl.bsr_jasa) AS total_jasa 
                     FROM jasa_lain jl 
                     JOIN pegawai p ON jl.id = p.id 
                     $where";
    $resTotalJasa = mysqli_query($conn, $sqlTotalJasa);
    $rowTotalJasa = mysqli_fetch_assoc($resTotalJasa);
    $ttlJasa      = $rowTotalJasa['total_jasa'] ?? 0;

    // pagination setup
    $totalPages = $jmlData > 0 ? ceil($jmlData / $limit) : 1;

    // ambil data pegawai + jasa lain
    $sql = "SELECT p.nik, p.nama, d.nama AS depart,
                   COALESCE(SUM(jl.bsr_jasa),0) AS total_jasa
            FROM pegawai p
            LEFT JOIN departemen d ON p.departemen = d.dep_id
            LEFT JOIN jasa_lain jl 
                   ON jl.id = p.id
                  AND jl.thn = '".mysqli_real_escape_string($conn,$tahunFilter)."'
                  AND jl.bln = '".mysqli_real_escape_string($conn,$bulanFilter)."'
            WHERE 1=1
            ".($filterPegawai && $filterPegawai!=='ALL' ? " AND p.nik='".mysqli_real_escape_string($conn,$filterPegawai)."'" : "")."
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
  <title>Jasa Lain</title>
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
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Jasa Lain</h5>
        <div class="d-flex gap-2">
          <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">

        <!-- Filter Pegawai -->
        <form method="get" class="mb-3">
          <label for="filterPegawai" class="form-label">Filter Pegawai:</label>
          <select name="filterPegawai" id="filterPegawai" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Pegawai --</option>
            <option value="ALL" <?= $filterPegawai === 'ALL' ? 'selected' : '' ?>>Pilih Semua</option>
            <?php if ($listPegawai): ?>
              <?php while ($p = $listPegawai->fetch_assoc()): ?>
                <option value="<?= $p['nik'] ?>" <?= ($filterPegawai == $p['nik']) ? 'selected' : '' ?>>
                  <?= $p['nik'].' - '.$p['nama'] ?>
                </option>
              <?php endwhile; ?>
            <?php endif; ?>
          </select>

        <!-- Filter Tahun Hitung -->
          <label for="tahunHitung" class="form-label">Filter Tahun-Bulan:</label>
          <select name="tahunHitung" id="tahunHitung" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Tahun-Bulan --</option>
            <?php if ($listTahun): ?>
              <?php while ($thn = $listTahun->fetch_assoc()): ?>
                <?php
                  $val      = $thn['tahun'].'-'.$thn['bulan'];
                  $label    = $thn['tahun'].' '.$bulanArr[(int)$thn['bulan']];
                  $selected = ($tahunHitung == $val) ? 'selected' : '';
                ?>
                <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
              <?php endwhile; ?>
            <?php endif; ?>
          </select>

        <!-- Tombol Terapkan -->
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

        <!-- TABEL -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-jasa_lain align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>NIP</th>
                <th>Nama</th>
                <th>Depart</th>
                <th>Total Jasa Lain</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['depart']) ?></td>
                    <td><?= number_format($row['total_jasa'], 0, ',', '.') ?></td>
                    <td class="text-center">
                      <a href="detail_jasa_lain.php?nik=<?= urlencode($row['nik']) ?>" class="btn btn-sm btn-primary">
                        Detail
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center">Silakan pilih pegawai dan tahun hitung untuk menampilkan data</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlData ?>,
          Ttl. Jasa Lain : Rp. <?= number_format($ttlJasa, 0, ',', '.') ?>
        </div>

        <!-- Pagination -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
              <!-- Tombol Prev -->
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&filterPegawai=<?= urlencode($filterPegawai) ?>&tahunHitung=<?= urlencode($tahunHitung) ?>">« Prev</a>
              </li>
              <!-- Nomor Halaman -->
              <?php
                $start = max(1, $page - 1);
                $end   = min($totalPages, $page + 1);
                for ($i = $start; $i <= $end; $i++):
              ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&filterPegawai=<?= urlencode($filterPegawai) ?>&tahunHitung=<?= urlencode($tahunHitung) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <!-- Tombol Next -->
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&filterPegawai=<?= urlencode($filterPegawai) ?>&tahunHitung=<?= urlencode($tahunHitung) ?>">Next »</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

</body>
</html>
