        <!-- Notifications Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-bell text-yellow-500 mr-2"></i>Notifikasi
            </h2>
            
            <div class="space-y-3">
                <?php
                // Get notifications
                $notifications = [];
                
                // Quiz notification
                if (!$quiz_passed) {
                    $notifications[] = [
                        'type' => 'warning',
                        'icon' => 'fa-graduation-cap',
                        'message' => 'Silakan selesaikan kuis pernikahan untuk melanjutkan',
                        'action' => 'quiz.php',
                        'action_text' => 'Kerjakan Kuis'
                    ];
                }
                
                // CV notification
                if ($quiz_passed && !$cv_submitted) {
                    $notifications[] = [
                        'type' => 'warning',
                        'icon' => 'fa-file-alt',
                        'message' => 'Lengkapi CV Taaruf Anda untuk proses matching',
                        'action' => 'cv-builder.php',
                        'action_text' => 'Buat CV'
                    ];
                }
                
                // Payment notification
                if ($payment_status == 'pending') {
                    $notifications[] = [
                        'type' => 'info',
                        'icon' => 'fa-credit-card',
                        'message' => 'Konfirmasi pembayaran Anda via WhatsApp ke admin',
                        'action' => '#',
                        'action_text' => 'Hubungi Admin'
                    ];
                }
                
                // Matches notification
                if ($pending_matches > 0) {
                    $notifications[] = [
                        'type' => 'success',
                        'icon' => 'fa-heart',
                        'message' => "Anda memiliki $pending_matches rekomendasi calon pasangan",
                        'action' => 'matches.php',
                        'action_text' => 'Lihat Rekomendasi'
                    ];
                }
                
                // If no notifications
                if (empty($notifications)) {
                    $notifications[] = [
                        'type' => 'info',
                        'icon' => 'fa-info-circle',
                        'message' => 'Tidak ada notifikasi baru',
                        'action' => null,
                        'action_text' => null
                    ];
                }
                
                foreach ($notifications as $notification):
                ?>
                <div class="flex items-center justify-between p-3 rounded-lg 
                    <?php echo $notification['type'] == 'warning' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 
                           ($notification['type'] == 'success' ? 'bg-green-50 border-l-4 border-green-500' : 
                           'bg-blue-50 border-l-4 border-blue-500'); ?>">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center 
                            <?php echo $notification['type'] == 'warning' ? 'bg-yellow-100 text-yellow-600' : 
                                   ($notification['type'] == 'success' ? 'bg-green-100 text-green-600' : 
                                   'bg-blue-100 text-blue-600'); ?>">
                            <i class="fas <?php echo $notification['icon']; ?>"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-gray-800"><?php echo $notification['message']; ?></p>
                        </div>
                    </div>
                    
                    <?php if ($notification['action']): ?>
                    <div>
                        <a href="<?php echo $notification['action']; ?>" 
                           class="text-sm px-4 py-2 rounded-lg font-medium
                           <?php echo $notification['type'] == 'warning' ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 
                                  ($notification['type'] == 'success' ? 'bg-green-100 text-green-700 hover:bg-green-200' : 
                                  'bg-blue-100 text-blue-700 hover:bg-blue-200'); ?>">
                            <?php echo $notification['action_text']; ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>