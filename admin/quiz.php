<?php
include 'includes/header.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_question'])) {
        $question = $conn->real_escape_string($_POST['question']);
        $option_a = $conn->real_escape_string($_POST['option_a']);
        $option_b = $conn->real_escape_string($_POST['option_b']);
        $option_c = $conn->real_escape_string($_POST['option_c']);
        $option_d = $conn->real_escape_string($_POST['option_d']);
        $correct_answer = $conn->real_escape_string($_POST['correct_answer']);
        
        $conn->query("INSERT INTO quiz_questions (question, option_a, option_b, option_c, option_d, correct_answer) 
                     VALUES ('$question', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_answer')");
        $success = "Soal berhasil ditambahkan!";
    } elseif (isset($_POST['update_question'])) {
        $question_id = intval($_POST['question_id']);
        $question = $conn->real_escape_string($_POST['question']);
        $option_a = $conn->real_escape_string($_POST['option_a']);
        $option_b = $conn->real_escape_string($_POST['option_b']);
        $option_c = $conn->real_escape_string($_POST['option_c']);
        $option_d = $conn->real_escape_string($_POST['option_d']);
        $correct_answer = $conn->real_escape_string($_POST['correct_answer']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $conn->query("UPDATE quiz_questions SET 
                     question = '$question',
                     option_a = '$option_a',
                     option_b = '$option_b',
                     option_c = '$option_c',
                     option_d = '$option_d',
                     correct_answer = '$correct_answer',
                     is_active = $is_active
                     WHERE id = $question_id");
        $success = "Soal berhasil diperbarui!";
    } elseif (isset($_POST['delete_question'])) {
        $question_id = intval($_POST['question_id']);
        $conn->query("DELETE FROM quiz_questions WHERE id = $question_id");
        $success = "Soal berhasil dihapus!";
    } elseif (isset($_POST['update_settings'])) {
        $min_score = intval($_POST['min_quiz_score']);
        $conn->query("UPDATE admin_settings SET min_quiz_score = $min_score WHERE id = 1");
        $success = "Pengaturan kuis berhasil diperbarui!";
    }
}

// Get quiz questions
$questions = $conn->query("SELECT * FROM quiz_questions ORDER BY id");
$total_questions = $questions->num_rows;
$active_questions = $conn->query("SELECT COUNT(*) as count FROM quiz_questions WHERE is_active = 1")->fetch_assoc()['count'];

// Get settings
$settings = $conn->query("SELECT min_quiz_score FROM admin_settings LIMIT 1")->fetch_assoc();
$min_score = $settings['min_quiz_score'] ?? 80;
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Kuis Pernikahan Management</h1>
        <p class="text-gray-600">Kelola soal kuis dan pengaturan sistem</p>
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

    <!-- Stats & Settings -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo $total_questions; ?></div>
                    <div class="text-gray-600">Total Soal</div>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-question-circle text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo $active_questions; ?></div>
                    <div class="text-gray-600">Soal Aktif</div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Nilai Minimal Lulus</label>
                    <div class="flex items-center">
                        <input type="number" 
                               name="min_quiz_score" 
                               min="0" 
                               max="100"
                               value="<?php echo $min_score; ?>"
                               class="w-24 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <span class="ml-2 text-gray-600">%</span>
                    </div>
                </div>
                <button type="submit" 
                        name="update_settings"
                        class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
                    <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                </button>
            </form>
        </div>
    </div>

    <!-- Add/Edit Question Form -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-800 mb-6" id="formTitle">Tambah Soal Baru</h2>
        
        <form method="POST" action="" id="questionForm">
            <input type="hidden" name="question_id" id="questionId">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Pertanyaan</label>
                    <textarea id="questionInput"
                              name="question" 
                              rows="3"
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Masukkan pertanyaan kuis..."></textarea>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Opsi A</label>
                        <input type="text" 
                               id="optionAInput"
                               name="option_a" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Opsi B</label>
                        <input type="text" 
                               id="optionBInput"
                               name="option_b" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Opsi C</label>
                        <input type="text" 
                               id="optionCInput"
                               name="option_c" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Opsi D</label>
                        <input type="text" 
                               id="optionDInput"
                               name="option_d" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Jawaban Benar</label>
                        <select id="correctAnswerInput"
                                name="correct_answer" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Pilih Jawaban Benar</option>
                            <option value="a">Opsi A</option>
                            <option value="b">Opsi B</option>
                            <option value="c">Opsi C</option>
                            <option value="d">Opsi D</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="flex items-center mt-8">
                            <input type="checkbox" 
                                   id="isActiveInput"
                                   name="is_active" 
                                   checked
                                   class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-gray-700">Soal Aktif</span>
                        </label>
                    </div>
                </div>
                
                <div class="pt-6 border-t border-gray-200">
                    <button type="submit" 
                            name="add_question"
                            id="submitBtn"
                            class="px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:opacity-90 font-medium">
                        <i class="fas fa-plus mr-2"></i>Tambah Soal
                    </button>
                    <button type="button" 
                            id="cancelEditBtn"
                            class="ml-4 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium hidden">
                        <i class="fas fa-times mr-2"></i>Batal Edit
                    </button>
                    <button type="button" 
                            id="previewQuestionBtn"
                            class="ml-4 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                        <i class="fas fa-eye mr-2"></i>Preview Soal
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- All Questions -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Semua Soal Kuis</h2>
                    <p class="text-gray-600 text-sm">Total: <?php echo $total_questions; ?> soal</p>
                </div>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 text-sm">
                        <th class="px-6 py-3 font-medium">ID</th>
                        <th class="px-6 py-3 font-medium">Pertanyaan</th>
                        <th class="px-6 py-3 font-medium">Jawaban Benar</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php 
                    $questions->data_seek(0);
                    while($question = $questions->fetch_assoc()):
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">#<?php echo $question['id']; ?></span>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800 mb-1">
                                <?php echo htmlspecialchars(substr($question['question'], 0, 100)); ?>
                                <?php if (strlen($question['question']) > 100): ?>...<?php endif; ?>
                            </div>
                            <div class="text-sm text-gray-500 space-y-1">
                                <div>A. <?php echo htmlspecialchars(substr($question['option_a'], 0, 50)); ?></div>
                                <div>B. <?php echo htmlspecialchars(substr($question['option_b'], 0, 50)); ?></div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800 font-medium">
                                <?php echo strtoupper($question['correct_answer']); ?>
                            </span>
                        </td>
                        
                        <td class="px-6 py-4">
                            <?php if ($question['is_active']): ?>
                            <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>Aktif
                            </span>
                            <?php else: ?>
                            <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                <i class="fas fa-times mr-1"></i>Nonaktif
                            </span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button type="button" 
                                        class="edit-question-btn px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm hover:bg-blue-200"
                                        data-id="<?php echo $question['id']; ?>"
                                        data-question="<?php echo htmlspecialchars($question['question']); ?>"
                                        data-option-a="<?php echo htmlspecialchars($question['option_a']); ?>"
                                        data-option-b="<?php echo htmlspecialchars($question['option_b']); ?>"
                                        data-option-c="<?php echo htmlspecialchars($question['option_c']); ?>"
                                        data-option-d="<?php echo htmlspecialchars($question['option_d']); ?>"
                                        data-correct="<?php echo $question['correct_answer']; ?>"
                                        data-active="<?php echo $question['is_active']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="" class="delete-form inline">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <button type="submit" 
                                            name="delete_question"
                                            class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($total_questions == 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-question-circle text-3xl mb-3 text-gray-300"></i>
                            <p>Belum ada soal kuis</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Edit question functionality
document.querySelectorAll('.edit-question-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const question = this.getAttribute('data-question');
        const optionA = this.getAttribute('data-option-a');
        const optionB = this.getAttribute('data-option-b');
        const optionC = this.getAttribute('data-option-c');
        const optionD = this.getAttribute('data-option-d');
        const correct = this.getAttribute('data-correct');
        const active = this.getAttribute('data-active') === '1';
        
        // Set form values
        document.getElementById('questionId').value = id;
        document.getElementById('questionInput').value = question;
        document.getElementById('optionAInput').value = optionA;
        document.getElementById('optionBInput').value = optionB;
        document.getElementById('optionCInput').value = optionC;
        document.getElementById('optionDInput').value = optionD;
        document.getElementById('correctAnswerInput').value = correct;
        document.getElementById('isActiveInput').checked = active;
        
        // Change form mode
        document.getElementById('formTitle').textContent = 'Edit Soal';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update Soal';
        document.getElementById('submitBtn').name = 'update_question';
        document.getElementById('cancelEditBtn').classList.remove('hidden');
        
        // Scroll to form
        document.getElementById('formTitle').scrollIntoView({ behavior: 'smooth' });
    });
});

// Cancel edit
document.getElementById('cancelEditBtn').addEventListener('click', function() {
    resetForm();
});

// Reset form function
function resetForm() {
    document.getElementById('questionId').value = '';
    document.getElementById('questionInput').value = '';
    document.getElementById('optionAInput').value = '';
    document.getElementById('optionBInput').value = '';
    document.getElementById('optionCInput').value = '';
    document.getElementById('optionDInput').value = '';
    document.getElementById('correctAnswerInput').value = '';
    document.getElementById('isActiveInput').checked = true;
    
    document.getElementById('formTitle').textContent = 'Tambah Soal Baru';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus mr-2"></i>Tambah Soal';
    document.getElementById('submitBtn').name = 'add_question';
    document.getElementById('cancelEditBtn').classList.add('hidden');
}

// Preview question
document.getElementById('previewQuestionBtn').addEventListener('click', function() {
    const question = document.getElementById('questionInput').value;
    const optionA = document.getElementById('optionAInput').value;
    const optionB = document.getElementById('optionBInput').value;
    const optionC = document.getElementById('optionCInput').value;
    const optionD = document.getElementById('optionDInput').value;
    const correct = document.getElementById('correctAnswerInput').value;
    
    if (!question || !optionA || !optionB || !optionC || !optionD || !correct) {
        alert('Semua field harus diisi untuk preview!');
        return;
    }
    
    const preview = `
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl mx-auto">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Preview Soal</h3>
            <div class="mb-6">
                <p class="text-gray-700 mb-4">${question}</p>
                <div class="space-y-3">
                    <div class="flex items-center p-3 border border-gray-300 rounded-lg ${correct === 'a' ? 'bg-green-50 border-green-500' : ''}">
                        <div class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center mr-3 ${correct === 'a' ? 'bg-green-500 text-white' : 'bg-gray-100'}">
                            ${correct === 'a' ? '✓' : 'A'}
                        </div>
                        <span>${optionA}</span>
                    </div>
                    <div class="flex items-center p-3 border border-gray-300 rounded-lg ${correct === 'b' ? 'bg-green-50 border-green-500' : ''}">
                        <div class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center mr-3 ${correct === 'b' ? 'bg-green-500 text-white' : 'bg-gray-100'}">
                            ${correct === 'b' ? '✓' : 'B'}
                        </div>
                        <span>${optionB}</span>
                    </div>
                    <div class="flex items-center p-3 border border-gray-300 rounded-lg ${correct === 'c' ? 'bg-green-50 border-green-500' : ''}">
                        <div class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center mr-3 ${correct === 'c' ? 'bg-green-500 text-white' : 'bg-gray-100'}">
                            ${correct === 'c' ? '✓' : 'C'}
                        </div>
                        <span>${optionC}</span>
                    </div>
                    <div class="flex items-center p-3 border border-gray-300 rounded-lg ${correct === 'd' ? 'bg-green-50 border-green-500' : ''}">
                        <div class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center mr-3 ${correct === 'd' ? 'bg-green-500 text-white' : 'bg-gray-100'}">
                            ${correct === 'd' ? '✓' : 'D'}
                        </div>
                        <span>${optionD}</span>
                    </div>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>Jawaban benar ditandai dengan warna hijau
            </div>
        </div>
    `;
    
    // Show modal
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    modal.innerHTML = `
        <div class="relative">
            <button class="absolute -top-2 -right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-gray-800" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
            ${preview}
        </div>
    `;
    document.body.appendChild(modal);
});
</script>

<?php include 'includes/footer.php'; ?>