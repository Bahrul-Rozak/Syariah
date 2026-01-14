<?php
include 'includes/header.php';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_settings'])) {
        $min_quiz_score = intval($_POST['min_quiz_score']);
        $payment_info = $conn->real_escape_string($_POST['payment_info']);
        $system_message = $conn->real_escape_string($_POST['system_message']);
        
        $conn->query("UPDATE admin_settings SET 
                     min_quiz_score = $min_quiz_score,
                     payment_info = '$payment_info',
                     system_message = '$system_message'
                     WHERE id = 1");
        
        $success = "Pengaturan sistem berhasil diperbarui!";
    } elseif (isset($_POST['create_admin'])) {
        $nama = $conn->real_escape_string($_POST['nama']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        
        // Check if email exists
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            $unique_id = 'ADM' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $conn->query("INSERT INTO users (unique_id, nama, email, password, is_admin, status) 
                         VALUES ('$unique_id', '$nama', '$email', '$hashed_password', 1, 'active')");
            $success = "Admin berhasil ditambahkan! ID: $unique_id";
        }
    }
}

// Get current settings
$settings = $conn->query("SELECT * FROM admin_settings LIMIT 1")->fetch_assoc();
$min_quiz_score = $settings['min_quiz_score'] ?? 80;
$payment_info = $settings['payment_info'] ?? '';
$system_message = $settings['system_message'] ?? '';

