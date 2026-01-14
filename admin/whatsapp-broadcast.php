<?php
include 'includes/header.php';

// Handle broadcast
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_broadcast'])) {
    $message_type = $_POST['message_type'];
    $custom_message = $_POST['custom_message'] ?? '';
    $target_users = $_POST['target_users'] ?? 'all';
    
    // Get users based on filter
    $user_query = "SELECT id, nama, unique_id, no_wa FROM users WHERE is_admin = 0 AND status = 'active'";
    
    if ($target_users == 'pending_payment') {
        $user_query .= " AND id IN (SELECT user_id FROM payments WHERE status = 'pending')";
    } elseif ($target_users == 'unsubmitted_cv') {
        $user_query .= " AND id IN (SELECT user_id FROM user_scores WHERE status_cv = 'belum')";
    } elseif ($target_users == 'pending_matches') {
        $user_query .= " AND id IN (SELECT user_id FROM matches WHERE status = 'pending')";
    }
    
    $users_result = $conn->query($user_query);
    $total_users = $users_result->num_rows;
    
    // Generate messages
    $messages = [];
    while($user = $users_result->fetch_assoc()) {
        $phone = preg_replace('/[^0-9]/', '', $user['no_wa']);
        
        switch($message_type) {
            case 'payment_reminder':
                $message = "Assalamu'alaikum {$user['nama']} (ID: {$user['unique_id']}).\n\n";
                $message .= "Mengingatkan untuk melakukan pembayaran biaya taaruf.\n";
                $message .= "Silakan cek dashboard Anda untuk detail pembayaran.\n\n";
                $message .= "Terima kasih,\nTaaruf Islami";
                break;
                
            case 'cv_reminder':
                $message = "Assalamu'alaikum {$user['nama']} (ID: {$user['unique_id']}).\n\n";
                $message .= "Anda belum menyelesaikan CV Taaruf.\n";
                $message .= "Silakan login dan lengkapi CV untuk melanjutkan proses matching.\n\n";
                $message .= "Terima kasih,\nTaaruf Islami";
                break;
                
            case 'match_update':
                $message = "Assalamu'alaikum {$user['nama']} (ID: {$user['unique_id']}).\n\n";
                $message .= "Ada update untuk proses matching Anda.\n";
                $message .= "Silakan login ke dashboard untuk melihat detail.\n\n";
                $message .= "Terima kasih,\nTaaruf Islami";
                break;
                
            case 'custom':
                $message = $custom_message;
                $message = str_replace('{{nama}}', $user['nama'], $message);
                $message = str_replace('{{user_id}}', $user['unique_id'], $message);
                break;
                
            default:
                $message = "Assalamu'alaikum {$user['nama']},\n\nPesan dari Taaruf Islami.";
        }
        
        $messages[] = [
            'phone' => $phone,
            'message' => $message,
            'user' => $user['nama']
        ];
    }
    
    $_SESSION['broadcast_messages'] = $messages;
    $success = "Pesan berhasil dipersiapkan untuk {$total_users} user!";
}
?>
<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">WhatsApp Broadcast System</h1>
        <p class="text-gray-600">Kirim pesan broadcast ke user via WhatsApp</p>
    </div>

    <?php if (isset($success)): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700"><?php echo $success; ?></p>
                <?php if (isset($_SESSION['broadcast_messages'])): ?>
                <div class="mt-2">
                    <a href="whatsapp-broadcast-send.php" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                       <i class="fab fa-whatsapp mr-2"></i>Kirim Semua Pesan
                    </a>
                    <span class="ml-2 text-sm text-gray-600">
                        (Akan membuka <?php echo count($_SESSION['broadcast_messages']); ?> tab WhatsApp)
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Broadcast Form -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <form method="POST" action="">
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Jenis Pesan</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" 
                                   name="message_type" 
                                   value="payment_reminder" 
                                   checked
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-800">Reminder Pembayaran</span>
                                <p class="text-sm text-gray-600">Untuk user yang belum bayar</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" 
                                   name="message_type" 
                                   value="cv_reminder" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-800">Reminder CV</span>
                                <p class="text-sm text-gray-600">Untuk user yang belum lengkapi CV</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" 
                                   name="message_type" 
                                   value="match_update" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-800">Update Matching</span>
                                <p class="text-sm text-gray-600">Informasi update matching</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" 
                                   name="message_type" 
                                   value="custom" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-800">Pesan Kustom</span>
                                <p class="text-sm text-gray-600">Pesan khusus dari admin</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Custom Message -->
                <div id="customMessageSection" class="hidden">
                    <label class="block text-gray-700 font-medium mb-2">Pesan Kustom</label>
                    <textarea name="custom_message" 
                              rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Tulis pesan Anda di sini..."></textarea>
                    <div class="mt-2 text-sm text-gray-600">
                        <p>Variabel yang tersedia:</p>
                        <ul class="list-disc pl-5 mt-1 space-y-1">
                            <li><code>{{nama}}</code> - Nama user</li>
                            <li><code>{{user_id}}</code> - ID user</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Target User</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" 
                                   name="target_users" 
                                   value="all" 
                                   checked
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-800">Semua User Aktif</span>
                                <p class="text-sm text-gray-600">Kirim ke semua user aktif</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" 
                                   name="target_users" 
                                   value="pending_payment" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-800">Pending Pembayaran</span>
                                <p class="text-sm text-gray-600">User dengan pembayaran pending</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" 
                                   name="target_users" 
                                   value="unsubmitted_cv" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-800">Belum Submit CV</span>
                                <p class="text-sm text-gray-600">User yang belum submit CV</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" 
                                   name="target_users" 
                                   value="pending_matches" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-800">Pending Matching</span>
                                <p class="text-sm text-gray-600">User dengan matching pending</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- User Count Preview -->
                <div id="userCountPreview" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-users text-blue-500 mr-3"></i>
                        <div>
                            <p class="font-medium text-blue-800">Memuat jumlah user...</p>
                            <p class="text-sm text-blue-600">Pilih target untuk melihat estimasi</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-6 border-t border-gray-200">
                    <button type="submit" 
                            name="send_broadcast"
                            class="px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 text-white rounded-lg hover:opacity-90 font-medium">
                        <i class="fab fa-whatsapp mr-2"></i>Siapkan Pesan Broadcast
                    </button>
                    <p class="mt-2 text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Pesan akan dipersiapkan dan bisa dikirim via WhatsApp Web
                    </p>
                </div>
            </div>
        </form>
    </div>

    <!-- Recent Broadcasts -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Riwayat Broadcast</h3>
        
        <div class="space-y-4">
            <?php for($i = 1; $i <= 3; $i++): ?>
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-medium text-gray-800">Reminder Pembayaran</div>
                        <div class="text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime("-$i days")); ?></div>
                    </div>
                    <div class="flex items-center">
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                            <i class="fas fa-check mr-1"></i>50 terkirim
                        </span>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    Target: User dengan pembayaran pending
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
// Show/hide custom message section
document.querySelectorAll('input[name="message_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const customSection = document.getElementById('customMessageSection');
        if (this.value === 'custom') {
            customSection.classList.remove('hidden');
        } else {
            customSection.classList.add('hidden');
        }
    });
});

