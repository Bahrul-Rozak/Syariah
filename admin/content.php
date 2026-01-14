<?php
include 'includes/header.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_content'])) {
        $section = $conn->real_escape_string($_POST['section']);
        $title = $conn->real_escape_string($_POST['title']);
        $content = $conn->real_escape_string($_POST['content']);
        $display_order = intval($_POST['display_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if section exists
        $check = $conn->query("SELECT id FROM landing_content WHERE section = '$section'");
        
        if ($check->num_rows > 0) {
            // Update existing
            $conn->query("UPDATE landing_content SET 
                         title = '$title', 
                         content = '$content',
                         display_order = $display_order,
                         is_active = $is_active
                         WHERE section = '$section'");
            $success = "Konten '$section' berhasil diperbarui!";
        } else {
            // Insert new
            $conn->query("INSERT INTO landing_content (section, title, content, display_order, is_active) 
                         VALUES ('$section', '$title', '$content', $display_order, $is_active)");
            $success = "Konten '$section' berhasil ditambahkan!";
        }
    }
}

// Get all sections
$sections = $conn->query("SELECT * FROM landing_content ORDER BY display_order, section");
$total_sections = $sections->num_rows;

// Get sections for dropdown
$existing_sections = $conn->query("SELECT DISTINCT section FROM landing_content ORDER BY section");
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Landing Page Content</h1>
        <p class="text-gray-600">Kelola semua konten yang tampil di halaman utama website</p>
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

    <!-- Content Sections -->
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Edit Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <h2 class="text-lg font-bold text-gray-800 mb-6">Edit Konten Section</h2>
                
                <form method="POST" action="">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Pilih Section</label>
                            <select id="sectionSelect" 
                                    name="section" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">-- Pilih Section --</option>
                                <option value="hero">Hero Section (Utama)</option>
                                <option value="about">About Section (Tentang)</option>
                                <option value="features">Features Section (Fitur)</option>
                                <option value="ustadz">Ustadz Section</option>
                                <option value="testimonial">Testimonial Section</option>
                                <option value="pricing">Pricing Section (Biaya)</option>
                                <option value="custom">Custom Section (Baru)</option>
                            </select>
                            <p class="text-gray-500 text-sm mt-1">Pilih section yang ingin diedit</p>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Judul</label>
                                <input type="text" 
                                       id="titleInput"
                                       name="title" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Urutan Tampil</label>
                                <input type="number" 
                                       id="orderInput"
                                       name="display_order" 
                                       min="1" 
                                       max="100"
                                       value="1"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Konten</label>
                            <textarea id="contentInput"
                                      name="content" 
                                      rows="6"
                                      required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                            <p class="text-gray-500 text-sm mt-1">Gunakan HTML sederhana untuk formatting</p>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       id="activeInput"
                                       name="is_active" 
                                       checked
                                       class="rounded text-purple-600 focus:ring-purple-500">
                                <span class="ml-2 text-gray-700">Tampilkan di website</span>
                            </label>
                        </div>
                        
                        <div class="pt-6 border-t border-gray-200">
                            <button type="submit" 
                                    name="save_content"
                                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:opacity-90 font-medium">
                                <i class="fas fa-save mr-2"></i>Simpan Perubahan
                            </button>
                            <button type="button" 
                                    id="previewBtn"
                                    class="ml-4 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                                <i class="fas fa-eye mr-2"></i>Preview
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Preview Section -->
            <div id="previewSection" class="bg-white rounded-xl shadow-md p-6 hidden">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Preview</h2>
                <div id="previewContent" class="prose max-w-none"></div>
            </div>
        </div>

        <!-- Right Column: All Sections -->
        <div>
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-6">Semua Sections</h2>
                
                <div class="space-y-4">
                    <?php 
                    $sections->data_seek(0);
                    while($section = $sections->fetch_assoc()):
                        $section_names = [
                            'hero' => 'Hero Section',
                            'about' => 'Tentang Kami',
                            'features' => 'Fitur',
                            'ustadz' => 'Ustadz',
                            'testimonial' => 'Testimonial',
                            'pricing' => 'Biaya',
                        ];
                    ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer section-item"
                         data-section="<?php echo $section['section']; ?>"
                         data-title="<?php echo htmlspecialchars($section['title']); ?>"
                         data-content="<?php echo htmlspecialchars($section['content']); ?>"
                         data-order="<?php echo $section['display_order']; ?>"
                         data-active="<?php echo $section['is_active']; ?>">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-medium text-gray-800">
                                    <?php echo $section_names[$section['section']] ?? ucfirst($section['section']); ?>
                                </div>
                                <div class="text-sm text-gray-500">Urutan: <?php echo $section['display_order']; ?></div>
                            </div>
                            <div class="flex items-center">
                                <?php if ($section['is_active']): ?>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                    <i class="fas fa-check"></i>
                                </span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">
                                    <i class="fas fa-times"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 truncate">
                            <?php echo htmlspecialchars(substr($section['content'], 0, 100)); ?>...
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($total_sections == 0): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-file-alt text-3xl mb-3 text-gray-300"></i>
                        <p>Belum ada konten</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                        Klik section untuk mengedit
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load section data when clicked
document.querySelectorAll('.section-item').forEach(item => {
    item.addEventListener('click', function() {
        const section = this.getAttribute('data-section');
        const title = this.getAttribute('data-title');
        const content = this.getAttribute('data-content');
        const order = this.getAttribute('data-order');
        const active = this.getAttribute('data-active') === '1';
        
        // Set form values
        document.getElementById('sectionSelect').value = section;
        document.getElementById('titleInput').value = title;
        document.getElementById('contentInput').value = content;
        document.getElementById('orderInput').value = order;
        document.getElementById('activeInput').checked = active;
        
        // Scroll to form
        document.getElementById('sectionSelect').scrollIntoView({ behavior: 'smooth' });
    });
});

// Preview functionality
document.getElementById('previewBtn').addEventListener('click', function() {
    const title = document.getElementById('titleInput').value;
    const content = document.getElementById('contentInput').value;
    
    if (!title || !content) {
        alert('Judul dan konten harus diisi untuk preview!');
        return;
    }
    
    const previewSection = document.getElementById('previewSection');
    const previewContent = document.getElementById('previewContent');
    
    previewContent.innerHTML = `
        <h2 class="text-xl font-bold text-gray-800 mb-4">${title}</h2>
        <div class="text-gray-600 leading-relaxed">${content}</div>
    `;
    
    previewSection.classList.remove('hidden');
});

// Auto-save indicator
let saveTimeout;
const saveBtn = document.querySelector('button[name="save_content"]');
const originalBtnText = saveBtn.innerHTML;

document.querySelectorAll('#titleInput, #contentInput').forEach(input => {
    input.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        
        // Show "menyimpan" indicator
        saveBtn.innerHTML = '<i class="fas fa-sync fa-spin mr-2"></i>Menyimpan...';
        saveBtn.disabled = true;
        
        saveTimeout = setTimeout(() => {
            saveBtn.innerHTML = originalBtnText;
            saveBtn.disabled = false;
            
            // Show saved notification
            const notification = document.createElement('div');
            notification.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            notification.innerHTML = '<i class="fas fa-check mr-2"></i>Draft tersimpan';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 2000);
        }, 1000);
    });
});

// Initialize form with first section if available
document.addEventListener('DOMContentLoaded', function() {
    const firstSection = document.querySelector('.section-item');
    if (firstSection) {
        firstSection.click();
    }
});
</script>

<?php include 'includes/footer.php'; ?>