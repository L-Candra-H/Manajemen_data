<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

if(!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

$conn = bukakoneksi();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id     = $_POST['id'];
    $angla  = $_POST['angla'];
    $telpri = $_POST['telpri'];
    $pajak  = $_POST['pajak'];
    $pribadi= $_POST['pribadi'];
    $lain   = $_POST['lain'];
    $ktg    = $_POST['ktg'];

    $angkop = $_POST['angkop']; // ambil dari form
    $sqlUpdate = "UPDATE potongan
                  SET angkop='$angkop',
                      angla='$angla', telpri='$telpri', pajak='$pajak',
                      pribadi='$pribadi', lain='$lain', ktg='$ktg'
                  WHERE id='$id'";
    mysqli_query($conn, $sqlUpdate);
}

// Ambil filter pegawai dan tahunHitung
$filter      = isset($_GET['filter']) ? $_GET['filter'] : '';
$tahunHitung = isset($_GET['tahunHitung']) ? $_GET['tahunHitung'] : '';

// Pecah tahunHitung jadi tahun & bulan
if (!empty($tahunHitung)) {
    $parts = explode('-', $tahunHitung);
    $tahun = $parts[0] ?? date("Y");
    $bulan = $parts[1] ?? date("m");
} else {
    $tahun = date("Y");
    $bulan = date("m");
}

// Keyword default kosong
$keyword = isset($_GET['keyword']) ? validTeks($_GET['keyword']) : '';

// Ambil list pegawai untuk dropdown
$listPegawai = bukaquery("SELECT nik, nama FROM pegawai WHERE stts_aktif='AKTIF' ORDER BY nik ASC");

// Ambil list tahun-bulan untuk dropdown
$listTahun   = bukaquery("SELECT tahun, bulan FROM set_tahun ORDER BY tahun DESC, bulan DESC");

// Ambil nilai Dana Sosial dari tabel dansos (pilih urutan paling awal)
$sqlDansos = "SELECT dana FROM dansos ORDER BY dana ASC LIMIT 1";
$resDansos = mysqli_query($conn, $sqlDansos);
$rowDansos = mysqli_fetch_assoc($resDansos);
$nilaiDansos = $rowDansos['dana'] ?? 0;

// Array bulan untuk label
$bulanArr = [
  1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April",
  5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus",
  9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"
];

// Hitung total angsuran koperasi (pokok + jasa) untuk pegawai & periode tahun-bulan, status Belum Lunas
$sqlAngkop = "SELECT id, SUM(pokok + jasa) AS total_angkop
              FROM peminjaman_koperasi
              WHERE YEAR(tanggal) = '$tahun'
                AND MONTH(tanggal) = '$bulan'
                AND status = 'Belum Lunas'
              GROUP BY id";
$resAngkop = mysqli_query($conn, $sqlAngkop);

// simpan ke array agar mudah dipakai di modal
$angkopArr = [];
while($row = mysqli_fetch_assoc($resAngkop)) {
    $angkopArr[$row['id']] = $row['total_angkop'];
}

// Pagination
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 5;
$offset = ($page - 1) * $limit;

// Hitung total data
$countSql = "SELECT COUNT(*) AS total
             FROM pegawai p
             INNER JOIN departemen d ON p.departemen = d.dep_id
             INNER JOIN keanggotaan k ON p.id = k.id
             WHERE (p.nik LIKE '%$keyword%'
                OR p.nama LIKE '%$keyword%'
                OR d.nama LIKE '%$keyword%')
                AND p.stts_aktif='AKTIF'
             ".($filter && $filter!=='ALL' ? " AND p.nik='".mysqli_real_escape_string($conn,$filter)."'" : "");
$countRes = mysqli_query($conn, $countSql);
$totalRows = (int) mysqli_fetch_assoc($countRes)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;

$hasil = false; // default kosong

