<?php
session_start();
require_once '../config/database.php';

// Initialize database
$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $nama = $db->escapeString($_POST['nama']);
    $email = $db->escapeString($_POST['email']);
    $password = $_POST['password'];
    $jenis_kelamin = $db->escapeString($_POST['jenis_kelamin']);
    $no_wa = $db->escapeString($_POST['no_wa']);
    
    // Validation
    if (empty($nama) || empty($email) || empty($password) || empty($jenis_kelamin) || empty($no_wa)) {
        $error = "Semua field wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif (!preg_match('/^[0-9]+$/', $no_wa)) {
        $error = "Nomor WhatsApp harus berupa angka!";
    } else {
        // Check if email already exists
        $check_email = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check_email->num_rows > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // Check if WhatsApp number exists
            $check_wa = $conn->query("SELECT id FROM users WHERE no_wa = '$no_wa'");
            if ($check_wa->num_rows > 0) {
                $error = "Nomor WhatsApp sudah terdaftar!";
            } else {
                // Generate unique ID
                $unique_id = 'TAR' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $sql = "INSERT INTO users (unique_id, nama, email, password, jenis_kelamin, no_wa, status) 
                        VALUES ('$unique_id', '$nama', '$email', '$hashed_password', '$jenis_kelamin', '$no_wa', 'pending')";
                
                if ($conn->query($sql)) {
                    $user_id = $conn->insert_id;
                    
                    // Insert into user_scores
                    $conn->query("INSERT INTO user_scores (user_id) VALUES ($user_id)");
                    
                    // Create CV data record
                    $conn->query("INSERT INTO cv_data (user_id) VALUES ($user_id)");
                    
                    // Create payment record
                    $conn->query("INSERT INTO payments (user_id, amount, status) VALUES ($user_id, 500000, 'pending')");
                    
                    $success = "Pendaftaran berhasil! ID Anda: $unique_id";
                    
                    // Set session for auto login
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['unique_id'] = $unique_id;
                    $_SESSION['nama'] = $nama;
                    $_SESSION['is_admin'] = false;
                    
                    // Redirect to dashboard
                    header("Location: ../dashboard/index.php");
                    exit();
                } else {
                    $error = "Terjadi kesalahan: " . $conn->error;
                }
            }
        }
    }
}

// If there's an error or we're not redirecting, show the form with message
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Taaruf Islami</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fdf2f8 0%, #f0f9ff 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-pink-600 to-purple-600 p-6 text-white text-center">
            <div class="text-2xl font-bold mb-2">
                <i class="fas fa-heart mr-2"></i>
                Registrasi
            </div>
            <p class="opacity-90">Bergabung dengan komunitas taaruf Islami</p>
        </div>
        
        <!-- Messages -->
        <div class="p-4">
            <?php if($error): ?>
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
            <?php elseif($success): ?>
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
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nama">Nama Lengkap *</label>
                        <input type="text" 
                               id="nama" 
                               name="nama"
                               required
                               value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email *</label>
                        <input type="email" 
                               id="email" 
                               name="email"
                               required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password *</label>
                        <input type="password" 
                               id="password" 
                               name="password"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <p class="text-gray-500 text-xs mt-1">Minimal 6 karakter</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="jenis_kelamin">Jenis Kelamin *</label>
                        <select id="jenis_kelamin" 
                                name="jenis_kelamin"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="no_wa">Nomor WhatsApp *</label>
                        <input type="tel" 
                               id="no_wa" 
                               name="no_wa"
                               required
                               placeholder="628123456789"
                               value="<?php echo isset($_POST['no_wa']) ? htmlspecialchars($_POST['no_wa']) : ''; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <p class="text-gray-500 text-xs mt-1">Contoh: 628123456789 (tanpa + dan spasi)</p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-pink-600 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                    </button>
                </div>
            </form>
            
            <!-- Links -->
            <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                <p class="text-gray-600 text-sm">
                    Sudah punya akun? 
                    <a href="login.php" class="text-pink-600 hover:underline font-semibold">Login disini</a>
                </p>
                <p class="text-gray-600 text-sm mt-2">
                    <a href="../index.php" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali ke beranda
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>