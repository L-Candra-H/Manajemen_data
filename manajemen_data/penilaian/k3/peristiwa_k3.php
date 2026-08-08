<?php
session_start();
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Pegawai.</div>";
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

$conn = bukakoneksi();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'delete') {
    $no_k3rs = $_POST['no_k3rs'] ?? null;
    if ($no_k3rs) {
        $stmt = $conn->prepare("DELETE FROM k3rs_peristiwa WHERE no_k3rs=?");
        $stmt->bind_param("s", $no_k3rs);
        if ($stmt->execute()) {
            header("Location: peristiwa_k3.php?mulai=".urlencode($_GET['mulai'])."&akhir=".urlencode($_GET['akhir']));
            exit;
        } else {
            die("Gagal hapus: " . $stmt->error);
        }
    }
}

// === PAGINATION ===
$limit = 5;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// === FILTER PERIODE ===
$mulai = $_GET['mulai'] ?? '';
$akhir = $_GET['akhir'] ?? '';

$sqlBase = "FROM k3rs_peristiwa p
    LEFT JOIN k3rs_jenis_pekerjaan jp ON p.kode_pekerjaan=jp.kode_pekerjaan
    LEFT JOIN k3rs_lokasi_kejadian lk ON p.kode_lokasi=lk.kode_lokasi
    LEFT JOIN k3rs_penyebab py ON p.kode_penyebab=py.kode_penyebab
    LEFT JOIN k3rs_jenis_cidera jc ON p.kode_cidera=jc.kode_cidera
    LEFT JOIN k3rs_jenis_luka jl ON p.kode_luka=jl.kode_luka
    LEFT JOIN k3rs_bagian_tubuh bt ON p.kode_bagian=bt.kode_bagian
    LEFT JOIN k3rs_dampak_cidera dc ON p.kode_dampak=dc.kode_dampak
    LEFT JOIN pegawai pk ON p.nik=pk.nik
    LEFT JOIN pegawai pl ON p.nik_pelapor=pl.nik
    LEFT JOIN pegawai tk ON p.nik_timk3=tk.nik";