if ($filter || $tahunHitung) {
    $sql = "SELECT p.id, p.nik, p.nama, d.nama AS departemen,
                   k.koperasi, k.jamsostek AS jamsostek_stts, k.bpjs AS bpjs_stts,
                   j.biaya AS jamsostek_biaya, b.biaya AS bpjs_biaya,
                   COALESCE(pt.bpjs,0) AS bpjs,
                   COALESCE(pt.jamsostek,0) AS jamsostek,
                   COALESCE($nilaiDansos,0) AS dansos,
                   COALESCE(pt.simwajib,0) AS simwajib,
                   COALESCE(pt.angkop,0) AS angkop,
                   COALESCE(pt.angla,0) AS angla,
                   COALESCE(pt.telpri,0) AS telpri,
                   COALESCE(pt.pajak,0) AS pajak,
                   COALESCE(pt.pribadi,0) AS pribadi,
                   COALESCE(pt.lain,0) AS lain,
                   pt.ktg
            FROM pegawai p
            INNER JOIN departemen d ON p.departemen = d.dep_id
            INNER JOIN keanggotaan k ON p.id = k.id
            INNER JOIN jamsostek j ON k.jamsostek = j.stts
            INNER JOIN bpjs b ON k.bpjs = b.stts
            LEFT JOIN potongan pt 
                   ON pt.id = p.id
                  AND pt.tahun = '$tahun'
                  AND pt.bulan = '$bulan'
            WHERE (p.nik LIKE '%$keyword%'
                OR p.nama LIKE '%$keyword%'
                OR d.nama LIKE '%$keyword%')
                AND p.stts_aktif='AKTIF'
            ".($filter && $filter!=='ALL' ? " AND p.nik='".mysqli_real_escape_string($conn,$filter)."'" : "")."
            ORDER BY p.nik ASC
            LIMIT $limit OFFSET $offset";

    $hasil = bukaquery($sql);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Potongan Gaji Pegawai</title>
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
        <h5 class="mb-0 text-uppercase text-center flex-grow-1">Potongan Gaji Pegawai</h5>
        <div class="d-flex gap-2">
          <a href="dansos.php" class="btn btn-light btn-sm">📄 Set Dana Sosial</a>
          <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>
      <div class="card-body p-3">

        <!-- Filter Pegawai -->
        <form method="get" class="mb-3">            
          <label for="filter" class="form-label">Filter Pegawai:</label>
          <select name="filter" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Pegawai --</option>
            <option value="ALL" <?= $filter === 'ALL' ? 'selected' : '' ?>>Pilih Semua</option>
            <?php if ($listPegawai) { while ($p = $listPegawai->fetch_assoc()) { ?>
              <option value="<?= $p['nik'] ?>" <?= ($filter == $p['nik']) ? 'selected' : '' ?>>
                <?= $p['nik'].' - '.$p['nama'] ?>
              </option>
            <?php } } ?>
          </select>

        <!-- Filter Tahun Hitung -->
          <label for="tahunHitung" class="form-label">Filter Tahun Hitung:</label>
          <select name="tahunHitung" id="tahunHitung" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Tahun Hitung --</option>
            <?php if ($listTahun) { while ($thn = $listTahun->fetch_assoc()) {
              $val   = $thn['tahun'].'-'.$thn['bulan'];
              $label = $thn['tahun'].' '.$bulanArr[(int)$thn['bulan']];
              $selected = ($tahunHitung == $val) ? 'selected' : '';
              echo "<option value='{$val}' {$selected}>{$label}</option>";
            } } ?>
          </select>

          <!-- Tombol Terapkan -->
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

        <!-- Tabel -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-pegawai align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>NIP</th>
                <th>Nama</th>
                <th>Departemen</th>
                <th>Anggota Koperasi</th>
                <th>Anggota BPJS Ketenagakerjaan</th>
                <th>Anggota BPJS Kesehatan</th>
                <th>BPJS Kesehatan</th>
                <th>BPJS Ketenagakerjaan</th>
                <th>Dana Sosial</th>
                <th>Simpanan Wajib</th>
                <th>Angsuran Koperasi</th>
                <th>Angsuran Lain</th>
                <th>Telepon Pribadi</th>
                <th>Pajak</th>
                <th>Pribadi</th>
                <th>Lain-lain</th>
                <th>Total Potongan</th>
                <th>Keterangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $jmlPotongan = 0;
              if ($hasil && mysqli_num_rows($hasil) > 0) {
                  while($row = mysqli_fetch_assoc($hasil)){
                      $total = $row['bpjs'] + $row['jamsostek'] + $row['dansos'] + $row['simwajib'] +
                               $row['angkop'] + $row['angla'] + $row['telpri'] + $row['pajak'] +
                               $row['pribadi'] + $row['lain'];
                      $jmlPotongan += $total;
                      echo "<tr>
                              <td>{$row['nik']}</td>
                              <td>{$row['nama']}</td>
                              <td>{$row['departemen']}</td>
                              <td>{$row['koperasi']}</td>
                              <td>{$row['jamsostek_stts']}</td>
                              <td>{$row['bpjs_stts']}</td>
                              <td>".formatDuit($row['bpjs'])."</td>
                              <td>".formatDuit($row['jamsostek'])."</td>
                              <td>".formatDuit($nilaiDansos)."</td>
                              <td>".formatDuit($row['simwajib'])."</td>
                              <td>".formatDuit($row['angkop'])."</td>
                              <td>".formatDuit($row['angla'])."</td>
                              <td>".formatDuit($row['telpri'])."</td>
                              <td>".formatDuit($row['pajak'])."</td>
                              <td>".formatDuit($row['pribadi'])."</td>
                              <td>".formatDuit($row['lain'])."</td>
                              <td>".formatDuit($total)."</td>
                              <td>{$row['ktg']}</td>
                              <td class='text-center'>
                                  <button class='btn btn-warning btn-sm'
                                    data-bs-toggle='modal' data-bs-target='#modalUpdate'
                                    data-id='{$row['id']}'
                                    data-nik='{$row['nik']}'
                                    data-nama='{$row['nama']}'
                                    data-departemen='{$row['departemen']}'
                                    data-simwajib='{$row['simwajib']}'
                                    data-bpjs='{$row['bpjs']}'
                                    data-jamsostek='{$row['jamsostek']}'
                                    data-dansos='{$row['dansos']}'
                                    data-angkop='".(isset($angkopArr[$row['id']]) ? $angkopArr[$row['id']] : 0)."'
                                    data-angla='{$row['angla']}'
                                    data-telpri='{$row['telpri']}'
                                    data-pajak='{$row['pajak']}'
                                    data-pribadi='{$row['pribadi']}'
                                    data-lain='{$row['lain']}'
                                    data-ktg='{$row['ktg']}'
                                    onclick=\"isiUpdateModal(this)\">
                                    🔄 Update
                                  </button>
                                </td>
                            </tr>";
                  }
              } else {
                  echo "<tr><td colspan='19' class='text-center'>
                        Silakan pilih pegawai dan tahun hitung untuk menampilkan data
                        </td></tr>";
              }
              ?>
            </tbody>
          </table>

          <div class="mt-2 small text-start text-muted">
            Data : <?= $totalRows ?>,
            Jml.Ptg : <?= formatDuit($jmlPotongan) ?>
          </div>

        </div> 

        <!-- Pagination -->
        <?php if ($totalPages >= 1): ?>
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&keyword=<?= urlencode($keyword) ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>">Prev</a>
              </li>

              <?php
              $start = max(1, $page - 2);
              $end   = min($totalPages, $page + 2);
              for ($i = $start; $i <= $end; $i++):
              ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&keyword=<?= urlencode($keyword) ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>">Next</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div> 
  </main>

  <!-- Modal Update -->
  <div class="modal fade" id="modalUpdate" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Update Potongan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="post" action="potongan.php">
            <input type="hidden" name="id" id="updateId">

            <!-- NIP -->
            <div class="mb-3">
              <label class="form-label">NIP</label>
              <input type="text" id="updateNik" class="form-control bg-danger text-white fw-bold" readonly>
            </div>

            <!-- Nama -->
            <div class="mb-3">
              <label class="form-label">Nama</label>
              <input type="text" id="updateNama" class="form-control bg-danger text-white fw-bold" readonly>
            </div>

            <!-- Departemen -->
            <div class="mb-3">
              <label class="form-label">Departemen</label>
              <input type="text" id="updateDepartemen" class="form-control bg-danger text-white fw-bold" readonly>
            </div>

            <!-- Simpanan Wajib Koperasi -->
            <div class="mb-3">
              <label class="form-label">Simpanan Wajib Koperasi</label>
              <input type="text" id="updateSimwajib" class="form-control bg-danger text-white fw-bold" readonly>
            </div>

            <!-- BPJS Kesehatan -->
            <div class="mb-3">
              <label class="form-label">BPJS Kesehatan</label>
              <input type="text" id="updateBpjs" class="form-control bg-danger text-white fw-bold" readonly>
            </div>

            <!-- BPJS Ketenagakerjaan -->
            <div class="mb-3">
              <label class="form-label">BPJS Ketenagakerjaan</label>
              <input type="text" id="updateJamsostek" class="form-control bg-danger text-white fw-bold" readonly>
            </div>

            <!-- Dana Sosial -->
            <div class="mb-3">
              <label class="form-label">Dana Sosial</label>
              <input type="text" id="updateDansos" class="form-control bg-danger text-white fw-bold" readonly>
            </div>

            <!-- Angsuran Koperasi -->
            <div class="mb-3">
              <label class="form-label">Angsuran Koperasi</label>
              <input type="text" name="angkop" id="updateAngkop" class="form-control bg-danger text-white fw-bold" readonly>
            </div>

            <!-- Angsuran Lain -->
            <div class="mb-3">
              <label class="form-label">Angsuran Lain</label>
              <input type="text" name="angla" id="updateAngla" class="form-control">
            </div>

            <!-- Telepon Pribadi -->
            <div class="mb-3">
              <label class="form-label">Telepon Pribadi</label>
              <input type="text" name="telpri" id="updateTelpri" class="form-control">
            </div>

            <!-- Pajak -->
            <div class="mb-3">
              <label class="form-label">Pajak</label>
              <input type="text" name="pajak" id="updatePajak" class="form-control">
            </div>

            <!-- Pribadi -->
            <div class="mb-3">
              <label class="form-label">Pribadi</label>
              <input type="text" name="pribadi" id="updatePribadi" class="form-control">
            </div>

            <!-- Lain -->
            <div class="mb-3">
              <label class="form-label">Lain</label>
              <input type="text" name="lain" id="updateLain" class="form-control">
            </div>

            <!-- Keterangan -->
            <div class="mb-3">
              <label class="form-label">Keterangan</label>
              <input type="text" name="ktg" id="updateKtg" class="form-control">
            </div>

            <div class="modal-footer">
              <button type="submit" class="btn btn-warning">🔄 Update</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // contoh fungsi isiUpdateModal
    function isiUpdateModal(btn) {
      document.getElementById('updateId').value = btn.dataset.id;
      document.getElementById('updateNik').value = btn.dataset.nik;
      document.getElementById('updateNama').value = btn.dataset.nama;
      document.getElementById('updateDepartemen').value = btn.dataset.departemen;
      document.getElementById('updateSimwajib').value = btn.dataset.simwajib;
      document.getElementById('updateBpjs').value = btn.dataset.bpjs;
      document.getElementById('updateJamsostek').value = btn.dataset.jamsostek;
      document.getElementById('updateDansos').value = btn.dataset.dansos;
      document.getElementById('updateAngkop').value = btn.getAttribute('data-angkop');
      document.getElementById('updateAngla').value = btn.dataset.angla;
      document.getElementById('updateTelpri').value = btn.dataset.telpri;
      document.getElementById('updatePajak').value = btn.dataset.pajak;
      document.getElementById('updatePribadi').value = btn.dataset.pribadi;
      document.getElementById('updateLain').value = btn.dataset.lain;
      document.getElementById('updateKtg').value = btn.dataset.ktg;
    }
  </script>

</body>
</html>
