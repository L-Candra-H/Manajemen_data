<?php
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = [];
if ($id > 0) {
  $sql = "SELECT * FROM pegawai WHERE id='$id'";
  $result = mysqli_query($conn, $sql);
  if ($result) {
    $data = mysqli_fetch_assoc($result) ?: [];
  }
}
$data = array_merge([
  'id' => '',
  'nik' => '',
  'nama' => '',
  'jk' => '',
  'jbtn' => '',
  'jnj_jabatan' => '',
  'kode_kelompok' => '',
  'departemen' => '',
  'bidang' => '',
  'kode_resiko' => '',
  'kode_emergency' => '',
  'stts_wp' => '',
  'stts_kerja' => '',
  'npwp' => '',
  'pendidikan' => '',
  'tmp_lahir' => '',
  'tgl_lahir' => '',
  'alamat' => '',
  'kota' => '',
  'mulai_kerja' => '',
  'ms_kerja' => '',
  'indexins' => '',
  'bpd' => '',
  'rekening' => '',
  'stts_aktif' => '',
  'wajibmasuk' => '',
  'mulai_kontrak' => '',
  'photo' => '',
  'no_ktp' => ''
], $data);

// dropdown data
$jnj_jabatan = mysqli_query($conn, "SELECT kode,nama,tnj,indek FROM jnj_jabatan ORDER BY indek");
$kelompok    = mysqli_query($conn, "SELECT kode_kelompok,nama_kelompok,indek FROM kelompok_jabatan ORDER BY indek");
$resiko      = mysqli_query($conn, "SELECT kode_resiko,nama_resiko,indek FROM resiko_kerja ORDER BY indek");
$emergency   = mysqli_query($conn, "SELECT kode_emergency,nama_emergency,indek FROM emergency_index ORDER BY indek");
$departemen  = mysqli_query($conn, "SELECT dep_id,nama FROM departemen ORDER BY dep_id");
try {
  $indexin = mysqli_query($conn, "SELECT i.dep_id, d.nama, i.persen FROM indexins i JOIN departemen d ON d.dep_id = i.dep_id ORDER BY i.dep_id");
} catch (Throwable $e) {
  $indexin = mysqli_query($conn, "SELECT dep_id, nama, 0 AS persen FROM departemen ORDER BY dep_id");
}
$bidang      = mysqli_query($conn, "SELECT nama FROM bidang ORDER BY nama");
$stts_wp     = mysqli_query($conn, "SELECT stts,ktg FROM stts_wp ORDER BY stts");
$stts_kerja  = mysqli_query($conn, "SELECT stts,ktg,indek,hakcuti FROM stts_kerja ORDER BY stts");
$pendidikan  = mysqli_query($conn, "SELECT tingkat,indek,gapok1,kenaikan,maksimal FROM pendidikan ORDER BY indek");
$bank        = mysqli_query($conn, "SELECT namabank FROM bank ORDER BY namabank");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Pegawai</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body class="edit-pegawai">
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <div class="container mt-4">
    <div class="card">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">TAMBAH PEGAWAI</h5>
        <a href="pegawai.php" class="btn btn-light btn-sm">← Kembali</a>
      </div>

      <div class="card-body">
        <form method="post" action="insert_pegawai.php" enctype="multipart/form-data">
          <input type="hidden" name="id" value="<?= htmlspecialchars($data['id']) ?>">

          <div class="row">
            <!-- Range 1 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">NIP</label>
              <input type="text" name="nik" class="form-control" value="">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Nama</label>
              <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama']) ?>">
            </div>

            <!-- Range 2 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Jenis Kelamin</label>
              <select name="jk" class="form-select">
                <option value="">-- Pilih JK --</option>
                <option value="Pria" <?= $data['jk'] == 'Pria' ? 'selected' : '' ?>>Pria</option>
                <option value="Wanita" <?= $data['jk'] == 'Wanita' ? 'selected' : '' ?>>Wanita</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Jabatan</label>
              <input type="text" name="jbtn" class="form-control" value="<?= htmlspecialchars($data['jbtn']) ?>">
            </div>

            <!-- Range 3 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Jenjang Jabatan</label>
              <select name="jnj_jabatan" class="form-select">
                <option value="">-- Pilih Jenjang Jabatan --</option>
                <?php mysqli_data_seek($jnj_jabatan, 0); while ($row = mysqli_fetch_assoc($jnj_jabatan)) : ?>
                  <option value="<?= $row['kode'] ?>" <?= $data['jnj_jabatan'] == $row['kode'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['kode']) ?> - <?= htmlspecialchars($row['nama']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Kelompok Jabatan</label>
              <select name="kode_kelompok" class="form-select">
                <option value="">-- Pilih Kelompok Jabatan --</option>
                <?php mysqli_data_seek($kelompok, 0); while ($row = mysqli_fetch_assoc($kelompok)) : ?>
                  <option value="<?= $row['kode_kelompok'] ?>" <?= $data['kode_kelompok'] == $row['kode_kelompok'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['kode_kelompok']) ?> - <?= htmlspecialchars($row['nama_kelompok']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Range 4 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Departemen</label>
              <select name="departemen" class="form-select">
                <option value="">-- Pilih Departemen --</option>
                <?php mysqli_data_seek($departemen, 0); while ($row = mysqli_fetch_assoc($departemen)) : ?>
                  <option value="<?= $row['dep_id'] ?>" <?= $data['departemen'] == $row['dep_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['dep_id']) ?> - <?= htmlspecialchars($row['nama']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Bagian</label>
              <select name="bidang" class="form-select">
                <option value="">-- Pilih Bagian --</option>
                <?php mysqli_data_seek($bidang, 0); while ($row = mysqli_fetch_assoc($bidang)) : ?>
                  <option value="<?= $row['nama'] ?>" <?= $data['bidang'] == $row['nama'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nama']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Range 5 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Resiko Kerja</label>
              <select name="kode_resiko" class="form-select">
                <option value="">-- Pilih Resiko Kerja --</option>
                <?php mysqli_data_seek($resiko, 0); while ($row = mysqli_fetch_assoc($resiko)) : ?>
                  <option value="<?= $row['kode_resiko'] ?>" <?= $data['kode_resiko'] == $row['kode_resiko'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['kode_resiko']) ?> - <?= htmlspecialchars($row['nama_resiko']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Tingkat Emergency</label>
              <select name="kode_emergency" class="form-select">
                <option value="">-- Pilih Tingkat Emergency --</option>
                <?php mysqli_data_seek($emergency, 0); while ($row = mysqli_fetch_assoc($emergency)) : ?>
                  <option value="<?= $row['kode_emergency'] ?>" <?= $data['kode_emergency'] == $row['kode_emergency'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['kode_emergency']) ?> - <?= htmlspecialchars($row['nama_emergency']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Range 6 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Status WP</label>
              <select name="stts_wp" class="form-select">
                <option value="">-- Pilih Status WP --</option>
                <?php mysqli_data_seek($stts_wp, 0); while ($row = mysqli_fetch_assoc($stts_wp)) : ?>
                  <option value="<?= $row['stts'] ?>" <?= $data['stts_wp'] == $row['stts'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['stts']) ?> - <?= htmlspecialchars($row['ktg']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select name="stts_kerja" class="form-select">
                <option value="">-- Pilih Status --</option>
                <?php mysqli_data_seek($stts_kerja, 0); while ($row = mysqli_fetch_assoc($stts_kerja)) : ?>
                  <option value="<?= $row['stts'] ?>" <?= $data['stts_kerja'] == $row['stts'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['stts']) ?> - <?= htmlspecialchars($row['ktg']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Range 7 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">NPWP</label>
              <input type="text" name="npwp" class="form-control" value="<?= htmlspecialchars($data['npwp']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Pendidikan</label>
              <select name="pendidikan" class="form-select">
                <option value="">-- Pilih Pendidikan --</option>
                <?php mysqli_data_seek($pendidikan, 0); while ($row = mysqli_fetch_assoc($pendidikan)) : ?>
                  <option value="<?= $row['tingkat'] ?>" <?= $data['pendidikan'] == $row['tingkat'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['tingkat']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Range 8 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Tempat Lahir</label>
              <input type="text" name="tmp_lahir" class="form-control" value="<?= htmlspecialchars($data['tmp_lahir']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Tanggal Lahir</label>
              <input type="date" name="tgl_lahir" class="form-control" value="<?= htmlspecialchars($data['tgl_lahir']) ?>">
            </div>

            <!-- Range 9 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Alamat</label>
              <textarea name="alamat" class="form-control"><?= htmlspecialchars($data['alamat']) ?></textarea>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Kota</label>
              <input type="text" name="kota" class="form-control" value="<?= htmlspecialchars($data['kota']) ?>">
            </div>

            <!-- Range 10 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Mulai Kerja</label>
              <input type="date" name="mulai_kerja" class="form-control" value="<?= htmlspecialchars($data['mulai_kerja']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Kode Ms Kerja</label>
              <select name="ms_kerja" class="form-select">
                <option value="">-- Pilih Kode Ms Kerja --</option>
                <option value="<1" <?= $data['ms_kerja'] == '<1' ? 'selected' : '' ?>><1</option>
                <option value="PT" <?= $data['ms_kerja'] == 'PT' ? 'selected' : '' ?>>PT</option>
                <option value="FT>1" <?= $data['ms_kerja'] == 'FT>1' ? 'selected' : '' ?>>FT>1</option>
              </select>
            </div>

            <!-- Range 11 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Kode Index</label>
              <select name="indexins" class="form-select">
                <option value="">-- Pilih Kode Index --</option>
                <?php mysqli_data_seek($indexin, 0); while ($row = mysqli_fetch_assoc($indexin)) : ?>
                  <option value="<?= $row['dep_id'] ?>" <?= $data['indexins'] == $row['dep_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['dep_id']) ?> - <?= htmlspecialchars($row['nama']) ?> - <?= htmlspecialchars($row['persen']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Bank</label>
              <select name="bpd" class="form-select">
                <option value="">-- Pilih Bank --</option>
                <?php mysqli_data_seek($bank, 0); while ($row = mysqli_fetch_assoc($bank)) : ?>
                  <option value="<?= $row['namabank'] ?>" <?= $data['bpd'] == $row['namabank'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['namabank']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Range 12 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Rekening</label>
              <input type="text" name="rekening" class="form-control" value="<?= htmlspecialchars($data['rekening']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status Aktif</label>
              <select name="stts_aktif" class="form-select">
                <option value="">-- Pilih Status Aktif --</option>
                <?php foreach (['AKTIF', 'CUTI', 'KELUAR', 'TENAGA LUAR', 'NON AKTIF'] as $status) : ?>
                  <option value="<?= $status ?>" <?= $data['stts_aktif'] == $status ? 'selected' : '' ?>><?= $status ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Range 13 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Wajib Masuk</label>
              <input type="text" name="wajibmasuk" class="form-control" value="<?= htmlspecialchars($data['wajibmasuk']) ?>">
              <small class="form-text text-muted">
                Isi dgn "-" jika wajib masuk 1 bulan-hari libur<br>
                Isi dgn "-1" jika wajib masuk kosong<br>
                Isi dgn "-2" jika wajib masuk 1 bulan-4 hari<br>
                Isi dgn "-3" jika wajib masuk 1 bulan-2 hari-linas<br>
                Isi dgn "-4" jika wajib masuk 1 bulan-hari Ahad<br>
                Isi dgn "-5" jika wajib mengikuti penjadwalan
              </small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Mulai Kontrak</label>
              <input type="date" name="mulai_kontrak" class="form-control" value="<?= htmlspecialchars($data['mulai_kontrak']) ?>">
            </div>

            <!-- Range 14 -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Photo</label>
              <input type="file" name="photo" class="form-control" accept="image/*">
              <small class="text-muted">Format yang diizinkan: JPG, PNG, GIF, WebP</small>
              <?php if (!empty($data['photo'])) : ?>
                <?php
                  $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                  $path = "/" . $appFolder . "/webapps/penggajian/" . htmlspecialchars($data['photo']);
                ?>
                <div class="mt-2 text-center">
                  <img src="<?= $path ?>" alt="Foto Pegawai" class="img-thumbnail" style="width:90px;height:90px;">
                </div>
              <?php else : ?>
                <small class="text-muted">Belum ada foto</small>
              <?php endif; ?>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">No. KTP</label>
              <input type="text" name="no_ktp" class="form-control" value="<?= htmlspecialchars($data['no_ktp']) ?>">
            </div>
          </div>

          <div class="mt-4 text-center">
            <button type="submit" class="btn btn-success">➕ Tambah Pegawai</button>
            <a href="pegawai.php" class="btn btn-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
