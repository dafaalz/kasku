<?php
// test_koneksi.php
echo "<h1>Test Koneksi & Session</h1>";

// Test koneksi database
require_once 'includes/db_config.php';
echo "<h3>1. Test Koneksi Database:</h3>";
if ($conn) {
    echo "✅ Koneksi database BERHASIL<br>";
    
    // Test query
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM kelas");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "✅ Query database BERHASIL - Total kelas: " . $row['total'] . "<br>";
    } else {
        echo "❌ Query database GAGAL: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "❌ Koneksi database GAGAL<br>";
}

// Test session
echo "<h3>2. Test Session:</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['test'] = 'Session berhasil';
if (isset($_SESSION['test'])) {
    echo "✅ Session BERHASIL: " . $_SESSION['test'] . "<br>";
} else {
    echo "❌ Session GAGAL<br>";
}

// Test function.php
echo "<h3>3. Test Function:</h3>";
require_once 'includes/function.php';
echo "Format Rupiah: " . format_rupiah(50000) . "<br>";

echo "<h3>4. Test Form Action:</h3>";
?>
<form method="POST" action="admin_transactions.php">
    <input type="hidden" name="id_kelas" value="15">
    <input type="hidden" name="jenis" value="pemasukan">
    <input type="hidden" name="jumlah" value="100000">
    <input type="hidden" name="tanggal" value="<?php echo date('Y-m-d'); ?>">
    <button type="submit" name="tambah_transaksi">Test Submit ke admin_transactions.php</button>
</form>