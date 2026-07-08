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

// 🔧 proses update data dulu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nik'])) {
    $nik             = mysqli_real_escape_string($conn, $_POST['nik']);
    $indek_struktural= mysqli_real_escape_string($conn, $_POST['indek_struktural']);
    $pengurang       = mysqli_real_escape_string($conn, $_POST['pengurang']);
    $cuti_diambil    = mysqli_real_escape_string($conn, $_POST['cuti_diambil']);
    $dankes          = mysqli_real_escape_string($conn, $_POST['dankes']);

    $sqlUpdate = "UPDATE pegawai 
                  SET indek = '$indek_struktural',
                      pengurang = '$pengurang',
                      cuti_diambil = '$cuti_diambil',
                      dankes = '$dankes'
                  WHERE nik = '$nik'";
    mysqli_query($conn, $sqlUpdate);
}

// pagination
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// filter
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : '';
$listPegawai = mysqli_query($conn, "SELECT nik, nama FROM pegawai ORDER BY nik");

// default
$totalPages = 0;
$sql = "";

// query utama
if($filter == 'ALL') {
    $countRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pegawai");
    $totalRows = mysqli_fetch_assoc($countRes)['total'];
    $totalPages = ceil($totalRows / $limit);

    $sql = "SELECT p.id, p.nik, p.nama, p.jbtn, p.pendidikan, p.mulai_kerja,
                   p.indek AS indek_struktural, p.pengurang, p.mulai_kontrak,
                   d.gapok1 AS gaji_pokok, p.cuti_diambil, p.dankes,
                   s.indek AS indek_status, s.hakcuti,
                   j.indek AS indek_jenjang, k.indek AS indek_kelompok,
                   r.indek AS indek_resiko, e.indek AS indek_emergency,
                   d.indek AS indek_pendidikan, ev.indek AS indek_evaluasi,
                   pc.indek AS indek_pencapaian, ad.total_dankes
            FROM pegawai p
            LEFT JOIN stts_kerja s ON p.stts_kerja = s.stts
            LEFT JOIN jnj_jabatan j ON p.jnj_jabatan = j.kode
            LEFT JOIN kelompok_jabatan k ON p.kode_kelompok = k.kode_kelompok
            LEFT JOIN resiko_kerja r ON p.kode_resiko = r.kode_resiko
            LEFT JOIN emergency_index e ON p.kode_emergency = e.kode_emergency
            LEFT JOIN pendidikan d ON p.pendidikan = d.tingkat
            LEFT JOIN (
                SELECT ep.id, MAX(ep.kode_evaluasi) AS kode_evaluasi
                FROM evaluasi_kinerja_pegawai ep GROUP BY ep.id
            ) ep ON ep.id = p.nik
            LEFT JOIN evaluasi_kinerja ev ON ep.kode_evaluasi = ev.kode_evaluasi
            LEFT JOIN (
                SELECT pp.id, MAX(pp.kode_pencapaian) AS kode_pencapaian
                FROM pencapaian_kinerja_pegawai pp GROUP BY pp.id
            ) pp ON pp.id = p.nik
            LEFT JOIN pencapaian_kinerja pc ON pp.kode_pencapaian = pc.kode_pencapaian
            LEFT JOIN (
                SELECT id, COALESCE(SUM(dankes),0) AS total_dankes
                FROM ambil_dankes GROUP BY id
            ) ad ON ad.id = p.id
            ORDER BY p.nik
            LIMIT $limit OFFSET $offset";
} elseif($filter != '' && $filter != 'ALL') {
    $sql = "SELECT p.id, p.nik, p.nama, p.jbtn, p.pendidikan, p.mulai_kerja,
                   p.indek AS indek_struktural, p.pengurang, p.mulai_kontrak,
                   d.gapok1 AS gaji_pokok, p.cuti_diambil, p.dankes,
                   s.indek AS indek_status, s.hakcuti,
                   j.indek AS indek_jenjang, k.indek AS indek_kelompok,
                   r.indek AS indek_resiko, e.indek AS indek_emergency,
                   d.indek AS indek_pendidikan, ev.indek AS indek_evaluasi,
                   pc.indek AS indek_pencapaian, ad.total_dankes
            FROM pegawai p
            LEFT JOIN stts_kerja s ON p.stts_kerja = s.stts
            LEFT JOIN jnj_jabatan j ON p.jnj_jabatan = j.kode
            LEFT JOIN kelompok_jabatan k ON p.kode_kelompok = k.kode_kelompok
            LEFT JOIN resiko_kerja r ON p.kode_resiko = r.kode_resiko
            LEFT JOIN emergency_index e ON p.kode_emergency = e.kode_emergency
            LEFT JOIN pendidikan d ON p.pendidikan = d.tingkat
            LEFT JOIN (
                SELECT ep.id, MAX(ep.kode_evaluasi) AS kode_evaluasi
                FROM evaluasi_kinerja_pegawai ep GROUP BY ep.id
            ) ep ON ep.id = p.nik
            LEFT JOIN evaluasi_kinerja ev ON ep.kode_evaluasi = ev.kode_evaluasi
            LEFT JOIN (
                SELECT pp.id, MAX(pp.kode_pencapaian) AS kode_pencapaian
                FROM pencapaian_kinerja_pegawai pp GROUP BY pp.id
            ) pp ON pp.id = p.nik
            LEFT JOIN pencapaian_kinerja pc ON pp.kode_pencapaian = pc.kode_pencapaian
            LEFT JOIN (
                SELECT id, COALESCE(SUM(dankes),0) AS total_dankes
                FROM ambil_dankes GROUP BY id
            ) ad ON ad.id = p.id
            WHERE p.nik = '$filter'
            ORDER BY p.nik";
}

