<?php
session_start();
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

if (!isset($_SESSION['user_login'])) {
    header("Location: ../../../login.php");
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

$conn = bukakoneksi();

// proses simpan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ambil semua field dari form
    $no_k3rs   = $_POST['no_k3rs'] ?? '';
    $tgl_insiden = $_POST['tgl_insiden'] ?? '';
    $waktu_insiden = $_POST['waktu_insiden'] ?? '';
    $kode_pekerjaan = $_POST['kode_pekerjaan'] ?? '';   // ✅ ganti dari jenis_pekerjaan
    $tgl_pelaporan = $_POST['tgl_pelaporan'] ?? '';
    $waktu_pelaporan = $_POST['waktu_pelaporan'] ?? '';
    $kode_lokasi = $_POST['kode_lokasi'] ?? '';         // ✅ ganti dari lokasi_kejadian
    $kronologi_kejadian = $_POST['kronologi_kejadian'] ?? '';
    $kode_penyebab = $_POST['kode_penyebab'] ?? '';     // ✅ ganti dari penyebab_kecelakaan
    $nik = $_POST['nik'] ?? '';
    $kategori_cidera = $_POST['kategori_cidera'] ?? '';
    $kode_cidera = $_POST['kode_cidera'] ?? '';         // ✅ ganti dari jenis_cidera
    $kode_luka = $_POST['kode_luka'] ?? '';             // ✅ ganti dari jenis_luka
    $kode_bagian = $_POST['kode_bagian'] ?? '';         // ✅ ganti dari bagian_tubuh
    $lt = $_POST['lt'] ?? '';
    $penyebab_langsung_kondisi = $_POST['penyebab_langsung_kondisi'] ?? '';
    $penyebab_langsung_tindakan = $_POST['penyebab_langsung_tindakan'] ?? '';
    $penyebab_tidak_langsung_pribadi = $_POST['penyebab_tidak_langsung_pribadi'] ?? '';
    $penyebab_tidak_langsung_pekerjaan = $_POST['penyebab_tidak_langsung_pekerjaan'] ?? '';
    $barang_bukti = $_POST['barang_bukti'] ?? '';
    $kode_dampak = $_POST['kode_dampak'] ?? '';         // ✅ ganti dari dampak_cidera
    $nik_pelapor = $_POST['nik_pelapor'] ?? '';
    $perbaikan_jenis_tindakan = $_POST['perbaikan_jenis_tindakan'] ?? '';
    $perbaikan_rencana_tindakan = $_POST['perbaikan_rencana_tindakan'] ?? '';
    $perbaikan_target = $_POST['perbaikan_target'] ?? '';
    $perbaikan_wewenang = $_POST['perbaikan_wewenang'] ?? '';
    $nik_timk3 = $_POST['nik_timk3'] ?? '';
    $catatan = $_POST['catatan'] ?? '';

    // query insert
    $sql = "INSERT INTO k3rs_peristiwa (
        no_k3rs, tgl_insiden, waktu_insiden, kode_pekerjaan,
        tgl_pelaporan, waktu_pelaporan, kode_lokasi, kronologi_kejadian,
        kode_penyebab, nik, kategori_cidera, kode_cidera,
        kode_luka, kode_bagian, lt,
        penyebab_langsung_kondisi, penyebab_langsung_tindakan,
        penyebab_tidak_langsung_pribadi, penyebab_tidak_langsung_pekerjaan,
        barang_bukti, kode_dampak, nik_pelapor,
        perbaikan_jenis_tindakan, perbaikan_rencana_tindakan,
        perbaikan_target, perbaikan_wewenang,
        nik_timk3, catatan
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssisssssssssssss",
        $no_k3rs, $tgl_insiden, $waktu_insiden, $kode_pekerjaan,
        $tgl_pelaporan, $waktu_pelaporan, $kode_lokasi, $kronologi_kejadian,
        $kode_penyebab, $nik, $kategori_cidera, $kode_cidera,
        $kode_luka, $kode_bagian, $lt,
        $penyebab_langsung_kondisi, $penyebab_langsung_tindakan,
        $penyebab_tidak_langsung_pribadi, $penyebab_tidak_langsung_pekerjaan,
        $barang_bukti, $kode_dampak, $nik_pelapor,
        $perbaikan_jenis_tindakan, $perbaikan_rencana_tindakan,
        $perbaikan_target, $perbaikan_wewenang,
        $nik_timk3, $catatan
    );

    if ($stmt->execute()) {
        header("Location: peristiwa_k3.php");
        exit;
    } else {
        die("Gagal simpan: " . $stmt->error);
    }
}

