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

// Ambil filter dari query string
$mode  = $_GET['mode'] ?? '';
$tahun = trim($_GET['tahun'] ?? '');
$bulan = trim($_GET['bulan'] ?? '');

$where = "WHERE p.stts_aktif='AKTIF'";
$validFilter   = false;
$filterMessage = 'Silakan pilih Tahun untuk menampilkan grafik.';

// Hitung bulan terakhir aktif
$currentYear  = date('Y');
$currentMonth = date('n'); // bulan sekarang (1–12)
$bulanTerakhir = 12;
$namaBulan = [
  1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
  5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
  9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

if ($mode==='bulan' && $tahun !== '') {
    if (intval($tahun) == $currentYear) {
        // kalau tahun yang dipilih adalah tahun sekarang → batasi sampai bulan berjalan
        $bulanTerakhir = $currentMonth;
    } else {
        // tahun sebelumnya → selalu sampai Desember
        $bulanTerakhir = 12;
    }
}

// Logika filter
if ($mode === 'tahun' && $tahun !== '') {
    $where .= " AND YEAR(p.mulai_kerja) <= " . intval($tahun);
    $validFilter   = true;
    $filterMessage = "Pegawai aktif sampai tahun $tahun.";
} elseif ($mode === 'bulan' && $tahun !== '') {
    if ($bulan === '') {
        $bulan = $bulanTerakhir; // otomatis pakai bulan terakhir
    }
    $where .= " AND (
        YEAR(p.mulai_kerja) < " . intval($tahun) . "
        OR (YEAR(p.mulai_kerja) = " . intval($tahun) . " 
            AND MONTH(p.mulai_kerja) <= " . intval($bulan) . ")
    )";
    $validFilter   = true;
    $filterMessage = "Pegawai aktif sampai bulan $bulan-$tahun.";
}

// Query khusus Pendidikan
$queryPendidikan = "
SELECT pd.tingkat AS pendidikan_tingkat, COUNT(*) AS jumlah
FROM pegawai p
INNER JOIN pendidikan pd ON p.pendidikan = pd.tingkat
$where
GROUP BY pd.tingkat, pd.indek
ORDER BY pd.indek ASC;
";

// Query gabungan lain
$queryGabungan = "
SELECT p.*, 
       kj.nama_kelompok, 
       d.nama AS departemen_nama, 
       b.nama AS nama_bagian,
       r.nama_resiko, 
       e.nama_emergency, 
       s.ktg AS status_karyawan
FROM pegawai p
LEFT JOIN kelompok_jabatan kj ON p.kode_kelompok = kj.kode_kelompok
LEFT JOIN departemen d ON p.departemen = d.dep_id
LEFT JOIN bidang b ON p.bidang = b.nama
LEFT JOIN resiko_kerja r ON p.kode_resiko = r.kode_resiko
LEFT JOIN emergency_index e ON p.kode_emergency = e.kode_emergency
LEFT JOIN stts_kerja s ON p.stts_kerja = s.stts
$where
ORDER BY r.kode_resiko ASC, e.kode_emergency ASC
";

// Inisialisasi array kategori
$dataGender     = ['Pria'=>0,'Wanita'=>0];
$dataJabatan    = [];
$dataDepartemen = [];
$dataBagian     = [];
$dataResiko     = [];
$dataEmergency  = [];
$dataStatus     = [];
$dataPendidikan = [];
$dataLamaKerja  = [
    'Index 0 (<1 thn)' => 0,
    'Index 2 (1 thn)'  => 0,
    'Index 4 (2 thn)'  => 0,
    'Index 6 (3 thn)'  => 0,
    'Index 8 (4 thn)'  => 0,
    'Index 10 (5 thn)' => 0,
    'Index 12 (6 thn)' => 0,
    'Index 14 (>=7 thn)' => 0
];

