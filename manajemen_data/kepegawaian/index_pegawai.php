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

// pagination
$limit = 5;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// filter pegawai
$filter = isset($_GET['filter']) ? $conn->real_escape_string($_GET['filter']) : '';
$listPegawai = $conn->query("SELECT nik, nama FROM pegawai WHERE stts_aktif='AKTIF' ORDER BY nik");

// filter tahun
$listTahun = $conn->query("SELECT tahun, bulan FROM set_tahun ORDER BY tahun DESC, bulan DESC");

// array nama bulan
$bulanArr = [
    1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April", 5=>"Mei", 6=>"Juni",
    7=>"Juli", 8=>"Agustus", 9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"
];

// tentukan periode: default hari ini jika kosong
if (!empty($_GET['tahunHitung'])) {
    $tahunHitung = $conn->real_escape_string($_GET['tahunHitung']); // format YYYY-MM
} else {
    $tahunHitung = date('Y-m'); // default periode berjalan
}
list($tahunFilter, $bulanFilter) = explode('-', $tahunHitung);

// hitung jumlah pegawai
$jmlPegawai = 0;
if ($filter == 'ALL') {
    $countRes = $conn->query("SELECT COUNT(*) AS total FROM pegawai WHERE stts_aktif='AKTIF'");
} elseif ($filter != '') {
    $countRes = $conn->query("SELECT COUNT(*) AS total FROM pegawai WHERE nik='$filter' AND stts_aktif='AKTIF'");
} else {
    $countRes = false;
}
if ($countRes) {
    $rowCount   = $countRes->fetch_assoc();
    $jmlPegawai = $rowCount['total'];
}
$totalPages = ($jmlPegawai > 0) ? ceil($jmlPegawai / $limit) : 0;

