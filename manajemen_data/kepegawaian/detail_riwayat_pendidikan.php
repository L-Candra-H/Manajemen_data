<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

if(!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

$conn = bukakoneksi();

// --- LOGIKA HAPUS ---
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])){
    $id         = mysqli_real_escape_string($conn, $_POST['hapus_id']);
    $pendidikan = mysqli_real_escape_string($conn, $_POST['hapus_pendidikan']);
    $thn_lulus  = mysqli_real_escape_string($conn, $_POST['hapus_thn_lulus']);

    $sqlDel = "DELETE FROM riwayat_pendidikan 
               WHERE id='$id' 
                  AND pendidikan='$pendidikan' 
                  AND thn_lulus='$thn_lulus' 
               LIMIT 1";

    if(mysqli_query($conn,$sqlDel)){
        $id = $_GET['id'] ?? '';
        header("Location: detail_riwayat_pendidikan.php?id=".$id);
        exit;
    } else {
        die("Gagal menghapus: ".mysqli_error($conn)." | Query: ".$sqlDel);
    }
}

// --- QUERY DATA RIWAYAT ---
$id = $_GET['id'] ?? '';
$sql = "SELECT r.*, p.nik, p.nama
        FROM riwayat_pendidikan r
        INNER JOIN pegawai p ON r.id=p.id
        WHERE r.id='".mysqli_real_escape_string($conn,$id)."'
        ORDER BY r.pendidikan ASC";
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
  <title>Detail Riwayat Pendidikan</title>
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
        Detail Riwayat Pendidikan - <?= htmlspecialchars($dataPegawai['nama'] ?? '') ?>
      </h5>
      <div class="d-flex gap-2">
        <a href="riwayat_pendidikan.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
      </div>
    </div>
    <div class="card-body">

      <!-- Ringkasan Riwayat Pendidikan -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-riwayat_pendidikan align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Pendidikan</th>
              <th>Sekolah/Kampus</th>
              <th>Jurusan</th>
              <th>Lulus</th>
              <th>Kepala/Rektor</th>
              <th>Asal Pendanaan</th>
              <th>Keterangan</th>
              <th>Status</th>
              <th>Berkas</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($riwayat)): ?>
              <tr><td colspan="10" class="text-center text-muted">Tidak ada riwayat pendidikan</td></tr>
            <?php else: ?>
              <?php foreach($riwayat as $rp): ?>
                <tr>
                  <td><?= $rp['pendidikan'] ?></td>
                  <td><?= $rp['sekolah'] ?></td>
                  <td><?= $rp['jurusan'] ?></td>
                  <td><?= $rp['thn_lulus'] ?></td>
                  <td><?= $rp['kepala'] ?></td>
                  <td><?= $rp['pendanaan'] ?></td>
                  <td><?= $rp['keterangan'] ?></td>
                  <td><?= $rp['status'] ?></td>
                  <td class="text-center">
                    <?php if($rp['berkas'] && !str_ends_with($rp['berkas'],'.pdf')): ?>
                      <?php 
                        $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                        $path = "/" . $appFolder  . "/webapps/penggajian/pages/riwayatpendidikan/berkas/" . basename($rp['berkas']);
                      ?>
                      <!-- Thumbnail -->
                      <img src="<?= $path ?>" class="img-thumbnail" style="max-height:80px;cursor:pointer"
                           data-bs-toggle="modal" data-bs-target="#lihatBerkas<?= $rp['id'] ?>">

                      <!-- Modal gambar -->                        
                      <div class="modal fade" id="lihatBerkas<?= $rp['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">Berkas Pendidikan</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                              <img src="<?= $path ?>" class="img-fluid">
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php elseif($rp['berkas'] && str_ends_with($rp['berkas'],'.pdf')): ?>
                      <?php
                        $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                        $path = "/" . $appFolder . "/webapps/penggajian/pages/riwayatpendidikan/berkas/" . basename($rp['berkas']);
                      ?>
                      <button type="button" class="btn btn-sm btn-info" 
                              data-bs-toggle="modal" data-bs-target="#lihatPdf<?= $rp['id'] ?>">
                        Lihat PDF
                      </button>
                      <div class="modal fade" id="lihatPdf<?= $rp['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">Berkas Pendidikan (PDF)</h5>
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
                    <form method="post" onsubmit="return confirm('Yakin hapus riwayat pendidikan ini?')">
                      <input type="hidden" name="hapus_id" value="<?= $rp['id'] ?>">
                      <input type="hidden" name="hapus_pendidikan" value="<?= $rp['pendidikan'] ?>">
                      <input type="hidden" name="hapus_thn_lulus" value="<?= $rp['thn_lulus'] ?>">
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
