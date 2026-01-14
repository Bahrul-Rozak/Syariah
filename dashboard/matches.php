<?php
include '../includes/header.php';

// Check if CV is submitted
if (!$cv_submitted) {
    echo '<script>alert("Silakan submit CV Taaruf terlebih dahulu"); window.location.href = "cv-builder.php";</script>';
    exit();
}

// Get user's gender for matching logic
$user_gender = $user['jenis_kelamin'];
$opposite_gender = $user_gender == 'Laki-laki' ? 'Perempuan' : 'Laki-laki';

// Get recommended matches
$matches_query = "
    SELECT m.*, u.nama, u.unique_id, u.no_wa, cv.tempat_tinggal, cv.profesi, cv.pendidikan_formal
    FROM matches m
    JOIN users u ON m.matched_user_id = u.id
    JOIN cv_data cv ON u.id = cv.user_id
    WHERE m.user_id = $user_id 
    AND u.jenis_kelamin = '$opposite_gender'
    AND u.status = 'active'
    AND cv.is_submitted = 1
    ORDER BY m.created_at DESC
";
$matches_result = $conn->query($matches_query);

// Handle match actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accept_match'])) {
        $match_id = intval($_POST['match_id']);
        $update_query = "UPDATE matches SET status = 'accepted' WHERE id = $match_id AND user_id = $user_id";
        $conn->query($update_query);
        
        $success_message = "Anda telah menerima rekomendasi ini. Admin akan menghubungi Anda via WhatsApp.";
    } elseif (isset($_POST['reject_match'])) {
        $match_id = intval($_POST['match_id']);
        $update_query = "UPDATE matches SET status = 'rejected' WHERE id = $match_id AND user_id = $user_id";
        $conn->query($update_query);
        
        $success_message = "Rekomendasi telah ditolak.";
    }
}
?>

