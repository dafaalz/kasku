<?php
// Define access constant for includes
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/function.php';

// Protect route: allow only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_username = $_SESSION['username'];

$message = '';
$message_type = '';

// Get filter parameters
$filter_kelas = isset($_GET['kelas']) ? intval($_GET['kelas']) : 0;
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';

// Check for message from URL parameters
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['message_type'];
}

// Fungsi untuk mendapatkan data kas berdasarkan kelas
function getKasByKelas($conn, $id_kelas) {
    $sql = "SELECT id_kas FROM kas WHERE id_kelas = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_kelas);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['id_kas'] : null;
}

// DEBUG: Tampilkan semua error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle Add Transaction - VERSI DIPERBAIKI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_transaksi'])) {
    
    // Ambil data dari POST
    $id_kelas = isset($_POST['id_kelas']) ? intval($_POST['id_kelas']) : 0;
    $jenis = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
    $jumlah = isset($_POST['jumlah']) ? floatval($_POST['jumlah']) : 0;
    $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    
    error_log("=== PROSES TAMBAH TRANSAKSI ===");
    error_log("Data POST: id_kelas=$id_kelas, jenis=$jenis, jumlah=$jumlah, tanggal=$tanggal");
    
    // Validasi input
    $errors = [];
    
    if ($id_kelas <= 0) {
        $errors[] = "Pilih kelas terlebih dahulu";
    }
    
    if (!in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        $errors[] = "Jenis transaksi tidak valid";
    }
    
    if ($jumlah <= 0) {
        $errors[] = "Jumlah harus lebih dari 0";
    }
    
    if (empty($tanggal)) {
        $errors[] = "Tanggal tidak boleh kosong";
    }
    
    if (empty($errors)) {
        // Dapatkan id_kas dari id_kelas
        $id_kas = getKasByKelas($conn, $id_kelas);
        error_log("ID Kas yang didapat: " . ($id_kas ? $id_kas : 'NULL'));
        
        if ($id_kas) {
            // Mulai transaction untuk memastikan konsistensi
            mysqli_begin_transaction($conn);
            
            try {
                // 1. Insert transaksi
                $sql = "INSERT INTO transaksi (id_kas, jenis, jumlah, tanggal, deskripsi, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                
                error_log("SQL Insert: $sql");
                error_log("Parameters: $id_kas, $jenis, $jumlah, $tanggal, $deskripsi, $admin_id");
                
                $stmt = mysqli_prepare($conn, $sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . mysqli_error($conn));
                }
                
                mysqli_stmt_bind_param($stmt, "isdssi", $id_kas, $jenis, $jumlah, $tanggal, $deskripsi, $admin_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Execute failed: " . mysqli_error($conn));
                }
                
                $insert_id = mysqli_insert_id($conn);
                error_log("Insert berhasil! ID Transaksi: $insert_id");
                mysqli_stmt_close($stmt);
                
                // 2. Update saldo kas
                if ($jenis === 'pemasukan') {
                    $update_sql = "UPDATE kas SET saldo = saldo + ? WHERE id_kas = ?";
                } else {
                    $update_sql = "UPDATE kas SET saldo = saldo - ? WHERE id_kas = ?";
                }
                
                error_log("SQL Update: $update_sql");
                error_log("Update Parameters: $jumlah, $id_kas");
                
                $update_stmt = mysqli_prepare($conn, $update_sql);
                if (!$update_stmt) {
                    throw new Exception("Prepare update failed: " . mysqli_error($conn));
                }
                
                mysqli_stmt_bind_param($update_stmt, "di", $jumlah, $id_kas);
                
                if (!mysqli_stmt_execute($update_stmt)) {
                    throw new Exception("Execute update failed: " . mysqli_error($conn));
                }
                
                mysqli_stmt_close($update_stmt);
                
                // Commit transaction
                mysqli_commit($conn);
                error_log("Transaction COMMIT berhasil!");
                
                // Redirect dengan parameter
                $redirect_url = "admin_transactions.php?message=" . urlencode("Transaksi berhasil ditambahkan! ID: $insert_id") . "&message_type=success";
                if ($filter_kelas > 0) $redirect_url .= "&kelas=" . $filter_kelas;
                if (!empty($filter_jenis)) $redirect_url .= "&jenis=" . urlencode($filter_jenis);
                if (!empty($filter_bulan)) $redirect_url .= "&bulan=" . urlencode($filter_bulan);
                
                error_log("Redirect ke: $redirect_url");
                header("Location: " . $redirect_url);
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction jika ada error
                mysqli_rollback($conn);
                $message = "Error: " . $e->getMessage();
                $message_type = 'danger';
                error_log("Transaction Error: " . $e->getMessage());
            }
        } else {
            $message = "Kas untuk kelas ini tidak ditemukan!";
            $message_type = 'danger';
            error_log("Kas tidak ditemukan untuk id_kelas: $id_kelas");
        }
    } else {
        $message = "Validasi gagal: " . implode(", ", $errors);
        $message_type = 'danger';
        error_log("Validasi gagal: " . implode(", ", $errors));
    }
}

