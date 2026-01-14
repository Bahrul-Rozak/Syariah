<?php
include 'includes/header.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_payment'])) {
        $payment_id = intval($_POST['payment_id']);
        $status = $conn->real_escape_string($_POST['status']);
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');
        
        $update_query = "UPDATE payments SET 
                        status = '$status',
                        confirmed_by_admin = $admin_id,
                        confirmed_at = NOW(),
                        notes = '$notes'
                        WHERE id = $payment_id";
        
        if ($conn->query($update_query)) {
            // Get user info for WhatsApp notification
            $user_query = $conn->query("
                SELECT u.nama, u.no_wa, u.unique_id, p.amount 
                FROM payments p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.id = $payment_id
            ");
            $user_data = $user_query->fetch_assoc();
            
            $success = "Pembayaran berhasil dikonfirmasi!";
            $success .= " <a href='https://wa.me/{$user_data['no_wa']}?text=" . urlencode("Assalamu'alaikum {$user_data['nama']}, pembayaran Anda sebesar Rp " . number_format($user_data['amount'], 0, ',', '.') . " telah dikonfirmasi. Terima kasih.") . "' target='_blank' class='text-green-600 hover:text-green-800 font-medium'>Kirim notifikasi via WhatsApp →</a>";
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['update_payment'])) {
        $payment_id = intval($_POST['payment_id']);
        $amount = floatval($_POST['amount']);
        $status = $conn->real_escape_string($_POST['status']);
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');
        
        $update_query = "UPDATE payments SET 
                        amount = $amount,
                        status = '$status',
                        notes = '$notes'
                        WHERE id = $payment_id";
        
        if ($conn->query($update_query)) {
            $success = "Data pembayaran berhasil diperbarui!";
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['add_payment'])) {
        $user_id = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $status = $conn->real_escape_string($_POST['status']);
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');
        
        // Check if user already has pending payment
        $check_query = "SELECT id FROM payments WHERE user_id = $user_id AND status IN ('pending', 'paid')";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows > 0) {
            $error = "User sudah memiliki pembayaran yang sedang diproses!";
        } else {
            $insert_query = "INSERT INTO payments (user_id, amount, status, notes) 
                            VALUES ($user_id, $amount, '$status', '$notes')";
            
            if ($conn->query($insert_query)) {
                $success = "Data pembayaran berhasil ditambahkan!";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    } elseif (isset($_POST['delete_payment'])) {
        $payment_id = intval($_POST['payment_id']);
        $conn->query("DELETE FROM payments WHERE id = $payment_id");
        $success = "Data pembayaran berhasil dihapus!";
    } elseif (isset($_POST['send_reminder'])) {
        $payment_id = intval($_POST['payment_id']);
        
        // Get payment details
        $payment_query = $conn->query("
            SELECT p.*, u.nama, u.no_wa, u.unique_id 
            FROM payments p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = $payment_id
        ");
        $payment_data = $payment_query->fetch_assoc();
        
        // WhatsApp reminder message
        $whatsapp_message = "Assalamu'alaikum {$payment_data['nama']} (ID: {$payment_data['unique_id']}).\n\n";
        $whatsapp_message .= "Mengingatkan untuk melakukan pembayaran biaya taaruf sebesar Rp " . number_format($payment_data['amount'], 0, ',', '.') . ".\n\n";
        $whatsapp_message .= "Silakan transfer ke:\n";
        $whatsapp_message .= "Bank: BCA\n";
        $whatsapp_message .= "No Rek: 1234567890\n";
        $whatsapp_message .= "Atas Nama: Taaruf Islami\n\n";
        $whatsapp_message .= "Setelah transfer, konfirmasi ke admin via WhatsApp ini dengan mengirim bukti transfer.\n\n";
        $whatsapp_message .= "Terima kasih.";
        
        $whatsapp_url = "https://wa.me/{$payment_data['no_wa']}?text=" . urlencode($whatsapp_message);
        
        $success = "Link reminder WhatsApp berhasil dibuat! <a href='$whatsapp_url' target='_blank' class='text-green-600 hover:text-green-800 font-medium'>Kirim reminder →</a>";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT p.*, 
           u.nama, u.unique_id, u.no_wa, u.email, u.jenis_kelamin,
           us.status_cv, us.kuis_score
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

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (u.nama LIKE '%$search%' OR u.unique_id LIKE '%$search%' OR u.no_wa LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$query .= " ORDER BY p.created_at DESC";

// Get payments
$payments_result = $conn->query($query);
$total_payments = $payments_result->num_rows;

// Get stats
$total_amount = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'confirmed'")->fetch_assoc()['total'] ?? 0;
$pending_count = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch_assoc()['count'];
$confirmed_count = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'confirmed'")->fetch_assoc()['count'];

// Get users for dropdown
$users = $conn->query("SELECT id, nama, unique_id FROM users WHERE is_admin = 0 AND status = 'active' ORDER BY nama");
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Payments Management</h1>
        <p class="text-gray-600">Kelola dan konfirmasi pembayaran peserta via WhatsApp</p>
    </div>

    <?php if (isset($success)): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 alert-auto-hide">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700"><?php echo $success; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 alert-auto-hide">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700"><?php echo $error; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></div>
                    <div class="text-gray-600">Total Terkonfirmasi</div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo $total_payments; ?></div>
                    <div class="text-gray-600">Total Transaksi</div>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exchange-alt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo $pending_count; ?></div>
                    <div class="text-gray-600">Menunggu Konfirmasi</div>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo $confirmed_count; ?></div>
                    <div class="text-gray-600">Terkonfirmasi</div>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Add Payment -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Filters -->
            <div>
                <h3 class="text-lg font-medium text-gray-800 mb-4">Filter Pembayaran</h3>
                <form method="GET" action="" class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                            <select name="status" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid (Menunggu Konfirmasi)</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Cari User</label>
                            <input type="text" 
                                   name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Nama, ID, atau WhatsApp"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Dari Tanggal</label>
                            <input type="date" 
                                   name="date_from" 
                                   value="<?php echo $date_from; ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Sampai Tanggal</label>
                            <input type="date" 
                                   name="date_to" 
                                   value="<?php echo $date_to; ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" 
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <?php if (!empty($status_filter) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
                        <a href="payments.php" 
                           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Add Payment -->
            <div>
                <h3 class="text-lg font-medium text-gray-800 mb-4">Tambah Pembayaran Manual</h3>
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pilih User</label>
                        <select name="user_id" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">-- Pilih User --</option>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['nama']); ?> (<?php echo $user['unique_id']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Jumlah</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                                <input type="number" 
                                       name="amount" 
                                       required
                                       min="0"
                                       step="50000"
                                       value="500000"
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                            <select name="status" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Catatan</label>
                        <textarea name="notes" 
                                  rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                  placeholder="Catatan tambahan..."></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                name="add_payment"
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-plus mr-2"></i>Tambah Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Data Pembayaran</h2>
                <p class="text-gray-600 text-sm">Menampilkan <?php echo $total_payments; ?> pembayaran</p>
            </div>
            <div class="flex space-x-2">
                <a href="payments.php?export=csv" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                   <i class="fas fa-file-export mr-2"></i>Export CSV
                </a>
                <button id="printBtn" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 text-sm">
                        <th class="px-6 py-3 font-medium">User</th>
                        <th class="px-6 py-3 font-medium">Pembayaran</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Progress User</th>
                        <th class="px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while($payment = $payments_result->fetch_assoc()): 
                        $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'paid' => 'bg-blue-100 text-blue-800',
                            'confirmed' => 'bg-green-100 text-green-800',
                            'expired' => 'bg-red-100 text-red-800'
                        ];
                        $status_texts = [
                            'pending' => 'Menunggu Pembayaran',
                            'paid' => 'Menunggu Konfirmasi',
                            'confirmed' => 'Terkonfirmasi',
                            'expired' => 'Kadaluarsa'
                        ];
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($payment['nama']); ?></div>
                                    <div class="text-sm text-gray-500">
                                        ID: <?php echo $payment['unique_id']; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        WA: <?php echo htmlspecialchars($payment['no_wa']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <div class="font-bold text-lg text-gray-800">
                                    Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?>
                                </div>
                                <?php if ($payment['confirmed_at']): ?>
                                <div class="text-sm text-green-600">
                                    Dikonfirmasi: <?php echo date('d/m/Y', strtotime($payment['confirmed_at'])); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($payment['notes'])): ?>
                                <div class="text-sm text-gray-500 italic">
                                    "<?php echo htmlspecialchars(substr($payment['notes'], 0, 50)); ?>"
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="space-y-2">
                                <span class="px-3 py-1 text-xs rounded-full <?php echo $status_colors[$payment['status']]; ?>">
                                    <?php echo $status_texts[$payment['status']]; ?>
                                </span>
                                
                                <!-- Action buttons based on status -->
                                <?php if ($payment['status'] == 'paid'): ?>
                                <form method="POST" action="" class="space-y-2">
                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                    <button type="submit" 
                                            name="confirm_payment"
                                            value="confirmed"
                                            class="w-full px-3 py-1 bg-green-100 text-green-700 rounded text-sm hover:bg-green-200">
                                        <i class="fas fa-check mr-1"></i>Konfirmasi
                                    </button>
                                    <button type="submit" 
                                            name="confirm_payment"
                                            value="expired"
                                            class="w-full px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200">
                                        <i class="fas fa-times mr-1"></i>Tolak
                                    </button>
                                </form>
                                <?php elseif ($payment['status'] == 'pending'): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                    <button type="submit" 
                                            name="send_reminder"
                                            class="w-full px-3 py-1 bg-yellow-100 text-yellow-700 rounded text-sm hover:bg-yellow-200">
                                        <i class="fab fa-whatsapp mr-1"></i>Kirim Reminder
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="space-y-2">
                                <div>
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>Kuis: <?php echo $payment['kuis_score'] ?? 0; ?>%</span>
                                    </div>
                                    <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="bg-blue-500 h-full rounded-full" style="width: <?php echo min($payment['kuis_score'] ?? 0, 100); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="text-xs text-gray-600 mb-1">
                                        Status CV: 
                                        <span class="<?php 
                                            $cv_status = $payment['status_cv'] ?? 'belum';
                                            $cv_colors = [
                                                'belum' => 'text-gray-600',
                                                'proses' => 'text-yellow-600',
                                                'lolos' => 'text-green-600',
                                                'tidak_lolos' => 'text-red-600'
                                            ];
                                            echo $cv_colors[$cv_status];
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $cv_status)); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="flex flex-col space-y-2">
                                <!-- Edit Button -->
                                <button type="button" 
                                        class="edit-payment-btn px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm hover:bg-blue-200"
                                        data-id="<?php echo $payment['id']; ?>"
                                        data-user="<?php echo htmlspecialchars($payment['nama']); ?>"
                                        data-amount="<?php echo $payment['amount']; ?>"
                                        data-status="<?php echo $payment['status']; ?>"
                                        data-notes="<?php echo htmlspecialchars($payment['notes'] ?? ''); ?>">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                
                                <!-- WhatsApp Button -->
                                <a href="https://wa.me/<?php echo $payment['no_wa']; ?>?text=<?php echo urlencode("Assalamu'alaikum {$payment['nama']} (ID: {$payment['unique_id']}), mengenai pembayaran taaruf Anda."); ?>" 
                                   target="_blank"
                                   class="px-3 py-1 bg-green-100 text-green-700 rounded text-sm hover:bg-green-200 text-center">
                                   <i class="fab fa-whatsapp mr-1"></i>Chat WA
                                </a>
                                
                                <!-- Delete Button -->
                                <form method="POST" action="" class="delete-form">
                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                    <button type="submit" 
                                            name="delete_payment"
                                            class="w-full px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200">
                                        <i class="fas fa-trash mr-1"></i>Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($total_payments == 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-money-bill-wave text-3xl mb-3 text-gray-300"></i>
                            <p>Tidak ada data pembayaran</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Total: Rp <?php echo number_format($total_amount, 0, ',', '.'); ?>
            </div>
            <div class="flex space-x-2">
                <button class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="px-3 py-1 bg-purple-600 text-white rounded text-sm">1</button>
                <button class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-600 hover:bg-gray-50">2</button>
                <button class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Edit Pembayaran</h3>
            <button type="button" 
                    onclick="document.getElementById('editModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" id="editForm">
            <input type="hidden" name="payment_id" id="editPaymentId">
            
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">User</label>
                    <div id="editUserName" class="px-4 py-2 bg-gray-50 rounded-lg"></div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Jumlah</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                        <input type="number" 
                               id="editAmount"
                               name="amount" 
                               required
                               min="0"
                               step="50000"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Status</label>
                    <select id="editStatus"
                            name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Catatan</label>
                    <textarea id="editNotes"
                              name="notes" 
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button type="button" 
                        onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" 
                        name="update_payment"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Edit payment modal
document.querySelectorAll('.edit-payment-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const user = this.getAttribute('data-user');
        const amount = this.getAttribute('data-amount');
        const status = this.getAttribute('data-status');
        const notes = this.getAttribute('data-notes');
        
        // Set form values
        document.getElementById('editPaymentId').value = id;
        document.getElementById('editUserName').textContent = user;
        document.getElementById('editAmount').value = amount;
        document.getElementById('editStatus').value = status;
        document.getElementById('editNotes').value = notes;
        
        // Show modal
        document.getElementById('editModal').classList.remove('hidden');
    });
});

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Print functionality
document.getElementById('printBtn').addEventListener('click', function() {
    const printContent = `
        <html>
        <head>
            <title>Laporan Pembayaran Taaruf Islami</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f4f4f4; }
                .total { font-weight: bold; font-size: 1.2em; }
            </style>
        </head>
        <body>
            <h1>Laporan Pembayaran Taaruf Islami</h1>
            <p>Tanggal: <?php echo date('d F Y H:i'); ?></p>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>ID</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $payments_result->data_seek(0);
                    while($payment = $payments_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['nama']); ?></td>
                        <td><?php echo $payment['unique_id']; ?></td>
                        <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                        <td><?php 
                            $status_texts = [
                                'pending' => 'Menunggu Pembayaran',
                                'paid' => 'Menunggu Konfirmasi',
                                'confirmed' => 'Terkonfirmasi',
                                'expired' => 'Kadaluarsa'
                            ];
                            echo $status_texts[$payment['status']];
                        ?></td>
                        <td><?php echo date('d/m/Y', strtotime($payment['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="total" style="margin-top: 20px;">
                Total Terkonfirmasi: Rp <?php echo number_format($total_amount, 0, ',', '.'); ?>
            </div>
        </body>
        </html>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
});

// Auto-refresh pending payments every 30 seconds
setTimeout(() => {
    if (window.location.href.indexOf('status=pending') !== -1 || window.location.href.indexOf('status=paid') !== -1) {
        window.location.reload();
    }
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>