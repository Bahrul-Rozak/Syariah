<?php include '../includes/header.php'; ?>



<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Status Card -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">Status Akun</h3>
            <span class="px-3 py-1 text-xs font-semibold rounded-full 
                <?php echo $user['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                       ($user['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                       'bg-gray-100 text-gray-800'); ?>">
                <?php echo ucfirst($user['status']); ?>
            </span>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">ID Taaruf:</span>
                <span class="font-medium"><?php echo $user['unique_id']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Jenis Kelamin:</span>
                <span class="font-medium"><?php echo $user['jenis_kelamin']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">WhatsApp:</span>
                <span class="font-medium"><?php echo $user['no_wa']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Bergabung:</span>
                <span class="font-medium"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Actions Card -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Aksi Cepat</h3>
        <div class="space-y-3">
            <?php if (!$quiz_passed): ?>
            <a href="quiz.php" class="flex items-center p-3 bg-pink-50 rounded-lg hover:bg-pink-100 transition">
                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-graduation-cap text-pink-600"></i>
                </div>
                <div>
                    <div class="font-medium text-gray-800">Kerjakan Kuis</div>
                    <div class="text-sm text-gray-600">Tes pemahaman pernikahan Islam</div>
                </div>
            </a>
            <?php elseif (!$cv_submitted): ?>
            <a href="cv-builder.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-file-alt text-blue-600"></i>
                </div>
                <div>
                    <div class="font-medium text-gray-800">Lengkapi CV</div>
                    <div class="text-sm text-gray-600">Buat profil taaruf Anda</div>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($pending_matches > 0): ?>
            <a href="matches.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-heart text-green-600"></i>
                </div>
                <div>
                    <div class="font-medium text-gray-800">Lihat Rekomendasi</div>
                    <div class="text-sm text-gray-600"><?php echo $pending_matches; ?> calon pasangan</div>
                </div>
            </a>
            <?php endif; ?>
            
            <a href="profile.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-user-circle text-gray-600"></i>
                </div>
                <div>
                    <div class="font-medium text-gray-800">Edit Profil</div>
                    <div class="text-sm text-gray-600">Perbarui data pribadi</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Statistik</h3>
        <div class="space-y-4">
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-gray-600">Skor Kuis</span>
                    <span class="font-medium"><?php echo $user['kuis_score'] ?? '0'; ?> / 100</span>
                </div>
                <div class="progress-bar bg-gray-200">
                    <div class="bg-pink-600 h-full rounded-full" 
                         style="width: <?php echo min(($user['kuis_score'] ?? 0), 100); ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-gray-600">Kelengkapan CV</span>
                    <span class="font-medium"><?php echo $user['cv_score'] ?? '0'; ?>%</span>
                </div>
                <div class="progress-bar bg-gray-200">
                    <div class="bg-blue-600 h-full rounded-full" 
                         style="width: <?php echo $user['cv_score'] ?? '0'; ?>%"></div>
                </div>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-pink-600"><?php echo $pending_matches; ?></div>
                        <div class="text-sm text-gray-600">Rekomendasi</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">
                            <?php echo $payment_status == 'confirmed' ? '✓' : '0'; ?>
                        </div>
                        <div class="text-sm text-gray-600">Pembayaran</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white rounded-xl shadow-md p-6 mt-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Aktivitas Terakhir</h3>
    <div class="space-y-3">
        <?php
        $activities = [];
        
        // Quiz activity
        if ($user['kuis_score']) {
            $activities[] = [
                'date' => date('d M Y', strtotime($user['created_at'])),
                'icon' => 'fa-graduation-cap',
                'color' => 'pink',
                'title' => 'Mengerjakan Kuis Pernikahan',
                'description' => 'Skor: ' . $user['kuis_score']
            ];
        }
        
        // CV activity
        if ($user['cv_score']) {
            $activities[] = [
                'date' => date('d M Y'),
                'icon' => 'fa-file-alt',
                'color' => 'blue',
                'title' => 'Memperbarui CV Taaruf',
                'description' => 'Kelengkapan: ' . $user['cv_score'] . '%'
            ];
        }
        
        // Registration activity (always)
        $activities[] = [
            'date' => date('d M Y', strtotime($user['created_at'])),
            'icon' => 'fa-user-plus',
            'color' => 'green',
            'title' => 'Bergabung dengan Taaruf Islami',
            'description' => 'Akun berhasil dibuat'
        ];
        
        // Sort by date (newest first)
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Display max 3 activities
        $display_activities = array_slice($activities, 0, 3);
        
        foreach ($display_activities as $activity):
        ?>
        <div class="flex items-center p-3 border border-gray-200 rounded-lg">
            <div class="w-10 h-10 rounded-full flex items-center justify-center mr-4 
                <?php echo 'bg-' . $activity['color'] . '-100 text-' . $activity['color'] . '-600'; ?>">
                <i class="fas <?php echo $activity['icon']; ?>"></i>
            </div>
            <div class="flex-1">
                <div class="font-medium text-gray-800"><?php echo $activity['title']; ?></div>
                <div class="text-sm text-gray-600"><?php echo $activity['description']; ?></div>
            </div>
            <div class="text-sm text-gray-500"><?php echo $activity['date']; ?></div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($display_activities)): ?>
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-history text-3xl mb-3"></i>
            <p>Belum ada aktivitas</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>