<?php
include 'includes/header.php';

// Get admin ID from session
$admin_id = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_match'])) {
        $user1_id = intval($_POST['user1_id']);
        $user2_id = intval($_POST['user2_id']);
        
        // Check if match already exists
        $check = $conn->query("SELECT id FROM matches WHERE user_id = $user1_id AND matched_user_id = $user2_id");
        
        if ($check->num_rows == 0) {
            $insert = "INSERT INTO matches (user_id, matched_user_id, recommended_by_admin, status) 
                       VALUES ($user1_id, $user2_id, $admin_id, 'pending'),
                              ($user2_id, $user1_id, $admin_id, 'pending')";
            
            if ($conn->query($insert)) {
                $success = "Matching berhasil dibuat untuk kedua user!";
            } else {
                $error = "Error: " . $conn->error;
            }
        } else {
            $error = "Matching sudah ada sebelumnya!";
        }
    } elseif (isset($_POST['send_whatsapp'])) {
        $match_id = intval($_POST['match_id']);
        $phone = $conn->real_escape_string($_POST['phone']);
        
        // Update chat status
        $conn->query("UPDATE matches SET chat_wa_sent = 1 WHERE id = $match_id");
        
        $success = "Link WhatsApp berhasil dibuat untuk nomor: $phone";
    } elseif (isset($_POST['delete_match'])) {
        $match_id = intval($_POST['match_id']);
        $conn->query("DELETE FROM matches WHERE id = $match_id");
        $success = "Matching berhasil dihapus!";
    }
}

// Get all matches - FIXED SQL QUERY
$matches_query = "
    SELECT m.*, 
           u1.nama as user1_nama, u1.unique_id as user1_id, u1.jenis_kelamin as user1_gender,
           u2.nama as user2_nama, u2.unique_id as user2_id, u2.jenis_kelamin as user2_gender
    FROM matches m
    JOIN users u1 ON m.user_id = u1.id
    JOIN users u2 ON m.matched_user_id = u2.id
    WHERE m.recommended_by_admin = $admin_id
    ORDER BY m.created_at DESC
";
$matches_result = $conn->query($matches_query);
$total_matches = $matches_result->num_rows;

// Get users for matching (only those with submitted CV)
$users_query = "
    SELECT u.id, u.nama, u.unique_id, u.jenis_kelamin, u.no_wa, 
           cv.tempat_tinggal, cv.profesi, cv.pendidikan_formal, cv.validation_score
    FROM users u
    JOIN cv_data cv ON u.id = cv.user_id
    WHERE u.is_admin = 0 
    AND u.status = 'active'
    AND cv.is_submitted = 1
    AND cv.validation_score >= 70
    ORDER BY u.jenis_kelamin, u.nama
