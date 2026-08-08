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
                    u.pegawai_admin, u.pegawai_user, u.master_berkas_pegawai, u.berkas_kepegawaian,
                    u.riwayat_jabatan, u.riwayat_pendidikan, u.riwayat_naik_gaji, u.kegiatan_ilmiah,
                    u.riwayat_penghargaan, u.riwayat_penelitian, u.riwayat_surat_peringatan, u.petugas,
                    u.dokter, u.pengajuan_cuti, u.jenis_cidera_k3rs, u.penyebab_k3rs, u.jenis_luka_k3rs,
                    u.lokasi_kejadian_k3rs, u.dampak_cidera_k3rs, u.jenis_pekerjaan_k3rs, u.bagian_tubuh_k3rs,
                    u.jenis_cidera_k3rstahun, u.penyebab_k3rstahun, u.jenis_luka_k3rstahun, u.lokasi_kejadian_k3rstahun,
                    u.dampak_cidera_k3rstahun, u.jenis_pekerjaan_k3rstahun, u.bagian_tubuh_k3rstahun, u.ruang_audit_kepatuhan,
                    u.skp_kategori_penilaian, u.skp_kriteria_penilaian, u.peristiwa_k3rs, u.audit_kepatuhan_apd,
                    u.audit_cuci_tangan_medis, u.audit_pembuangan_limbah, u.audit_pembuangan_benda_tajam,
                    u.audit_penanganan_darah, u.audit_pengelolaan_linen_kotor, u.audit_penempatan_pasien,
                    u.audit_kamar_jenazah, u.audit_bundle_iadp, u.audit_bundle_ido, u.audit_fasilitas_kebersihan_tangan,
                    u.audit_fasilitas_apd, u.audit_pembuangan_limbah_cair_infeksius, u.audit_sterilisasi_alat,
                    u.audit_bundle_isk, u.audit_bundle_plabsi, u.audit_bundle_vap, u.skp_penilaian, u.skp_rekapitulasi_penilaian
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
        $_SESSION["pegawai_admin"]                          = true;
        $_SESSION["pegawai_user"]                           = true;
        $_SESSION["master_berkas_pegawai"]                  = true;
        $_SESSION["berkas_kepegawaian"]                     = true;
        $_SESSION["riwayat_jabatan"]                        = true;
        $_SESSION["riwayat_pendidikan"]                     = true;
        $_SESSION["riwayat_naik_gaji"]                      = true;
        $_SESSION["kegiatan_ilmiah"]                        = true;
        $_SESSION["riwayat_penghargaan"]                    = true;
        $_SESSION["riwayat_penelitian"]                     = true;
        $_SESSION["riwayat_surat_peringatan"]               = true;
        $_SESSION["petugas"]                                = true;
        $_SESSION["dokter"]                                 = true;
        $_SESSION["pengajuan_cuti"]                         = true;
        $_SESSION["jenis_cidera_k3rs"]                      = true;
        $_SESSION["penyebab_k3rs"]                          = true;
        $_SESSION["jenis_luka_k3rs"]                        = true;
        $_SESSION["lokasi_kejadian_k3rs"]                   = true;
        $_SESSION["dampak_cidera_k3rs"]                     = true;
        $_SESSION["jenis_pekerjaan_k3rs"]                   = true;
        $_SESSION["bagian_tubuh_k3rs"]                      = true;
        $_SESSION["jenis_cidera_k3rstahun"]                 = true;
        $_SESSION["penyebab_k3rstahun"]                     = true;
        $_SESSION["jenis_luka_k3rstahun"]                   = true;
        $_SESSION["lokasi_kejadian_k3rstahun"]              = true;
        $_SESSION["dampak_cidera_k3rstahun"]                = true;
        $_SESSION["jenis_pekerjaan_k3rstahun"]              = true;
        $_SESSION["bagian_tubuh_k3rstahun"]                 = true;
        $_SESSION["ruang_audit_kepatuhan"]                  = true;
        $_SESSION["skp_kategori_penilaian"]                 = true;
        $_SESSION["skp_kriteria_penilaian"]                 = true;
        $_SESSION["peristiwa_k3rs"]                         = true;
        $_SESSION["audit_kepatuhan_apd"]                    = true;
        $_SESSION["audit_cuci_tangan_medis"]                = true;
        $_SESSION["audit_pembuangan_limbah"]                = true;
        $_SESSION["audit_pembuangan_benda_tajam"]           = true;
        $_SESSION["audit_penanganan_darah"]                 = true;
        $_SESSION["audit_pengelolaan_linen_kotor"]          = true;
        $_SESSION["audit_penempatan_pasien"]                = true;
        $_SESSION["audit_kamar_jenazah"]                    = true;
        $_SESSION["audit_bundle_iadp"]                      = true;
        $_SESSION["audit_bundle_ido"]                       = true;
        $_SESSION["audit_fasilitas_kebersihan_tangan"]      = true;
        $_SESSION["audit_fasilitas_apd"]                    = true;
        $_SESSION["audit_pembuangan_limbah_cair_infeksius"] = true;
        $_SESSION["audit_sterilisasi_alat"]                 = true;
        $_SESSION["audit_bundle_isk"]                       = true;
        $_SESSION["audit_bundle_plabsi"]                    = true;
        $_SESSION["audit_bundle_vap"]                       = true;
        $_SESSION["skp_penilaian"]                          = true;
        $_SESSION["skp_rekapitulasi_penilaian"]             = true;

        header("Location: dashboard.php");
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
            $_SESSION["pegawai_admin"]                              = ($rowUser['pegawai_admin'] == "true");
            $_SESSION["pegawai_user"]                               = ($rowUser['pegawai_user'] == "true");
            $_SESSION["master_berkas_pegawai"]                      = ($rowUser['master_berkas_pegawai'] == "true");
            $_SESSION["berkas_kepegawaian"]                         = ($rowUser['berkas_kepegawaian'] == "true");
            $_SESSION["riwayat_jabatan"]                            = ($rowUser['riwayat_jabatan'] == "true");
            $_SESSION["riwayat_pendidikan"]                         = ($rowUser['riwayat_pendidikan'] == "true");
            $_SESSION["riwayat_naik_gaji"]                          = ($rowUser['riwayat_naik_gaji'] == "true");
            $_SESSION["kegiatan_ilmiah"]                            = ($rowUser['kegiatan_ilmiah'] == "true");
            $_SESSION["riwayat_penghargaan"]                        = ($rowUser['riwayat_penghargaan'] == "true");
            $_SESSION["riwayat_penelitian"]                         = ($rowUser['riwayat_penelitian'] == "true");
            $_SESSION["riwayat_surat_peringatan"]                   = ($rowUser['riwayat_surat_peringatan'] == "true");
            $_SESSION["petugas"]                                    = ($rowUser['petugas'] == "true");
            $_SESSION["dokter"]                                     = ($rowUser['dokter'] == "true");
            $_SESSION["pengajuan_cuti"]                             = ($rowUser['pengajuan_cuti'] == "true");
            $_SESSION["jenis_cidera_k3rs"]                          = ($rowUser['jenis_cidera_k3rs'] == "true");
            $_SESSION["penyebab_k3rs"]                              = ($rowUser['penyebab_k3rs'] == "true");
            $_SESSION["jenis_luka_k3rs"]                            = ($rowUser['jenis_luka_k3rs'] == "true");
            $_SESSION["lokasi_kejadian_k3rs"]                       = ($rowUser['lokasi_kejadian_k3rs'] == "true");
            $_SESSION["dampak_cidera_k3rs"]                         = ($rowUser['dampak_cidera_k3rs'] == "true");
            $_SESSION["jenis_pekerjaan_k3rs"]                       = ($rowUser['jenis_pekerjaan_k3rs'] == "true");
            $_SESSION["bagian_tubuh_k3rs"]                          = ($rowUser['bagian_tubuh_k3rs'] == "true");
            $_SESSION["jenis_cidera_k3rstahun"]                     = ($rowUser['jenis_cidera_k3rstahun'] == "true");
            $_SESSION["penyebab_k3rstahun"]                         = ($rowUser['penyebab_k3rstahun'] == "true");
            $_SESSION["jenis_luka_k3rstahun"]                       = ($rowUser['jenis_luka_k3rstahun'] == "true");
            $_SESSION["lokasi_kejadian_k3rstahun"]                  = ($rowUser['lokasi_kejadian_k3rstahun'] == "true");
            $_SESSION["dampak_cidera_k3rstahun"]                    = ($rowUser['dampak_cidera_k3rstahun'] == "true");
            $_SESSION["jenis_pekerjaan_k3rstahun"]                  = ($rowUser['jenis_pekerjaan_k3rstahun'] == "true");
            $_SESSION["bagian_tubuh_k3rstahun"]                     = ($rowUser['bagian_tubuh_k3rstahun'] == "true");
            $_SESSION["ruang_audit_kepatuhan"]                      = ($rowUser['ruang_audit_kepatuhan'] == "true");
            $_SESSION["skp_kategori_penilaian"]                     = ($rowUser['skp_kategori_penilaian'] == "true");
            $_SESSION["skp_kriteria_penilaian"]                     = ($rowUser['skp_kriteria_penilaian'] == "true");
            $_SESSION["peristiwa_k3rs"]                             = ($rowUser['peristiwa_k3rs'] == "true");
            $_SESSION["audit_kepatuhan_apd"]                        = ($rowUser['audit_kepatuhan_apd'] == "true");
            $_SESSION["audit_cuci_tangan_medis"]                    = ($rowUser['audit_cuci_tangan_medis'] == "true");
            $_SESSION["audit_pembuangan_limbah"]                    = ($rowUser['audit_pembuangan_limbah'] == "true");
            $_SESSION["audit_pembuangan_benda_tajam"]               = ($rowUser['audit_pembuangan_benda_tajam'] == "true");
            $_SESSION["audit_penanganan_darah"]                     = ($rowUser['audit_penanganan_darah'] == "true");
            $_SESSION["audit_pengelolaan_linen_kotor"]              = ($rowUser['audit_pengelolaan_linen_kotor'] == "true");
            $_SESSION["audit_penempatan_pasien"]                    = ($rowUser['audit_penempatan_pasien'] == "true");
            $_SESSION["audit_kamar_jenazah"]                        = ($rowUser['audit_kamar_jenazah'] == "true");
            $_SESSION["audit_bundle_iadp"]                          = ($rowUser['audit_bundle_iadp'] == "true");
            $_SESSION["audit_bundle_ido"]                           = ($rowUser['audit_bundle_ido'] == "true");
            $_SESSION["audit_fasilitas_kebersihan_tangan"]          = ($rowUser['audit_fasilitas_kebersihan_tangan'] == "true");
            $_SESSION["audit_fasilitas_apd"]                        = ($rowUser['audit_fasilitas_apd'] == "true");
            $_SESSION["audit_pembuangan_limbah_cair_infeksius"]     = ($rowUser['audit_pembuangan_limbah_cair_infeksius'] == "true");
            $_SESSION["audit_sterilisasi_alat"]                     = ($rowUser['audit_sterilisasi_alat'] == "true");
            $_SESSION["audit_bundle_isk"]                           = ($rowUser['audit_bundle_isk'] == "true");
            $_SESSION["audit_bundle_plabsi"]                        = ($rowUser['audit_bundle_plabsi'] == "true");
            $_SESSION["audit_bundle_vap"]                           = ($rowUser['audit_bundle_vap'] == "true");
            $_SESSION["skp_penilaian"]                              = ($rowUser['skp_penilaian'] == "true");
            $_SESSION["skp_rekapitulasi_penilaian"]                 = ($rowUser['skp_rekapitulasi_penilaian'] == "true");

            header("Location: dashboard.php");
            exit;
        } else {
            // captcha tanggal lahir salah
            header("Location: login.php?error=2");
            exit;
        }
    } else {
        // username / password salah
        header("Location: login.php?error=1");
        exit;
    }

    mysqli_close($conn);
}
ob_end_flush();
?>
