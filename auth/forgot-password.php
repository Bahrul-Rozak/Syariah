<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = $db->escapeString($_POST['identifier']);
    
    if (empty($identifier)) {
        $error = "Email atau WhatsApp wajib diisi!";
    } else {
        // Check if identifier is email or WhatsApp
        $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        
        if ($is_email) {
            $sql = "SELECT * FROM users WHERE email = '$identifier'";
        } else {
            $sql = "SELECT * FROM users WHERE no_wa = '$identifier'";
        }
        
        $result = $conn->query($sql);
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Generate random password
            $new_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = {$user['id']}";
            if ($conn->query($update_query)) {
                $success = "Password baru telah dibuat. Admin akan mengirimkannya via WhatsApp ke nomor Anda.";
                
                // In real implementation, you would send WhatsApp message here
                // For demo, we'll just show the password
                $success .= " Password baru: <strong>$new_password</strong> (Simpan password ini)";
            } else {
                $error = "Terjadi kesalahan: " . $conn->error;
            }
        } else {
            $error = "Email/WhatsApp tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Taaruf Islami</title>
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
        <div class="bg-gradient-to-r from-orange-600 to-yellow-600 p-6 text-white text-center">
            <div class="text-2xl font-bold mb-2">
                <i class="fas fa-key mr-2"></i>
                Lupa Password
            </div>
            <p class="opacity-90">Reset password via WhatsApp</p>
        </div>
        
        <!-- Messages -->
        <div class="p-6">
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
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?php echo $success; ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="login.php" class="inline-block w-full text-center bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login dengan Password Baru
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(!$success): ?>
            <!-- Forgot Password Form -->
            <form method="POST" action="">
                <div class="mb-6">
                    <p class="text-gray-600 mb-4">
                        Masukkan email atau nomor WhatsApp Anda. Admin akan membuat password baru dan mengirimkannya via WhatsApp.
                    </p>
                    
                    <label class="block text-gray-700 font-medium mb-2" for="identifier">
                        Email atau WhatsApp *
                    </label>
                    <input type="text" 
                           id="identifier" 
                           name="identifier"
                           required
                           value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                           placeholder="email@example.com atau 628123456789">
                </div>
                
                <div class="mb-6">
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-orange-600 to-yellow-600 text-white font-bold py-3 px-4 rounded-lg hover:opacity-90 transition shadow-lg">
                        <i class="fab fa-whatsapp mr-2"></i>Reset Password via WhatsApp
                    </button>
                </div>
            </form>
            <?php endif; ?>
            
            <!-- Links -->
            <div class="pt-6 border-t border-gray-200 text-center">
                <p class="text-gray-600 text-sm">
                    Ingat password? 
                    <a href="login.php" class="text-orange-600 hover:underline font-semibold">Login disini</a>
                </p>
                <p class="text-gray-600 text-sm mt-2">
                    Belum punya akun? 
                    <a href="register.php" class="text-blue-600 hover:underline">Daftar disini</a>
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