// Update user count preview
document.querySelectorAll('input[name="target_users"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const target = this.value;
        const preview = document.getElementById('userCountPreview');
        
        // Simulate API call to get user count
        preview.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-sync fa-spin text-blue-500 mr-3"></i>
                <div>
                    <p class="font-medium text-blue-800">Menghitung jumlah user...</p>
                </div>
            </div>
        `;
        
        // Simulate delay
        setTimeout(() => {
            const counts = {
                'all': <?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0 AND status = 'active'")->fetch_assoc()['count']; ?>,
                'pending_payment': <?php echo $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM payments WHERE status = 'pending'")->fetch_assoc()['count']; ?>,
                'unsubmitted_cv': <?php echo $conn->query("SELECT COUNT(*) as count FROM user_scores WHERE status_cv = 'belum'")->fetch_assoc()['count']; ?>,
                'pending_matches': <?php echo $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM matches WHERE status = 'pending'")->fetch_assoc()['count']; ?>
            };
            
            const count = counts[target] || 0;
            preview.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-users text-blue-500 mr-3"></i>
                    <div>
                        <p class="font-medium text-blue-800">${count} user akan menerima pesan</p>
                        <p class="text-sm text-blue-600">Target: ${this.parentElement.querySelector('.font-medium').textContent}</p>
                    </div>
                </div>
            `;
        }, 500);
    });
});

// Initialize with default values
document.querySelector('input[name="target_users"][value="all"]').dispatchEvent(new Event('change'));
</script>

<?php include 'includes/footer.php'; ?>