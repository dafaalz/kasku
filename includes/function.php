<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format number to Rupiah currency
 */
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Redirect to specified URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate secure password hash
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is regular user
 */
function is_user() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

/**
 * Get current user ID
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 */
function get_username() {
    return $_SESSION['username'] ?? null;
}

/**
 * Log error messages
 */
function log_error($message, $file = null, $line = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[KASKU ERROR] {$timestamp} - {$message}";
    
    if ($file) {
        $log_message .= " in {$file}";
    }
    
    if ($line) {
        $log_message .= " on line {$line}";
    }
    
    error_log($log_message);
}

/**
 * Generate random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

/**
 * Format date to Indonesian format
 */
function format_date_id($date) {
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "{$day} {$month} {$year}";
}

/**
 * Calculate time difference
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    ];

    $result = [];

    foreach ($string as $k => $v) {
        if ($diff->$k) {
            $result[] = $diff->$k . ' ' . $v;
        }
    }

    if (!$full) $result = array_slice($result, 0, 1);

    return $result ? implode(', ', $result) . ' yang lalu' : 'baru saja';
}


/**
 * Validate class code format
 */
function validate_class_code($code) {
    // Class code should be 6 characters, alphanumeric, uppercase
    return preg_match('/^[A-Z0-9]{6}$/', $code);
}

/**
 * Format transaction amount with color
 */
function format_transaction_amount($amount, $type) {
    $formatted = format_rupiah($amount);
    $color_class = $type === 'pemasukan' ? 'text-success' : 'text-danger';
    $icon = $type === 'pemasukan' ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle';
    
    return "<span class='{$color_class}'><i class='bi {$icon} me-1'></i>{$formatted}</span>";
}

/**
 * Get transaction type badge
 */
function get_transaction_badge($type) {
    if ($type === 'pemasukan') {
        return "<span class='kas-badge kas-badge-success'><i class='bi bi-arrow-up-circle me-1'></i>Pemasukan</span>";
    } else {
        return "<span class='kas-badge kas-badge-danger'><i class='bi bi-arrow-down-circle me-1'></i>Pengeluaran</span>";
    }
}

/**
 * Get status badge
 */
function get_status_badge($status) {
    if ($status === 'aktif') {
        return "<span class='kas-badge kas-badge-success'><i class='bi bi-check-circle me-1'></i>Aktif</span>";
    } else {
        return "<span class='kas-badge kas-badge-danger'><i class='bi bi-x-circle me-1'></i>Nonaktif</span>";
    }
}

/**
 * Clean and validate numeric input
 */
function clean_numeric($value) {
    // Remove any non-numeric characters except decimal point
    $cleaned = preg_replace('/[^0-9.]/', '', $value);
    return floatval($cleaned);
}

/**
 * Generate breadcrumb navigation
 */
function generate_breadcrumb($items) {
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $total = count($items);
    $current = 1;
    
    foreach ($items as $item) {
        if ($current === $total) {
            $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['text']) . '</li>';
        } else {
            $breadcrumb .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['text']) . '</a></li>';
        }
        $current++;
    }
    
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}
?>