// query utama (ALL atau PERSONAL)
$sql = "";
if ($filter == 'ALL') {
    $sql = "SELECT p.id, p.nik, p.nama, p.jbtn, p.pendidikan, p.mulai_kerja,
                   p.indek AS indek_struktural, p.pengurang, p.mulai_kontrak,
                   d.gapok1 AS gaji_pokok, p.dankes,
                   s.indek AS indek_status, s.hakcuti,
                   j.indek AS indek_jenjang, k.indek AS indek_kelompok,
                   r.indek AS indek_resiko, e.indek AS indek_emergency,
                   d.indek AS indek_pendidikan, ev.indek AS indek_evaluasi,
                   pc.indek AS indek_pencapaian, ad.total_dankes,
                   (COALESCE(p.cuti_diambil,0) + COALESCE(cu.cuti_bulan,0)) AS cuti_diambil,
                   (s.hakcuti - (COALESCE(p.cuti_diambil,0) + COALESCE(cu.cuti_bulan,0))) AS sisa_cuti
            FROM pegawai p
            LEFT JOIN stts_kerja s ON p.stts_kerja = s.stts
            LEFT JOIN jnj_jabatan j ON p.jnj_jabatan = j.kode
            LEFT JOIN kelompok_jabatan k ON p.kode_kelompok = k.kode_kelompok
            LEFT JOIN resiko_kerja r ON p.kode_resiko = r.kode_resiko
            LEFT JOIN emergency_index e ON p.kode_emergency = e.kode_emergency
            LEFT JOIN pendidikan d ON p.pendidikan = d.tingkat
            LEFT JOIN (
                SELECT ep.id, ep.kode_evaluasi
                FROM evaluasi_kinerja_pegawai ep
                WHERE CONCAT(ep.tahun,'-',LPAD(ep.bulan,2,'0')) <= '$tahunHitung'
                ORDER BY ep.tahun DESC, ep.bulan DESC
                LIMIT 1
            ) ep ON ep.id = p.id
            LEFT JOIN evaluasi_kinerja ev ON ep.kode_evaluasi = ev.kode_evaluasi
            LEFT JOIN (
                SELECT pp.id, pp.kode_pencapaian
                FROM pencapaian_kinerja_pegawai pp
                WHERE CONCAT(pp.tahun,'-',LPAD(pp.bulan,2,'0')) <= '$tahunHitung'
                ORDER BY pp.tahun DESC, pp.bulan DESC
                LIMIT 1
            ) pp ON pp.id = p.id
            LEFT JOIN pencapaian_kinerja pc ON pp.kode_pencapaian = pc.kode_pencapaian
            LEFT JOIN (
                SELECT id, COALESCE(SUM(dankes),0) AS total_dankes
                FROM ambil_dankes
                WHERE DATE_FORMAT(tanggal,'%Y-%c') <= '$tahunHitung'
                GROUP BY id
            ) ad ON ad.id = p.id
            LEFT JOIN (
                SELECT nik, COALESCE(SUM(jumlah),0) AS cuti_bulan
                FROM pengajuan_cuti
                WHERE status='Disetujui'
                  AND YEAR(tanggal_awal) = $tahunFilter
                  AND MONTH(tanggal_awal) <= $bulanFilter
                GROUP BY nik
            ) cu ON cu.nik = p.nik
            WHERE p.stts_aktif='AKTIF'
            ORDER BY p.nik
            LIMIT $limit OFFSET $offset";
} elseif ($filter != '' && $filter != 'ALL') {
    $sql = "SELECT p.id, p.nik, p.nama, p.jbtn, p.pendidikan, p.mulai_kerja,
                   p.indek AS indek_struktural, p.pengurang, p.mulai_kontrak,
                   d.gapok1 AS gaji_pokok, p.dankes,
                   s.indek AS indek_status, s.hakcuti,
                   j.indek AS indek_jenjang, k.indek AS indek_kelompok,
                   r.indek AS indek_resiko, e.indek AS indek_emergency,
                   d.indek AS indek_pendidikan, ev.indek AS indek_evaluasi,
                   pc.indek AS indek_pencapaian, ad.total_dankes,
                   (COALESCE(p.cuti_diambil,0) + COALESCE(cu.cuti_bulan,0)) AS cuti_diambil,
                   (s.hakcuti - (COALESCE(p.cuti_diambil,0) + COALESCE(cu.cuti_bulan,0))) AS sisa_cuti
            FROM pegawai p
            LEFT JOIN stts_kerja s ON p.stts_kerja = s.stts
            LEFT JOIN jnj_jabatan j ON p.jnj_jabatan = j.kode
            LEFT JOIN kelompok_jabatan k ON p.kode_kelompok = k.kode_kelompok
            LEFT JOIN resiko_kerja r ON p.kode_resiko = r.kode_resiko
            LEFT JOIN emergency_index e ON p.kode_emergency = e.kode_emergency
            LEFT JOIN pendidikan d ON p.pendidikan = d.tingkat
            LEFT JOIN (
                SELECT ep.id, ep.kode_evaluasi
                FROM evaluasi_kinerja_pegawai ep
                WHERE CONCAT(ep.tahun,'-',LPAD(ep.bulan,2,'0')) <= '$tahunHitung'
                ORDER BY ep.tahun DESC, ep.bulan DESC
                LIMIT 1
            ) ep ON ep.id = p.id
            LEFT JOIN evaluasi_kinerja ev ON ep.kode_evaluasi = ev.kode_evaluasi
            LEFT JOIN (
                SELECT pp.id, pp.kode_pencapaian
                FROM pencapaian_kinerja_pegawai pp
                WHERE CONCAT(pp.tahun,'-',LPAD(pp.bulan,2,'0')) <= '$tahunHitung'
                ORDER BY pp.tahun DESC, pp.bulan DESC
                LIMIT 1
            ) pp ON pp.id = p.id
            LEFT JOIN pencapaian_kinerja pc ON pp.kode_pencapaian = pc.kode_pencapaian
            LEFT JOIN (
                SELECT id, COALESCE(SUM(dankes),0) AS total_dankes
                FROM ambil_dankes
                WHERE DATE_FORMAT(tanggal,'%Y-%c') <= '$tahunHitung'
                GROUP BY id
            ) ad ON ad.id = p.id
            LEFT JOIN (
                SELECT nik, COALESCE(SUM(jumlah),0) AS cuti_bulan
                FROM pengajuan_cuti
                WHERE status='Disetujui'
                  AND YEAR(tanggal_awal) = $tahunFilter
                  AND MONTH(tanggal_awal) <= $bulanFilter
                GROUP BY nik
            ) cu ON cu.nik = p.nik
            WHERE p.nik = '$filter'
            ORDER BY p.nik";
}

// Eksekusi query
$result = ($sql != "") ? $conn->query($sql) : null;

