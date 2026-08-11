<?php
session_start();
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

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

// Filter
$validModes = ['tahun','bulan','tanggal'];
$modeParam  = $_GET['mode'] ?? '';
$mode       = in_array($modeParam,$validModes,true)?$modeParam:'';

$tahun = !empty($_GET['tahun'])?(int)$_GET['tahun']:null;
$bulan = !empty($_GET['bulan'])?(int)$_GET['bulan']:null;
$awal  = !empty($_GET['awal'])?$_GET['awal']:null;
$akhir = !empty($_GET['akhir'])?$_GET['akhir']:null;

$validChartTypes=['bar','pie','line','doughnut','polarArea','bubble','scatter'];
$chartParam=$_GET['chartType']??'';
$chartType=in_array($chartParam,$validChartTypes,true)?$chartParam:'';

// Nama bulan Indonesia
$namaBulan=[1=>"Januari",2=>"Februari",3=>"Maret",4=>"April",5=>"Mei",6=>"Juni",7=>"Juli",8=>"Agustus",9=>"September",10=>"Oktober",11=>"November",12=>"Desember"];

// Tambahan logika bulan & tanggal
$currentYear  = date('Y');
$currentMonth = date('n');
$today        = date('Y-m-d');

// default bulan terakhir
$bulanTerakhir = 12;

if ($mode==='bulan') {
    if ($tahun) {
        if ($tahun == $currentYear) {
            $bulanTerakhir = $currentMonth; // batasi sampai bulan aktif
        } else {
            $bulanTerakhir = 12; // tahun sebelumnya penuh
        }
    } else {
        // kalau tahun belum dipilih, default pakai bulan aktif tahun berjalan
        $bulanTerakhir = $currentMonth;
    }

    if (!$bulan) {
        $bulan = $bulanTerakhir; // otomatis pakai bulan terakhir
    }
}

if ($mode==='tanggal' && $awal && $akhir) {
    if ($akhir > $today) {
        $akhir = $today; // batasi akhir maksimal hari ini
    }
}

// Fungsi ambil data detail
function getData($conn,$field,$joinTable,$joinKey,$labelField,$mode,$tahun=null,$bulan=null,$awal=null,$akhir=null){
  $where="WHERE 1=1"; $params=[]; $types="";
  if($mode=='tahun' && $tahun){ $where.=" AND YEAR(p.tgl_insiden)=?"; $params[]=$tahun; $types.="i"; }
  if($mode=='bulan' && $tahun && $bulan){ $where.=" AND YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=?"; $params[]=$tahun; $params[]=$bulan; $types.="ii"; }
  if($mode=='tanggal' && $awal && $akhir){ $where.=" AND p.tgl_insiden BETWEEN ? AND ?"; $params[]=$awal; $params[]=$akhir; $types.="ss"; }
  $sql="SELECT $labelField AS label, COUNT(*) AS jumlah 
        FROM k3rs_peristiwa p 
        JOIN $joinTable t ON p.$field=t.$joinKey 
        $where GROUP BY $labelField";
  $stmt=$conn->prepare($sql);
  if(!empty($params)){ $stmt->bind_param($types,...$params); }
  $stmt->execute(); $res=$stmt->get_result();
  $labels=[]; $data=[];
  while($row=$res->fetch_assoc()){ $labels[]=$row['label']; $data[]=$row['jumlah']; }
  return [$labels,$data];
}

