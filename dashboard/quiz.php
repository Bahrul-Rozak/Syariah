<?php
include '../includes/header.php';

// Check if user already passed
if ($quiz_passed) {
    echo '<script>window.location.href = "cv-builder.php";</script>';
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $total_questions = 0;
    $correct_answers = 0;
    
    // Get all active questions
    $questions_query = "SELECT * FROM quiz_questions WHERE is_active = 1";
    $questions_result = $conn->query($questions_query);
    
    while ($question = $questions_result->fetch_assoc()) {
        $total_questions++;
        $question_id = $question['id'];
        $user_answer = $_POST['question_' . $question_id] ?? '';
        
        if ($user_answer == $question['correct_answer']) {
            $correct_answers++;
        }
    }
    
    // Calculate score
    if ($total_questions > 0) {
        $score = round(($correct_answers / $total_questions) * 100);
    }
    
    // Get minimum passing score
    $settings_query = "SELECT min_quiz_score FROM admin_settings LIMIT 1";
    $settings_result = $conn->query($settings_query);
    $settings = $settings_result->fetch_assoc();
    $min_score = $settings['min_quiz_score'] ?? 80;
    
    $passed = $score >= $min_score;
    
    // Save quiz result
    $save_query = "INSERT INTO quiz_results (user_id, score, passed) VALUES ($user_id, $score, " . ($passed ? '1' : '0') . ")";
    $conn->query($save_query);
    
    // Update user scores
    $update_query = "UPDATE user_scores SET kuis_score = $score WHERE user_id = $user_id";
    $conn->query($update_query);
    
    // Show result
    if ($passed) {
        $success_message = "Selamat! Anda lulus kuis dengan skor $score%";
    } else {
        $error_message = "Skor Anda $score%. Anda belum mencapai batas minimal ($min_score%). Silakan pelajari materinya lagi.";
    }
}

// Get quiz questions
$questions_query = "SELECT * FROM quiz_questions WHERE is_active = 1 ORDER BY id";
$questions_result = $conn->query($questions_query);
$total_questions = $questions_result->num_rows;
?>

<div class="max-w-4xl mx-auto">
    <!-- Quiz Header -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Kuis Pernikahan Islami</h1>
                <p class="text-gray-600">Tes pemahaman Anda tentang pernikahan dalam Islam</p>
            </div>
            <div class="mt-4 md:mt-0">
                <div class="bg-pink-50 text-pink-800 px-4 py-2 rounded-lg">
                    <i class="fas fa-info-circle mr-2"></i>
                    Skor minimal: 80% untuk lanjut ke CV Taaruf
                </div>
            </div>
        </div>
        
        <!-- Quiz Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-gray-800"><?php echo $total_questions; ?></div>
                <div class="text-gray-600">Jumlah Soal</div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-gray-800">80%</div>
                <div class="text-gray-600">Nilai Minimal</div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-gray-800"><?php echo $user['kuis_score'] ?? '0'; ?>%</div>
                <div class="text-gray-600">Skor Terakhir</div>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800"><?php echo $success_message; ?></h3>
                    <div class="mt-2">
                        <a href="cv-builder.php" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-arrow-right mr-2"></i>Lanjutkan ke CV Taaruf
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif (isset($error_message)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800"><?php echo $error_message; ?></h3>
                    <div class="mt-2">
                        <a href="#materi" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-book mr-2"></i>Pelajari Materi
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!isset($success_message) && !isset($error_message)): ?>
    <!-- Quiz Form -->
    <form method="POST" action="">
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Soal Kuis</h2>
            
            <?php
            if ($total_questions == 0) {
                echo '<div class="text-center py-8 text-gray-500">
                        <i class="fas fa-question-circle text-3xl mb-3"></i>
                        <p>Belum ada soal kuis yang tersedia</p>
                      </div>';
            } else {
                $question_number = 1;
                $questions_result->data_seek(0); // Reset pointer
                
                while ($question = $questions_result->fetch_assoc()):
            ?>
            <div class="mb-8 pb-8 <?php echo $question_number < $total_questions ? 'border-b border-gray-200' : ''; ?>">
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center mr-3">
                        <span class="text-pink-700 font-bold"><?php echo $question_number; ?></span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-800 mb-4"><?php echo htmlspecialchars($question['question']); ?></h3>
                        
                        <div class="space-y-3">
                            <?php
                            $options = [
                                'a' => $question['option_a'],
                                'b' => $question['option_b'],
                                'c' => $question['option_c'],
                                'd' => $question['option_d']
                            ];
                            
                            foreach ($options as $key => $option):
                            ?>
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" 
                                       name="question_<?php echo $question['id']; ?>" 
                                       value="<?php echo $key; ?>" 
                                       class="h-5 w-5 text-pink-600 focus:ring-pink-500"
                                       required>
                                <span class="ml-3 text-gray-700">
                                    <span class="font-medium mr-2"><?php echo strtoupper($key); ?>.</span>
                                    <?php echo htmlspecialchars($option); ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                    $question_number++;
                endwhile;
            }
            ?>
            
            <?php if ($total_questions > 0): ?>
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                        Pastikan semua soal telah dijawab
                    </div>
                    <button type="submit" 
                            name="submit_quiz"
                            class="bg-gradient-to-r from-pink-600 to-purple-600 text-white px-8 py-3 rounded-lg hover:opacity-90 transition shadow-lg font-medium">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim Jawaban
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
    
    <!-- Learning Materials -->
    <div id="materi" class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Materi Pembelajaran</h2>
        
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-book-open text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Panduan Pernikahan Islam</h3>
                        <p class="text-sm text-gray-600">Referensi lengkap</p>
                    </div>
                </div>
                <ul class="space-y-2">
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Hukum dan tujuan pernikahan</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Kewajiban suami dan istri</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Proses taaruf yang syar'i</span>
                    </li>
                </ul>
                <a href="#" class="inline-flex items-center mt-4 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-external-link-alt mr-2"></i>Baca Materi
                </a>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-video text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Video Edukasi</h3>
                        <p class="text-sm text-gray-600">Penjelasan dari ustadz</p>
                    </div>
                </div>
                <ul class="space-y-2">
                    <li class="flex items-center">
                        <i class="fas fa-play-circle text-green-500 mr-2"></i>
                        <span>Fiqh Munakahah dasar</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-play-circle text-green-500 mr-2"></i>
                        <span>Tips memilih calon pasangan</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-play-circle text-green-500 mr-2"></i>
                        <span>Persiapan mental dan finansial</span>
                    </li>
                </ul>
                <a href="#" class="inline-flex items-center mt-4 text-green-600 hover:text-green-800">
                    <i class="fab fa-youtube mr-2"></i>Tonton Video
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto save answers
document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const questionId = this.name.split('_')[1];
        const answer = this.value;
        
        // Save to localStorage
        localStorage.setItem('quiz_answer_' + questionId, answer);
    });
});

// Load saved answers on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        const questionId = radio.name.split('_')[1];
        const savedAnswer = localStorage.getItem('quiz_answer_' + questionId);
        
        if (savedAnswer && radio.value === savedAnswer) {
            radio.checked = true;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>