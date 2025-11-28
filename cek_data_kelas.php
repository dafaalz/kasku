<?php
require_once 'includes/db_config.php';
require_once 'includes/function.php';

echo "<h1>CEK DATA KELAS & KAS</h1>";

// Cek semua kelas
$sql_kelas = "SELECT k.*, kas.id_kas, kas.saldo 
              FROM kelas k 
              LEFT JOIN kas ON k.id_kelas = kas.id_kelas 
              ORDER BY k.id_kelas";
$result_kelas = mysqli_query($conn, $sql_kelas);

echo "<h2>Data Kelas:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID Kelas</th><th>Nama Kelas</th><th>ID Admin</th><th>ID Kas</th><th>Saldo</th></tr>";
while ($row = mysqli_fetch_assoc($result_kelas)) {
    echo "<tr>";
    echo "<td>" . $row['id_kelas'] . "</td>";
    echo "<td>" . $row['nama_kelas'] . "</td>";
    echo "<td>" . $row['id_admin'] . "</td>";
    echo "<td>" . $row['id_kas'] . "</td>";
    echo "<td>" . $row['saldo'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Cek transaksi
$sql_transaksi = "SELECT * FROM transaksi ORDER BY id_transaksi DESC LIMIT 5";
$result_transaksi = mysqli_query($conn, $sql_transaksi);

echo "<h2>5 Transaksi Terakhir:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID Transaksi</th><th>ID Kas</th><th>Jenis</th><th>Jumlah</th><th>Tanggal</th></tr>";
while ($row = mysqli_fetch_assoc($result_transaksi)) {
    echo "<tr>";
    echo "<td>" . $row['id_transaksi'] . "</td>";
    echo "<td>" . $row['id_kas'] . "</td>";
    echo "<td>" . $row['jenis'] . "</td>";
    echo "<td>" . $row['jumlah'] . "</td>";
    echo "<td>" . $row['tanggal'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>