// siapkan array hasil
$dataPegawai = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // hitung masa kerja
        $lamaKerja = "";
        $indexMasakerja = 0;
        if (!empty($row['mulai_kerja'])) {
            $mulaiKerja = new DateTime($row['mulai_kerja']);
            $endKerja   = new DateTime($tahunHitung . "-01");
            $diffKerja  = $mulaiKerja->diff($endKerja);
            $lamaKerja  = $diffKerja->y . " Tahun " . $diffKerja->m . " Bulan";
            if ($diffKerja->y == 1) $indexMasakerja = 2;
            elseif ($diffKerja->y == 2) $indexMasakerja = 4;
            elseif ($diffKerja->y == 3) $indexMasakerja = 6;
            elseif ($diffKerja->y == 4) $indexMasakerja = 8;
            elseif ($diffKerja->y == 5) $indexMasakerja = 10;
            elseif ($diffKerja->y == 6) $indexMasakerja = 12;
            elseif ($diffKerja->y >= 7) $indexMasakerja = 14;
        }

        // Lama Kontrak
        $lamaKontrak = "";
        if (!empty($row['mulai_kontrak'])) {
            $mulaiKontrak = new DateTime($row['mulai_kontrak']);
            $endKontrak   = new DateTime($tahunHitung . "-01");
            $diffKontrak  = $mulaiKontrak->diff($endKontrak);
            $lamaKontrak  = $diffKontrak->y . " Tahun " . $diffKontrak->m . " Bulan";
        }

        // Hitung cuti
        $hariCuti = (int)$row['cuti_diambil'];
        $sisaCuti = (int)$row['sisa_cuti'];

        // Sisa Dankes
        $sisaDankes = $row['dankes'] - ($row['total_dankes'] ?? 0);

        // Total Index
        $totalIndex = ($row['indek_pendidikan'] ?? 0) + $indexMasakerja +
                      ($row['indek_status'] ?? 0) +
                      ($row['indek_jenjang'] ?? 0) +
                      ($row['indek_kelompok'] ?? 0) +
                      ($row['indek_resiko'] ?? 0) +
                      ($row['indek_emergency'] ?? 0) +
                      ($row['indek_evaluasi'] ?? 0) +
                      ($row['indek_pencapaian'] ?? 0) +
                      ($row['indek_struktural'] ?? 0);

        // simpan hasil ke array
        $dataPegawai[] = [
            'row'           => $row,
            'lamaKerja'     => $lamaKerja,
            'indexMasakerja'=> $indexMasakerja,
            'lamaKontrak'   => $lamaKontrak,
            'hakCuti'       => (int)$row['hakcuti'],
            'hariCuti'      => $hariCuti,
            'sisaCuti'      => $sisaCuti,
            'sisaDankes'    => $sisaDankes,
            'totalIndex'    => $totalIndex
        ];
    }
}
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
    <a href="pegawai.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
  </div>

  <!-- FILTER -->
  <div class="card-body">
    <form method="get" class="mb-3">
      <!-- Filter Pegawai -->
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
  </div>

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
        if (empty($dataPegawai)) {
          echo "<tr><td colspan='27' class='text-center text-muted'>
                Silakan pilih pegawai dan tahun hitung untuk menampilkan data
                </td></tr>";
        } else {
          foreach ($dataPegawai as $dp) {
            $row = $dp['row'];
            echo "<tr>
              <td>{$row['nik']}</td>
              <td>{$row['nama']}</td>
              <td>{$row['jbtn']}</td>
              <td>{$row['pendidikan']}</td>
              <td>{$row['mulai_kerja']}</td>
              <td>{$dp['lamaKerja']}</td>
              <td>{$row['indek_pendidikan']}</td>
              <td>{$dp['indexMasakerja']}</td>
              <td>{$row['indek_status']}</td>
              <td>{$row['indek_jenjang']}</td>
              <td>{$row['indek_kelompok']}</td>
              <td>{$row['indek_resiko']}</td>
              <td>{$row['indek_emergency']}</td>
              <td>".($row['indek_evaluasi'] ?? 0)."</td>
              <td>".($row['indek_pencapaian'] ?? 0)."</td>
              <td>{$row['indek_struktural']}</td>
              <td>{$row['pengurang']}%</td>
              <td>{$dp['totalIndex']}</td>
              <td>{$row['mulai_kontrak']}</td>
              <td>{$dp['lamaKontrak']}</td>
              <td>".("Rp. ".number_format($row['gaji_pokok'],0,',','.'))."</td>
              <td>{$row['hakcuti']}</td>
              <td>{$dp['hariCuti']}</td>
              <td>{$dp['sisaCuti']}</td>
              <td>".("Rp. ".number_format($row['dankes'],0,',','.'))."</td>
              <td>".("Rp. ".number_format($dp['sisaDankes'],0,',','.'))."</td>
              <td>
                <button type='button' class='btn btn-warning btn-sm'
                        data-bs-toggle='modal' data-bs-target='#editModal'
                        data-nik='{$row['nik']}'>Edit</button>
                <a href='ambil_dankes.php?nik={$row['nik']}'
                   class='btn btn-info btn-sm'>Ambil Dankes</a>
              </td>
            </tr>";
          }
        }
        ?>
      </tbody>
    </table>
    <div class="mt-2 small text-start text-muted">
      Data : <?= $jmlPegawai ?>
    </div>
  </div>
</div>
</div>
</main>

<!-- Modal container -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" id="modalContent">
      <!-- Isi modal akan di-load dari edit_index.php -->
    </div>
  </div>
</div>

<!-- FOOTER Pagination -->
<?php if ($totalPages >= 1): ?>
<nav aria-label="Page navigation" class="mt-3">
  <ul class="pagination justify-content-center">
    <!-- Tombol Prev -->
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&filter=ALL">« Prev</a>
    </li>
    <!-- Nomor Halaman -->
    <?php
    $start = max(1, $page - 1);
    $end   = min($totalPages, $page + 1);
    for ($i = $start; $i <= $end; $i++): ?>
      <li class="page-item <?= $i == $page ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>&filter=ALL"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    <!-- Tombol Next -->
    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
      <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&filter=ALL">Next >></a>
    </li>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

<!-- Script Modal -->
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
</body>
</html>
