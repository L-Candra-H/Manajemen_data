<?php
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = bukakoneksi();
$qSetting = mysqli_query($conn, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($qSetting);

$logo          = 'data:image/png;base64,' . base64_encode($setting['logo']);
$nama_instansi = $setting['nama_instansi'];
$alamat        = $setting['alamat_instansi'];
$kabupaten     = $setting['kabupaten'];
$propinsi      = $setting['propinsi'];
$kontak        = $setting['kontak'];
$email         = $setting['email'];

// ambil NIK dari session (id_user = nik hasil decrypt)
$nikLogin = $_SESSION['id_user'] ?? '';

if (empty($nikLogin)) {
    echo "<div class='alert alert-danger'>Tidak ada data pegawai untuk user login.</div>";
    exit;
}

// query data pegawai + keanggotaan + status nikah + email + agama + no. telp
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
                      dk.agama AS agama_dokter,
                      pt.no_telp AS no_telp_petugas,
                      dk.no_telp AS no_telp_dokter

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
  <link rel="stylesheet" href="pegawai.css">
<meta charset="UTF-8">
<title>Cetak Data Pegawai</title>

</head>
<body>

<table class="header garis-header">
  <tr>
    <!-- Logo -->
    <td class="logo">
      <img src="<?= $logo ?>" alt="Logo">
    </td>

    <!-- Info Instansi -->
    <td class="instansi">
      <h1><?= htmlspecialchars($nama_instansi) ?></h1>
      <p><?= htmlspecialchars($alamat) ?> - <?= htmlspecialchars($kabupaten) ?> - <?= htmlspecialchars($propinsi) ?></p>
      <p><?= htmlspecialchars($kontak) ?> | <?= htmlspecialchars($email) ?></p>
    </td>
  </tr>
</table>

<div class="judul-biodata">BIODATA PEGAWAI</div>

<table width="100%" cellpadding="5" cellspacing="0">
  <!-- isi tabel A -->
</table>

<!-- A. DATA INDUK -->
<div class="subjudul">A. DATA INDUK</div>
<table class="data-induk">
  <!-- Baris 1: NIK + Foto + NIP -->
  <tr>
    <td style="width:25%;"><strong>Nomor Induk Kependudukan </strong></td>
    <td style="width:25%;">: <?= $pegawai['no_ktp'] ?? '-' ?></td>
    <td style="width:25%;"></td>
    <td style="width:25%; text-align:center;" rowspan="6">
      <?php if (!empty($pegawai['photo'])): ?>
        <img src="<?= purl($pegawai['photo']) ?>" alt="Foto Pegawai" style="width:80px; height:120px; object-fit:cover; border:1px solid #ccc;"><br>
      <?php else: ?>
        <div class="foto-placeholder">FOTO PEGAWAI</div>
      <?php endif; ?>
      <strong>NIP </strong>: <?= $pegawai['nik'] ?? '-' ?>
    </td>
  </tr>

  <!-- Identitas Utama -->
  <tr><td><strong>Nama Lengkap </strong></td><td>: <?= $pegawai['nama'] ?? '-' ?></td><td></td><td></td></tr>
  <tr>
  <td><strong>Tempat / Tanggal Lahir </strong></td>
      <td>: <?= $pegawai['tmp_lahir'] ?? '-' ?> / 
            <?= !empty($pegawai['tgl_lahir']) ? date('d-m-Y', strtotime($pegawai['tgl_lahir'])) : '-' ?>
      </td>
      <td></td>
      <td></td>
    </tr>
  <tr><td><strong>Jenis Kelamin </strong></td><td>: <?= $pegawai['jk'] ?? '-' ?></td><td></td><td></td></tr>
  <tr><td><strong>Agama </strong></td> <td>: <?= $pegawai['agama_petugas'] ?? $pegawai['agama_dokter'] ?? '-' ?></td><td></td><td></td></tr>
  <tr><td><strong>Alamat Lengkap </strong></td> <td>: <?= $pegawai['alamat'] ?? '-' ?> / <?= $pegawai['kota'] ?? '-' ?></td><td></td><td></td></tr>

  <!-- Data Tambahan: KOLOM A/B vs KOLOM C/D -->
  <tr>
    <td><strong>Email </strong></td>
    <td>: <?= $pegawai['email_petugas'] ?? $pegawai['email_dokter'] ?? '-' ?></td>
    <td><strong>Telepon </strong></td>
    <td>: <?= $pegawai['no_telp_petugas'] ?? $pegawai['no_telp_dokter'] ?? '-' ?></td>
  </tr>
  <tr>
    <td><strong>Status Pernikahan </strong></td>
    <td>: <?= $pegawai['stts_nikah_petugas'] ?? $pegawai['stts_nikah_dokter'] ?? '-' ?></td>
  </tr>
  <tr>
    <td><strong>Nama Bank </strong></td>
    <td>: <?= $pegawai['bpd'] ?? '-' ?></td>
    <td><strong>Nomor Rekening </strong></td>
    <td>: <?= $pegawai['rekening'] ?? '-' ?></td>
  </tr>

  <!-- Data Pekerjaan -->
  <tr>
    <td><strong>Jabatan / Jenjang / Kelompok </strong></td>
    <td colspan="3">: <?= $pegawai['jbtn'] ?? '-' ?> / <?= $pegawai['nama_jenjang'] ?? '-' ?> / <?= $pegawai['nama_kelompok'] ?? '-' ?></td>
  </tr>
  <tr>
    <td><strong>Departemen / Bagian </strong></td>
    <td colspan="3">: <?= $pegawai['nama_departemen'] ?? '-' ?> / <?= $pegawai['bidang'] ?? '-' ?></td>
  </tr>
  <tr>
    <td><strong>Status Wajib Pajak</strong></td>
    <td>: <?= $pegawai['stts_wp_ktg'] ?? '-' ?></td>
  </tr>
  <tr>
    <td><strong>NPWP</strong></td>
    <td>: <?= $pegawai['npwp'] ?? '-' ?></td>
  </tr>
  <tr>
    <td><strong>Status Pekerjaan </strong></td>
    <td colspan="3">: <?= $pegawai['stts_kerja_ktg'] ?? '-' ?></td>
  </tr>
  <tr>
    <td><strong>Mulai Kerja</strong></td>
    <td colspan="3">: <?= !empty($pegawai['mulai_kerja']) ? date('d-m-Y', strtotime($pegawai['mulai_kerja'])) : '-' ?></td>
  </tr>
  <tr>
    <td><strong>Mulai Kontrak</strong></td>
    <td colspan="3">: <?= !empty($pegawai['mulai_kontrak']) ? date('d-m-Y', strtotime($pegawai['mulai_kontrak'])) : '-' ?></td>
  </tr>
</table>

<!-- B. KEANGGOTAAN -->
<div class="subjudul">B. KEANGGOTAAN</div>
<table class="tabel-bordered" width="100%" cellpadding="5" cellspacing="0">
  <tr><td><strong>Koperasi</strong></td><td><?= $pegawai['koperasi'] ?? '-' ?></td></tr>
  <tr><td><strong>BPJS Ketenagakerjaan</strong></td><td><?= $pegawai['jamsostek'] ?? '-' ?></td></tr>
  <tr><td><strong>BPJS Kesehatan</strong></td><td><?= $pegawai['bpjs'] ?? '-' ?></td></tr>
</table>

<!-- FOOTER -->
<div class="footer-info p-2" 
     style="border-top: 2px solid #ccc; font-size: 14px; background-color: #f9f9f9; text-align:left;">
  
  <div style="font-weight:bold; color:#007bff; margin-bottom:6px;">
    Jika ada kekeliruan/kesalahan agar menghubungi bagian Kepegawaian / petugas yang menangani Data Kepegawaian
  </div>
  
  <div style="font-weight:bold; color:#333;">
    <?php
      $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
      $formatter = new IntlDateFormatter(
        'id_ID',
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'Asia/Jakarta',
        IntlDateFormatter::GREGORIAN,
        'EEEE, dd-MM-yyyy'
      );
      $tanggal = $formatter->format($now);
      $jam     = $now->format('H:i');
      echo "Akses terakhir: <span style='color:#007bff;'>$tanggal $jam WIB</span>";
    ?>
  </div>
</div>

</body>
</html>
