<?php
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik         = trim($_POST['nik'] ?? '');
    $nama        = trim($_POST['nama'] ?? '');
    $jk          = in_array($_POST['jk'] ?? '', ['Pria', 'Wanita'], true) ? ($_POST['jk'] ?? 'Pria') : 'Pria';
    $jbtn        = trim($_POST['jbtn'] ?? '');
    $jnj_jabatan = resolveReferenceValue($conn, 'jnj_jabatan', 'kode', trim($_POST['jnj_jabatan'] ?? ''));
    $kode_kelompok = resolveReferenceValue($conn, 'kelompok_jabatan', 'kode_kelompok', trim($_POST['kode_kelompok'] ?? ''));
    $departemen  = resolveReferenceValue($conn, 'departemen', 'dep_id', trim($_POST['departemen'] ?? ''));
    $bidang      = resolveReferenceValue($conn, 'bidang', 'nama', trim($_POST['bidang'] ?? ''));
    $kode_resiko = resolveReferenceValue($conn, 'resiko_kerja', 'kode_resiko', trim($_POST['kode_resiko'] ?? ''));
    $kode_emergency = resolveReferenceValue($conn, 'emergency_index', 'kode_emergency', trim($_POST['kode_emergency'] ?? ''));
    $stts_wp     = resolveReferenceValue($conn, 'stts_wp', 'stts', trim($_POST['stts_wp'] ?? ''));
    $stts_kerja  = resolveReferenceValue($conn, 'stts_kerja', 'stts', trim($_POST['stts_kerja'] ?? ''));
    $npwp        = trim($_POST['npwp'] ?? '');
    $pendidikan  = resolveReferenceValue($conn, 'pendidikan', 'tingkat', trim($_POST['pendidikan'] ?? ''));
    $tmp_lahir   = trim($_POST['tmp_lahir'] ?? '');
    $tgl_lahir   = trim($_POST['tgl_lahir'] ?? '');
    $alamat      = trim($_POST['alamat'] ?? '');
    $kota        = trim($_POST['kota'] ?? '');
    $mulai_kerja = trim($_POST['mulai_kerja'] ?? '');
    $ms_kerja    = in_array($_POST['ms_kerja'] ?? '', ['<1', 'PT', 'FT>1'], true) ? ($_POST['ms_kerja'] ?? 'PT') : 'PT';
    $indexins    = resolveReferenceValue($conn, 'departemen', 'dep_id', trim($_POST['indexins'] ?? ''));
    $bpd         = resolveReferenceValue($conn, 'bank', 'namabank', trim($_POST['bpd'] ?? ''));
    $rekening    = trim($_POST['rekening'] ?? '');
    $stts_aktif  = in_array($_POST['stts_aktif'] ?? '', ['AKTIF', 'CUTI', 'KELUAR', 'TENAGA LUAR', 'NON AKTIF'], true) ? ($_POST['stts_aktif'] ?? 'AKTIF') : 'AKTIF';
    $wajibmasuk  = is_numeric($_POST['wajibmasuk'] ?? '') ? (int)$_POST['wajibmasuk'] : 0;
    $mulai_kontrak = trim($_POST['mulai_kontrak'] ?? '');
    $no_ktp      = trim($_POST['no_ktp'] ?? '');

    $gapok       = 0;
    $pengurang   = 0;
    $indek       = 0;
    $cuti_diambil = 0;
    $dankes      = 0;
    $defaultDate = date('Y-m-d');
    $tgl_lahir   = $tgl_lahir !== '' ? $tgl_lahir : $defaultDate;
    $mulai_kerja = $mulai_kerja !== '' ? $mulai_kerja : $defaultDate;
    $mulai_kontrak = $mulai_kontrak !== '' ? $mulai_kontrak : $defaultDate;

    // handle upload foto
    $photo = '';
    if (!empty($_FILES['photo']['name'])) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            echo "Upload foto gagal.";
            exit;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes, true)) {
            echo "File photo harus berupa gambar.";
            exit;
        }

        $targetDir = __DIR__ . "/../../webapps/penggajian/pages/pegawai/photo/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName  = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $_FILES['photo']['name']);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
            $photo = "pages/pegawai/photo/" . $fileName;
        }
    }

    $stmt = $conn->prepare("INSERT INTO pegawai 
        (nik,nama,jk,jbtn,jnj_jabatan,kode_kelompok,departemen,bidang,
         kode_resiko,kode_emergency,stts_wp,stts_kerja,npwp,pendidikan,
         tmp_lahir,tgl_lahir,alamat,kota,mulai_kerja,ms_kerja,indexins,
         bpd,rekening,stts_aktif,wajibmasuk,gapok,pengurang,indek,
         mulai_kontrak,cuti_diambil,dankes,photo,no_ktp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        echo "Prepare gagal: " . $conn->error;
        exit;
    }

    $types = str_repeat('s', 33);
    $stmt->bind_param(
        $types,
        $nik,
        $nama,
        $jk,
        $jbtn,
        $jnj_jabatan,
        $kode_kelompok,
        $departemen,
        $bidang,
        $kode_resiko,
        $kode_emergency,
        $stts_wp,
        $stts_kerja,
        $npwp,
        $pendidikan,
        $tmp_lahir,
        $tgl_lahir,
        $alamat,
        $kota,
        $mulai_kerja,
        $ms_kerja,
        $indexins,
        $bpd,
        $rekening,
        $stts_aktif,
        $wajibmasuk,
        $gapok,
        $pengurang,
        $indek,
        $mulai_kontrak,
        $cuti_diambil,
        $dankes,
        $photo,
        $no_ktp
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: pegawai.php?status=success");
        exit;
    } else {
        echo "Insert gagal: " . $stmt->error;
        $stmt->close();
    }
} else {
    echo "Invalid request.";
}
?>