$where = "";
if ($mulai && $akhir) {
    $where = " WHERE p.tgl_insiden BETWEEN '$mulai' AND '$akhir'";
    // hitung total data
    $countSql = "SELECT COUNT(*) AS total ".$sqlBase.$where;
    $countRes = mysqli_query($conn, $countSql);
    $rowCount = mysqli_fetch_assoc($countRes);
    $jmlData  = $rowCount['total'];
    $totalPages = ($jmlData > 0) ? ceil($jmlData / $limit) : 1;

    // ambil data per halaman
    $sql = "SELECT p.*, 
                   jp.jenis_pekerjaan, 
                   lk.lokasi_kejadian, 
                   py.penyebab_kecelakaan, 
                   jc.jenis_cidera, 
                   jl.jenis_luka, 
                   bt.bagian_tubuh, 
                   dc.dampak_cidera, 
                   pk.nama AS nama_korban, 
                   pl.nama AS nama_pelapor, 
                   tk.nama AS nama_timk3
            ".$sqlBase.$where."
            ORDER BY p.tgl_insiden DESC
            LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $sql);
} else {
    $result = false;
    $jmlData = 0;
    $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Peristiwa K3</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="k3.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="main-content">
    <div class="container-fluid mt-4">
      <div class="card shadow">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Peristiwa K3</h5>
          <div class="d-flex gap-2">
            <a href="tambah_peristiwa_k3.php" class="btn btn-light btn-sm">➕ Tambah</a>
            <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <!-- BODY -->
        <div class="card-body p-3">
          <!-- Filter Periode -->
          <form method="get" class="row g-2 align-items-center mb-3">
            <div class="col-auto">
              <label for="mulai" class="col-form-label col-form-label-sm fw-bold">Periode</label>
            </div>
            <div class="col-auto">
              <input type="date" id="mulai" name="mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($mulai) ?>">
            </div>
            <div class="col-auto fw-bold">s/d</div>
            <div class="col-auto">
              <input type="date" id="akhir" name="akhir" class="form-control form-control-sm" value="<?= htmlspecialchars($akhir) ?>">
            </div>
            <div class="col-auto">
              <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
            </div>
          </form>

          <!-- Wrapper untuk scroll -->
          <div class="table-wrapper">
            <table class="table table-striped table-bordered table-peristiwa align-middle">
              <thead class="table-dark text-center">
                <tr>
                  <th>No. Laporan</th>
                  <th>DTPCari Insiden</th>
                  <th>Jenis Pekerjaan</th>
                  <th>DTPCari Pelaporan</th>
                  <th>Lokasi Kejadian</th>
                  <th>Kronologi Kejadian</th>
                  <th>Aspek Penyebab</th>
                  <th>NIK Korban</th>
                  <th>Nama Korban</th>
                  <th>Kategori Cidera</th>
                  <th>LT (Hari)</th>
                  <th>Jenis Cidera</th>
                  <th>Jenis Luka</th>
                  <th>Bagian Tubuh</th>
                  <th>Kondisi Tidak Aman</th>
                  <th>Tindakan Tidak Aman</th>
                  <th>Pribadi</th>
                  <th>Pekerjaan</th>
                  <th>Dampak Kejadian</th>
                  <th>NIK Pelapor</th>
                  <th>Nama Pelapor</th>
                  <th>Barang Bukti</th>
                  <th>Jenis Tindakan</th>
                  <th>Rencana Tindakan</th>
                  <th>Target</th>
                  <th>Wewenang</th>
                  <th>Catatan</th>
                  <th>NIK Tim K3</th>
                  <th>Nama Tim K3</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$result): ?>
                  <tr><td colspan="33" class="text-center text-muted">Silakan pilih periode untuk menampilkan data</td></tr>
                <?php elseif (mysqli_num_rows($result) === 0): ?>
                  <tr><td colspan="33" class="text-center text-muted">Tidak ada data peristiwa</td></tr>
                <?php else: ?>
                  <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['no_k3rs']) ?></td>
                      <td><?= htmlspecialchars($row['tgl_insiden'].' '.$row['waktu_insiden']) ?></td>
                      <td><?= htmlspecialchars($row['jenis_pekerjaan']) ?></td>
                      <td><?= htmlspecialchars($row['tgl_pelaporan'].' '.$row['waktu_pelaporan']) ?></td>
                      <td><?= htmlspecialchars($row['lokasi_kejadian']) ?></td>
                      <td><?= htmlspecialchars($row['kronologi_kejadian']) ?></td>
                      <td><?= htmlspecialchars($row['penyebab_kecelakaan']) ?></td>
                      <td><?= htmlspecialchars($row['nik']) ?></td>
                      <td><?= htmlspecialchars($row['nama_korban']) ?></td>
                      <td><?= htmlspecialchars($row['kategori_cidera']) ?></td>
                      <td><?= htmlspecialchars($row['lt']) ?></td>
                      <td><?= htmlspecialchars($row['jenis_cidera']) ?></td>
                      <td><?= htmlspecialchars($row['jenis_luka']) ?></td>
                      <td><?= htmlspecialchars($row['bagian_tubuh']) ?></td>
                      <td><?= htmlspecialchars($row['penyebab_langsung_kondisi']) ?></td>
                      <td><?= htmlspecialchars($row['penyebab_langsung_tindakan']) ?></td>
                      <td><?= htmlspecialchars($row['penyebab_tidak_langsung_pribadi']) ?></td>
                      <td><?= htmlspecialchars($row['penyebab_tidak_langsung_pekerjaan']) ?></td>
                      <td><?= htmlspecialchars($row['dampak_cidera']) ?></td>
                      <td><?= htmlspecialchars($row['nik_pelapor']) ?></td>
                      <td><?= htmlspecialchars($row['nama_pelapor']) ?></td>
                      <td><?= htmlspecialchars($row['barang_bukti']) ?></td>
                      <td><?= htmlspecialchars($row['perbaikan_jenis_tindakan']) ?></td>
                      <td><?= htmlspecialchars($row['perbaikan_rencana_tindakan']) ?></td>
                      <td><?= htmlspecialchars($row['perbaikan_target']) ?></td>
                      <td><?= htmlspecialchars($row['perbaikan_wewenang']) ?></td>
                      <td><?= htmlspecialchars($row['catatan']) ?></td>
                      <td><?= htmlspecialchars($row['nik_timk3']) ?></td>
                      <td><?= htmlspecialchars($row['nama_timk3']) ?></td>
                      <td class="text-center">
                        <a href="edit_peristiwa_k3.php?no_k3rs=<?= urlencode($row['no_k3rs']) ?>" 
                             class="btn btn-warning btn-sm">✏️ Edit</a>
                        <form method="post" action="peristiwa_k3.php" style="display:inline">
                          <input type="hidden" name="mode" value="delete">
                          <input type="hidden" name="no_k3rs" value="<?= htmlspecialchars($row['no_k3rs']) ?>">
                          <button type="submit" class="btn btn-danger btn-sm"
                                  onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
                        </form>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Info Data -->
          <div class="mt-2 small text-muted">
            Jumlah Data : <?= $jmlData ?>
          </div>

          <!-- Pagination -->
          <?php if ($result && $jmlData > 0): ?>
            <nav aria-label="Page navigation" class="mt-3">
              <ul class="pagination pagination-sm justify-content-center">
                <!-- Prev -->
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                  <a class="page-link" href="?page=<?= $page-1 ?>&mulai=<?= urlencode($mulai) ?>&akhir=<?= urlencode($akhir) ?>">Prev</a>
                </li>

                <!-- Numbered pages -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&mulai=<?= urlencode($mulai) ?>&akhir=<?= urlencode($akhir) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>

                <!-- Next -->
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                  <a class="page-link" href="?page=<?= $page+1 ?>&mulai=<?= urlencode($mulai) ?>&akhir=<?= urlencode($akhir) ?>">Next</a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  
</body>
</html>
