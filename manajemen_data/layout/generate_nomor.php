<?php
include __DIR__ . '/../conf/conf.php';
$conn = bukakoneksi();

$tanggal = $_GET['tanggal'] ?? date('Ymd');

$sql = "SELECT COUNT(*) AS jml 
        FROM pengajuan_cuti 
        WHERE DATE(tanggal) = STR_TO_DATE('$tanggal','%Y%m%d')";
$res = mysqli_query($conn,$sql);

if(!$res){
    die("Query error: " . mysqli_error($conn));
}

$row = mysqli_fetch_assoc($res);
$urut = (int)$row['jml'] + 1;

// hasil murni nomor urut (misalnya 001, 002, dst.)
echo str_pad($urut,3,'0',STR_PAD_LEFT);
