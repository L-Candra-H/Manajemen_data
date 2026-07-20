<?php
session_start();
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// cek hak akses
if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu User.</div>";
    exit;
}

$conn = bukakoneksi();

// pagination setup
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 6;
$offset = ($page - 1) * $limit;

// filter setup
$filter = isset($_GET['filter']) ? $_GET['filter'] : "";

// simpan data baru
if (isset($_POST['simpan'])) {
    $id_user  = $_POST['id_user']; 
    $password = $_POST['password'];

    // cek duplikat
    $cek = mysqli_query($conn, "SELECT 1 FROM user WHERE id_user = AES_ENCRYPT('$id_user','nur')");
    if ($cek && mysqli_num_rows($cek) > 0) {
        echo "<div class='alert alert-warning text-center'>ID User sudah ada!</div>";
    } else {
        // ambil semua kolom ENUM di tabel user
        $qCols = "
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME='user' 
              AND TABLE_SCHEMA=DATABASE()
              AND DATA_TYPE='enum'
        ";
        $resCols = mysqli_query($conn, $qCols);

        $fields = [];
        $values = [];
        while($row = mysqli_fetch_assoc($resCols)) {
            $fields[] = $row['COLUMN_NAME'];
            $values[] = "'false'";
        }

        // gabungkan field + value
        $fieldList = implode(',', array_merge(['id_user','password'], $fields));
        $valueList = implode(',', array_merge([
            "AES_ENCRYPT('$id_user','nur')",
            "AES_ENCRYPT('$password','windi')"
        ], $values));

        // query insert massal
        $sql = "INSERT INTO user($fieldList) VALUES($valueList)";
        mysqli_query($conn, $sql);

        header("Location: user.php");
        exit;
    }
}

// ambil daftar nama dari tabel user (relasi ke pegawai/petugas/dokter) urut NIK ASC
$namaList = [];
$resNama = mysqli_query($conn,"
    SELECT DISTINCT 
        CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) AS nik_user,
        COALESCE(pg.nama, p.nama, d.nm_dokter) AS nama_user
    FROM user u
    LEFT JOIN pegawai pg ON CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) = pg.nik
    LEFT JOIN petugas p ON p.nip = pg.nik
    LEFT JOIN dokter d ON d.kd_dokter = pg.nik
    WHERE pg.nama IS NOT NULL OR p.nama IS NOT NULL OR d.nm_dokter IS NOT NULL
    ORDER BY nik_user ASC
");
if ($resNama) {
    while($row = mysqli_fetch_assoc($resNama)) {
        $namaList[] = [
            'nik'  => $row['nik_user'],
            'nama' => $row['nama_user']
        ];
    }
}

// query utama
$where = "";
if (!empty($filter) && $filter !== "ALL") {
    // pecah value "NIK - Nama"
    list($nikFilter, $namaFilter) = explode(' - ', $filter, 2);

    // filter berdasarkan NIK (lebih aman karena unik)
    $where = "WHERE CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) = '$nikFilter'";
}


// hitung total data untuk pagination
$countQuery = "
    SELECT COUNT(*) AS total
    FROM user u
    LEFT JOIN pegawai pg ON CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) = pg.nik
    LEFT JOIN petugas p ON p.nip = pg.nik
    LEFT JOIN dokter d ON d.kd_dokter = pg.nik
    LEFT JOIN jabatan j ON p.kd_jbtn = j.kd_jbtn
    LEFT JOIN spesialis s ON d.kd_sps = s.kd_sps
    $where
";
$countRes = mysqli_query($conn, $countQuery);
$totalRows = 0;
if ($countRes) {
    $rowCount = mysqli_fetch_assoc($countRes);
    $totalRows = $rowCount['total'];
}
$totalPages = ceil($totalRows / $limit);