function hitungUsia($tgl_lahir, $tgl_pelaporan) {
    $lahir = new DateTime($tgl_lahir);
    $lapor = new DateTime($tgl_pelaporan);
    return $lahir->diff($lapor)->y; // selisih tahun
}

// generate nomor laporan otomatis: K3/YYYY/MM/DD/####
// ambil nomor terakhir
$tglHariIni = date("Y-m-d");
$qNo = mysqli_query($conn, "
    SELECT MAX(RIGHT(no_k3rs,4)) AS noTerbesar
    FROM k3rs_peristiwa
    WHERE DATE(tgl_insiden) = '$tglHariIni'
");
$dataNo = mysqli_fetch_assoc($qNo);
$urut   = $dataNo['noTerbesar'] ? (int)$dataNo['noTerbesar']+1 : 1;
$noBaru = "K3/" . date("Y/m/d") . "/" . sprintf("%04s", $urut);

// ambil data referensi
$jenis_pekerjaan = mysqli_query($conn, "SELECT kode_pekerjaan, jenis_pekerjaan FROM k3rs_jenis_pekerjaan ORDER BY kode_pekerjaan ASC");
$lokasi          = mysqli_query($conn, "SELECT kode_lokasi, lokasi_kejadian FROM k3rs_lokasi_kejadian ORDER BY kode_lokasi ASC");
$penyebab        = mysqli_query($conn, "SELECT kode_penyebab, penyebab_kecelakaan FROM k3rs_penyebab ORDER BY kode_penyebab ASC");
$cidera          = mysqli_query($conn, "SELECT kode_cidera, jenis_cidera FROM k3rs_jenis_cidera ORDER BY kode_cidera ASC");
$luka            = mysqli_query($conn, "SELECT kode_luka, jenis_luka FROM k3rs_jenis_luka ORDER BY kode_luka ASC");
$bagian          = mysqli_query($conn, "SELECT kode_bagian, bagian_tubuh FROM k3rs_bagian_tubuh ORDER BY kode_bagian ASC");
$dampak          = mysqli_query($conn, "SELECT kode_dampak, dampak_cidera FROM k3rs_dampak_cidera ORDER BY kode_dampak ASC");

// ambil data pegawai lengkap dengan relasi bidang & departemen
$pegawai = mysqli_query($conn, "
    SELECT p.nik,
           p.nama,
           p.jk,
           p.tgl_lahir,
           p.jbtn,
           p.stts_aktif,
           b.nama AS bidang,
           d.nama AS departemen
    FROM pegawai p
    LEFT JOIN bidang b ON p.bidang = b.nama
    LEFT JOIN departemen d ON p.departemen = d.dep_id
    WHERE p.stts_aktif = 'AKTIF'
    ORDER BY p.nik ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Peristiwa K3</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="k3.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>
  <div class="container mt-4">
    <div class="card">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">TAMBAH PERISTIWA K3</h5>
        <a href="peristiwa_k3.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
      </div>

      <div class="card-body">
        <form method="post" action="tambah_peristiwa_k3.php">
          <div class="row">
            <!-- Kolom kiri: A & B -->
            <div class="col-md-6">
              <!-- A. INSIDEN -->
              <div class="card mb-3">
                <div class="card-header bg-primary text-white">A. Insiden</div>
                <div class="card-body">
                  <div class="mb-2 d-flex align-items-center">
                    <label for="no_k3rs" class="me-2">No. Laporan:</label>
                    <input type="text" name="no_k3rs" id="no_k3rs" class="form-control w-75 bg-secondary text-white" value="<?= $noBaru ?>" readonly>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="tgl_insiden" class="me-2">Tgl. Insiden:</label>
                    <input type="date" name="tgl_insiden" id="tgl_insiden" class="form-control w-50 me-2" required>
                    <input type="time" name="waktu_insiden" id="waktu_insiden" class="form-control w-25" required>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kode_pekerjaan" class="me-2">Jenis Pekerjaan:</label>
                    <select name="kode_pekerjaan" id="kode_pekerjaan" class="form-select w-75" required>
                      <option value="">-- pilih --</option>
                      <?php while($row=mysqli_fetch_assoc($jenis_pekerjaan)): ?>
                        <option value="<?= $row['kode_pekerjaan'] ?>"><?= $row['jenis_pekerjaan'] ?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="tgl_pelaporan" class="me-2">Tgl. Pelaporan:</label>
                    <input type="date" name="tgl_pelaporan" id="tgl_pelaporan" class="form-control w-50 me-2" required>
                    <input type="time" name="waktu_pelaporan" id="waktu_pelaporan" class="form-control w-25" required>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kode_lokasi" class="me-2">Lokasi Kejadian:</label>
                    <select name="kode_lokasi" id="kode_lokasi" class="form-select w-75" required>
                      <option value="">-- pilih --</option>
                      <?php while($row=mysqli_fetch_assoc($lokasi)): ?>
                        <option value="<?= $row['kode_lokasi'] ?>"><?= $row['lokasi_kejadian'] ?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kronologi_kejadian" class="me-2">Kronologi:</label>
                    <textarea name="kronologi_kejadian" id="kronologi_kejadian" class="form-control w-75" rows="3" required></textarea>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kode_penyebab" class="me-2">Penyebab:</label>
                    <select name="kode_penyebab" id="kode_penyebab" class="form-select w-75" required>
                      <option value="">-- pilih --</option>
                      <?php while($row=mysqli_fetch_assoc($penyebab)): ?>
                        <option value="<?= $row['kode_penyebab'] ?>"><?= $row['penyebab_kecelakaan'] ?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- B. KORBAN -->
              <div class="card mb-3">
                <div class="card-header bg-danger text-white">B. Korban</div>
                <div class="card-body">
                  <div class="mb-2 d-flex align-items-center">
                    <label for="nik_korban" class="me-2">Korban:</label>
                    <select name="nik" id="nik_korban" class="form-select w-25 me-2" required>
                      <option value="">-- pilih --</option>
                      <?php mysqli_data_seek($pegawai,0); while($row=mysqli_fetch_assoc($pegawai)): ?>
                        <option value="<?= $row['nik'] ?>"
                                data-nama="<?= $row['nama'] ?>"
                                data-bidang="<?= $row['bidang'] ?>"
                                data-departemen="<?= $row['departemen'] ?>"
                                data-jk="<?= $row['jk'] ?>"
                                data-tgllahir="<?= $row['tgl_lahir'] ?>"
                                data-jabatan="<?= $row['jbtn'] ?>">
                          <?= $row['nik'] ?> - <?= $row['nama'] ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                    <input type="text" name="nama_korban" id="nama_korban" class="form-control w-50 bg-secondary text-white" readonly>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="bidang_korban" class="me-2">Bidang:</label>
                    <input type="text" name="bidang_korban" id="bidang_korban" class="form-control w-50 bg-secondary text-white me-2" readonly>
                    <label for="departemen_korban" class="me-2">Departemen:</label>
                    <input type="text" name="departemen_korban" id="departemen_korban" class="form-control w-50 bg-secondary text-white" readonly>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="jk_korban" class="me-2">J.K:</label>
                    <input type="text" name="jk_korban" id="jk_korban" class="form-control w-25 bg-secondary text-white me-2" readonly>
                    <label for="usia_korban" class="me-2">Usia:</label>
                    <input type="text" name="usia_korban" id="usia_korban" class="form-control w-25 bg-secondary text-white me-2" readonly>
                    <label for="jabatan_korban" class="me-2">Jabatan:</label>
                    <input type="text" name="jabatan_korban" id="jabatan_korban" class="form-control w-50 bg-secondary text-white" readonly>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kategori_cidera" class="me-2 flex-shrink-0" style="width:130px;">Kategori Cidera:</label>
                    <select name="kategori_cidera" id="kategori_cidera" class="form-select w-50 me-3" required>
                      <option value="">-- pilih --</option>
                      <option value="Ringan">Ringan</option>
                      <option value="Sedang">Sedang</option>
                      <option value="Berat">Berat</option>
                      <option value="Fatal">Fatal</option>
                    </select>
                    <label for="lt" class="me-2 flex-shrink-0" style="width:100px;">Lost Time:</label>
                    <input type="number" name="lt" id="lt" class="form-control w-25 me-2" min="0" required>
                    <span>Hari</span>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kode_cidera" class="me-2">Jenis Cidera:</label>
                    <select name="kode_cidera" id="kode_cidera" class="form-select w-75" required>
                      <option value="">-- pilih --</option>
                      <?php while($row=mysqli_fetch_assoc($cidera)): ?>
                        <option value="<?= $row['kode_cidera'] ?>"><?= $row['jenis_cidera'] ?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kode_luka" class="me-2">Jenis Luka:</label>
                    <select name="kode_luka" id="kode_luka" class="form-select w-75" required>
                      <option value="">-- pilih --</option>
                      <?php while($row=mysqli_fetch_assoc($luka)): ?>
                        <option value="<?= $row['kode_luka'] ?>"><?= $row['jenis_luka'] ?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kode_bagian" class="me-2">Bagian Tubuh:</label>
                    <select name="kode_bagian" id="kode_bagian" class="form-select w-75" required>
                      <option value="">-- pilih --</option>
                      <?php while($row=mysqli_fetch_assoc($bagian)): ?>
                        <option value="<?= $row['kode_bagian'] ?>"><?= $row['bagian_tubuh'] ?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Kolom kanan: C & D -->
            <div class="col-md-6">
              <!-- C. INVESTIGASI -->
              <div class="card mb-3">
                <div class="card-header bg-warning">C. Investigasi Kecelakaan</div>
                <div class="card-body">
                  <h6 class="fw-bold">1. Penyebab Langsung</h6>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="penyebab_langsung_kondisi" class="me-2 flex-shrink-0" style="width:160px;">Kondisi Tidak Aman:</label>
                    <input type="text" name="penyebab_langsung_kondisi" id="penyebab_langsung_kondisi" class="form-control w-75" required>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="penyebab_langsung_tindakan" class="me-2 flex-shrink-0" style="width:160px;">Tindakan Tidak Aman:</label>
                    <input type="text" name="penyebab_langsung_tindakan" id="penyebab_langsung_tindakan" class="form-control w-75" required>
                  </div>
                  <h6 class="fw-bold">2. Tidak Langsung</h6>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="penyebab_tidak_langsung_pribadi" class="me-2">Pribadi:</label>
                    <input type="text" name="penyebab_tidak_langsung_pribadi" id="penyebab_tidak_langsung_pribadi" class="form-control w-75" required>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="penyebab_tidak_langsung_pekerjaan" class="me-2">Pekerjaan:</label>
                    <input type="text" name="penyebab_tidak_langsung_pekerjaan" id="penyebab_tidak_langsung_pekerjaan" class="form-control w-75" required>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="kode_dampak" class="me-2">Dampak:</label>
                    <select name="kode_dampak" id="kode_dampak" class="form-select w-75" required>
                      <option value="">-- pilih --</option>
                      <?php while($row=mysqli_fetch_assoc($dampak)): ?>
                        <option value="<?= $row['kode_dampak'] ?>"><?= $row['dampak_cidera'] ?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="nik_pelapor" class="me-2">Pelapor:</label>
                    <select name="nik_pelapor" id="nik_pelapor" class="form-select w-25 me-2" required>
                      <option value="">-- pilih --</option>
                      <?php mysqli_data_seek($pegawai,0); while($row=mysqli_fetch_assoc($pegawai)): ?>
                        <option value="<?= $row['nik'] ?>"
                                data-nama="<?= $row['nama'] ?>"
                                data-bidang="<?= $row['bidang'] ?>"
                                data-departemen="<?= $row['departemen'] ?>">
                          <?= $row['nik'] ?> - <?= $row['nama'] ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                    <input type="text" name="nama_pelapor" id="nama_pelapor" class="form-control w-50 bg-secondary text-white" readonly>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="bidang_pelapor" class="me-2">Bidang:</label>
                    <input type="text" name="bidang_pelapor" id="bidang_pelapor" class="form-control w-25 bg-secondary text-white me-3" readonly>
                    <label for="departemen_pelapor" class="me-2">Departemen:</label>
                    <input type="text" name="departemen_pelapor" id="departemen_pelapor" class="form-control w-25 bg-secondary text-white" readonly>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="barang_bukti" class="me-2">Barang Bukti:</label>
                    <select name="barang_bukti" id="barang_bukti" class="form-select w-25" required>
                      <option value="">-- pilih --</option>
                      <option value="Ya">Ya</option>
                      <option value="Tidak">Tidak</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- D. PERBAIKAN & PENCEGAHAN -->
              <div class="card mb-3">
                <div class="card-header bg-success text-white">D. Perbaikan & Pencegahan</div>
                <div class="card-body">
                  <div class="mb-2 d-flex align-items-center">
                    <label for="perbaikan_jenis_tindakan" class="me-2">Jenis:</label>
                    <select name="perbaikan_jenis_tindakan" id="perbaikan_jenis_tindakan" class="form-select w-75" required>
                      <option value="">-- pilih --</option>
                      <option value="Tindakan Perbaikan">Tindakan Perbaikan</option>
                      <option value="Tindakan Pencegahan">Tindakan Pencegahan</option>
                    </select>
                  </div>
                  <div class="mb-2 d-flex align-items-start">
                    <label for="perbaikan_rencana_tindakan" class="me-2">Rencana:</label>
                    <textarea name="perbaikan_rencana_tindakan" id="perbaikan_rencana_tindakan" class="form-control w-75" rows="2" required></textarea>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="perbaikan_target" class="me-2">Target:</label>
                    <input type="date" name="perbaikan_target" id="perbaikan_target" class="form-control w-25 me-3" required>
                    <label for="perbaikan_wewenang" class="me-2">Wewenang:</label>
                    <input type="text" name="perbaikan_wewenang" id="perbaikan_wewenang" class="form-control w-50" required>
                  </div>
                  <div class="mb-2 d-flex align-items-start">
                    <label for="catatan" class="me-2">Catatan:</label>
                    <textarea name="catatan" id="catatan" class="form-control w-75" rows="2"></textarea>
                  </div>
                  <div class="mb-2 d-flex align-items-center">
                    <label for="nik_timk3" class="me-2">Tim K3:</label>
                    <select name="nik_timk3" id="nik_timk3" class="form-select w-25 me-2" required>
                      <option value="">-- pilih --</option>
                      <?php mysqli_data_seek($pegawai,0); while($row=mysqli_fetch_assoc($pegawai)): ?>
                        <option value="<?= $row['nik'] ?>" data-nama="<?= $row['nama'] ?>">
                          <?= $row['nik'] ?> - <?= $row['nama'] ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                    <input type="text" name="nama_timk3" id="nama_timk3" class="form-control w-50 bg-secondary text-white" readonly>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tombol Simpan/Batal -->
          <div class="text-center mt-3">
            <button type="submit" class="btn btn-primary px-4">💾 Simpan</button>
            <a href="peristiwa_k3.php" class="btn btn-secondary px-4">❌ Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

  <script>
    // Korban
    document.getElementById('nik_korban').addEventListener('change', function() {
      const opt = this.options[this.selectedIndex];
      document.getElementById('nama_korban').value = opt.dataset.nama;
      document.getElementById('bidang_korban').value = opt.dataset.bidang;
      document.getElementById('departemen_korban').value = opt.dataset.departemen;
      document.getElementById('jk_korban').value = opt.dataset.jk;
      document.getElementById('jabatan_korban').value = opt.dataset.jabatan;
      const tglLahir = new Date(opt.dataset.tgllahir);
      const tglPelaporan = new Date(document.querySelector('[name="tgl_pelaporan"]').value);
      if(!isNaN(tglLahir) && !isNaN(tglPelaporan)) {
        let usia = tglPelaporan.getFullYear() - tglLahir.getFullYear();
        const m = tglPelaporan.getMonth() - tglLahir.getMonth();
        if(m < 0 || (m === 0 && tglPelaporan.getDate() < tglLahir.getDate())) {
          usia--;
        }
        document.getElementById('usia_korban').value = usia;
      }
    });

    // Pelapor
    document.getElementById('nik_pelapor').addEventListener('change', function() {
      const opt = this.options[this.selectedIndex];
      document.getElementById('nama_pelapor').value = opt.dataset.nama;
      document.getElementById('bidang_pelapor').value = opt.dataset.bidang;
      document.getElementById('departemen_pelapor').value = opt.dataset.departemen;
    });

    // Tim K3
    document.getElementById('nik_timk3').addEventListener('change', function() {
      const opt = this.options[this.selectedIndex];
      document.getElementById('nama_timk3').value = opt.dataset.nama;
    });
  </script>
</body>
</html>
