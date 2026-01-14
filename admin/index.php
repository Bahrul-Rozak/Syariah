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
$admin_id = $_SESSION['user_id'];

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Active users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active' AND is_admin = 0");
$stats['active_users'] = $result->fetch_assoc()['total'];

// Pending CV
$result = $conn->query("SELECT COUNT(*) as total FROM user_scores WHERE status_cv = 'proses'");
$stats['pending_cv'] = $result->fetch_assoc()['total'];

// Total matches
$result = $conn->query("SELECT COUNT(*) as total FROM matches");
$stats['total_matches'] = $result->fetch_assoc()['total'];

// Pending payments
$result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
$stats['pending_payments'] = $result->fetch_assoc()['total'];

// Recent users
$recent_users = $conn->query("
    SELECT u.*, us.status_cv 
    FROM users u 
    LEFT JOIN user_scores us ON u.id = us.user_id 
    WHERE u.is_admin = 0 
    ORDER BY u.created_at DESC 
    LIMIT 5
");

// Recent payments
$recent_payments = $conn->query("
    SELECT p.*, u.nama, u.unique_id 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");

// Quick stats by gender
$male_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE jenis_kelamin = 'Laki-laki' AND is_admin = 0")->fetch_assoc()['total'];
$female_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE jenis_kelamin = 'Perempuan' AND is_admin = 0")->fetch_assoc()['total'];

// Recent activity logs (simplified)
$activities = [
    ['icon' => 'fa-user-plus', 'color' => 'green', 'title' => 'User baru registrasi', 'time' => '5 menit lalu'],
    ['icon' => 'fa-file-alt', 'color' => 'blue', 'title' => 'CV baru disubmit', 'time' => '1 jam lalu'],
    ['icon' => 'fa-heart', 'color' => 'pink', 'title' => 'Match berhasil dibuat', 'time' => '2 jam lalu'],
    ['icon' => 'fa-money-bill', 'color' => 'green', 'title' => 'Pembayaran dikonfirmasi', 'time' => '5 jam lalu'],
    ['icon' => 'fa-question-circle', 'color' => 'orange', 'title' => 'Pertanyaan baru dari user', 'time' => '1 hari lalu'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Taaruf Islami</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .sidebar {
            min-height: calc(100vh - 64px);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .bg-gradient-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo & Title -->
                <div class="flex items-center">
                    <div class="text-xl font-bold text-purple-600">
                        <i class="fas fa-heart mr-2"></i>
                        Taaruf Islami
                    </div>
                    <span class="ml-4 text-sm text-gray-500">| Admin Dashboard</span>
                </div>
                
                <!-- Admin Menu -->
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <button class="flex items-center space-x-2 focus:outline-none">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-shield text-purple-600"></i>
                            </div>
                            <span class="hidden md:inline font-medium"><?php echo $_SESSION['nama']; ?></span>
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden group-hover:block">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-purple-50">
                                <i class="fas fa-user-cog mr-2"></i>Profil Admin
                            </a>
                            <a href="settings.php" class="block px-4 py-2 text-gray-700 hover:bg-purple-50">
                                <i class="fas fa-cog mr-2"></i>Pengaturan
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6">
        <!-- Welcome & Quick Stats -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                Selamat datang, <span class="text-purple-600"><?php echo $_SESSION['nama']; ?></span>
            </h1>
            <p class="text-gray-600">Hari ini: <?php echo date('l, d F Y'); ?></p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <a href="users.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                        <div class="text-gray-600">Total User</div>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-blue-600">
                    <i class="fas fa-arrow-right mr-1"></i>Kelola user
                </div>
            </a>
            
            <a href="matches.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total_matches']; ?></div>
                        <div class="text-gray-600">Total Matching</div>
                    </div>
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-heart text-pink-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-pink-600">
                    <i class="fas fa-arrow-right mr-1"></i>Kelola matching
                </div>
            </a>
            
            <a href="payments.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $stats['pending_payments']; ?></div>
                        <div class="text-gray-600">Pembayaran Pending</div>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-money-bill text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-green-600">
                    <i class="fas fa-arrow-right mr-1"></i>Konfirmasi pembayaran
                </div>
            </a>
            
            <a href="content.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $stats['pending_cv']; ?></div>
                        <div class="text-gray-600">CV Menunggu</div>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-alt text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-yellow-600">
                    <i class="fas fa-arrow-right mr-1"></i>Review CV
                </div>
            </a>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Charts & Stats -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Gender Distribution -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Distribusi Gender User</h2>
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
                            <span class="text-gray-600">Laki-laki: <?php echo $male_users; ?></span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-pink-500 rounded mr-2"></div>
                            <span class="text-gray-600">Perempuan: <?php echo $female_users; ?></span>
                        </div>
                    </div>
                    
                    <!-- Simple Bar Chart -->
                    <div class="h-48 flex items-end space-x-4">
                        <?php
                        $total_gender = $male_users + $female_users;
                        $male_percentage = $total_gender > 0 ? ($male_users / $total_gender * 100) : 0;
                        $female_percentage = $total_gender > 0 ? ($female_users / $total_gender * 100) : 0;
                        ?>
                        <div class="flex-1">
                            <div class="bg-blue-500 rounded-t-lg" style="height: <?php echo $male_percentage; ?>%"></div>
                            <div class="text-center mt-2 text-sm text-gray-600">Laki-laki</div>
                            <div class="text-center text-sm font-semibold"><?php echo round($male_percentage); ?>%</div>
                        </div>
                        <div class="flex-1">
                            <div class="bg-pink-500 rounded-t-lg" style="height: <?php echo $female_percentage; ?>%"></div>
                            <div class="text-center mt-2 text-sm text-gray-600">Perempuan</div>
                            <div class="text-center text-sm font-semibold"><?php echo round($female_percentage); ?>%</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-gray-800">User Terbaru</h2>
                        <a href="users.php" class="text-sm text-purple-600 hover:text-purple-800">
                            Lihat semua →
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-gray-500 text-sm border-b">
                                    <th class="pb-3">Nama</th>
                                    <th class="pb-3">ID</th>
                                    <th class="pb-3">Gender</th>
                                    <th class="pb-3">Status CV</th>
                                    <th class="pb-3">Bergabung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $recent_users->fetch_assoc()): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3">
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($user['nama']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td class="py-3">
                                        <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded"><?php echo $user['unique_id']; ?></span>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $user['jenis_kelamin'] == 'Laki-laki' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                            <?php echo $user['jenis_kelamin']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <?php
                                        $status_cv = $user['status_cv'] ?? 'belum';
                                        $status_colors = [
                                            'belum' => 'bg-gray-100 text-gray-800',
                                            'proses' => 'bg-yellow-100 text-yellow-800',
                                            'lolos' => 'bg-green-100 text-green-800',
                                            'tidak_lolos' => 'bg-red-100 text-red-800'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$status_cv]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $status_cv)); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-sm text-gray-600">
                                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Activity & Quick Actions -->
            <div class="space-y-8">
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-6">Aksi Cepat</h2>
                    
                    <div class="space-y-3">
                        <a href="matches.php?action=create" class="flex items-center p-3 bg-pink-50 rounded-lg hover:bg-pink-100 transition">
                            <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-heart text-pink-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">Buat Matching</div>
                                <div class="text-sm text-gray-600">Rekomendasikan calon pasangan</div>
                            </div>
                        </a>
                        
                        <a href="quiz.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-graduation-cap text-blue-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">Kelola Soal Kuis</div>
                                <div class="text-sm text-gray-600">Tambah/edit soal kuis</div>
                            </div>
                        </a>
                        
                        <a href="content.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-edit text-green-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">Edit Landing Page</div>
                                <div class="text-sm text-gray-600">Update konten website</div>
                            </div>
                        </a>
                        
                        <a href="payments.php" class="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-money-bill-wave text-yellow-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">Konfirmasi Pembayaran</div>
                                <div class="text-sm text-gray-600">Update status pembayaran</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-6">Aktivitas Terbaru</h2>
                    
                    <div class="space-y-4">
                        <?php foreach($activities as $activity): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo 'bg-' . $activity['color'] . '-100 text-' . $activity['color'] . '-600'; ?>">
                                    <i class="fas <?php echo $activity['icon']; ?> text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <div class="font-medium text-gray-800 text-sm"><?php echo $activity['title']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo $activity['time']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <a href="#" class="text-sm text-purple-600 hover:text-purple-800">
                            <i class="fas fa-history mr-1"></i>Lihat semua aktivitas
                        </a>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Status Sistem</h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Database</span>
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                <i class="fas fa-check-circle mr-1"></i>Online
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Server</span>
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                <i class="fas fa-check-circle mr-1"></i>Normal
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Backup Terakhir</span>
                            <span class="text-sm text-gray-500"><?php echo date('d/m/Y'); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Pengunjung</span>
                            <span class="text-sm font-semibold">1,234</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <a href="settings.php" class="text-sm text-purple-600 hover:text-purple-800">
                            <i class="fas fa-cog mr-1"></i>Pengaturan Sistem
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Info Bar -->
    <div class="bg-white border-t border-gray-200 py-4 mt-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-shield-alt text-purple-500 mr-1"></i>
                    Sistem Admin Taaruf Islami © <?php echo date('Y'); ?>
                </div>
                <div class="text-sm text-gray-600 mt-2 md:mt-0">
                    <span class="mr-4"><i class="fas fa-user mr-1"></i> <?php echo $stats['total_users']; ?> Users</span>
                    <span><i class="fas fa-heart mr-1"></i> <?php echo $stats['total_matches']; ?> Matches</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh stats every 30 seconds
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>