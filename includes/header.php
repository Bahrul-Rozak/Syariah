<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get user data
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

$user_query = "SELECT u.*, us.kuis_score, us.status_cv, us.cv_score 
               FROM users u 
               LEFT JOIN user_scores us ON u.id = us.user_id 
               WHERE u.id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Check if CV is submitted
$cv_query = "SELECT is_submitted FROM cv_data WHERE user_id = $user_id";
$cv_result = $conn->query($cv_query);
$cv_data = $cv_result->fetch_assoc();
$cv_submitted = $cv_data ? $cv_data['is_submitted'] : false;

// Check quiz status
$quiz_query = "SELECT passed FROM quiz_results WHERE user_id = $user_id ORDER BY attempt_date DESC LIMIT 1";
$quiz_result = $conn->query($quiz_query);
$quiz_data = $quiz_result->fetch_assoc();
$quiz_passed = $quiz_data ? $quiz_data['passed'] : false;

// Get pending matches count
$matches_query = "SELECT COUNT(*) as count FROM matches WHERE user_id = $user_id AND status = 'pending'";
$matches_result = $conn->query($matches_query);
$matches_data = $matches_result->fetch_assoc();
$pending_matches = $matches_data['count'] ?? 0;

// Get payment status
$payment_query = "SELECT status FROM payments WHERE user_id = $user_id ORDER BY id DESC LIMIT 1";
$payment_result = $conn->query($payment_query);
$payment_data = $payment_result->fetch_assoc();
$payment_status = $payment_data ? $payment_data['status'] : 'pending';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Taaruf Islami</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .font-arabic {
            font-family: 'Amiri', serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #fdf2f8 0%, #f0f9ff 100%);
        }
        .sidebar {
            min-height: calc(100vh - 64px);
        }
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
        }
        .step-active {
            background-color: #ec4899;
            color: white;
        }
        .step-completed {
            background-color: #10b981;
            color: white;
        }
        .step-pending {
            background-color: #e5e7eb;
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="text-xl font-bold text-pink-600">
                        <i class="fas fa-heart mr-2"></i>
                        <span class="font-arabic">تعارف</span>
                        <span>Islami</span>
                    </div>
                    <span class="ml-4 text-sm text-gray-500">| Dashboard Peserta</span>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <button class="flex items-center space-x-2 focus:outline-none">
                            <div class="w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-pink-600"></i>
                            </div>
                            <span class="hidden md:inline font-medium"><?php echo htmlspecialchars($user['nama']); ?></span>
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden group-hover:block">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50">
                                <i class="fas fa-user-circle mr-2"></i>Profil
                            </a>
                            <a href="change-password.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50">
                                <i class="fas fa-key mr-2"></i>Ubah Password
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
        <!-- Welcome Message -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                Assalamu'alaikum, <span class="text-pink-600"><?php echo htmlspecialchars($user['nama']); ?></span>
            </h1>
            <p class="text-gray-600">ID Anda: <span class="font-mono bg-gray-100 px-2 py-1 rounded"><?php echo $user['unique_id']; ?></span></p>
        </div>

        <!-- Progress Steps -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Proses Taaruf Anda</h2>
            
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <?php
                $steps = [
                    ['icon' => 'fa-graduation-cap', 'title' => 'Kuis Pernikahan', 'completed' => $quiz_passed],
                    ['icon' => 'fa-file-alt', 'title' => 'Buat CV Taaruf', 'completed' => $cv_submitted],
                    ['icon' => 'fa-handshake', 'title' => 'Matching', 'completed' => $pending_matches > 0],
                    ['icon' => 'fa-heart', 'title' => 'Pertemuan', 'completed' => $payment_status == 'confirmed'],
                ];
                
                foreach ($steps as $index => $step):
                    $step_number = $index + 1;
                    $step_class = '';
                    
                    if ($step['completed']) {
                        $step_class = 'step-completed';
                    } elseif ($step_number == 1 && !$quiz_passed) {
                        $step_class = 'step-active';
                    } elseif ($step_number == 2 && $quiz_passed && !$cv_submitted) {
                        $step_class = 'step-active';
                    } else {
                        $step_class = 'step-pending';
                    }
                ?>
                <div class="flex items-center mb-4 md:mb-0">
                    <div class="step-circle <?php echo $step_class; ?>">
                        <?php if ($step['completed']): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            <?php echo $step_number; ?>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <div class="font-medium text-gray-800"><?php echo $step['title']; ?></div>
                        <div class="text-sm text-gray-500">
                            <?php if ($step['completed']): ?>
                                <span class="text-green-600">Selesai</span>
                            <?php elseif ($step_class == 'step-active'): ?>
                                <span class="text-pink-600">Sedang berjalan</span>
                            <?php else: ?>
                                Menunggu
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($index < count($steps) - 1): ?>
                        <div class="hidden md:block mx-6">
                            <i class="fas fa-chevron-right text-gray-300"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Progress Bar -->
            <div class="mt-4">
                <?php
                $total_steps = count($steps);
                $completed_steps = array_filter($steps, function($step) {
                    return $step['completed'];
                });
                $progress = count($completed_steps) / $total_steps * 100;
                ?>
                <div class="flex justify-between text-sm text-gray-600 mb-1">
                    <span>Progress: <?php echo round($progress); ?>%</span>
                    <span><?php echo count($completed_steps); ?> dari <?php echo $total_steps; ?> langkah</span>
                </div>
                <div class="progress-bar bg-gray-200">
                    <div class="bg-pink-600 h-full rounded-full" style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>
        </div>