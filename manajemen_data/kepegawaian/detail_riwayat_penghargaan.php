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

// --- LOGIKA HAPUS ---
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])){
    $id               = mysqli_real_escape_string($conn, $_POST['hapus_id']);
    $nama_penghargaan = mysqli_real_escape_string($conn, $_POST['hapus_nama_penghargaan']);
    $tanggal          = mysqli_real_escape_string($conn, $_POST['hapus_tanggal']);
    $instansi         = mysqli_real_escape_string($conn, $_POST['hapus_instansi']);

    $sqlDel = "DELETE FROM riwayat_penghargaan 
               WHERE id='$id'
                 AND nama_penghargaan='$nama_penghargaan' 
                 AND tanggal='$tanggal' 
                 AND instansi='$instansi' 
               LIMIT 1";

    if(mysqli_query($conn,$sqlDel)){
        $id = $_GET['id'] ?? '';
        header("Location: detail_riwayat_penghargaan.php?id=".$id);
        exit;
    } else {
        die("Gagal menghapus: ".mysqli_error($conn)." | Query: ".$sqlDel);
    }
}

// --- QUERY DATA RIWAYAT ---
$id = $_GET['id'] ?? '';
$sql = "SELECT r.*, p.nik, p.nama 
        FROM riwayat_penghargaan r 
        INNER JOIN pegawai p ON r.id=p.id 
        WHERE r.id='".mysqli_real_escape_string($conn,$id)."' 
        ORDER BY r.nama_penghargaan ASC";
$res = mysqli_query($conn,$sql);

$riwayat = [];
while($row = mysqli_fetch_assoc($res)){
    $riwayat[] = $row;
}

$dataPegawai = $riwayat[0] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Riwayat Penghargaan</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>

<?php include __DIR__ . '/../layout/header.php'; ?>

<main class="main-content container-fluid mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-uppercase text-center flex-grow-1">
        Detail Riwayat Penghargaan - <?= htmlspecialchars($dataPegawai['nama'] ?? '') ?>
      </h5>
      <div class="d-flex gap-2">
        <a href="riwayat_penghargaan.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
      </div>
    </div>
    <div class="card-body">

      <!-- Ringkasan Riwayat Penghargaan -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-riwayat_penghargaan align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Jenis Penghargaan</th>
              <th>Nama Penghargaan</th>
              <th>Tanggal</th>
              <th>Instansi Pemberi Penghargaan</th>
              <th>Pejabat Pemberi Penghargaan</th>
              <th>Berkas</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($riwayat)): ?>
              <tr><td colspan="10" class="text-center text-muted">Tidak ada riwayat penghargaan</td></tr>
            <?php else: ?>
              <?php foreach($riwayat as $rj): ?>
                <tr>
                  <td><?= $rj['jenis'] ?></td>
                  <td><?= $rj['nama_penghargaan'] ?></td>
                  <td><?= $rj['tanggal'] ?></td>
                  <td><?= $rj['instansi'] ?></td>
                  <td><?= $rj['pejabat_pemberi'] ?></td>
                  <td class="text-center">
                    <?php if($rj['berkas'] && !str_ends_with($rj['berkas'],'.pdf')): ?>
                      <?php 
                        $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                        $path = "/" . $appFolder . "/webapps/penggajian/pages/riwayatpenghargaan/berkas/" . basename($rj['berkas']);
                      ?>
                      <!-- Thumbnail -->
                      <img src="<?= $path ?>" class="img-thumbnail" style="max-height:80px;cursor:pointer"
                           data-bs-toggle="modal" data-bs-target="#lihatBerkas<?= $rj['id'] ?>">

                      <!-- Modal gambar -->
                      <div class="modal fade" id="lihatBerkas<?= $rj['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">Berkas Penghargaan</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                              <img src="<?= $path ?>" class="img-fluid">
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php elseif($rj['berkas'] && str_ends_with($rj['berkas'],'.pdf')): ?>
                      <?php 
                        $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                        $path = "/" . $appFolder . "/webapps/penggajian/pages/riwayatpenghargaan/berkas/" . basename($rj['berkas']);
                      ?>
                      <button type="button" class="btn btn-sm btn-info" 
                              data-bs-toggle="modal" data-bs-target="#lihatPdf<?= $rj['id'] ?>">
                        Lihat PDF
                      </button>
                      <div class="modal fade" id="lihatPdf<?= $rj['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">Berkas Penghargaan (PDF)</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                              <iframe src="<?= $path ?>" style="width:100%;height:600px;" frameborder="0"></iframe>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <form method="post" onsubmit="return confirm('Yakin hapus riwayat penghargaan ini?')">
                      <input type="hidden" name="hapus_id" value="<?= $rj['id'] ?>">
                      <input type="hidden" name="hapus_nama_penghargaan" value="<?= $rj['nama_penghargaan'] ?>">
                      <input type="hidden" name="hapus_tanggal" value="<?= $rj['tanggal'] ?>">
                      <input type="hidden" name="hapus_instansi" value="<?= $rj['instansi'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</main>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
