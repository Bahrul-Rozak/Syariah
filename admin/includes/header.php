<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Taaruf Islami</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .sidebar {
            min-height: calc(100vh - 64px);
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
        }
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Navigation -->
    <nav class="bg-gradient-to-r from-purple-700 to-purple-900 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo & Title -->
                <div class="flex items-center">
                    <button id="sidebarToggle" class="mr-4 text-xl md:hidden">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="text-xl font-bold">
                        <i class="fas fa-heart mr-2"></i>
                        Taaruf Islami
                    </div>
                    <span class="ml-4 text-sm opacity-75">Admin Panel</span>
                </div>
                
                <!-- Admin Menu -->
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="../index.php" class="text-sm opacity-75 hover:opacity-100">
                            <i class="fas fa-home mr-1"></i>Beranda User
                        </a>
                        <span class="opacity-50">|</span>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 focus:outline-none">
                                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <span class="text-sm">Notifikasi</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="relative group">
                        <button class="flex items-center space-x-2 focus:outline-none">
                            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <span class="hidden md:inline"><?php echo $_SESSION['nama']; ?></span>
                            <i class="fas fa-chevron-down opacity-75"></i>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden group-hover:block">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-purple-50">
                                <i class="fas fa-user-cog mr-2"></i>Profil Admin
                            </a>
                            <a href="settings.php" class="block px-4 py-2 text-gray-700 hover:bg-purple-50">
                                <i class="fas fa-cog mr-2"></i>Pengaturan
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="../../auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar text-white w-64 hidden md:block">
            <!-- User Profile -->
            <div class="p-6 border-b border-white/10">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user-shield text-xl"></i>
                    </div>
                    <div>
                        <div class="font-bold"><?php echo $_SESSION['nama']; ?></div>
                        <div class="text-sm opacity-75">Administrator</div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="p-4">
                <div class="mb-6">
                    <h3 class="text-xs uppercase tracking-wider opacity-50 mb-2">Utama</h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="index.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt w-6 mr-3"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="text-xs uppercase tracking-wider opacity-50 mb-2">Kelola Data</h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="users.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                                <i class="fas fa-users w-6 mr-3"></i>
                                <span>User Management</span>
                            </a>
                        </li>
                        <li>
                            <a href="matches.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'matches.php' ? 'active' : ''; ?>">
                                <i class="fas fa-heart w-6 mr-3"></i>
                                <span>Matching System</span>
                            </a>
                        </li>
                        <li>
                            <a href="payments.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>">
                                <i class="fas fa-money-bill-wave w-6 mr-3"></i>
                                <span>Pembayaran</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="text-xs uppercase tracking-wider opacity-50 mb-2">Konten & Sistem</h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="content.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'content.php' ? 'active' : ''; ?>">
                                <i class="fas fa-edit w-6 mr-3"></i>
                                <span>Landing Page</span>
                            </a>
                        </li>
                        <li>
                            <a href="quiz.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'quiz.php' ? 'active' : ''; ?>">
                                <i class="fas fa-graduation-cap w-6 mr-3"></i>
                                <span>Kuis Pernikahan</span>
                            </a>
                        </li>
                        <li>
                            <a href="ustadz.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'ustadz.php' ? 'active' : ''; ?>">
                                <i class="fas fa-user-tie w-6 mr-3"></i>
                                <span>Data Ustadz</span>
                            </a>
                        </li>
                        <li>
                            <a href="testimonials.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'testimonials.php' ? 'active' : ''; ?>">
                                <i class="fas fa-comment-alt w-6 mr-3"></i>
                                <span>Testimonial</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="text-xs uppercase tracking-wider opacity-50 mb-2">Pengaturan</h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="settings.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                                <i class="fas fa-cog w-6 mr-3"></i>
                                <span>System Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="backup.php" class="nav-item flex items-center p-3 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>">
                                <i class="fas fa-database w-6 mr-3"></i>
                                <span>Backup Data</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 md:p-6">