";
$users_result = $conn->query($users_query);
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Matching System</h1>
        <p class="text-gray-600">Kelola rekomendasi calon pasangan untuk user</p>
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

    <?php if (isset($error)): ?>
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

    <!-- Create Match Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-800 mb-6">Buat Matching Baru</h2>
        
        <form method="POST" action="" id="matchForm">
            <div class="grid md:grid-cols-3 gap-6 mb-6">
                <!-- User 1 Selection -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Pilih User Pertama</label>
                    <select id="user1Select" 
                            name="user1_id" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">-- Pilih User --</option>
                        <?php 
                        $users_result->data_seek(0);
                        while($user = $users_result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $user['id']; ?>" 
                                data-gender="<?php echo $user['jenis_kelamin']; ?>"
                                data-location="<?php echo htmlspecialchars($user['tempat_tinggal']); ?>"
                                data-profession="<?php echo htmlspecialchars($user['profesi']); ?>">
                            <?php echo htmlspecialchars($user['nama']); ?> (<?php echo $user['unique_id']; ?>) - <?php echo $user['jenis_kelamin']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <!-- User 1 Preview -->
                    <div id="user1Preview" class="mt-4 p-4 border border-gray-200 rounded-lg hidden">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-pink-600"></i>
                            </div>
                            <div>
                                <div id="user1Name" class="font-medium text-gray-800"></div>
                                <div id="user1Gender" class="text-sm text-gray-600"></div>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div id="user1Location" class="text-gray-600"></div>
                            <div id="user1Profession" class="text-gray-600"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Match Info -->
                <div class="flex flex-col items-center justify-center">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-heart text-pink-600 text-2xl"></i>
                        </div>
                        <p class="text-gray-600 text-sm">
                            Sistem akan mencocokkan berdasarkan kriteria yang sesuai
                        </p>
                    </div>
                </div>
                
                <!-- User 2 Selection -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Pilih User Kedua</label>
                    <select id="user2Select" 
                            name="user2_id" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">-- Pilih User --</option>
                        <?php 
                        $users_result->data_seek(0);
                        while($user = $users_result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $user['id']; ?>" 
                                data-gender="<?php echo $user['jenis_kelamin']; ?>"
                                data-location="<?php echo htmlspecialchars($user['tempat_tinggal']); ?>"
                                data-profession="<?php echo htmlspecialchars($user['profesi']); ?>">
                            <?php echo htmlspecialchars($user['nama']); ?> (<?php echo $user['unique_id']; ?>) - <?php echo $user['jenis_kelamin']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <!-- User 2 Preview -->
                    <div id="user2Preview" class="mt-4 p-4 border border-gray-200 rounded-lg hidden">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-blue-600"></i>
                            </div>
                            <div>
                                <div id="user2Name" class="font-medium text-gray-800"></div>
                                <div id="user2Gender" class="text-sm text-gray-600"></div>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div id="user2Location" class="text-gray-600"></div>
                            <div id="user2Profession" class="text-gray-600"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Validation Messages -->
            <div id="validationMessage" class="mb-6 p-4 rounded-lg hidden"></div>
            
            <!-- Submit Button -->
            <div class="flex justify-center">
                <button type="submit" 
                        name="create_match"
                        class="px-8 py-3 bg-gradient-to-r from-pink-600 to-purple-600 text-white rounded-lg hover:opacity-90 font-medium">
                    <i class="fas fa-heart mr-2"></i>Buat Matching
                </button>
            </div>
        </form>
    </div>

    <!-- All Matches Section -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Semua Matching</h2>
                    <p class="text-gray-600 text-sm">Total: <?php echo $total_matches; ?> matching</p>
                </div>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <button class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 text-sm">
                        <th class="px-6 py-3 font-medium">User 1</th>
                        <th class="px-6 py-3 font-medium">User 2</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">WhatsApp</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($total_matches > 0): ?>
                        <?php while($match = $matches_result->fetch_assoc()): 
                            $status_color = $match['status'] == 'pending' ? 'yellow' : 
                                          ($match['status'] == 'accepted' ? 'green' : 
                                          ($match['status'] == 'rejected' ? 'red' : 'blue'));
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 <?php echo $match['user1_gender'] == 'Laki-laki' ? 'bg-blue-100' : 'bg-pink-100'; ?> rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-user <?php echo $match['user1_gender'] == 'Laki-laki' ? 'text-blue-600' : 'text-pink-600'; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($match['user1_nama']); ?></div>
                                        <div class="text-sm text-gray-500">ID: <?php echo $match['user1_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 <?php echo $match['user2_gender'] == 'Laki-laki' ? 'bg-blue-100' : 'bg-pink-100'; ?> rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-user <?php echo $match['user2_gender'] == 'Laki-laki' ? 'text-blue-600' : 'text-pink-600'; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($match['user2_nama']); ?></div>
                                        <div class="text-sm text-gray-500">ID: <?php echo $match['user2_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-xs rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                    <?php echo ucfirst($match['status']); ?>
                                </span>
                            </td>
                            
                            <td class="px-6 py-4">
                                <?php if ($match['chat_wa_sent']): ?>
                                <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                    <i class="fab fa-whatsapp mr-1"></i>Terkirim
                                </span>
                                <?php else: ?>
                                <?php
                                // Get user WhatsApp number
                                $phone_query = $conn->query("SELECT no_wa FROM users WHERE id = {$match['user_id']}");
                                $phone_data = $phone_query->fetch_assoc();
                                $phone_number = $phone_data['no_wa'] ?? '';
                                ?>
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                    <input type="hidden" name="phone" value="<?php echo $phone_number; ?>">
                                    <button type="submit" 
                                            name="send_whatsapp"
                                            class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                        <i class="fab fa-whatsapp mr-1"></i>Kirim WA
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo date('d/m/Y', strtotime($match['created_at'])); ?>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <a href="match-detail.php?id=<?php echo $match['id']; ?>" 
                                       class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm hover:bg-blue-200">
                                       <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="" class="delete-form">
                                        <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                        <button type="submit" 
                                                name="delete_match"
                                                class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-heart text-3xl mb-3 text-gray-300"></i>
                            <p>Belum ada matching yang dibuat</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// User preview functionality
const user1Select = document.getElementById('user1Select');
const user2Select = document.getElementById('user2Select');
const user1Preview = document.getElementById('user1Preview');
const user2Preview = document.getElementById('user2Preview');
const validationMessage = document.getElementById('validationMessage');

function showUserPreview(select, preview, nameField, genderField, locationField, professionField) {
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        preview.classList.remove('hidden');
        nameField.textContent = selectedOption.text.split(' (')[0];
        genderField.textContent = selectedOption.getAttribute('data-gender');
        locationField.textContent = 'Domisili: ' + (selectedOption.getAttribute('data-location') || '-');
        professionField.textContent = 'Profesi: ' + (selectedOption.getAttribute('data-profession') || '-');
    } else {
        preview.classList.add('hidden');
    }
    
    validateMatching();
}

// Initialize previews
user1Select.addEventListener('change', () => {
    showUserPreview(
        user1Select, 
        user1Preview,
        document.getElementById('user1Name'),
        document.getElementById('user1Gender'),
        document.getElementById('user1Location'),
        document.getElementById('user1Profession')
    );
});

user2Select.addEventListener('change', () => {
    showUserPreview(
        user2Select, 
        user2Preview,
        document.getElementById('user2Name'),
        document.getElementById('user2Gender'),
        document.getElementById('user2Location'),
        document.getElementById('user2Profession')
    );
});

// Validate matching
function validateMatching() {
    const user1Value = user1Select.value;
    const user2Value = user2Select.value;
    
    if (!user1Value || !user2Value) {
        validationMessage.classList.add('hidden');
        return;
    }
    
    const user1Gender = user1Select.options[user1Select.selectedIndex].getAttribute('data-gender');
    const user2Gender = user2Select.options[user2Select.selectedIndex].getAttribute('data-gender');
    
    if (user1Value === user2Value) {
        validationMessage.classList.remove('hidden');
        validationMessage.className = 'mb-6 p-4 rounded-lg bg-red-50 text-red-700 border border-red-200';
        validationMessage.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Tidak bisa match user dengan diri sendiri!';
        return;
    }
    
    if (user1Gender === user2Gender) {
        validationMessage.classList.remove('hidden');
        validationMessage.className = 'mb-6 p-4 rounded-lg bg-yellow-50 text-yellow-700 border border-yellow-200';
        validationMessage.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Gender sama! Pastikan matching antara laki-laki dan perempuan.';
    } else {
        validationMessage.classList.remove('hidden');
        validationMessage.className = 'mb-6 p-4 rounded-lg bg-green-50 text-green-700 border border-green-200';
        validationMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Matching valid! Gender berbeda, bisa diproses.';
    }
}

// Prevent form submission if invalid
document.getElementById('matchForm').addEventListener('submit', function(e) {
    const user1Value = user1Select.value;
    const user2Value = user2Select.value;
    
    if (!user1Value || !user2Value) {
        e.preventDefault();
        alert('Pilih kedua user untuk membuat matching!');
        return;
    }
    
    if (user1Value === user2Value) {
        e.preventDefault();
        alert('Tidak bisa match user dengan diri sendiri!');
        return;
    }
    
    if (!confirm('Apakah Anda yakin ingin membuat matching ini?')) {
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; ?>