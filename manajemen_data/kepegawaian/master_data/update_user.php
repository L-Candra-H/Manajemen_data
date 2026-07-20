<?php
include __DIR__ . '/../../conf/conf.php';
$conn = bukakoneksi();

$id_user = $_POST['id_user'];
$field   = $_POST['field'];
$value   = $_POST['value'];

$allowed = ['dokter','petugas','barcode','presensi_harian','presensi_bulanan',
            'pegawai_admin','pegawai_user','sms','sidikjari','jam_masuk','jadwal_pegawai',
            'temporary_presensi','master_berkas_pegawai','berkas_kepegawaian',
            'riwayat_jabatan','riwayat_pendidikan','riwayat_naik_gaji','kegiatan_ilmiah',
            'riwayat_penghargaan','riwayat_penelitian','jenis_cidera_k3rs','penyebab_k3rs',
            'jenis_luka_k3rs','lokasi_kejadian_k3rs','dampak_cidera_k3rs','jenis_pekerjaan_k3rs',
            'bagian_tubuh_k3rs','peristiwa_k3rs','jenis_cidera_k3rstahun','penyebab_k3rstahun',
            'jenis_luka_k3rstahun','lokasi_kejadian_k3rstahun','dampak_cidera_k3rstahun',
            'jenis_pekerjaan_k3rstahun','bagian_tubuh_k3rstahun','pengajuan_cuti',
            'audit_kepatuhan_apd','audit_cuci_tangan_medis','audit_pembuangan_limbah',
            'ruang_audit_kepatuhan','audit_pembuangan_benda_tajam','audit_penanganan_darah',
            'audit_pengelolaan_linen_kotor','audit_penempatan_pasien','audit_kamar_jenazah',
            'audit_bundle_iadp','audit_bundle_ido','audit_fasilitas_kebersihan_tangan',
            'audit_fasilitas_apd','audit_pembuangan_limbah_cair_infeksius','audit_sterilisasi_alat',
            'audit_bundle_isk','audit_bundle_plabsi','audit_bundle_vap','skp_kategori_penilaian',
            'skp_kriteria_penilaian','skp_penilaian','skp_rekapitulasi_penilaian','riwayat_surat_peringatan'];

if (in_array($field, $allowed)) {
    $sql = "UPDATE user SET $field='$value' WHERE CAST(AES_DECRYPT(id_user,'nur') AS CHAR)='$id_user'";
    mysqli_query($conn, $sql);
    echo "Updated $field to $value";
} else {
    echo "Field not allowed";
}
?>
