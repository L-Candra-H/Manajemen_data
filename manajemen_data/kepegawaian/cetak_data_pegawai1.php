<?php
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = bukakoneksi();

// ambil NIK dari session (id_user = nik hasil decrypt)
$nikLogin = $_SESSION['id_user'] ?? '';

if (empty($nikLogin)) {
    echo "<div class='alert alert-danger'>Tidak ada data pegawai untuk user login.</div>";
    exit;
}

// query data pegawai + keanggotaan + status nikah
$sqlPegawai = "SELECT p.*, 
                      kelompok_jabatan.nama_kelompok,
                      jnj_jabatan.nama AS nama_jenjang,
                      departemen.nama AS nama_departemen,
                      stts_wp.ktg AS stts_wp_ktg,
                      stts_kerja.ktg AS stts_kerja_ktg,
                      k.koperasi, k.jamsostek, k.bpjs,
                      pt.stts_nikah AS stts_nikah_petugas,
                      dk.stts_nikah AS stts_nikah_dokter,
                      pt.email AS email_petugas,
                      dk.email AS email_dokter,
                      pt.agama AS agama_petugas,
                      dk.agama AS agama_dokter

               FROM pegawai p
               LEFT JOIN kelompok_jabatan ON p.kode_kelompok = kelompok_jabatan.kode_kelompok
               LEFT JOIN jnj_jabatan ON p.jnj_jabatan = jnj_jabatan.kode
               LEFT JOIN departemen ON p.departemen = departemen.dep_id
               LEFT JOIN stts_wp ON p.stts_wp = stts_wp.stts
               LEFT JOIN stts_kerja ON p.stts_kerja = stts_kerja.stts
               LEFT JOIN keanggotaan k ON k.id = p.id
               LEFT JOIN petugas pt ON pt.nip = p.nik
               LEFT JOIN dokter dk ON dk.kd_dokter = p.nik
               WHERE p.nik='".mysqli_real_escape_string($conn,$nikLogin)."' LIMIT 1";

$resultPegawai = mysqli_query($conn, $sqlPegawai);
$pegawai = mysqli_fetch_assoc($resultPegawai);

mysqli_close($conn);

// helper untuk foto
function purl($path){
  $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
  return "/" . $appFolder . "/webapps/penggajian/" . htmlspecialchars($path);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Cetak Data Pegawai</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

<div class="card shadow">
  <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0 text-uppercase">Cetak Biodata Pegawai</h5>
    <a href="../index.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
  </div>

  <div class="card-body">
    <!-- DATA INDUK -->
    <h6 class="fw-bold mt-3">A. Data Induk</h6>
    <table class="table table-bordered table-sm">
      <tr><td><strong>No. KTP</strong></td><td><?= $pegawai['no_ktp'] ?? '-' ?></td></tr>
      <tr><td><strong>Nama</strong></td><td><?= $pegawai['nama'] ?? '-' ?></td></tr>
      <tr><td><strong>Tempat / Tgl Lahir</strong></td><td><?= $pegawai['tmp_lahir'] ?? '-' ?> / <?= !empty($pegawai['tgl_lahir']) ? date('d-m-Y', strtotime($pegawai['tgl_lahir'])) : '-' ?></td></tr>
      <tr><td><strong>Jenis Kelamin</strong></td><td><?= $pegawai['jk'] ?? '-' ?></td></tr>
      <tr><td><strong>Agama</strong></td><td><?= $pegawai['agama_petugas'] ?? $pegawai['agama_dokter'] ?? '-' ?></td></tr>
      <tr><td><strong>Alamat / Kota</strong></td><td><?= $pegawai['alamat'] ?? '-' ?> / <?= $pegawai['kota'] ?? '-' ?></td></tr>
      <tr><td><strong>Email</strong></td><td><?= $pegawai['email_petugas'] ?? $pegawai['email_dokter'] ?? '-' ?></td></tr>
      <tr><td><strong>Status Nikah</strong></td><td><?= $pegawai['stts_nikah_petugas'] ?? $pegawai['stts_nikah_dokter'] ?? '-' ?></td></tr>
      <tr><td><strong>Bank / Rekening</strong></td><td><?= $pegawai['bpd'] ?? '-' ?> / <?= $pegawai['rekening'] ?? '-' ?></td></tr>
      <tr><td><strong>Jabatan / Jenjang / Kelompok</strong></td><td><?= $pegawai['jbtn'] ?? '-' ?> / <?= $pegawai['nama_jenjang'] ?? '-' ?> / <?= $pegawai['nama_kelompok'] ?? '-' ?></td></tr>
      <tr><td><strong>Departemen / Bagian</strong></td><td><?= $pegawai['nama_departemen'] ?? '-' ?> / <?= $pegawai['bidang'] ?? '-' ?></td></tr>
      <tr><td><strong>Status WP</strong></td><td><?= $pegawai['stts_wp_ktg'] ?? '-' ?></td></tr>
      <tr><td><strong>Status Pekerjaan</strong></td><td><?= $pegawai['stts_kerja_ktg'] ?? '-' ?></td></tr>
      <tr><td><strong>NPWP</strong></td><td><?= $pegawai['npwp'] ?? '-' ?></td></tr>
      <tr><td><strong>Mulai Kerja</strong></td><td><?= !empty($pegawai['mulai_kerja']) ? date('d-m-Y', strtotime($pegawai['mulai_kerja'])) : '-' ?></td></tr>
      <tr><td><strong>Mulai Kontrak</strong></td><td><?= !empty($pegawai['mulai_kontrak']) ? date('d-m-Y', strtotime($pegawai['mulai_kontrak'])) : '-' ?></td></tr>
      <tr>
        <td><strong>Photo</strong></td>
        <td class="text-center">
          <?php if (!empty($pegawai['photo'])): ?>
            <img src="<?= purl($pegawai['photo']) ?>" class="img-thumbnail" style="max-height:100px;">
          <?php else: ?>
            <div class="text-muted">FOTO PEGAWAI</div>
          <?php endif; ?>
          <div><strong>NIP</strong>: <?= $pegawai['nik'] ?? '-' ?></div>
        </td>
      </tr>
    </table>

    <!-- KEANGGOTAAN -->
    <h6 class="fw-bold mt-3">B. Keanggotaan</h6>
    <table class="table table-bordered table-sm">
      <tr><td><strong>Koperasi</strong></td><td><?= $pegawai['koperasi'] ?? '-' ?></td></tr>
      <tr><td><strong>BPJS Ketenagakerjaan</strong></td><td><?= $pegawai['jamsostek'] ?? '-' ?></td></tr>
      <tr><td><strong>BPJS Kesehatan</strong></td><td><?= $pegawai['bpjs'] ?? '-' ?></td></tr>
    </table>
  </div>

  <div class="card-footer text-end">
    <small class="text-muted">
      <?php
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $formatter = new IntlDateFormatter('id_ID', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Jakarta');
        echo "Data diperbarui terakhir pada " . $formatter->format($now) . " " . $now->format('H:i') . " WIB";
      ?>
    </small>
  </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