$result = mysqli_query($conn, "
    SELECT 
        CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) AS id_user,
        CAST(AES_DECRYPT(u.password,'windi') AS CHAR) AS password,
        pg.nama AS nama_user,
        CONCAT(IFNULL(j.nm_jbtn,''), IFNULL(s.nm_sps,'')) AS jabatan_user,
        u.dokter, u.petugas, u.barcode, u.presensi_harian, u.presensi_bulanan, u.pegawai_admin, u.pegawai_user, u.sms, 
	      u.sidikjari, u.jam_masuk, u.jadwal_pegawai, u.temporary_presensi, u.master_berkas_pegawai, u.berkas_kepegawaian,
	      u.riwayat_jabatan, u.riwayat_pendidikan, u.riwayat_naik_gaji, u.kegiatan_ilmiah, u.riwayat_penghargaan, u.riwayat_penelitian,
	      u.jenis_cidera_k3rs, u.penyebab_k3rs, u.jenis_luka_k3rs, u.lokasi_kejadian_k3rs, u.dampak_cidera_k3rs, u.jenis_pekerjaan_k3rs,
	      u.bagian_tubuh_k3rs, u.peristiwa_k3rs, u.jenis_cidera_k3rstahun, u.penyebab_k3rstahun, u.jenis_luka_k3rstahun, u.lokasi_kejadian_k3rstahun,
	      u.dampak_cidera_k3rstahun, u.jenis_pekerjaan_k3rstahun, u.bagian_tubuh_k3rstahun, u.pengajuan_cuti, u.audit_kepatuhan_apd, u.audit_cuci_tangan_medis,
	      u.audit_pembuangan_limbah, u.ruang_audit_kepatuhan, u.audit_pembuangan_benda_tajam, u.audit_penanganan_darah, u.audit_pengelolaan_linen_kotor,
	      u.audit_penempatan_pasien, u.audit_kamar_jenazah, u.audit_bundle_iadp, u.audit_bundle_ido, u.audit_fasilitas_kebersihan_tangan, u.audit_fasilitas_apd,
	      u.audit_pembuangan_limbah_cair_infeksius, u.audit_sterilisasi_alat, u.audit_bundle_isk, u.audit_bundle_plabsi, u.audit_bundle_vap, u.skp_kategori_penilaian,
	      u.skp_kriteria_penilaian, u.skp_penilaian, u.skp_rekapitulasi_penilaian, u.riwayat_surat_peringatan    FROM user u
    LEFT JOIN pegawai pg ON CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) = pg.nik
    LEFT JOIN petugas p ON p.nip = pg.nik
    LEFT JOIN dokter d ON d.kd_dokter = pg.nik
    LEFT JOIN jabatan j ON p.kd_jbtn = j.kd_jbtn
    LEFT JOIN spesialis s ON d.kd_sps = s.kd_sps
    $where
    ORDER BY pg.nik ASC
    LIMIT $offset,$limit
");

// gabungan petugas yang belum ada di user
$qPetugas = "
    SELECT p.nip AS id_user, p.nama AS nama_user, j.nm_jbtn AS jabatan_user
    FROM petugas p
    LEFT JOIN jabatan j ON p.kd_jbtn=j.kd_jbtn
    WHERE p.nip NOT IN (SELECT CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) FROM user u)
";

// gabungan dokter yang belum ada di user
$qDokter = "
    SELECT d.kd_dokter AS id_user, d.nm_dokter AS nama_user, s.nm_sps AS jabatan_user
    FROM dokter d
    LEFT JOIN spesialis s ON d.kd_sps=s.kd_sps
    WHERE d.kd_dokter NOT IN (SELECT CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) FROM user u)
";

$resPetugas = mysqli_query($conn,$qPetugas);
$resDokter  = mysqli_query($conn,$qDokter);

$options = [];
if ($resPetugas) {
  while($row=mysqli_fetch_assoc($resPetugas)) {
    $options[] = $row;
  }
}
if ($resDokter) {
  while($row=mysqli_fetch_assoc($resDokter)) {
    $options[] = $row;
  }
}

