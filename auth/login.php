<?php
session_start();
require_once '../config/database.php';

// Initialize database
$db = new Database();
$conn = $db->getConnection();

$error = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin']) {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../dashboard/index.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = $db->escapeString($_POST['identifier']);
    $password = $_POST['password'];
    
    if (empty($identifier) || empty($password)) {
        $error = "Email/WhatsApp dan password wajib diisi!";
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
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['unique_id'] = $user['unique_id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Redirect based on user type
                if ($user['is_admin']) {
                    header("Location: ../admin/index.php");
                } else {
                    header("Location: ../dashboard/index.php");
                }
                exit();
            } else {
                $error = "Password salah!";
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
    <title>Login - Taaruf Islami</title>
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
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white text-center">
            <div class="text-2xl font-bold mb-2">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Login
            </div>
            <p class="opacity-90">Masuk ke akun Anda</p>
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
            
            <!-- Login Form -->
            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="identifier">Email atau WhatsApp *</label>
                        <input type="text" 
                               id="identifier" 
                               name="identifier"
                               required
                               value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="email@example.com atau 628123456789">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password *</label>
                        <input type="password" 
                               id="password" 
                               name="password"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <!-- Forgot Password Link -->
                <div class="mt-4 text-right">
                    <a href="forgot-password.php" class="text-sm text-blue-600 hover:underline">
                        <i class="fas fa-key mr-1"></i>Lupa Password?
                    </a>
                </div>
                
                <div class="mt-6">
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </div>
            </form>
            
            <!-- Links -->
            <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                <p class="text-gray-600 text-sm">
                    Belum punya akun? 
                    <a href="register.php" class="text-blue-600 hover:underline font-semibold">Daftar disini</a>
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