<?php
include 'includes/header.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        $conn->query("DELETE FROM users WHERE id = $user_id AND is_admin = 0");
        $success = "User berhasil dihapus!";
    } elseif (isset($_POST['update_status'])) {
        $user_id = intval($_POST['user_id']);
        $status = $conn->real_escape_string($_POST['status']);
        $conn->query("UPDATE users SET status = '$status' WHERE id = $user_id");
        $success = "Status user berhasil diperbarui!";
    } elseif (isset($_POST['reset_password'])) {
        $user_id = intval($_POST['user_id']);
        $new_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$hashed_password' WHERE id = $user_id");
        $success = "Password berhasil direset! Password baru: <strong>$new_password</strong>";
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$gender_filter = $_GET['gender'] ?? '';

// Build query
$query = "
    SELECT u.*, us.kuis_score, us.status_cv, us.cv_score, 
           (SELECT status FROM payments WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as payment_status
    FROM users u 
    LEFT JOIN user_scores us ON u.id = us.user_id 
    WHERE u.is_admin = 0
";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (u.nama LIKE '%$search%' OR u.email LIKE '%$search%' OR u.unique_id LIKE '%$search%' OR u.no_wa LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $status_filter = $conn->real_escape_string($status_filter);
    $query .= " AND u.status = '$status_filter'";
}

if (!empty($gender_filter)) {
    $gender_filter = $conn->real_escape_string($gender_filter);
    $query .= " AND u.jenis_kelamin = '$gender_filter'";
}

$query .= " ORDER BY u.created_at DESC";

// Get users
$users_result = $conn->query($query);
$total_users = $users_result->num_rows;

// Get stats for filters
$status_stats = $conn->query("SELECT status, COUNT(*) as count FROM users WHERE is_admin = 0 GROUP BY status");
$gender_stats = $conn->query("SELECT jenis_kelamin, COUNT(*) as count FROM users WHERE is_admin = 0 GROUP BY jenis_kelamin");
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">User Management</h1>
        <p class="text-gray-600">Kelola semua data peserta taaruf</p>
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

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo $total_users; ?></div>
                    <div class="text-gray-600">Total User</div>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND is_admin = 0")->fetch_assoc()['count']; ?>
                    </div>
                    <div class="text-gray-600">User Aktif</div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php echo $conn->query("SELECT COUNT(*) as count FROM user_scores WHERE status_cv = 'lolos'")->fetch_assoc()['count']; ?>
                    </div>
                    <div class="text-gray-600">CV Lolos</div>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php echo $conn->query("SELECT COUNT(*) as count FROM matches")->fetch_assoc()['count']; ?>
                    </div>
                    <div class="text-gray-600">Total Matching</div>
                </div>
                <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-heart text-pink-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <form method="GET" action="" class="space-y-4">
            <div class="grid md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Cari User</label>
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Nama, Email, ID, atau WhatsApp"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                    <select name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Semua Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Jenis Kelamin</label>
                    <select name="gender" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Semua Gender</option>
                        <option value="Laki-laki" <?php echo $gender_filter == 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo $gender_filter == 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <?php if (!empty($search) || !empty($status_filter) || !empty($gender_filter)): ?>
                    <a href="users.php" 
                       class="ml-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Reset
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Data User</h2>
                <p class="text-gray-600 text-sm">Menampilkan <?php echo $total_users; ?> user</p>
            </div>
            <div>
                <a href="users.php?export=csv" 
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                   <i class="fas fa-file-export mr-2"></i>Export CSV
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 text-sm">
                        <th class="px-6 py-3 font-medium">User</th>
                        <th class="px-6 py-3 font-medium">ID / Kontak</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px 6 py-3 font-medium">Progress</th>
                        <th class="px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while($user = $users_result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($user['nama']); ?></div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo $user['jenis_kelamin']; ?> • 
                                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <div>
                                    <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded"><?php echo $user['unique_id']; ?></span>
                                </div>
                                <div class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></div>
                                <div class="text-sm text-gray-600"><?php echo htmlspecialchars($user['no_wa']); ?></div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="space-y-2">
                                <!-- Account Status -->
                                <div>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="status" 
                                                onchange="this.form.submit()" 
                                                class="text-xs px-2 py-1 rounded border focus:ring-1 focus:ring-purple-500">
                                            <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="pending" <?php echo $user['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <input type="hidden" name="update_status">
                                    </form>
                                </div>
                                
                                <!-- CV Status -->
                                <div>
                                    <?php
                                    $cv_status = $user['status_cv'] ?? 'belum';
                                    $cv_colors = [
                                        'belum' => 'bg-gray-100 text-gray-800',
                                        'proses' => 'bg-yellow-100 text-yellow-800',
                                        'lolos' => 'bg-green-100 text-green-800',
                                        'tidak_lolos' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $cv_colors[$cv_status]; ?>">
                                        CV: <?php echo ucfirst(str_replace('_', ' ', $cv_status)); ?>
                                    </span>
                                </div>
                                
                                <!-- Payment Status -->
                                <div>
                                    <?php
                                    $payment_status = $user['payment_status'] ?? 'pending';
                                    $payment_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'paid' => 'bg-blue-100 text-blue-800',
                                        'confirmed' => 'bg-green-100 text-green-800',
                                        'expired' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $payment_colors[$payment_status]; ?>">
                                        Bayar: <?php echo ucfirst($payment_status); ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="space-y-2">
                                <div>
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>Kuis: <?php echo $user['kuis_score'] ?? 0; ?>%</span>
                                    </div>
                                    <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="bg-blue-500 h-full rounded-full" style="width: <?php echo min($user['kuis_score'] ?? 0, 100); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>CV: <?php echo $user['cv_score'] ?? 0; ?>%</span>
                                    </div>
                                    <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="bg-purple-500 h-full rounded-full" style="width: <?php echo min($user['cv_score'] ?? 0, 100); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="flex flex-col space-y-2">
                                <a href="user-detail.php?id=<?php echo $user['id']; ?>" 
                                   class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm hover:bg-blue-200 text-center">
                                   <i class="fas fa-eye mr-1"></i>Detail
                                </a>
                                
                                <form method="POST" action="" class="delete-form">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" 
                                            name="reset_password"
                                            class="w-full px-3 py-1 bg-yellow-100 text-yellow-700 rounded text-sm hover:bg-yellow-200">
                                        <i class="fas fa-key mr-1"></i>Reset Password
                                    </button>
                                </form>
                                
                                <form method="POST" action="" class="delete-form">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" 
                                            name="delete_user"
                                            class="w-full px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200">
                                        <i class="fas fa-trash mr-1"></i>Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($total_users == 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-3xl mb-3 text-gray-300"></i>
                            <p>Tidak ada user yang ditemukan</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Menampilkan <?php echo $total_users; ?> user
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

<?php include 'includes/footer.php'; ?>