function badgeBool($val) {
    if ($val === 'true') {
        return "<span style='color:green; font-weight:bold;'>TRUE</span>";
    } else {
        return "<span style='color:red; font-weight:bold;'>FALSE</span>";
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Manajemen User Kepegawaian</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Manajemen User Kepegawaian</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">

      <!-- Filter Nama -->
      <form method="get" class="mb-3">
        <label for="filter" class="form-label">Filter Nama:</label>
        <select name="filter" id="filter" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
          <option value="">-- Pilih Nama --</option>
          <option value="ALL" <?= $filter==='ALL'?'selected':'' ?>>Tampilkan Semua</option>
          <?php foreach($namaList as $nm): ?>
            <?php $val = $nm['nik'].' - '.$nm['nama']; ?>
            <option value="<?= htmlspecialchars($val) ?>" <?= $filter==$val?'selected':'' ?>>
              <?= htmlspecialchars($val) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
      </form>

      <!-- Tabel User -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-master align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>NIK</th>
              <th>Nama User</th>
              <th>Jabatan</th>
              <th>Password</th>
              <th>Dokter</th>
              <th>Petugas</th>
              <th>Barcode Presensi</th>
              <th>Presensi Harian</th>
              <th>Presensi Bulanan</th>
              <th>Pegawai Admin</th>
              <th>Pegawai User</th>
              <th>SMS Gateway</th>
              <th>Sidik Jari</th>
              <th>Jam Presensi</th>
              <th>Jadwal Pegawai</th>
              <th>Temporary Presensi</th>
              <th>Master Berkas Pegawai</th>
              <th>Berkas Kepegawaian</th>
              <th>Riwayat Jabatan</th>
              <th>Riwayat Pendidikan</th>
              <th>Riwayat Naik Gaji</th>
              <th>Kegiatan Ilmiah & Pelatihan</th>
              <th>Riwayat Penghargaan</th>
              <th>Riwayat Penelitian</th>
              <th>Jenis Cidera K3</th>
              <th>Penyebab Kecelakaan K3</th>
              <th>Jenis Luka K3</th>
              <th>Lokasi Kejadian K3</th>
              <th>Dampak Cidera K3</th>
              <th>Jenis Pekerjaan K3</th>
              <th>Bagian Tubuh K3</th>
              <th>Peristiwa K3</th>
              <th>Jenis Cidera K3 Per Tahun</th>
              <th>Penyebab Kecelakaan K3 Per Tahun</th>
              <th>Jenis Luka K3 Per Tahun</th>
              <th>Lokasi Kejadian K3 Per Tahun</th>
              <th>Dampak Cidera K3 Per Tahun</th>
              <th>Jenis Pekerjaan K3 Per Tahun</th>
              <th>Bagian Tubuh K3 Per Tahun</th>
              <th>Pengajuan Cuti</th>
              <th>Audit Kepatuhan APD</th>
              <th>Audit Cuci Tangan Medis</th>
              <th>Audit Pembuangan Limbah</th>
              <th>Ruang/Unit Audit Kepatuhan</th>
              <th>Audit Pembuangan Benda Tajam & Jarum</th>
              <th>Audit Penanganan Darah</th>
              <th>Audit Pengelolaan Linen Kotor</th>
              <th>Audit Penempatan Pasien</th>
              <th>Audit Kamar Jenazah</th>
              <th>Audit Bundle IADP</th>
              <th>Audit Bundle IDO</th>
              <th>Audit Fasilitas Kebersihan tangan</th>
              <th>Audit Fasilitas APD</th>
              <th>Audit Pembuangan Limbah Cair Infeksius</th>
              <th>Audit Sterilisasi Alat</th>
              <th>Audit Bundle ISK</th>
              <th>Audit Bundle PLABSI</th>
              <th>Audit Bundle VAP</th>
              <th>Kategori Pengkajian SKP</th>
              <th>Kriteria Pengkajian SKP</th>
              <th>Pengkajian SKP Petugas/Dokter</th>
              <th>Rekapitulasi Pengkajian SKP</th>
              <th>Riwayat Surat Peringatan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($filter)): ?>
              <tr><td colspan="27" class="text-center text-muted">Silakan pilih pegawai untuk menampilkan data</td></tr>
            <?php elseif ($result && mysqli_num_rows($result)===0): ?>
              <tr><td colspan="27" class="text-center text-muted">Tidak ada data</td></tr>
            <?php else: ?>
              <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                  <td><?= $row['id_user'] ?></td>
                  <td><?= $row['nama_user'] ?></td>
                  <td><?= $row['jabatan_user'] ?></td>
                  <td><?= $row['password'] ?></td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="dokter"
                           <?= $row['dokter']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="petugas"
                           <?= $row['petugas']==='true'?'checked':'' ?>>
                  </td>

                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="barcode"
                           <?= $row['barcode']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="presensi_harian"
                           <?= $row['presensi_harian']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="presensi_bulanan"
                           <?= $row['presensi_bulanan']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="pegawai_admin"
                           <?= $row['pegawai_admin']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="pegawai_user"
                           <?= $row['pegawai_user']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="sms"
                           <?= $row['sms']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="sidikjari"
                           <?= $row['sidikjari']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="jam_masuk"
                           <?= $row['jam_masuk']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="jadwal_pegawai"
                           <?= $row['jadwal_pegawai']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="temporary_presensi"
                           <?= $row['temporary_presensi']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="master_berkas_pegawai"
                           <?= $row['master_berkas_pegawai']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="berkas_kepegawaian"
                           <?= $row['berkas_kepegawaian']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="riwayat_jabatan"
                           <?= $row['riwayat_jabatan']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="riwayat_pendidikan"
                           <?= $row['riwayat_pendidikan']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="riwayat_naik_gaji"
                           <?= $row['riwayat_naik_gaji']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="kegiatan_ilmiah"
                           <?= $row['kegiatan_ilmiah']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="riwayat_penghargaan"
                           <?= $row['riwayat_penghargaan']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="riwayat_penelitian"
                           <?= $row['riwayat_penelitian']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="jenis_cidera_k3rs"
                           <?= $row['jenis_cidera_k3rs']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="penyebab_k3rs"
                           <?= $row['penyebab_k3rs']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="jenis_luka_k3rs"
                           <?= $row['jenis_luka_k3rs']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="lokasi_kejadian_k3rs"
                           <?= $row['lokasi_kejadian_k3rs']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="dampak_cidera_k3rs"
                           <?= $row['dampak_cidera_k3rs']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           <?= $row['jenis_pekerjaan_k3rs']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="bagian_tubuh_k3rs"
                           <?= $row['bagian_tubuh_k3rs']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="peristiwa_k3rs"
                           <?= $row['peristiwa_k3rs']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="jenis_cidera_k3rstahun"
                           <?= $row['jenis_cidera_k3rstahun']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="penyebab_k3rstahun"
                           <?= $row['penyebab_k3rstahun']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="jenis_luka_k3rstahun"
                           <?= $row['jenis_luka_k3rstahun']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="lokasi_kejadian_k3rstahun"
                           <?= $row['lokasi_kejadian_k3rstahun']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="dampak_cidera_k3rstahun"
                           <?= $row['dampak_cidera_k3rstahun']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="jenis_pekerjaan_k3rstahun"
                           <?= $row['jenis_pekerjaan_k3rstahun']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="bagian_tubuh_k3rstahun"
                           <?= $row['bagian_tubuh_k3rstahun']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="pengajuan_cuti"
                           <?= $row['pengajuan_cuti']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_kepatuhan_apd"
                           <?= $row['audit_kepatuhan_apd']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_cuci_tangan_medis"
                           <?= $row['audit_cuci_tangan_medis']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_pembuangan_limbah"
                           <?= $row['audit_pembuangan_limbah']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="ruang_audit_kepatuhan"
                           <?= $row['ruang_audit_kepatuhan']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_pembuangan_benda_tajam"
                           <?= $row['audit_pembuangan_benda_tajam']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_penanganan_darah"
                           <?= $row['audit_penanganan_darah']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_pengelolaan_linen_kotor"
                           <?= $row['audit_pengelolaan_linen_kotor']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_penempatan_pasien"
                           <?= $row['audit_penempatan_pasien']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_kamar_jenazah"
                           <?= $row['audit_kamar_jenazah']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_bundle_iadp"
                           <?= $row['audit_bundle_iadp']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_bundle_ido"
                           <?= $row['audit_bundle_ido']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_fasilitas_kebersihan_tangan"
                           <?= $row['audit_fasilitas_kebersihan_tangan']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_fasilitas_apd"
                           <?= $row['audit_fasilitas_apd']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_pembuangan_limbah_cair_infeksius"
                           <?= $row['audit_pembuangan_limbah_cair_infeksius']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_sterilisasi_alat"
                           <?= $row['audit_sterilisasi_alat']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_bundle_isk"
                           <?= $row['audit_bundle_isk']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_bundle_plabsi"
                           <?= $row['audit_bundle_plabsi']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="audit_bundle_vap"
                           <?= $row['audit_bundle_vap']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="skp_kategori_penilaian"
                           <?= $row['skp_kategori_penilaian']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="skp_kriteria_penilaian"
                           <?= $row['skp_kriteria_penilaian']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="skp_penilaian"
                           <?= $row['skp_penilaian']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="skp_rekapitulasi_penilaian"
                           <?= $row['skp_rekapitulasi_penilaian']==='true'?'checked':'' ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="update-field"
                           data-id="<?= $row['id_user'] ?>"
                           data-field="riwayat_surat_peringatan"
                           <?= $row['riwayat_surat_peringatan']==='true'?'checked':'' ?>>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages >= 1 && !empty($filter)): ?>
        <nav aria-label="Page navigation" class="mt-3">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&filter=<?= urlencode($filter) ?>">Prev</a>
            </li>
            <?php
              $start = max(1, $page - 1);
              $end   = min($totalPages, $page + 1);
              for ($i = $start; $i <= $end; $i++):
            ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&filter=<?= urlencode($filter) ?>">Next</a>
            </li>
          </ul>
        </nav>
        <?php endif; ?>

      </div>
    </div>
  </div>
