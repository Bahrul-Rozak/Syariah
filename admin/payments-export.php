<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "
    SELECT 
        p.id,
        u.unique_id as user_id,
        u.nama as user_name,
        u.email,
        u.no_wa,
        u.jenis_kelamin,
        p.amount,
        p.status,
        DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') as created_date,
        DATE_FORMAT(p.confirmed_at, '%Y-%m-%d %H:%i:%s') as confirmed_date,
        p.confirmation_method,
        p.notes,
        us.kuis_score,
        us.status_cv,
        us.cv_score
    FROM payments p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN user_scores us ON u.id = us.user_id
    WHERE u.is_admin = 0
";

if (!empty($status_filter)) {
    $status_filter = $conn->real_escape_string($status_filter);
    $query .= " AND p.status = '$status_filter'";
}

if (!empty($date_from)) {
    $date_from = $conn->real_escape_string($date_from);
    $query .= " AND DATE(p.created_at) >= '$date_from'";
}

if (!empty($date_to)) {
    $date_to = $conn->real_escape_string($date_to);
    $query .= " AND DATE(p.created_at) <= '$date_to'";
}

$query .= " ORDER BY p.created_at DESC";

$result = $conn->query($query);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="payments_export_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fwrite($output, "\xEF\xBB\xBF");

// Add headers
$headers = [
    'ID Transaksi',
    'ID User',
    'Nama User',
    'Email',
    'WhatsApp',
    'Jenis Kelamin',
    'Jumlah (Rp)',
    'Status',
    'Tanggal Transaksi',
    'Tanggal Konfirmasi',
    'Metode Konfirmasi',
    'Catatan',
    'Skor Kuis',
    'Status CV',
    'Skor CV'
];
fputcsv($output, $headers);

// Add data rows
while($row = $result->fetch_assoc()) {
    $status_texts = [
        'pending' => 'Menunggu Pembayaran',
        'paid' => 'Menunggu Konfirmasi',
        'confirmed' => 'Terkonfirmasi',
        'expired' => 'Kadaluarsa'
    ];
    
    $csv_row = [
        $row['id'],
        $row['user_id'],
        $row['user_name'],
        $row['email'],
        $row['no_wa'],
        $row['jenis_kelamin'],
        number_format($row['amount'], 0, ',', '.'),
        $status_texts[$row['status']] ?? $row['status'],
        $row['created_date'],
        $row['confirmed_date'] ?? '',
        $row['confirmation_method'] ?? 'whatsapp',
        $row['notes'] ?? '',
        $row['kuis_score'] ?? '0',
        $row['status_cv'] ?? 'belum',
        $row['cv_score'] ?? '0'
    ];
    
    fputcsv($output, $csv_row);
}

fclose($output);
exit;