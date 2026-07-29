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

// ambil filter tahun-bulan
$tahunHitung = $_GET['tahunHitung'] ?? '';

// daftar tahun-bulan dari set_tahun
$listTahun = $conn->query("SELECT tahun, bulan FROM set_tahun ORDER BY tahun DESC, bulan DESC");

// pagination setup
$limit = 5;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// query data presensi hanya jika filter dipilih
$result = false;
$totalPages = 0;
if ($tahunHitung !== '') {
    [$tahun,$bulan] = explode('-', $tahunHitung);

    // hitung total pegawai
    $countSql = "SELECT COUNT(*) AS total FROM pegawai";
    $countRes = $conn->query($countSql);
    $countRow = $countRes->fetch_assoc();
    $totalData = $countRow['total'] ?? 0;
    $totalPages = $totalData > 0 ? ceil($totalData / $limit) : 0;

    $sql = "SELECT p.id, p.nik, p.nama,
                   SUM(CASE WHEN pr.jns='HB' THEN 1 ELSE 0 END) AS hadir_hb,
                   SUM(CASE WHEN pr.jns='HB' THEN pr.lembur ELSE 0 END) AS lembur_hb,
                   SUM(CASE WHEN pr.jns='HR' THEN 1 ELSE 0 END) AS hadir_hr,
                   SUM(CASE WHEN pr.jns='HR' THEN pr.lembur ELSE 0 END) AS lembur_hr
            FROM pegawai p
            LEFT JOIN presensi pr ON p.id = pr.id 
                 AND YEAR(pr.tgl)=? AND MONTH(pr.tgl)=?
            GROUP BY p.id, p.nik, p.nama
            ORDER BY p.nik
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $tahun, $bulan, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

// hitung jumlah pegawai aktif
$jmlPegawai = 0;
$countRes = $conn->query("SELECT COUNT(*) AS total FROM pegawai WHERE stts_aktif='AKTIF'");
if ($countRes) {
    $rowCount   = $countRes->fetch_assoc();
    $jmlPegawai = $rowCount['total'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lembur Pegawai</title>
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
          <h5 class="mb-0 text-uppercase text-center flex-grow-1">Lembur Pegawai</h5>
          <div class="d-flex gap-2">
            <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <!-- Filter Tahun Hitung -->
        <div class="card-body p-3">
          <form method="get" class="mb-3">
            <label for="tahunHitung" class="form-label">Filter Tahun Hitung :</label>
            <select name="tahunHitung" id="tahunHitung" class="form-select form-select-sm"
                    style="max-width:220px; display:inline-block;">
              <option value="">-- Pilih Tahun Hitung --</option>
              <?php
              if ($listTahun) {
                  while($thn = $listTahun->fetch_assoc()) {
                      $val   = $thn['tahun'].'-'.$thn['bulan'];
                      $label = $thn['tahun'].' - '.$bulanArr[(int)$thn['bulan']];
                      ?>
                      <option value="<?= $val ?>" <?= ($tahunHitung==$val)?'selected':'' ?>>
                        <?= $label ?>
                      </option>
                      <?php
                  }
              }
              ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
          </form>

          <!-- Tabel -->
          <div class="table-wrapper">
            <table class="table table-bordered table-striped align-middle table-lembur">
              <thead class="table-dark text-center">
                <tr>
                  <th>NIP</th>
                  <th>Nama</th>
                  <th>Hadir HB</th>
                  <th>Index Lembur HB</th>
                  <th>Hadir HR</th>
                  <th>Index Lembur HR</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['nik']) ?></td>
                      <td><?= htmlspecialchars($row['nama']) ?></td>
                      <td class="text-center"><?= $row['hadir_hb'] ?></td>
                      <td class="text-center"><?= $row['lembur_hb'] ?></td>
                      <td class="text-center"><?= $row['hadir_hr'] ?></td>
                      <td class="text-center"><?= $row['lembur_hr'] ?></td>
                      <td class="text-center">
                        <a href="detail_lembur_pegawai.php?id=<?= $row['id'] ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>" class="btn btn-info btn-sm">Detail Lembur</a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center">Silakan pilih tahun hitung untuk menampilkan data</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

            <div class="mt-2 small text-start text-muted">
              Data : <?= $jmlPegawai ?>,
            </div>
            
          </div>

          <!-- Pagination -->
          <?php if ($totalPages >= 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
              <ul class="pagination justify-content-center">
                <!-- Tombol Prev -->
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="?tahunHitung=<?= urlencode($tahunHitung) ?>&page=<?= max(1, $page - 1) ?>">« Prev</a>
                </li>

                <!-- Nomor Halaman -->
                <?php
                  $start = max(1, $page - 1);
                  $end   = min($totalPages, $page + 1);
                  for ($i = $start; $i <= $end; $i++):
                ?>
                  <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?tahunHitung=<?= urlencode($tahunHitung) ?>&page=<?= $i ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>

                <!-- Tombol Next -->
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="?tahunHitung=<?= urlencode($tahunHitung) ?>&page=<?= min($totalPages, $page + 1) ?>">Next »</a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

</body>
</html>