// [KODE UNTUK EDIT, DELETE DAN LAINNYA TETAP SAMA...]

// Get all transactions with kas saldo
$transactions = [];
$sql = "SELECT t.*, k.nama_kelas, k.id_kelas, kas.saldo
        FROM transaksi t
        JOIN kas ON t.id_kas = kas.id_kas
        JOIN kelas k ON kas.id_kelas = k.id_kelas
        WHERE k.id_admin = ?";

$params = [$admin_id];
$types = 'i';

if ($filter_kelas > 0) {
    $sql .= " AND k.id_kelas = ?";
    $params[] = $filter_kelas;
    $types .= 'i';
}

if (!empty($filter_jenis)) {
    $sql .= " AND t.jenis = ?";
    $params[] = $filter_jenis;
    $types .= 's';
}

if (!empty($filter_bulan)) {
    $sql .= " AND DATE_FORMAT(t.tanggal, '%Y-%m') = ?";
    $params[] = $filter_bulan;
    $types .= 's';
}

$sql .= " ORDER BY t.tanggal DESC, t.created_at DESC";

error_log("SQL Query Transactions: $sql");
error_log("Parameters: " . implode(", ", $params));

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error preparing transactions query: " . mysqli_error($conn));
}

// Get classes for filter and form
$classes = [];
$sql_classes = "SELECT id_kelas, nama_kelas FROM kelas WHERE id_admin = ? ORDER BY nama_kelas";
if ($stmt = mysqli_prepare($conn, $sql_classes)) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result_classes = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result_classes)) {
        $classes[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error preparing classes query: " . mysqli_error($conn));
}

// Calculate statistics
$total_pemasukan = 0;
$total_pengeluaran = 0;

foreach ($transactions as $trans) {
    if ($trans['jenis'] === 'pemasukan') {
        $total_pemasukan += $trans['jumlah'];
    } else {
        $total_pengeluaran += $trans['jumlah'];
    }
}

$saldo_kas = $total_pemasukan - $total_pengeluaran;
$jumlah_transaksi = count($transactions);

$pageTitle = "Manajemen Transaksi - Admin KasKelas";
include 'includes/admin_header.php';
?>

<!-- DEBUG INFO -->
<div class="container mt-3">
    <div class="alert alert-info">
        <h5>Debug Info:</h5>
        <p><strong>Jumlah Kelas: <?php echo count($classes); ?></strong></p>
        <p><strong>Jumlah Transaksi: <?php echo count($transactions); ?></strong></p>
        <p><strong>Saldo Total: <?php echo format_rupiah($saldo_kas); ?></strong></p>
        <p><strong>Kelas yang tersedia:</strong></p>
        <ul>
            <?php foreach ($classes as $class): ?>
                <li>ID: <?php echo $class['id_kelas']; ?> - <?php echo htmlspecialchars($class['nama_kelas']); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <p><strong>Data POST yang diterima:</strong></p>
            <ul>
                <li>id_kelas: <?php echo $_POST['id_kelas'] ?? 'Tidak ada'; ?></li>
                <li>jenis: <?php echo $_POST['jenis'] ?? 'Tidak ada'; ?></li>
                <li>jumlah: <?php echo $_POST['jumlah'] ?? 'Tidak ada'; ?></li>
                <li>tanggal: <?php echo $_POST['tanggal'] ?? 'Tidak ada'; ?></li>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- [REST OF YOUR HTML CODE REMAINS THE SAME...] -->