$result = ($sql != "") ? mysqli_query($conn, $sql) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Index Pegawai</title>
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
          <h5 class="mb-0 text-uppercase text-center flex-grow-1">Index Pegawai</h5>
          <a href="../index.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
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
                  <?= $peg['nik'] ?> - <?= $peg['nama'] ?>
                </option>
              <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
          </form>

          <!-- TABEL -->
          <div class="table-wrapper">
            <table class="table table-striped table-bordered table-index-pegawai align-middle">
              <thead class="table-dark text-center">
                <tr>
                  <th>NIP</th><th>Nama</th><th>Jabatan</th><th>Pendidikan</th>
                  <th>Mulai Kerja</th><th>Lama Kerja</th>
                  <th>Index Pendidikan</th><th>Index Masa Kerja</th><th>Index Status</th>
                  <th>Index Jenjang Jabatan</th><th>Index Kelompok Jabatan</th><th>Index Resiko Kerja</th>
                  <th>Index Tingkat Emergency</th><th>Index Evaluasi Kinerja</th><th>Index Pencapaian Kinerja</th>
                  <th>Index Struktural</th><th>Pengurang</th><th>Total Index</th>
                  <th>Mulai Kontrak</th><th>Lama Kontrak</th>
                  <th>Gaji Pokok</th><th>Hak Cuti</th><th>Cuti Diambil</th><th>Sisa Cuti</th>
                  <th>Dankes</th><th>Sisa Dankes</th><th>Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php
              if($sql == "") {
                  echo "<tr><td colspan='27' class='text-center text-muted'>Silakan pilih pegawai terlebih dahulu</td></tr>";
              } else {
                  while($row = mysqli_fetch_assoc($result)) {
                      $now = new DateTime();

                      // Lama Kerja & Index Masa Kerja
                      $lamaKerja = '';
                      $indexMasaKerja = 0;
                      if(!empty($row['mulai_kerja'])) {
                          $mulaiKerja = new DateTime($row['mulai_kerja']);
                          $diffKerja = $mulaiKerja->diff($now);
                          $lamaKerja = $diffKerja->y . " tahun " . $diffKerja->m . " bulan";

                          if($diffKerja->y == 1) $indexMasaKerja = 2;
                          elseif($diffKerja->y == 2) $indexMasaKerja = 4;
                          elseif($diffKerja->y == 3) $indexMasaKerja = 6;
                          elseif($diffKerja->y == 4) $indexMasaKerja = 8;
                          elseif($diffKerja->y == 5) $indexMasaKerja = 10;
                          elseif($diffKerja->y == 6) $indexMasaKerja = 12;
                          elseif($diffKerja->y >= 7) $indexMasaKerja = 14;
                      }

                      // Lama Kontrak
                      $lamaKontrak = '';
                      if(!empty($row['mulai_kontrak'])) {
                          $mulaiKontrak = new DateTime($row['mulai_kontrak']);
                          $diffKontrak = $mulaiKontrak->diff($now);
                          $lamaKontrak = $diffKontrak->y . " tahun " . $diffKontrak->m . " bulan";
                      }

                      // Sisa Cuti
                      $sisaCuti = $row['hakcuti'] - $row['cuti_diambil'];

                      // Sisa Dankes
                      $sisaDankes = $row['dankes'] - ($row['total_dankes'] ?? 0);
                      
                      // Total Index
                      $totalIndex = ($row['indek_pendidikan'] ?? 0) + $indexMasaKerja + ($row['indek_status'] ?? 0) +
                                    ($row['indek_jenjang'] ?? 0) + ($row['indek_kelompok'] ?? 0) + ($row['indek_resiko'] ?? 0) +
                                    ($row['indek_emergency'] ?? 0) + ($row['indek_evaluasi'] ?? 0) + ($row['indek_pencapaian'] ?? 0) +
                                    ($row['indek_struktural'] ?? 0);

                      echo "<tr>
                          <td>{$row['nik']}</td>
                          <td>{$row['nama']}</td>
                          <td>{$row['jbtn']}</td>
                          <td>{$row['pendidikan']}</td>
                          <td>{$row['mulai_kerja']}</td>
                          <td>{$lamaKerja}</td>
                          <td>{$row['indek_pendidikan']}</td>
                          <td>{$indexMasaKerja}</td>
                          <td>{$row['indek_status']}</td>
                          <td>{$row['indek_jenjang']}</td>
                          <td>{$row['indek_kelompok']}</td>
                          <td>{$row['indek_resiko']}</td>
                          <td>{$row['indek_emergency']}</td>
                          <td>".($row['indek_evaluasi'] ?? 0)."</td>
                          <td>".($row['indek_pencapaian'] ?? 0)."</td>
                          <td>{$row['indek_struktural']}</td>
                          <td>{$row['pengurang']}%</td>
                          <td>{$totalIndex}</td>
                          <td>{$row['mulai_kontrak']}</td>
                          <td>{$lamaKontrak}</td>
                          <td>".("Rp. " . number_format($row['gaji_pokok'], 0, ',', '.'))."</td>
                          <td>{$row['hakcuti']}</td>
                          <td>{$row['cuti_diambil']}</td>
                          <td>{$sisaCuti}</td>
                          <td>".("Rp. " . number_format($row['dankes'], 0, ',', '.'))."</td>
                          <td>".("Rp. " . number_format($sisaDankes, 0, ',', '.'))."</td>
                          <td>
                            <button type='button' class='btn btn-warning btn-sm' 
                                    data-bs-toggle='modal' data-bs-target='#editModal' 
                                    data-nik='{$row['nik']}'>
                              Edit
                            </button>
                            <a href='ambil_dankes.php?nik={$row['nik']}' class='btn btn-info btn-sm'>Ambil Dankes</a>
                          </td>
                        </tr>";
                  }
              }
              ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal container -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content" id="modalContent">
              <!-- Isi modal akan di-load dari edit_index.php -->
            </div>
          </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function(){
          var editModal = document.getElementById('editModal');
          editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var nik = button.getAttribute('data-nik');
            // load isi modal via AJAX
            fetch('edit_index.php?nik=' + nik)
              .then(response => response.text())
              .then(html => {
                document.getElementById('modalContent').innerHTML = html;
              });
          });
        });
        </script>

        <!-- FOOTER -->
        <?php if ($totalPages >= 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
          <ul class="pagination justify-content-center">
            <!-- Tombol Prev -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&filter=ALL">Prev</a>
            </li>

            <!-- Nomor Halaman (hanya 3: halaman aktif ±1) -->
            <?php for ($i = max(1, $page - 1); $i <= min($totalPages, $page + 1); $i++): ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&filter=ALL"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <!-- Tombol Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&filter=ALL">Next</a>
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
