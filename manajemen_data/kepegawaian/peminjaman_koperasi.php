<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

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

// pagination setup
$limit  = 5;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// ambil filter dari GET
$filterPegawai = $_GET['filterPegawai'] ?? '';
$tahunBulan    = $_GET['tahunBulan'] ?? ''; // format: YYYY-MM

// pecah tahun-bulan
$tahunFilter = '';
$bulanFilter = '';
if (!empty($tahunBulan)) {
    [$tahunFilter, $bulanFilter] = explode('-', $tahunBulan);
}

// query daftar pegawai koperasi
$listPegawai = mysqli_query($conn, "
    SELECT p.nik, p.nama 
    FROM pegawai p
    JOIN keanggotaan k ON k.id = p.id
    WHERE k.koperasi='Y'
      AND p.stts_aktif='AKTIF'
    ORDER BY p.nik ASC
");

// query daftar tahun-bulan dari peminjaman_koperasi
$listTahun = mysqli_query($conn, "
    SELECT DISTINCT YEAR(tanggal) AS tahun, MONTH(tanggal) AS bulan
    FROM peminjaman_koperasi
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
$result  = false;
$totalPages = 0;

// hanya jalankan query kalau filter aktif
if ($filterPegawai) {
    $where = "WHERE k.koperasi='Y' AND p.stts_aktif='AKTIF'";
    if ($filterPegawai && $filterPegawai !== 'ALL') {
        $where .= " AND p.nik='".mysqli_real_escape_string($conn,$filterPegawai)."'";
    }

    // hitung jumlah data
    $sqlCount = "SELECT COUNT(DISTINCT p.id) AS total 
                 FROM pegawai p
                 JOIN keanggotaan k ON k.id = p.id
                 LEFT JOIN peminjaman_koperasi pk ON pk.id = p.id
                 $where";
    $resCount = mysqli_query($conn, $sqlCount);
    $rowCount = mysqli_fetch_assoc($resCount);
    $jmlData  = $rowCount['total'] ?? 0;

    $totalPages = $jmlData > 0 ? ceil($jmlData / $limit) : 1;

    // ambil data pegawai + status pinjaman
    $sql = "SELECT p.id, p.nik, p.nama,
                   CASE 
                     WHEN COUNT(pk.id)=0 THEN 'Tidak Ada Pinjaman'
                     ELSE 'Ada Pinjaman'
                   END AS ket
            FROM pegawai p
            JOIN keanggotaan k ON k.id = p.id
            LEFT JOIN peminjaman_koperasi pk ON pk.id = p.id
            WHERE k.koperasi='Y'
              AND p.stts_aktif='AKTIF'
            ".($filterPegawai && $filterPegawai!=='ALL' ? " AND p.nik='".mysqli_real_escape_string($conn,$filterPegawai)."'" : "")."
            GROUP BY p.id, p.nik, p.nama
            ORDER BY p.nik ASC
            LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn,$sql);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Peminjaman Koperasi</title>
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
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Peminjaman Koperasi</h5>
        <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
      </div>

      <div class="card-body p-3">

        <!-- Filter Pegawai -->
        <form method="get" class="mb-3">
          <label class="form-label">Filter Pegawai:</label>
          <select name="filterPegawai" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Pegawai --</option>
            <option value="ALL" <?= $filterPegawai==='ALL'?'selected':'' ?>>Semua</option>
            <?php while($p = mysqli_fetch_assoc($listPegawai)): ?>
              <option value="<?= $p['nik'] ?>" <?= ($filterPegawai==$p['nik'])?'selected':'' ?>>
                <?= $p['nik'].' - '.$p['nama'] ?>
              </option>
            <?php endwhile; ?>
          </select>
       
        <!-- Tombol Terapkan -->
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

        <!-- TABEL -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-koperasi align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>NIP</th>
                <th>Nama</th>
                <th>Keterangan Pinjam</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['ket']) ?></td>
                    <td class="text-center">
                      <a href="detail_pinjaman_koperasi.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Detail</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="4" class="text-center">Silakan pilih pegawai dan tahun hitung untuk menampilkan data</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlData ?>,
        </div>

        <!-- Pagination -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
              <!-- Tombol Prev -->
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>
                   &filterPegawai=<?= urlencode($filterPegawai) ?>
                   &tahunBulan=<?= urlencode($tahunBulan) ?>">« Prev</a>
              </li>

              <!-- Nomor Halaman -->
              <?php
                $start = max(1, $page - 1);
                $end   = min($totalPages, $page + 1);
                for ($i = $start; $i <= $end; $i++):
              ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>
                     &filterPegawai=<?= urlencode($filterPegawai) ?>
                     &tahunBulan=<?= urlencode($tahunBulan) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <!-- Tombol Next -->
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>
                   &filterPegawai=<?= urlencode($filterPegawai) ?>
                   &tahunBulan=<?= urlencode($tahunBulan) ?>">Next »</a>
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