</main>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Tambah User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" id="formTambah">
        <div class="modal-body row g-3">
          <div class="col-md-12">
            <label class="form-label">ID User</label>
            <select name="id_user" id="id_user" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach($options as $opt): ?>
                <option value="<?= htmlspecialchars($opt['id_user']) ?>"
                        data-nama="<?= htmlspecialchars($opt['nama_user']) ?>"
                        data-jabatan="<?= htmlspecialchars($opt['jabatan_user']) ?>">
                  <?= htmlspecialchars($opt['id_user'].' - '.$opt['nama_user'].' ('.$opt['jabatan_user'].')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama User</label>
            <input type="text" id="nama_user" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Jabatan</label>
            <input type="text" id="jabatan_user" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('id_user').addEventListener('change', function() {
  var selected = this.options[this.selectedIndex];
  document.getElementById('nama_user').value = selected.getAttribute('data-nama') || '';
  document.getElementById('jabatan_user').value = selected.getAttribute('data-jabatan') || '';
});

document.querySelectorAll('.update-field').forEach(el => {
  el.addEventListener('change', function() {
    const id    = this.dataset.id;
    const field = this.dataset.field;
    const value = this.type === 'checkbox' ? (this.checked ? 'true' : 'false') : this.value;

    fetch('update_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id_user=${encodeURIComponent(id)}&field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}`
    })
    .then(res => res.text())
    .then(msg => console.log(msg));
  });
});

</script>

<?php include __DIR__ . '/../../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
