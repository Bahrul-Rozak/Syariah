<?php
include '../includes/header.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama = $db->escapeString($_POST['nama']);
        $email = $db->escapeString($_POST['email']);
        $no_wa = $db->escapeString($_POST['no_wa']);
        
        // Check if email already exists (except current user)
        $check_email = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $user_id");
        if ($check_email->num_rows > 0) {
            $error = "Email sudah digunakan oleh user lain!";
        } else {
            $update_query = "UPDATE users SET nama = '$nama', email = '$email', no_wa = '$no_wa' WHERE id = $user_id";
            if ($conn->query($update_query)) {
                $success = "Profil berhasil diperbarui!";
                $_SESSION['nama'] = $nama;
            } else {
                $error = "Terjadi kesalahan: " . $conn->error;
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current password
        $user_query = $conn->query("SELECT password FROM users WHERE id = $user_id");
        $user_data = $user_query->fetch_assoc();
        
        if (!password_verify($current_password, $user_data['password'])) {
            $error = "Password saat ini salah!";
        } elseif ($new_password != $confirm_password) {
            $error = "Password baru tidak cocok!";
        } elseif (strlen($new_password) < 6) {
            $error = "Password minimal 6 karakter!";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            
            if ($conn->query($update_query)) {
                $success = "Password berhasil diubah!";
            } else {
                $error = "Terjadi kesalahan: " . $conn->error;
            }
        }
    }
}

// Get updated user data
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();
?>

<div class="max-w-4xl mx-auto">
    <!-- Profile Header -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center mb-6">
            <div class="w-20 h-20 bg-pink-100 rounded-full flex items-center justify-center mr-6">
                <?php if ($user['foto_profile']): ?>
                    <img src="../assets/images/<?php echo htmlspecialchars($user['foto_profile']); ?>" 
                         alt="Profile" 
                         class="w-full h-full rounded-full object-cover">
                <?php else: ?>
                    <i class="fas fa-user text-pink-600 text-3xl"></i>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user['nama']); ?></h1>
                <p class="text-gray-600">ID: <?php echo $user['unique_id']; ?></p>
                <p class="text-gray-600">Bergabung: <?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
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
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
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
    </div>
    
    <!-- Profile Update Form -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Informasi Pribadi</h2>
        
        <form method="POST" action="">
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="nama">Nama Lengkap *</label>
                    <input type="text" 
                           id="nama" 
                           name="nama"
                           required
                           value="<?php echo htmlspecialchars($user['nama']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="email">Email *</label>
                    <input type="email" 
                           id="email" 
                           name="email"
                           required
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="jenis_kelamin">Jenis Kelamin</label>
                    <input type="text" 
                           id="jenis_kelamin" 
                           value="<?php echo htmlspecialchars($user['jenis_kelamin']); ?>"
                           disabled
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                    <p class="text-gray-500 text-sm mt-1">Tidak dapat diubah</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="no_wa">Nomor WhatsApp *</label>
                    <input type="tel" 
                           id="no_wa" 
                           name="no_wa"
                           required
                           value="<?php echo htmlspecialchars($user['no_wa']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
            </div>
            
            <div class="pt-6 border-t border-gray-200">
                <button type="submit" 
                        name="update_profile"
                        class="px-6 py-3 bg-gradient-to-r from-pink-600 to-purple-600 text-white rounded-lg hover:opacity-90 font-medium">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
    
    <!-- Password Change Form -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Ubah Password</h2>
        
        <form method="POST" action="">
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="current_password">Password Saat Ini *</label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="new_password">Password Baru *</label>
                    <input type="password" 
                           id="new_password" 
                           name="new_password"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <p class="text-gray-500 text-sm mt-1">Minimal 6 karakter</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="confirm_password">Konfirmasi Password Baru *</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
            </div>
            
            <div class="pt-6 border-t border-gray-200">
                <button type="submit" 
                        name="update_password"
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:opacity-90 font-medium">
                    <i class="fas fa-key mr-2"></i>Ubah Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>