<div class="max-w-6xl mx-auto">
    <!-- Matches Header -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Rekomendasi Calon Pasangan</h1>
                <p class="text-gray-600">Admin telah memilihkan calon yang sesuai dengan profil Anda</p>
            </div>
            
            <div class="mt-4 md:mt-0">
                <div class="bg-pink-50 text-pink-800 px-4 py-2 rounded-lg">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?php echo $matches_result->num_rows; ?> rekomendasi tersedia
                </div>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?php echo $success_message; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Match Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-pink-50 border border-pink-200 rounded-xl p-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-heart text-pink-600"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800">
                            <?php 
                            $pending_count = 0;
                            $matches_result->data_seek(0);
                            while($match = $matches_result->fetch_assoc()) {
                                if ($match['status'] == 'pending') $pending_count++;
                            }
                            echo $pending_count;
                            ?>
                        </div>
                        <div class="text-gray-600">Menunggu Respon</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800">
                            <?php 
                            $accepted_count = 0;
                            $matches_result->data_seek(0);
                            while($match = $matches_result->fetch_assoc()) {
                                if ($match['status'] == 'accepted') $accepted_count++;
                            }
                            echo $accepted_count;
                            ?>
                        </div>
                        <div class="text-gray-600">Diterima</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-comments text-blue-600"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800">0</div>
                        <div class="text-gray-600">Dalam Pembicaraan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Matches List -->
    <div class="space-y-6">
        <?php
        $matches_result->data_seek(0);
        
        if ($matches_result->num_rows == 0):
        ?>
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-user-friends text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Belum Ada Rekomendasi</h3>
            <p class="text-gray-600 mb-6">Admin sedang memproses matching untuk Anda. Silakan tunggu atau hubungi admin via WhatsApp.</p>
            <a href="https://wa.me/6281234567890?text=Halo%20Admin%20Taaruf%20Islami,%20saya%20<?php echo urlencode($user['nama']); ?>%20(ID:%20<?php echo $user['unique_id']; ?>).%20Saya%20ingin%20bertanya%20tentang%20proses%20matching." 
               target="_blank"
               class="inline-flex items-center px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600">
                <i class="fab fa-whatsapp mr-2"></i>Hubungi Admin via WhatsApp
            </a>
        </div>
        <?php
        else:
            while ($match = $matches_result->fetch_assoc()):
                $match_status = $match['status'];
                $status_color = $match_status == 'pending' ? 'yellow' : 
                               ($match_status == 'accepted' ? 'green' : 
                               ($match_status == 'rejected' ? 'red' : 'gray'));
                $status_text = $match_status == 'pending' ? 'Menunggu Respon' : 
                              ($match_status == 'accepted' ? 'Diterima' : 
                              ($match_status == 'rejected' ? 'Ditolak' : 'Dalam Proses'));
        ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <!-- Match Header -->
            <div class="bg-gradient-to-r from-pink-500 to-purple-600 p-6 text-white">
                <div class="flex flex-col md:flex-row md:items-center justify-between">
                    <div class="flex items-center mb-4 md:mb-0">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($match['nama']); ?></h3>
                            <p class="opacity-90">ID: <?php echo $match['unique_id']; ?></p>
                        </div>
                    </div>
                    
                    <div>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                            <?php echo $status_text; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Match Details -->
            <div class="p-6">
                <div class="grid md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Informasi Dasar</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Domisili:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($match['tempat_tinggal'] ?? '-'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Profesi:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($match['profesi'] ?? '-'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Pendidikan:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($match['pendidikan_formal'] ?? '-'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Status Matching</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Direkomendasikan:</span>
                                <span class="font-medium"><?php echo date('d M Y', strtotime($match['created_at'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status Chat:</span>
                                <span class="font-medium">
                                    <?php echo $match['chat_wa_sent'] ? 'Terkirim' : 'Belum'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Aksi</h4>
                        <div class="space-y-2">
                            <?php if ($match_status == 'pending'): ?>
                            <form method="POST" action="" class="space-y-2">
                                <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                <button type="submit" 
                                        name="accept_match"
                                        class="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition">
                                    <i class="fas fa-check mr-2"></i>Terima
                                </button>
                                <button type="submit" 
                                        name="reject_match"
                                        class="w-full bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition">
                                    <i class="fas fa-times mr-2"></i>Tolak
                                </button>
                            </form>
                            <?php elseif ($match_status == 'accepted'): ?>
                            <div class="space-y-2">
                                <a href="https://wa.me/<?php echo $match['no_wa']; ?>?text=Assalamu%27alaikum%20<?php echo urlencode($match['nama']); ?>%2C%20saya%20<?php echo urlencode($user['nama']); ?>%20dari%20Taaruf%20Islami.%20Mohon%20ijin%20berkenalan." 
                                   target="_blank"
                                   class="block w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition text-center">
                                   <i class="fab fa-whatsapp mr-2"></i>Chat via WhatsApp
                                </a>
                                <button class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">
                                    <i class="fas fa-phone-alt mr-2"></i>Hubungi Admin
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Notes -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="font-medium text-gray-700 mb-2">Catatan Admin:</h4>
                    <p class="text-gray-600 italic">
                        "Calon ini direkomendasikan karena kesesuaian dalam <?php echo $opposite_gender == 'Laki-laki' ? 'visi pernikahan dan kematangan finansial' : 'pendidikan dan akhlak'; ?>. Cocok dengan kriteria yang Anda inginkan."
                    </p>
                </div>
            </div>
        </div>
        <?php
            endwhile;
        endif;
        ?>
    </div>
    
    <!-- Matching Process Info -->
    <div class="bg-white rounded-xl shadow-md p-6 mt-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Proses Matching</h3>
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center mr-3 mt-1">
                    <span class="text-pink-700 font-bold">1</span>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Admin Menganalisis CV</h4>
                    <p class="text-gray-600 text-sm">Admin akan memeriksa kelengkapan dan kesesuaian CV Anda</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex-shrink-0 w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center mr-3 mt-1">
                    <span class="text-pink-700 font-bold">2</span>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Pencocokan Kriteria</h4>
                    <p class="text-gray-600 text-sm">Sistem akan mencocokkan berdasarkan kriteria yang Anda inginkan</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex-shrink-0 w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center mr-3 mt-1">
                    <span class="text-pink-700 font-bold">3</span>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Rekomendasi 5 Terbaik</h4>
                    <p class="text-gray-600 text-sm">Anda akan menerima maksimal 5 rekomendasi terbaik</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex-shrink-0 w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center mr-3 mt-1">
                    <span class="text-pink-700 font-bold">4</span>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Konfirmasi dan Pertemuan</h4>
                    <p class="text-gray-600 text-sm">Jika cocok, admin akan mengatur pertemuan dengan pendampingan ustadz</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>