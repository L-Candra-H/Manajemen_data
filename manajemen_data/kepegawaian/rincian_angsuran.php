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

// Ambil parameter
$idPegawai     = validTeks($_GET['id']);
$tanggalPinjam = validTeks($_GET['tanggal']);

// === Hapus Angsuran ===
if (isset($_GET['action']) && $_GET['action'] == 'hapus') {
    $idPegawai = validTeks($_GET['id']);
    $tglPinjam = validTeks($_GET['tanggal_pinjam']);
    $tglAngsur = validTeks($_GET['tanggal_angsur']);

    HapusAll("angsuran_koperasi 
              WHERE id='$idPegawai' 
              AND tanggal_pinjam='$tglPinjam' 
              AND tanggal_angsur='$tglAngsur'");

    header("Location: rincian_angsuran.php?id=$idPegawai&tanggal=$tglPinjam");
    exit;
}

// Ambil data pinjaman
$sqlPinjam = "SELECT pk.id, pk.banyak_angsur, pk.pinjaman, pk.pokok, pk.jasa, pk.tanggal, pk.status,
                     p.nik, p.nama
              FROM peminjaman_koperasi pk
              JOIN pegawai p ON pk.id=p.id
              WHERE pk.id='$idPegawai' AND pk.tanggal='$tanggalPinjam'";
$hasilPinjam = bukaquery($sqlPinjam);
$dataPinjam  = mysqli_fetch_assoc($hasilPinjam);

$pokok    = $dataPinjam['pokok'];
$jasa     = $dataPinjam['jasa'];
$setoran  = $pokok + $jasa;
$banyak   = $dataPinjam['banyak_angsur'];
$pinjaman = $dataPinjam['pinjaman'];

// Ambil data angsuran
$sqlAngsur = "SELECT * FROM angsuran_koperasi 
              WHERE id='$idPegawai' AND tanggal_pinjam='$tanggalPinjam'
              ORDER BY tanggal_angsur ASC";
$hasilAngsur = bukaquery($sqlAngsur);
$jmlData     = mysqli_num_rows($hasilAngsur);

// Hitung progress
$jml_sdh_angsur = $jmlData;
$ttl_sdh_angsur = $jml_sdh_angsur * $pokok;
$sisa_pinjam    = $pinjaman - $ttl_sdh_angsur;
$status         = ($jml_sdh_angsur >= $banyak) ? "Lunas" : "Belum Lunas";

// === Generate Angsuran ===
if (isset($_POST['BtnGenerate'])) {
    $noTerakhir = validangka($_POST['no_terakhir']);
    $mode       = $_POST['mode'] ?? 'semua';

    // tanggal dasar = tanggal pinjam + 4 hari + 1 bulan
    $tglBase    = date('Y-m-d', strtotime("$tanggalPinjam +4 days +1 month"));

    if ($mode == 'satu') {
        // Tambah 1x angsuran saja
        $i = $noTerakhir + 1;
        if ($i <= $banyak) {
            $tglAngsur = date('Y-m-d', strtotime("$tglBase +".($i-1)." month"));
            $cek = getOne("SELECT COUNT(*) FROM angsuran_koperasi 
                           WHERE id='$idPegawai' AND tanggal_pinjam='$tanggalPinjam' AND tanggal_angsur='$tglAngsur'");
            if ($cek == 0) {
                InsertData("angsuran_koperasi",
                    "'$idPegawai','$tanggalPinjam','$tglAngsur','$pokok','$jasa'");
            }
        }
    } else {
        // Generate semua sisa angsuran
        for ($i = $noTerakhir+1; $i <= $banyak; $i++) {
            $tglAngsur = date('Y-m-d', strtotime("$tglBase +".($i-1)." month"));
            $cek = getOne("SELECT COUNT(*) FROM angsuran_koperasi 
                           WHERE id='$idPegawai' AND tanggal_pinjam='$tanggalPinjam' AND tanggal_angsur='$tglAngsur'");
            if ($cek == 0) {
                InsertData("angsuran_koperasi",
                    "'$idPegawai','$tanggalPinjam','$tglAngsur','$pokok','$jasa'");
            }
        }
    }

    header("Location: rincian_angsuran.php?id=$idPegawai&tanggal=$tanggalPinjam");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rincian Angsuran</title>
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
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">
          RINCIAN PINJAMAN KOPERASI | <?= $dataPinjam['nik'] ?> - <?= $dataPinjam['nama'] ?> | <?= $tanggalPinjam ?>
        </h5>
        <div class="d-flex gap-2">
          <a href="detail_pinjaman_koperasi.php?id=<?= $idPegawai ?>" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
        
        <!-- DATA PINJAMAN (3 kolom) -->
        <div class="row">
          <div class="col-md-4">
            <div class="mb-1">NIP: <input class="form-control form-control-sm bg-danger" readonly value="<?= $dataPinjam['nik'] ?>"></div>
            <div class="mb-1">Nama: <input class="form-control form-control-sm bg-danger" readonly value="<?= $dataPinjam['nama'] ?>"></div>
            <div class="mb-1">Tanggal Pinjam: <input class="form-control form-control-sm bg-danger" readonly value="<?= $tanggalPinjam ?>"></div>
            <div class="mb-1">Jml. Angsuran: <input class="form-control form-control-sm bg-danger" readonly value="<?= $banyak ?>"></div>
          </div>
          <div class="col-md-4">
            <div class="mb-1">Jml. Pinjaman: <input class="form-control form-control-sm bg-danger" readonly value="<?= formatDuit($pinjaman) ?>"></div>
            <div class="mb-1">Pokok: <input class="form-control form-control-sm bg-danger" readonly value="<?= formatDuit($pokok) ?>"></div>
            <div class="mb-1">Jasa: <input class="form-control form-control-sm bg-danger" readonly value="<?= formatDuit($jasa) ?>"></div>
            <div class="mb-1">Setoran: <input class="form-control form-control-sm bg-danger" readonly value="<?= formatDuit($setoran) ?>"></div>
          </div>
          <div class="col-md-4">
            <div class="mb-1">Status Pinjaman: <input class="form-control form-control-sm bg-danger" readonly value="<?= $status ?>"></div>
            <div class="mb-1">Jml. Sdh Diangsur: <input class="form-control form-control-sm bg-danger" readonly value="<?= $jml_sdh_angsur ?>"></div>
            <div class="mb-1">Ttl. Sdh Diangsur: <input class="form-control form-control-sm bg-danger" readonly value="<?= formatDuit($ttl_sdh_angsur) ?>"></div>
            <div class="mb-1">Sisa Pinjaman: <input class="form-control form-control-sm bg-danger" readonly value="<?= formatDuit($sisa_pinjam) ?>"></div>
          </div>
        </div>

        <!-- GENERATE -->
        <form method="post" class="mt-3" id="formGenerate">
          <div class="mb-2">No. Angsuran Terakhir:
            <input type="number" id="no_terakhir" name="no_terakhir" 
                   class="form-control form-control-sm d-inline-block" 
                   style="width:100px" value="<?= $jml_sdh_angsur ?>" readonly>
            <button type="submit" name="BtnGenerate" class="btn btn-primary btn-sm" 
                    onclick="this.form.mode.value='semua'">Generate Semua</button>
            <button type="submit" name="BtnGenerate" class="btn btn-success btn-sm" 
                    onclick="this.form.mode.value='satu'">Tambah 1x Angsuran</button>
            <input type="hidden" name="mode" value="">
          </div>
        </form>

        <!-- TABEL ANGSURAN -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-koperasi align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>No</th>
                <th>Tgl. Angsur</th>
                <th>Pokok</th>
                <th>Jasa</th>
                <th>Angsuran</th>
                <th>Proses</th>
              </tr>
            </thead>
            <tbody>
              <?php $i=1; while($row = mysqli_fetch_assoc($hasilAngsur)): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= $row['tanggal_angsur'] ?></td>
                  <td><?= formatDuit($row['pokok']) ?></td>
                  <td><?= formatDuit($row['jasa']) ?></td>
                  <td><?= formatDuit($row['pokok']+$row['jasa']) ?></td>
                  <td>
                    <a href="rincian_angsuran.php?action=hapus&id=<?= $row['id'] ?>&tanggal_pinjam=<?= $row['tanggal_pinjam'] ?>&tanggal_angsur=<?= $row['tanggal_angsur'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Yakin hapus angsuran ini?')">🗑️ Hapus</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <!-- Info Data -->
        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlData ?>,
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

  <script>
    // Disable tombol jika sudah mencapai maksimal
    document.addEventListener("DOMContentLoaded", function() {
      const noTerakhir = parseInt(document.getElementById("no_terakhir").value);
      const maxAngsur = <?= $banyak ?>;
      if (noTerakhir >= maxAngsur) {
        document.querySelectorAll("#formGenerate button").forEach(btn => {
          btn.disabled = true;
        });
      }
    });
  </script>

</body>
</html>