// Grafik utama
$peristiwaLabels=[]; $peristiwaData=[];
if($mode=='tahun' && $tahun){
  $sql="SELECT YEAR(tgl_insiden) AS label, COUNT(*) AS jumlah 
        FROM k3rs_peristiwa 
        WHERE YEAR(tgl_insiden)=? 
        GROUP BY YEAR(tgl_insiden)";
  $stmt=$conn->prepare($sql); $stmt->bind_param("i",$tahun); $stmt->execute(); $res=$stmt->get_result();
  while($row=$res->fetch_assoc()){ $peristiwaLabels[]=$row['label']; $peristiwaData[]=$row['jumlah']; }
}elseif($mode=='bulan' && $tahun && $bulan){
  $sql="SELECT DAY(tgl_insiden) AS hari, COUNT(*) AS jumlah 
        FROM k3rs_peristiwa 
        WHERE YEAR(tgl_insiden)=? AND MONTH(tgl_insiden)=? 
        GROUP BY DAY(tgl_insiden)";
  $stmt=$conn->prepare($sql); $stmt->bind_param("ii",$tahun,$bulan); $stmt->execute(); $res=$stmt->get_result();
  while($row=$res->fetch_assoc()){ 
      $peristiwaLabels[]=$row['hari']." ".$namaBulan[$bulan]." ".$tahun; 
      $peristiwaData[]=$row['jumlah']; 
  }
}elseif($mode=='tanggal' && $awal && $akhir){
  $sql="SELECT DATE(tgl_insiden) AS label, COUNT(*) AS jumlah 
        FROM k3rs_peristiwa 
        WHERE tgl_insiden BETWEEN ? AND ? 
        GROUP BY DATE(tgl_insiden)";
  $stmt=$conn->prepare($sql); $stmt->bind_param("ss",$awal,$akhir); $stmt->execute(); $res=$stmt->get_result();
  while($row=$res->fetch_assoc()){ $peristiwaLabels[]=$row['label']; $peristiwaData[]=$row['jumlah']; }
}

