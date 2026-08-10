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

// === Ambil tanggal input (default hari ini) ===
$tanggalInput = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
$tanggalFormat = date('Ymd', strtotime($tanggalInput));

// === Generate Nomor Pengkajian otomatis berdasarkan tanggal input ===
$qLast = $conn->query("SELECT nomor_penilaian 
                       FROM skp_penilaian 
                       WHERE nomor_penilaian LIKE 'SKP".$tanggalFormat."%' 
                       ORDER BY nomor_penilaian DESC LIMIT 1");
$lastKode = $qLast->num_rows>0 ? $qLast->fetch_assoc()['nomor_penilaian'] : 'SKP'.$tanggalFormat.'0000';
$lastNum = intval(substr($lastKode,-4))+1;
$newKode = 'SKP'.$tanggalFormat.str_pad($lastNum,4,'0',STR_PAD_LEFT);

// === Ambil data pegawai untuk dropdown ===
$qPegawai=$conn->query("SELECT nik,nama FROM pegawai ORDER BY nik ASC");
$pegawaiList=[];
while($row=$qPegawai->fetch_assoc()){
    $pegawaiList[]=$row;
}

// === Ambil data kriteria ===
$qKriteria=$conn->query("SELECT kode_kriteria,nama_kriteria FROM skp_kriteria_penilaian ORDER BY kode_kriteria ASC");
$kriteriaList=[];
while($row=$qKriteria->fetch_assoc()){
    $kriteriaList[]=$row;
}

// === Proses Simpan Penilaian ===
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi']) && $_POST['aksi']==='simpan'){
    // Ambil tanggal dari form
    $tanggalInput = $_POST['tanggal']; 
    $tanggalFormat = date('Ymd', strtotime($tanggalInput));

    // Generate nomor pengkajian berdasarkan tanggal input
    $qLast = $conn->query("SELECT nomor_penilaian 
                           FROM skp_penilaian 
                           WHERE nomor_penilaian LIKE 'SKP".$tanggalFormat."%' 
                           ORDER BY nomor_penilaian DESC LIMIT 1");
    $lastKode = $qLast->num_rows>0 ? $qLast->fetch_assoc()['nomor_penilaian'] : 'SKP'.$tanggalFormat.'0000';
    $lastNum = intval(substr($lastKode,-4))+1;
    $newKode = 'SKP'.$tanggalFormat.str_pad($lastNum,4,'0',STR_PAD_LEFT);

    // Gunakan $newKode untuk insert
    $nomor=$newKode;
    $nik_penilai=$_POST['nik_penilai'];
    $nik_dinilai=$_POST['nik_dinilai'];
    $tanggal=$_POST['tanggal'].' '.$_POST['jam'];
    $keterangan=$_POST['keterangan'];

    $sql="INSERT INTO skp_penilaian (nomor_penilaian,nik_dinilai,nik_penilai,tanggal,keterangan,status) VALUES (?,?,?,?,?,'Proses Penilaian')";
    $stmt=$conn->prepare($sql);
    $stmt->bind_param("sssss",$nomor,$nik_dinilai,$nik_penilai,$tanggal,$keterangan);
    $stmt->execute();

    foreach($kriteriaList as $krit){
        $kode=$krit['kode_kriteria'];
        $nilai=isset($_POST['nilai'][$kode])?$_POST['nilai'][$kode]:null;
        if($nilai){
            $sql2="INSERT INTO skp_detail_penilaian (nomor_penilaian,kode_kriteria,skala_penilaian) VALUES (?,?,?)";
            $stmt2=$conn->prepare($sql2);
            $stmt2->bind_param("sss",$nomor,$kode,$nilai);
            $stmt2->execute();
        }
    }
    echo "<script>alert('Data penilaian berhasil disimpan');window.location='penilaian.php';</script>";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengkajian Petugas/Dokter Dalam Implementasi SKP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="skp.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Pengkajian Petugas/Dokter Dalam Implementasi Sasaran Keselamatan Pasien</h5>
        <div class="d-flex gap-2">
          <a href="history_penilaian.php" class="btn btn-info btn-sm">📑 Daftar Penilaian</a>
          <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="simpan">

          <!-- Baris 1: No. Pengkajian + Yang Menilai -->
          <div class="row mb-3">
            <label class="col-md-2 col-form-label">No. Pengkajian :</label>
            <div class="col-md-4">
              <input type="text" name="nomor_penilaian" id="nomor_penilaian"
                     class="form-control bg-danger text-white" 
                     readonly>
            </div>

            <label class="col-md-2 col-form-label">Yang Menilai :</label>
            <div class="col-md-4 d-flex gap-2">
              <select name="nik_penilai" id="nik_penilai" class="form-select" required>
                <option value="">-- Pilih --</option>
                <?php foreach($pegawaiList as $peg): ?>
                  <option value="<?= $peg['nik']?>" data-nama="<?= $peg['nama']?>"><?= $peg['nik']?> - <?= $peg['nama']?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" id="nama_penilai" class="form-control bg-secondary text-white" readonly>
            </div>
          </div>

          <!-- Baris 2: Tanggal + Yang Dinilai -->
          <div class="row mb-3">
            <label class="col-md-2 col-form-label">Tanggal :</label>
            <div class="col-md-4 d-flex gap-2">
              <input type="date" name="tanggal" class="form-control" required>
              <input type="time" name="jam" class="form-control" required>
            </div>

            <label class="col-md-2 col-form-label">Yang Dinilai :</label>
            <div class="col-md-4 d-flex gap-2">
              <select name="nik_dinilai" id="nik_dinilai" class="form-select" required>
                <option value="">-- Pilih --</option>
                <?php foreach($pegawaiList as $peg): ?>
                  <option value="<?= $peg['nik']?>" data-nama="<?= $peg['nama']?>"><?= $peg['nik']?> - <?= $peg['nama']?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" id="nama_dinilai" class="form-control bg-secondary text-white" readonly>
            </div>
          </div>

          <!-- Baris 3 -->
          <div class="row mb-3">
            <div class="col-md-12 d-flex gap-2">
              <label class="me-2">Keterangan</label>
              <input type="text" name="keterangan" class="form-control" required>
            </div>
          </div>

          <!-- Tabel -->
          <div class="table-wrapper">
            <table class="table table-striped table-bordered table-master_skp_penilaian align-middle">
              <thead class="table-dark text-center">
                <tr>
                  <th>Kode Kriteria</th>
                  <th>Kriteria</th>
                  <th>Ya</th>
                  <th>Tidak</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($kriteriaList as $krit): ?>
                <tr>
                  <td><?= $krit['kode_kriteria']?></td>
                  <td><?= $krit['nama_kriteria']?></td>
                  <td class="text-center"><input type="radio" name="nilai[<?= $krit['kode_kriteria']?>]" value="Ya"></td>
                  <td class="text-center"><input type="radio" name="nilai[<?= $krit['kode_kriteria']?>]" value="Tidak"></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="text-center mt-3">
            <button type="submit" class="btn btn-success">💾 Simpan</button>
          </div>

        </form>
      </div>
    </div>
  </main>

  <script>
  document.addEventListener("DOMContentLoaded",function(){
    var penilai = document.getElementById('nik_penilai');
    var namaPenilai = document.getElementById('nama_penilai');
    var dinilai = document.getElementById('nik_dinilai');
    var namaDinilai = document.getElementById('nama_dinilai');

    // Saat dropdown "Yang Menilai" berubah
    penilai.addEventListener('change',function(){
      var opt = this.options[this.selectedIndex];
      namaPenilai.value = opt.getAttribute('data-nama') || '';

      // Sembunyikan pilihan yang sama di dropdown "Yang Dinilai"
      Array.from(dinilai.options).forEach(function(o){
        o.hidden = (o.value === opt.value);
      });

      // Reset pilihan "Yang Dinilai"
      dinilai.value = '';
      namaDinilai.value = '';
    });

    // Saat dropdown "Yang Dinilai" berubah
    dinilai.addEventListener('change',function(){
      var opt = this.options[this.selectedIndex];
      namaDinilai.value = opt.getAttribute('data-nama') || '';
    });
  });

  document.addEventListener("DOMContentLoaded",function(){
    var tanggalInput = document.querySelector("input[name='tanggal']");
    var nomorField = document.getElementById("nomor_penilaian");

    tanggalInput.addEventListener("change", function(){
      var tgl = new Date(this.value);
      if(!isNaN(tgl)){
        var y = tgl.getFullYear();
        var m = String(tgl.getMonth()+1).padStart(2,'0');
        var d = String(tgl.getDate()).padStart(2,'0');
        // preview nomor, urutan default 0001
        nomorField.value = "SKP" + y + m + d + "0001";
      }
    });
  });

  </script>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