// Get all admins
$admins = $conn->query("SELECT * FROM users WHERE is_admin = 1 ORDER BY created_at DESC");
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">System Settings</h1>
        <p class="text-gray-600">Konfigurasi sistem dan pengaturan umum</p>
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
    <?php elseif (isset($error)): ?>
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

    <!-- Settings Tabs -->
    <div class="bg-white rounded-xl shadow-md mb-8">
        <!-- Tab Headers -->
        <div class="border-b border-gray-200">
            <nav class="flex">
                <button class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-purple-600 text-purple-600" data-tab="general">
                    <i class="fas fa-cog mr-2"></i>General Settings
                </button>
                <button class="tab-btn px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="admins">
                    <i class="fas fa-user-shield mr-2"></i>Admin Management
                </button>
                <button class="tab-btn px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="backup">
                    <i class="fas fa-database mr-2"></i>Backup & Restore
                </button>
                <button class="tab-btn px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="logs">
                    <i class="fas fa-history mr-2"></i>System Logs
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-6">
            <!-- General Settings Tab -->
            <div id="general-tab" class="tab-content">
                <form method="POST" action="">
                    <div class="space-y-6">
                        <!-- Quiz Settings -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Pengaturan Kuis</h3>
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Nilai Minimal Lulus Kuis</label>
                                    <div class="flex items-center">
                                        <input type="number" 
                                               name="min_quiz_score" 
                                               min="0" 
                                               max="100"
                                               value="<?php echo $min_quiz_score; ?>"
                                               class="w-32 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <span class="ml-2 text-gray-600">% (0-100)</span>
                                    </div>
                                    <p class="text-gray-500 text-sm mt-1">Skor minimal untuk bisa lanjut ke CV Builder</p>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Informasi Pembayaran</h3>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Penjelasan Biaya</label>
                                <textarea name="payment_info" 
                                          rows="4"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?php echo htmlspecialchars($payment_info); ?></textarea>
                                <p class="text-gray-500 text-sm mt-1">Teks yang ditampilkan di bagian "Mengapa Berbayar?"</p>
                            </div>
                        </div>

                        <!-- System Message -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Pesan Sistem</h3>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Pesan Selamat Datang</label>
                                <textarea name="system_message" 
                                          rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?php echo htmlspecialchars($system_message); ?></textarea>
                                <p class="text-gray-500 text-sm mt-1">Pesan yang ditampilkan di dashboard user</p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6 border-t border-gray-200">
                            <button type="submit" 
                                    name="update_settings"
                                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:opacity-90 font-medium">
                                <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Admin Management Tab -->
            <div id="admins-tab" class="tab-content hidden">
                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Add Admin Form -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Tambah Admin Baru</h3>
                        <form method="POST" action="">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Nama Lengkap</label>
                                    <input type="text" 
                                           name="nama" 
                                           required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Email</label>
                                    <input type="email" 
                                           name="email" 
                                           required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                                    <input type="password" 
                                           name="password" 
                                           required
                                           minlength="6"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <p class="text-gray-500 text-sm mt-1">Minimal 6 karakter</p>
                                </div>
                                
                                <div>
                                    <button type="submit" 
                                            name="create_admin"
                                            class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">
                                        <i class="fas fa-user-plus mr-2"></i>Tambah Admin
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Admin List -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Daftar Admin</h3>
                        <div class="space-y-4">
                            <?php while($admin = $admins->fetch_assoc()): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($admin['nama']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $admin['email']; ?></div>
                                    </div>
                                    <div>
                                        <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">
                                            <?php echo $admin['unique_id']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600">
                                    Bergabung: <?php echo date('d/m/Y', strtotime($admin['created_at'])); ?>
                                    <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                    <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                        <i class="fas fa-user mr-1"></i>Anda
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Tab -->
            <div id="backup-tab" class="tab-content hidden">
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-database text-blue-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Backup & Restore</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        Backup data penting sistem secara berkala. File backup akan disimpan dalam format SQL.
                    </p>
                    
                    <div class="flex flex-col md:flex-row justify-center gap-4">
                        <a href="backup.php?action=download" 
                           class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                           <i class="fas fa-download mr-2"></i>Download Backup
                        </a>
                        
                        <button class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                            <i class="fas fa-upload mr-2"></i>Restore Backup
                        </button>
                        
                        <button class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                            <i class="fas fa-trash mr-2"></i>Hapus Data Lama
                        </button>
                    </div>
                    
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Backup terakhir: <?php echo date('d F Y H:i'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Tab -->
            <div id="logs-tab" class="tab-content hidden">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-800">System Logs</h3>
                        <button class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-trash mr-1"></i>Clear Logs
                        </button>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <div class="space-y-4">
                            <?php for($i = 1; $i <= 10; $i++): ?>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center 
                                        <?php echo $i % 3 == 0 ? 'bg-green-100 text-green-600' : 
                                               ($i % 3 == 1 ? 'bg-blue-100 text-blue-600' : 
                                               'bg-yellow-100 text-yellow-600'); ?>">
                                        <i class="fas 
                                            <?php echo $i % 3 == 0 ? 'fa-check-circle' : 
                                                   ($i % 3 == 1 ? 'fa-info-circle' : 
                                                   'fa-exclamation-triangle'); ?> text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-800 text-sm">
                                        <?php 
                                        $messages = [
                                            'User baru registrasi: Ahmad (ID: TAR2024011001)',
                                            'CV berhasil disubmit oleh Siti (ID: TAR2024011002)',
                                            'Matching berhasil dibuat untuk Ahmad dan Siti',
                                            'Pembayaran dikonfirmasi untuk user ID: TAR2024011001',
                                            'Backup otomatis berhasil dilakukan',
                                            'Admin login dari IP: 192.168.1.1',
                                            'Konten landing page diperbarui',
                                            'Soal kuis baru ditambahkan',
                                            'Password user direset oleh admin',
                                            'System settings diperbarui'
                                        ];
                                        echo $messages[$i-1];
                                        ?>
                                    </div>
                                    <div class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime("-{$i} hours")); ?></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Sistem</h3>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">v1.0.0</div>
                <div class="text-gray-600">Versi Sistem</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo date('Y'); ?></div>
                <div class="text-gray-600">Tahun Rilis</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">PHP <?php echo phpversion(); ?></div>
                <div class="text-gray-600">PHP Version</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">MySQLi</div>
                <div class="text-gray-600">Database</div>
            </div>
        </div>
        
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="text-sm text-gray-600">
                <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                Sistem aman dan terenkripsi. Selalu lakukan backup berkala.
            </div>
        </div>
    </div>
</div>

<script>
// Tab functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(t => {
            t.classList.remove('border-purple-600', 'text-purple-600');
            t.classList.add('text-gray-500');
        });
        this.classList.add('border-purple-600', 'text-purple-600');
        this.classList.remove('text-gray-500');
        
        // Show selected tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        document.getElementById(tab + '-tab').classList.remove('hidden');
    });
});

// Initialize first tab
document.querySelector('.tab-btn').click();
</script>

<?php include 'includes/footer.php'; ?>