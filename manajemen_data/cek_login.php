<?php
ob_start();
session_start();
include __DIR__ . '/conf/conf.php';
include __DIR__ . '/conf/command.php';

if (isset($_POST['usere']) && isset($_POST['passworde'])) {
    $usere     = validTeks4($_POST['usere'],30);
    $passworde = validTeks4($_POST['passworde'],30);

    $conn = bukakoneksi();

    // cek admin
    $sqlAdmin = "SELECT 
                    CAST(AES_DECRYPT(usere,'nur') AS CHAR) AS usere 
                 FROM admin 
                 WHERE usere=AES_ENCRYPT('$usere','nur') 
                   AND passworde=AES_ENCRYPT('$passworde','windi') 
                 LIMIT 1";
    $rowAdmin = mysqli_fetch_assoc(mysqli_query($conn, $sqlAdmin));

    // cek user + join pegawai (pakai nik) + ambil hak akses
    $sqlUser = "SELECT 
                    CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) AS id_user,
                    p.nama AS nama_pegawai,
                    u.pegawai_admin,
                    u.pegawai_user,
                    u.master_berkas_pegawai,
                    u.berkas_kepegawaian,
                    u.riwayat_jabatan,
                    u.riwayat_pendidikan,
                    u.riwayat_naik_gaji,
                    u.kegiatan_ilmiah,
                    u.riwayat_penghargaan,
                    u.riwayat_penelitian,
                    u.riwayat_surat_peringatan,
                    u.petugas,
                    u.dokter,
                    u.pengajuan_cuti
                FROM user u
                INNER JOIN pegawai p 
                        ON p.nik = TRIM(CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR))
                WHERE u.id_user = AES_ENCRYPT('$usere','nur') 
                  AND u.password = AES_ENCRYPT('$passworde','windi') 
                LIMIT 1";
    $rowUser = mysqli_fetch_assoc(mysqli_query($conn, $sqlUser));

    if ($rowAdmin) {
        // admin login → akses penuh
        $_SESSION["id_user"]      = $rowAdmin['usere'];
        $_SESSION["nama_pegawai"] = "Administrator";
        $_SESSION["user_login"]   = "Administrator";
        $_SESSION["hak_akses"]    = "administrator";

        // full akses ke semua menu
        $_SESSION["pegawai_admin"]              = true;
        $_SESSION["pegawai_user"]               = true;
        $_SESSION["master_berkas_pegawai"]      = true;
        $_SESSION["berkas_kepegawaian"]         = true;
        $_SESSION["riwayat_jabatan"]            = true;
        $_SESSION["riwayat_pendidikan"]         = true;
        $_SESSION["riwayat_naik_gaji"]          = true;
        $_SESSION["kegiatan_ilmiah"]            = true;
        $_SESSION["riwayat_penghargaan"]        = true;
        $_SESSION["riwayat_penelitian"]         = true;
        $_SESSION["riwayat_surat_peringatan"]   = true;
        $_SESSION["petugas"]                    = true;
        $_SESSION["dokter"]                     = true;
        $_SESSION["pengajuan_cuti"]             = true;

        header("Location: index.php");
        exit;
    } elseif ($rowUser) {
    // ambil tanggal lahir dari tabel pegawai
        $nikLogin = $rowUser['id_user'];
        $sqlPegawai = "SELECT tgl_lahir FROM pegawai 
                       WHERE nik='".mysqli_real_escape_string($conn,$nikLogin)."' 
                       LIMIT 1";
        $rowPegawai = mysqli_fetch_assoc(mysqli_query($conn, $sqlPegawai));

        // input dari form login
        $tglInput = $_POST['tgl_lahir'] ?? '';
        // normalisasi format ke yyyy-mm-dd
        $tglInputFormatted = date('Y-m-d', strtotime(str_replace('/', '-', $tglInput)));

        if ($rowPegawai && $tglInputFormatted === $rowPegawai['tgl_lahir']) {
            // user login → akses sesuai DB
            $_SESSION["id_user"]      = $rowUser['id_user'];
            $_SESSION["nama_pegawai"] = $rowUser['nama_pegawai'];
            $_SESSION["user_login"]   = $rowUser["nama_pegawai"]." (".$rowUser["id_user"].")";
            $_SESSION["hak_akses"]    = "user";

            // hak akses dari DB tabel user
            $_SESSION["pegawai_admin"]        = ($rowUser['pegawai_admin'] == "true");
            $_SESSION["pegawai_user"]         = ($rowUser['pegawai_user'] == "true");
            $_SESSION["master_berkas_pegawai"]= ($rowUser['master_berkas_pegawai'] == "true");
            $_SESSION["berkas_kepegawaian"]   = ($rowUser['berkas_kepegawaian'] == "true");
            $_SESSION["riwayat_jabatan"]      = ($rowUser['riwayat_jabatan'] == "true");
            $_SESSION["riwayat_pendidikan"]   = ($rowUser['riwayat_pendidikan'] == "true");
            $_SESSION["riwayat_naik_gaji"]    = ($rowUser['riwayat_naik_gaji'] == "true");
            $_SESSION["kegiatan_ilmiah"]      = ($rowUser['kegiatan_ilmiah'] == "true");
            $_SESSION["riwayat_penghargaan"]  = ($rowUser['riwayat_penghargaan'] == "true");
            $_SESSION["riwayat_penelitian"]   = ($rowUser['riwayat_penelitian'] == "true");
            $_SESSION["riwayat_surat_peringatan"] = ($rowUser['riwayat_surat_peringatan'] == "true");
            $_SESSION["petugas"]              = ($rowUser['petugas'] == "true");
            $_SESSION["dokter"]               = ($rowUser['dokter'] == "true");
            $_SESSION["pengajuan_cuti"]       = ($rowUser['pengajuan_cuti'] == "true");

            header("Location: index.php");
            exit;
        } else {
            // captcha tanggal lahir salah
            header("Location: login.php?error=2");
            exit;
        }
    }

    mysqli_close($conn);
}
ob_end_flush();
?>