// Eksekusi query
if ($validFilter) {
    // Pendidikan
    $resultPendidikan = mysqli_query($conn, $queryPendidikan);
    if ($resultPendidikan === false) {
        echo "<div class='alert alert-danger'>Query Pendidikan error: " . mysqli_error($conn) . "</div>";
    } else {
        while($row = mysqli_fetch_assoc($resultPendidikan)){
            $tingkat = $row['pendidikan_tingkat'] ?: 'Tidak Diketahui';
            $dataPendidikan[$tingkat] = (int)$row['jumlah'];
        }
    }

    // Gabungan lain
    $resultGabungan = mysqli_query($conn, $queryGabungan);
    if ($resultGabungan === false) {
        echo "<div class='alert alert-danger'>Query Gabungan error: " . mysqli_error($conn) . "</div>";
    } else {
        while($row = mysqli_fetch_assoc($resultGabungan)){
            if($row['jk']=='Pria') $dataGender['Pria']++; else $dataGender['Wanita']++;

            $jab = $row['nama_kelompok'] ?: 'Tidak Diketahui';
            $dataJabatan[$jab] = ($dataJabatan[$jab]??0)+1;

            $dep = $row['departemen_nama'] ?: 'Tidak Diketahui';
            $dataDepartemen[$dep] = ($dataDepartemen[$dep]??0)+1;

            $bag = $row['nama_bagian'] ?: 'Tidak Diketahui';
            $dataBagian[$bag] = ($dataBagian[$bag]??0)+1;

            $res = $row['nama_resiko'] ?: 'Tidak Diketahui';
            $dataResiko[$res] = ($dataResiko[$res]??0)+1;

            $emg = $row['nama_emergency'] ?: 'Tidak Diketahui';
            $dataEmergency[$emg] = ($dataEmergency[$emg]??0)+1;

            $st = $row['status_karyawan'] ?: 'Tidak Diketahui';
            $dataStatus[$st] = ($dataStatus[$st]??0)+1;

            // Lama kerja dihitung dari mulai_kerja
            $mulai = new DateTime($row['mulai_kerja']);
            $now   = new DateTime();
            $diffKerja = $mulai->diff($now);

            if ($diffKerja->y == 0) $dataLamaKerja['Index 0 (<1 thn)']++;
            elseif ($diffKerja->y == 1) $dataLamaKerja['Index 2 (1 thn)']++;
            elseif ($diffKerja->y == 2) $dataLamaKerja['Index 4 (2 thn)']++;
            elseif ($diffKerja->y == 3) $dataLamaKerja['Index 6 (3 thn)']++;
            elseif ($diffKerja->y == 4) $dataLamaKerja['Index 8 (4 thn)']++;
            elseif ($diffKerja->y == 5) $dataLamaKerja['Index 10 (5 thn)']++;
            elseif ($diffKerja->y == 6) $dataLamaKerja['Index 12 (6 thn)']++;
            elseif ($diffKerja->y >= 7) $dataLamaKerja['Index 14 (>=7 thn)']++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Grafik Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="main-content container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Grafik Pegawai</h5>
        <div class="d-flex gap-2">
          <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
        <!-- Filter -->
        <?php $currentYear = date('Y'); ?>
        <form method="get" class="d-flex flex-wrap align-items-center gap-3 mb-4">
          <div class="d-flex align-items-center gap-2">
            <label class="mb-0">Mode:</label>
            <select name="mode" class="form-select" onchange="this.form.submit()">
              <option value="">--pilih--</option>
              <option value="tahun" <?=($mode==='tahun'?'selected':'')?>>Per Tahun</option>
              <option value="bulan" <?=($mode==='bulan'?'selected':'')?>>Per Bulan</option>
            </select>
          </div>

          <div class="d-flex align-items-center gap-2">
            <label class="mb-0">Tahun:</label>
            <select name="tahun" class="form-select" onchange="this.form.submit()">
              <option value="">--pilih--</option>
              <?php for($y=$currentYear;$y>=1990;$y--): ?>
                <option value="<?=$y?>" <?=($tahun==$y?'selected':'')?>><?=$y?></option>
              <?php endfor; ?>
            </select>
          </div>

          <?php if ($mode==='bulan'): ?>
            <div class="d-flex align-items-center gap-2">
              <label class="mb-0">Bulan:</label>
              <select name="bulan" class="form-select" onchange="this.form.submit()">
                <option value="">--pilih--</option>
                <?php for($m=1; $m<=$bulanTerakhir; $m++): ?>
                  <option value="<?=$m?>" <?=($bulan==$m?'selected':'')?>>
                    <?=$namaBulan[$m]?>
                  </option>
                <?php endfor; ?>
              </select>
            </div>
          <?php endif; ?>

          <a href="?" class="btn btn-secondary">Reset</a>
        </form>

        <?php if ($validFilter && (
          !empty($dataGender) || 
          !empty($dataJabatan) || 
          !empty($dataDepartemen) || 
          !empty($dataBagian) || 
          !empty($dataResiko) || 
          !empty($dataEmergency) || 
          !empty($dataStatus) || 
          !empty($dataLamaKerja) || 
          !empty($dataPendidikan)
        )): ?>
          <!-- Grafik -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Jenis Kelamin</div>
                <div class="card-body"><canvas id="genderChart"></canvas></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Kelompok Jabatan</div>
                <div class="card-body"><canvas id="jabatanChart"></canvas></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Departemen</div>
                <div class="card-body"><canvas id="departemenChart"></canvas></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Bagian</div>
                <div class="card-body"><canvas id="bagianChart"></canvas></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Resiko Kerja</div>
                <div class="card-body"><canvas id="resikoChart"></canvas></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Tingkat Emergency</div>
                <div class="card-body"><canvas id="emergencyChart"></canvas></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Status Karyawan</div>
                <div class="card-body"><canvas id="statusChart"></canvas></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Lama Kerja</div>
                <div class="card-body"><canvas id="lamaChart"></canvas></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header text-center fw-bold">Pendidikan</div>
                <div class="card-body"><canvas id="pendidikanChart"></canvas></div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="alert alert-warning text-center">
            <?= htmlspecialchars($filterMessage) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    function makeChart(id, type, labels, data, title){
      new Chart(document.getElementById(id), {
        type: type,
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: [
              'rgba(54,162,235,0.7)',
              'rgba(255,99,132,0.7)',
              'rgba(255,206,86,0.7)',
              'rgba(75,192,192,0.7)',
              'rgba(153,102,255,0.7)',
              'rgba(255,159,64,0.7)'
            ]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          aspectRatio: (id==='resikoChart' || id==='emergencyChart') ? 1.2 : (type==='pie'?2:1.5),
          plugins: {
            legend: { display: type==='bar'?false:true },
            title: {
              display: false,
              text: title,
              font: { size: 14, weight: 'bold' }
            }
          }
        }
      });
    }

    // Render semua grafik
    makeChart('genderChart','bar',
      <?=json_encode(array_keys($dataGender))?>,
      <?=json_encode(array_values($dataGender))?>,
      'Jenis Kelamin'
    );

    makeChart('jabatanChart','bar',
      <?=json_encode(array_keys($dataJabatan))?>,
      <?=json_encode(array_values($dataJabatan))?>,
      'Kelompok Jabatan'
    );

    makeChart('departemenChart','bar',
      <?=json_encode(array_keys($dataDepartemen))?>,
      <?=json_encode(array_values($dataDepartemen))?>,
      'Departemen'
    );

    makeChart('bagianChart','bar',
      <?=json_encode(array_keys($dataBagian))?>,
      <?=json_encode(array_values($dataBagian))?>,
      'Bagian'
    );

    makeChart('resikoChart','pie',
      <?=json_encode(array_keys($dataResiko))?>,
      <?=json_encode(array_values($dataResiko))?>,
      'Resiko Kerja'
    );

    makeChart('emergencyChart','pie',
      <?=json_encode(array_keys($dataEmergency))?>,
      <?=json_encode(array_values($dataEmergency))?>,
      'Tingkat Emergency'
    );

    makeChart('statusChart','bar',
      <?=json_encode(array_keys($dataStatus))?>,
      <?=json_encode(array_values($dataStatus))?>,
      'Status Karyawan'
    );

    makeChart('lamaChart','pie',
      <?=json_encode(array_keys($dataLamaKerja))?>,
      <?=json_encode(array_values($dataLamaKerja))?>,
      'Lama Kerja'
    );

    makeChart('pendidikanChart','bar',
      <?=json_encode(array_keys($dataPendidikan))?>,
      <?=json_encode(array_values($dataPendidikan))?>,
      'Pendidikan'
    );
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