// Data detail
$cideraLabels=$cideraData=$penyebabLabels=$penyebabData=$lukaLabels=$lukaData=$lokasiLabels=$lokasiData=$dampakLabels=$dampakData=$pekerjaanLabels=$pekerjaanData=$bagianLabels=$bagianData=[];
if($mode){
  list($cideraLabels,$cideraData)=getData($conn,'kode_cidera','k3rs_jenis_cidera','kode_cidera','jenis_cidera',$mode,$tahun,$bulan,$awal,$akhir);
  list($penyebabLabels,$penyebabData)=getData($conn,'kode_penyebab','k3rs_penyebab','kode_penyebab','penyebab_kecelakaan',$mode,$tahun,$bulan,$awal,$akhir);
  list($lukaLabels,$lukaData)=getData($conn,'kode_luka','k3rs_jenis_luka','kode_luka','jenis_luka',$mode,$tahun,$bulan,$awal,$akhir);
  list($lokasiLabels,$lokasiData)=getData($conn,'kode_lokasi','k3rs_lokasi_kejadian','kode_lokasi','lokasi_kejadian',$mode,$tahun,$bulan,$awal,$akhir);
  list($dampakLabels,$dampakData)=getData($conn,'kode_dampak','k3rs_dampak_cidera','kode_dampak','dampak_cidera',$mode,$tahun,$bulan,$awal,$akhir);
  list($pekerjaanLabels,$pekerjaanData)=getData($conn,'kode_pekerjaan','k3rs_jenis_pekerjaan','kode_pekerjaan','jenis_pekerjaan',$mode,$tahun,$bulan,$awal,$akhir);
  list($bagianLabels,$bagianData)=getData($conn,'kode_bagian','k3rs_bagian_tubuh','kode_bagian','bagian_tubuh',$mode,$tahun,$bulan,$awal,$akhir);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Grafik K3</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="k3.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="main-content">
    <div class="container-fluid mt-4">
    <div class="card shadow">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Grafik Keselamatan & Kesehatan Kerja (K3)</h5>
          <div class="d-flex gap-2">
            <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <!-- BODY -->
        <div class="card-body p-3">
          <!-- Filter Tahun -->
          <form method="get" class="d-flex flex-nowrap align-items-center gap-3 mb-4" style="overflow-x:auto;">
            <div class="d-flex align-items-center gap-2">
              <label class="mb-0">Mode:</label>
              <select name="mode" class="form-select" onchange="this.form.submit()">
                <option value="">--pilih--</option>
                <option value="tahun" <?=($mode=='tahun'?'selected':'')?>>Per Tahun</option>
                <option value="bulan" <?=($mode=='bulan'?'selected':'')?>>Per Bulan</option>
                <option value="tanggal" <?=($mode=='tanggal'?'selected':'')?>>Per Tanggal</option>
              </select>
            </div>

            <?php if($mode=='tahun'): ?>
              <div class="d-flex align-items-center gap-2">
                <label class="mb-0">Tahun:</label>
                <select name="tahun" class="form-select">
                  <option value="">--pilih--</option>
                  <?php for($y=date('Y'); $y>=1990; $y--): ?>
                    <option value="<?=$y?>" <?=($tahun==$y?'selected':'')?>><?=$y?></option>
                  <?php endfor; ?>
                </select>
              </div>

            <?php elseif($mode=='bulan'): ?>
              <div class="d-flex align-items-center gap-2">
                <label class="mb-0">Tahun:</label>
                <select name="tahun" class="form-select">
                  <option value="">--pilih--</option>
                  <?php for($y=date('Y'); $y>=1990; $y--): ?>
                    <option value="<?=$y?>" <?=($tahun==$y?'selected':'')?>><?=$y?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="d-flex align-items-center gap-2">
                <label class="mb-0">Bulan:</label>
                <select name="bulan" class="form-select">
                  <option value="">--pilih--</option>
                  <?php for($m=1; $m<=$bulanTerakhir; $m++): ?>
                    <option value="<?=$m?>" <?=($bulan==$m?'selected':'')?>>
                      <?=$namaBulan[$m]?>
                    </option>
                  <?php endfor; ?>
                </select>
              </div>


            <?php elseif($mode=='tanggal'): ?>
              <div class="d-flex align-items-center gap-2">
                <label class="mb-0">Awal:</label>
                <input type="date" name="awal" value="<?=htmlspecialchars($awal??'')?>" class="form-control">
              </div>
              <div class="d-flex align-items-center gap-2">
                <label class="mb-0">Akhir:</label>
                <input type="date" name="akhir" value="<?=htmlspecialchars($akhir??'')?>" class="form-control" max="<?=date('Y-m-d')?>">
              </div>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-2">
              <label class="mb-0">Bentuk:</label>
              <select name="chartType" class="form-select">
                <option value="">--pilih--</option>
                <option value="bar" <?=($chartType=='bar'?'selected':'')?>>Bar</option>
                <option value="pie" <?=($chartType=='pie'?'selected':'')?>>Pie</option>
                <option value="line" <?= ($chartType == 'line' ? 'selected' : '') ?>>Line</option>
                <option value="doughnut" <?= ($chartType == 'doughnut' ? 'selected' : '') ?>>Doughnut</option>
                <option value="polarArea" <?= ($chartType=='polarArea'?'selected':'') ?>>Polar Area</option>
                <option value="bubble" <?= ($chartType=='bubble'?'selected':'') ?>>Bubble</option>
                <option value="scatter" <?= ($chartType=='scatter'?'selected':'') ?>>Scatter</option>
              </select>
            </div>

            <button type="submit" class="btn btn-success">Terapkan</button>
            <a href="?" class="btn btn-secondary">Reset</a>
          </form>

            <!-- Judul sesuai filter -->
            <?php if($mode): ?>
            <div class="mb-4 text-center">
              <h5>
                <?php if($mode=='tahun' && $tahun): ?>
                  Grafik Per Tahun (<?=$tahun?>)
                <?php elseif($mode=='bulan' && $tahun && $bulan): ?>
                  Grafik Per Bulan (<?=$namaBulan[$bulan]." ".$tahun?>)
                <?php elseif($mode=='tanggal' && $awal && $akhir): ?>
                  Grafik Per Tanggal (<?=$awal?> s/d <?=$akhir?>)
                <?php endif; ?>
              </h5>
            </div>
            <?php endif; ?>

            <!-- Grafik Utama -->
            <div class="chart-card text-center">
              <div class="chart-title">Peristiwa K3</div>
              <canvas id="chartPeristiwa"></canvas>
            </div>

            <!-- Grafik Detail -->
            <div class="row text-center">
              <div class="col-md-6"><div class="chart-card"><div class="chart-title">Jenis Cidera</div><canvas id="chartCidera"></canvas></div></div>
              <div class="col-md-6"><div class="chart-card"><div class="chart-title">Penyebab Kecelakaan</div><canvas id="chartPenyebab"></canvas></div></div>
              <div class="col-md-6"><div class="chart-card"><div class="chart-title">Jenis Luka</div><canvas id="chartLuka"></canvas></div></div>
              <div class="col-md-6"><div class="chart-card"><div class="chart-title">Lokasi Kejadian</div><canvas id="chartLokasi"></canvas></div></div>
              <div class="col-md-6"><div class="chart-card"><div class="chart-title">Dampak Cidera</div><canvas id="chartDampak"></canvas></div></div>
              <div class="col-md-6"><div class="chart-card"><div class="chart-title">Jenis Pekerjaan</div><canvas id="chartPekerjaan"></canvas></div></div>
              <div class="col-md-6"><div class="chart-card"><div class="chart-title">Bagian Tubuh</div><canvas id="chartBagian"></canvas></div></div>
            </div>
        </div> <!-- end card-body -->
      </div> <!-- end card -->
    </div> <!-- end container-fluid -->
  </main>

  <script>
  function renderChart(canvasId, labels, data, title, chartType){
    if(!chartType) return;
    // 👉 Tambahkan blok ini di sini
    if(chartType==='bubble'){
      data = data.map((val,i)=>({x:i,y:val,r:val}));
    }
    if(chartType==='scatter'){
      data = data.map((val,i)=>({x:i,y:val}));
    }
    // 👆 selesai sisipan
    new Chart(document.getElementById(canvasId), {
      type: chartType,
      data: { labels: labels, datasets: [{ label: title, data: data,
        backgroundColor: (chartType==='pie' || chartType==='doughnut' || chartType==='polarArea') ? [
          'rgba(255,99,132,0.7)','rgba(54,162,235,0.7)','rgba(255,206,86,0.7)',
          'rgba(75,192,192,0.7)','rgba(153,102,255,0.7)','rgba(255,159,64,0.7)'
        ] : 'rgba(54,162,235,0.7)',
        borderColor:'rgba(54,162,235,1)', fill: chartType==='line' ? false : true}]},
      options:{responsive:true,plugins:{legend:{display: chartType==='pie' || chartType==='doughnut' || chartType==='polarArea' || chartType==='line'},title:{display:true,text:title}}}
    });
  }

  <?php if($mode && $chartType): ?>
  renderChart('chartPeristiwa', <?=json_encode($peristiwaLabels)?>, <?=json_encode($peristiwaData)?>, '', '<?=$chartType?>');
  renderChart('chartCidera', <?=json_encode($cideraLabels)?>, <?=json_encode($cideraData)?>, '', '<?=$chartType?>');
  renderChart('chartPenyebab', <?=json_encode($penyebabLabels)?>, <?=json_encode($penyebabData)?>, '', '<?=$chartType?>');
  renderChart('chartLuka', <?=json_encode($lukaLabels)?>, <?=json_encode($lukaData)?>, '', '<?=$chartType?>');
  renderChart('chartLokasi', <?=json_encode($lokasiLabels)?>, <?=json_encode($lokasiData)?>, '', '<?=$chartType?>');
  renderChart('chartDampak', <?=json_encode($dampakLabels)?>, <?=json_encode($dampakData)?>, '', '<?=$chartType?>');
  renderChart('chartPekerjaan', <?=json_encode($pekerjaanLabels)?>, <?=json_encode($pekerjaanData)?>, '', '<?=$chartType?>');
  renderChart('chartBagian', <?=json_encode($bagianLabels)?>, <?=json_encode($bagianData)?>, '', '<?=$chartType?>');

  <?php endif; ?>
  </script>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
