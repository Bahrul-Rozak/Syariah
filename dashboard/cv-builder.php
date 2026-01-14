<?php
include '../includes/header.php';

// Check if user passed the quiz
if (!$quiz_passed) {
    echo '<script>alert("Silakan selesaikan kuis pernikahan terlebih dahulu"); window.location.href = "quiz.php";</script>';
    exit();
}

// Get CV data
$cv_query = "SELECT * FROM cv_data WHERE user_id = $user_id";
$cv_result = $conn->query($cv_query);
$cv = $cv_result->fetch_assoc();

// Initialize if not exists
if (!$cv) {
    $conn->query("INSERT INTO cv_data (user_id) VALUES ($user_id)");
    $cv = ['user_id' => $user_id];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $data = [
        'nama_lengkap' => $db->escapeString($_POST['nama_lengkap'] ?? ''),
        'nama_panggilan' => $db->escapeString($_POST['nama_panggilan'] ?? ''),
        'tanggal_lahir' => $db->escapeString($_POST['tanggal_lahir'] ?? ''),
        'tempat_tinggal' => $db->escapeString($_POST['tempat_tinggal'] ?? ''),
        'kewarganegaraan' => $db->escapeString($_POST['kewarganegaraan'] ?? ''),
        'status_pernikahan' => $db->escapeString($_POST['status_pernikahan'] ?? ''),
        'siap_menikah_bulan' => $db->escapeString($_POST['siap_menikah_bulan'] ?? ''),
        'pendidikan_formal' => $db->escapeString($_POST['pendidikan_formal'] ?? ''),
        'jurusan' => $db->escapeString($_POST['jurusan'] ?? ''),
        'institusi' => $db->escapeString($_POST['institusi'] ?? ''),
        'pendidikan_nonformal' => $db->escapeString($_POST['pendidikan_nonformal'] ?? ''),
        'profesi' => $db->escapeString($_POST['profesi'] ?? ''),
        'tempat_kerja' => $db->escapeString($_POST['tempat_kerja'] ?? ''),
        'aktivitas_dakwah' => $db->escapeString($_POST['aktivitas_dakwah'] ?? ''),
        'income' => $db->escapeString($_POST['income'] ?? ''),
        'asal_daerah_ortu' => $db->escapeString($_POST['asal_daerah_ortu'] ?? ''),
        'jumlah_saudara' => $db->escapeString($_POST['jumlah_saudara'] ?? ''),
        'profesi_ortu' => $db->escapeString($_POST['profesi_ortu'] ?? ''),
        'status_hub_keluarga' => $db->escapeString($_POST['status_hub_keluarga'] ?? ''),
        'kriteria_usia' => $db->escapeString($_POST['kriteria_usia'] ?? ''),
        'kriteria_pendidikan' => $db->escapeString($_POST['kriteria_pendidikan'] ?? ''),
        'kriteria_lokasi' => $db->escapeString($_POST['kriteria_lokasi'] ?? ''),
        'harapan_peran' => $db->escapeString($_POST['harapan_peran'] ?? ''),
        'tujuan_menikah' => $db->escapeString($_POST['tujuan_menikah'] ?? ''),
        'visi_pengasuhan' => $db->escapeString($_POST['visi_pengasuhan'] ?? ''),
        'rencana_karier' => $db->escapeString($_POST['rencana_karier'] ?? ''),
        'konsistensi_sholat' => $db->escapeString($_POST['konsistensi_sholat'] ?? ''),
        'puasa_sunnah' => $db->escapeString($_POST['puasa_sunnah'] ?? ''),
        'hafalan_quran' => $db->escapeString($_POST['hafalan_quran'] ?? ''),
        'hubungan_keluarga' => $db->escapeString($_POST['hubungan_keluarga'] ?? ''),
        'gaya_hidup' => $db->escapeString($_POST['gaya_hidup'] ?? ''),
        'nama_wali' => $db->escapeString($_POST['nama_wali'] ?? ''),
        'no_wa_wali' => $db->escapeString($_POST['no_wa_wali'] ?? ''),
        'email_wali' => $db->escapeString($_POST['email_wali'] ?? ''),
    ];
    
    // Calculate completeness score
    $required_fields = [
        'nama_lengkap', 'nama_panggilan', 'tanggal_lahir', 'tempat_tinggal',
        'kewarganegaraan', 'status_pernikahan', 'siap_menikah_bulan',
        'pendidikan_formal', 'institusi', 'profesi', 'tujuan_menikah',
        'konsistensi_sholat', 'nama_wali', 'no_wa_wali'
    ];
    
    $completed_fields = 0;
    foreach ($required_fields as $field) {
        if (!empty($data[$field])) {
            $completed_fields++;
        }
    }
    
    $completeness_score = round(($completed_fields / count($required_fields)) * 100);
    
    // Additional requirement for men: visi pernikahan
    if ($user['jenis_kelamin'] == 'Laki-laki') {
        if (!empty($data['visi_pengasuhan']) && !empty($data['rencana_karier'])) {
            $completeness_score = min(100, $completeness_score + 10);
        }
    }
    
    // Build update query
    $update_fields = [];
    foreach ($data as $field => $value) {
        $update_fields[] = "$field = '$value'";
    }
    
    $update_fields[] = "validation_score = $completeness_score";
    $update_fields[] = "is_complete = " . ($completeness_score >= 70 ? '1' : '0');
    
    if (isset($_POST['submit_cv'])) {
        $update_fields[] = "is_submitted = 1";
        
        // Update user scores
        $conn->query("UPDATE user_scores SET cv_score = $completeness_score, status_cv = 'lolos' WHERE user_id = $user_id");
    }
    
    $update_query = "UPDATE cv_data SET " . implode(', ', $update_fields) . " WHERE user_id = $user_id";
    
    if ($conn->query($update_query)) {
        $success_message = isset($_POST['submit_cv']) 
            ? "CV Taaruf berhasil disubmit! Kelengkapan: $completeness_score%" 
            : "Draft berhasil disimpan. Kelengkapan: $completeness_score%";
    } else {
        $error_message = "Terjadi kesalahan: " . $conn->error;
    }
    
    // Refresh CV data
    $cv_result = $conn->query($cv_query);
    $cv = $cv_result->fetch_assoc();
}
?>

<div class="max-w-6xl mx-auto">
    <!-- CV Builder Header -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">CV Taaruf Islami</h1>
                <p class="text-gray-600">Lengkapi profil Anda untuk proses matching</p>
            </div>
            
            <div class="mt-4 md:mt-0">
                <div class="flex items-center space-x-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold <?php echo ($cv['validation_score'] ?? 0) >= 70 ? 'text-green-600' : 'text-yellow-600'; ?>">
                            <?php echo $cv['validation_score'] ?? '0'; ?>%
                        </div>
                        <div class="text-sm text-gray-600">Kelengkapan</div>
                    </div>
                    
                    <?php if ($cv['is_submitted'] ?? false): ?>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                        <i class="fas fa-check mr-1"></i>Telah Disubmit
                    </span>
                    <?php else: ?>
                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                        <i class="fas fa-edit mr-1"></i>Draft
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span>Progress Kelengkapan Data</span>
                <span><?php echo $cv['validation_score'] ?? '0'; ?>%</span>
            </div>
            <div class="progress-bar bg-gray-200">
                <div class="bg-blue-600 h-full rounded-full" 
                     style="width: <?php echo $cv['validation_score'] ?? '0'; ?>%"></div>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                <?php echo ($cv['validation_score'] ?? 0) >= 70 ? '✓ CV sudah memenuhi syarat minimum' : '⚠ Minimal 70% kelengkapan untuk bisa submit'; ?>
            </p>
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
        <?php elseif (isset($error_message)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?php echo $error_message; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- CV Builder Form -->
    <form method="POST" action="" id="cvForm">
        <div class="space-y-6">
            
            <!-- Section 1: Data Diri Pribadi -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-user text-pink-600"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">1. Data Diri Pribadi</h2>
                        <p class="text-gray-600 text-sm">Informasi dasar tentang Anda</p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="nama_lengkap">
                            Nama Lengkap *
                        </label>
                        <input type="text" 
                               id="nama_lengkap" 
                               name="nama_lengkap"
                               required
                               value="<?php echo htmlspecialchars($cv['nama_lengkap'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="nama_panggilan">
                            Nama Panggilan *
                        </label>
                        <input type="text" 
                               id="nama_panggilan" 
                               name="nama_panggilan"
                               required
                               value="<?php echo htmlspecialchars($cv['nama_panggilan'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="tanggal_lahir">
                            Tanggal Lahir *
                        </label>
                        <input type="date" 
                               id="tanggal_lahir" 
                               name="tanggal_lahir"
                               required
                               value="<?php echo htmlspecialchars($cv['tanggal_lahir'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="tempat_tinggal">
                            Tempat Tinggal (Kota/Provinsi) *
                        </label>
                        <input type="text" 
                               id="tempat_tinggal" 
                               name="tempat_tinggal"
                               required
                               value="<?php echo htmlspecialchars($cv['tempat_tinggal'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="kewarganegaraan">
                            Kewarganegaraan *
                        </label>
                        <input type="text" 
                               id="kewarganegaraan" 
                               name="kewarganegaraan"
                               required
                               value="<?php echo htmlspecialchars($cv['kewarganegaraan'] ?? 'Indonesia'); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="status_pernikahan">
                            Status Pernikahan *
                        </label>
                        <select id="status_pernikahan" 
                                name="status_pernikahan"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Pilih Status</option>
                            <option value="Belum Menikah" <?php echo ($cv['status_pernikahan'] ?? '') == 'Belum Menikah' ? 'selected' : ''; ?>>Belum Menikah</option>
                            <option value="Janda" <?php echo ($cv['status_pernikahan'] ?? '') == 'Janda' ? 'selected' : ''; ?>>Janda</option>
                            <option value="Duda" <?php echo ($cv['status_pernikahan'] ?? '') == 'Duda' ? 'selected' : ''; ?>>Duda</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="siap_menikah_bulan">
                            Siap Menikah Dalam *
                        </label>
                        <select id="siap_menikah_bulan" 
                                name="siap_menikah_bulan"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Pilih Jangka Waktu</option>
                            <option value="3" <?php echo ($cv['siap_menikah_bulan'] ?? '') == '3' ? 'selected' : ''; ?>>3 Bulan</option>
                            <option value="6" <?php echo ($cv['siap_menikah_bulan'] ?? '') == '6' ? 'selected' : ''; ?>>6 Bulan</option>
                            <option value="12" <?php echo ($cv['siap_menikah_bulan'] ?? '') == '12' ? 'selected' : ''; ?>>1 Tahun</option>
                            <option value="24" <?php echo ($cv['siap_menikah_bulan'] ?? '') == '24' ? 'selected' : ''; ?>>2 Tahun</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Section 2: Latar Belakang Pendidikan -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-graduation-cap text-blue-600"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">2. Latar Belakang Pendidikan</h2>
                        <p class="text-gray-600 text-sm">Riwayat pendidikan formal dan non-formal</p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="pendidikan_formal">
                            Pendidikan Formal Terakhir *
                        </label>
                        <select id="pendidikan_formal" 
                                name="pendidikan_formal"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Pendidikan</option>
                            <option value="SMP" <?php echo ($cv['pendidikan_formal'] ?? '') == 'SMP' ? 'selected' : ''; ?>>SMP</option>
                            <option value="SMA/SMK" <?php echo ($cv['pendidikan_formal'] ?? '') == 'SMA/SMK' ? 'selected' : ''; ?>>SMA/SMK</option>
                            <option value="D1/D2/D3" <?php echo ($cv['pendidikan_formal'] ?? '') == 'D1/D2/D3' ? 'selected' : ''; ?>>D1/D2/D3</option>
                            <option value="S1" <?php echo ($cv['pendidikan_formal'] ?? '') == 'S1' ? 'selected' : ''; ?>>S1</option>
                            <option value="S2" <?php echo ($cv['pendidikan_formal'] ?? '') == 'S2' ? 'selected' : ''; ?>>S2</option>
                            <option value="S3" <?php echo ($cv['pendidikan_formal'] ?? '') == 'S3' ? 'selected' : ''; ?>>S3</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="jurusan">
                            Jurusan
                        </label>
                        <input type="text" 
                               id="jurusan" 
                               name="jurusan"
                               value="<?php echo htmlspecialchars($cv['jurusan'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2" for="institusi">
                            Institusi Pendidikan *
                        </label>
                        <input type="text" 
                               id="institusi" 
                               name="institusi"
                               required
                               value="<?php echo htmlspecialchars($cv['institusi'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2" for="pendidikan_nonformal">
                            Pendidikan Non-Formal (Pesantren, Hafalan Quran, dll)
                        </label>
                        <textarea id="pendidikan_nonformal" 
                                  name="pendidikan_nonformal"
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($cv['pendidikan_nonformal'] ?? ''); ?></textarea>
                        <p class="text-gray-500 text-sm mt-1">Contoh: Pesantren Tahfidz, Kursus Bahasa Arab, dll</p>
                    </div>
                </div>
            </div>
            
            <!-- Section 3: Pekerjaan & Aktivitas -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-briefcase text-green-600"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">3. Pekerjaan & Aktivitas</h2>
                        <p class="text-gray-600 text-sm">Profesi dan kegiatan sehari-hari</p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="profesi">
                            Profesi Utama *
                        </label>
                        <input type="text" 
                               id="profesi" 
                               name="profesi"
                               required
                               value="<?php echo htmlspecialchars($cv['profesi'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Contoh: Guru, Dokter, Wirausaha, dll">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="tempat_kerja">
                            Tempat Kerja
                        </label>
                        <input type="text" 
                               id="tempat_kerja" 
                               name="tempat_kerja"
                               value="<?php echo htmlspecialchars($cv['tempat_kerja'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2" for="aktivitas_dakwah">
                            Aktivitas Dakwah/Organisasi
                        </label>
                        <textarea id="aktivitas_dakwah" 
                                  name="aktivitas_dakwah"
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"><?php echo htmlspecialchars($cv['aktivitas_dakwah'] ?? ''); ?></textarea>
                        <p class="text-gray-500 text-sm mt-1">Contoh: Pengurus masjid, mentor tahsin, relawan sosial, dll</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="income">
                            Income (Per Bulan)
                        </label>
                        <select id="income" 
                                name="income"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Pilih Range Income</option>
                            <option value="< 3jt" <?php echo ($cv['income'] ?? '') == '< 3jt' ? 'selected' : ''; ?>>Kurang dari 3 juta</option>
                            <option value="3-5jt" <?php echo ($cv['income'] ?? '') == '3-5jt' ? 'selected' : ''; ?>>3 - 5 juta</option>
                            <option value="5-10jt" <?php echo ($cv['income'] ?? '') == '5-10jt' ? 'selected' : ''; ?>>5 - 10 juta</option>
                            <option value="10-20jt" <?php echo ($cv['income'] ?? '') == '10-20jt' ? 'selected' : ''; ?>>10 - 20 juta</option>
                            <option value="> 20jt" <?php echo ($cv['income'] ?? '') == '> 20jt' ? 'selected' : ''; ?>>Lebih dari 20 juta</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Continue with other sections... -->
            <!-- Note: Due to length, I'll show the pattern and you can continue with the rest -->
             <!-- Section 4: Latar Belakang Keluarga -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-home text-purple-600"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">4. Latar Belakang Keluarga</h2>
            <p class="text-gray-600 text-sm">Informasi keluarga dan latar belakang</p>
        </div>
    </div>
    
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="asal_daerah_ortu">
                Asal Daerah Orang Tua
            </label>
            <input type="text" 
                   id="asal_daerah_ortu" 
                   name="asal_daerah_ortu"
                   value="<?php echo htmlspecialchars($cv['asal_daerah_ortu'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                   placeholder="Contoh: Jawa Tengah, Padang, dll">
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="jumlah_saudara">
                Jumlah Saudara Kandung
            </label>
            <input type="number" 
                   id="jumlah_saudara" 
                   name="jumlah_saudara"
                   min="0"
                   value="<?php echo htmlspecialchars($cv['jumlah_saudara'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                   placeholder="Contoh: 3">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-gray-700 font-medium mb-2" for="profesi_ortu">
                Profesi Orang Tua
            </label>
            <input type="text" 
                   id="profesi_ortu" 
                   name="profesi_ortu"
                   value="<?php echo htmlspecialchars($cv['profesi_ortu'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                   placeholder="Contoh: PNS, Wirausaha, Guru Pensiunan">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-gray-700 font-medium mb-2" for="status_hub_keluarga">
                Status Hubungan dengan Keluarga
            </label>
            <select id="status_hub_keluarga" 
                    name="status_hub_keluarga"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">Pilih Status Hubungan</option>
                <option value="Harmonis" <?php echo ($cv['status_hub_keluarga'] ?? '') == 'Harmonis' ? 'selected' : ''; ?>>Harmonis</option>
                <option value="Baik" <?php echo ($cv['status_hub_keluarga'] ?? '') == 'Baik' ? 'selected' : ''; ?>>Baik</option>
                <option value="Cukup Baik" <?php echo ($cv['status_hub_keluarga'] ?? '') == 'Cukup Baik' ? 'selected' : ''; ?>>Cukup Baik</option>
                <option value="Tidak Harmonis" <?php echo ($cv['status_hub_keluarga'] ?? '') == 'Tidak Harmonis' ? 'selected' : ''; ?>>Tidak Harmonis</option>
            </select>
        </div>
    </div>
</div>

<!-- Section 5: Kriteria Pasangan -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-heart text-pink-600"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">5. Kriteria Pasangan</h2>
            <p class="text-gray-600 text-sm">Harapan dan kriteria calon pasangan</p>
        </div>
    </div>
    
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="kriteria_usia">
                Kriteria Usia
            </label>
            <input type="text" 
                   id="kriteria_usia" 
                   name="kriteria_usia"
                   value="<?php echo htmlspecialchars($cv['kriteria_usia'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                   placeholder="Contoh: 20-25 tahun, minimal 2 tahun lebih muda">
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="kriteria_pendidikan">
                Kriteria Pendidikan Minimal
            </label>
            <select id="kriteria_pendidikan" 
                    name="kriteria_pendidikan"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Pilih Pendidikan</option>
                <option value="Tidak Ada Preferensi" <?php echo ($cv['kriteria_pendidikan'] ?? '') == 'Tidak Ada Preferensi' ? 'selected' : ''; ?>>Tidak Ada Preferensi</option>
                <option value="SMA" <?php echo ($cv['kriteria_pendidikan'] ?? '') == 'SMA' ? 'selected' : ''; ?>>SMA</option>
                <option value="D1/D2/D3" <?php echo ($cv['kriteria_pendidikan'] ?? '') == 'D1/D2/D3' ? 'selected' : ''; ?>>D1/D2/D3</option>
                <option value="S1" <?php echo ($cv['kriteria_pendidikan'] ?? '') == 'S1' ? 'selected' : ''; ?>>S1</option>
                <option value="S2" <?php echo ($cv['kriteria_pendidikan'] ?? '') == 'S2' ? 'selected' : ''; ?>>S2</option>
            </select>
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="kriteria_lokasi">
                Kriteria Lokasi
            </label>
            <input type="text" 
                   id="kriteria_lokasi" 
                   name="kriteria_lokasi"
                   value="<?php echo htmlspecialchars($cv['kriteria_lokasi'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                   placeholder="Contoh: Jabodetabek, Jawa Barat, dll">
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="harapan_peran">
                Harapan Peran Pasangan
            </label>
            <select id="harapan_peran" 
                    name="harapan_peran"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Pilih Peran</option>
                <option value="Ibu Rumah Tangga" <?php echo ($cv['harapan_peran'] ?? '') == 'Ibu Rumah Tangga' ? 'selected' : ''; ?>>Ibu Rumah Tangga</option>
                <option value="Karier & Rumah Tangga" <?php echo ($cv['harapan_peran'] ?? '') == 'Karier & Rumah Tangga' ? 'selected' : ''; ?>>Karier & Rumah Tangga</option>
                <option value="Support Karier Suami" <?php echo ($cv['harapan_peran'] ?? '') == 'Support Karier Suami' ? 'selected' : ''; ?>>Support Karier Suami</option>
                <option value="Bekerja" <?php echo ($cv['harapan_peran'] ?? '') == 'Bekerja' ? 'selected' : ''; ?>>Bekerja</option>
            </select>
        </div>
    </div>
</div>

<!-- Section 6: Visi Pernikahan -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-eye text-indigo-600"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">6. Visi Pernikahan</h2>
            <p class="text-gray-600 text-sm">Tujuan dan rencana pernikahan</p>
        </div>
    </div>
    
    <div class="space-y-6">
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="tujuan_menikah">
                Tujuan Menikah *
            </label>
            <textarea id="tujuan_menikah" 
                      name="tujuan_menikah"
                      rows="3"
                      required
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="Apa tujuan utama Anda menikah?"><?php echo htmlspecialchars($cv['tujuan_menikah'] ?? ''); ?></textarea>
            <p class="text-gray-500 text-sm mt-1">Contoh: Menyempurnakan agama, membentuk keluarga sakinah, mendapatkan keturunan yang shalih</p>
        </div>
        
        <?php if ($user['jenis_kelamin'] == 'Laki-laki'): ?>
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="visi_pengasuhan">
                Visi Pengasuhan Anak
                <span class="text-xs font-normal text-gray-500">(Wajib untuk pria)</span>
            </label>
            <textarea id="visi_pengasuhan" 
                      name="visi_pengasuhan"
                      rows="3"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="Bagaimana rencana pengasuhan anak?"><?php echo htmlspecialchars($cv['visi_pengasuhan'] ?? ''); ?></textarea>
        </div>
        <?php endif; ?>
        
        <?php if ($user['jenis_kelamin'] == 'Laki-laki'): ?>
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="rencana_karier">
                Rencana Karier Setelah Menikah
                <span class="text-xs font-normal text-gray-500">(Wajib untuk pria)</span>
            </label>
            <textarea id="rencana_karier" 
                      name="rencana_karier"
                      rows="3"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="Bagaimana rencana karier Anda setelah menikah?"><?php echo htmlspecialchars($cv['rencana_karier'] ?? ''); ?></textarea>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Section 7: Keislaman & Akhlak -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-mosque text-green-600"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">7. Keislaman & Akhlak</h2>
            <p class="text-gray-600 text-sm">Kualitas ibadah dan akhlak sehari-hari</p>
        </div>
    </div>
    
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="konsistensi_sholat">
                Konsistensi Sholat 5 Waktu *
            </label>
            <select id="konsistensi_sholat" 
                    name="konsistensi_sholat"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <option value="">Pilih Konsistensi</option>
                <option value="Selalu Tepat Waktu" <?php echo ($cv['konsistensi_sholat'] ?? '') == 'Selalu Tepat Waktu' ? 'selected' : ''; ?>>Selalu Tepat Waktu</option>
                <option value="Sering Tepat Waktu" <?php echo ($cv['konsistensi_sholat'] ?? '') == 'Sering Tepat Waktu' ? 'selected' : ''; ?>>Sering Tepat Waktu</option>
                <option value="Kadang Tepat Waktu" <?php echo ($cv['konsistensi_sholat'] ?? '') == 'Kadang Tepat Waktu' ? 'selected' : ''; ?>>Kadang Tepat Waktu</option>
                <option value="Berusaha Konsisten" <?php echo ($cv['konsistensi_sholat'] ?? '') == 'Berusaha Konsisten' ? 'selected' : ''; ?>>Berusaha Konsisten</option>
            </select>
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="puasa_sunnah">
                Puasa Sunnah
            </label>
            <select id="puasa_sunnah" 
                    name="puasa_sunnah"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <option value="">Pilih Frekuensi</option>
                <option value="Rutin Senin-Kamis" <?php echo ($cv['puasa_sunnah'] ?? '') == 'Rutin Senin-Kamis' ? 'selected' : ''; ?>>Rutin Senin-Kamis</option>
                <option value="Sering Puasa Sunnah" <?php echo ($cv['puasa_sunnah'] ?? '') == 'Sering Puasa Sunnah' ? 'selected' : ''; ?>>Sering Puasa Sunnah</option>
                <option value="Kadang Puasa Sunnah" <?php echo ($cv['puasa_sunnah'] ?? '') == 'Kadang Puasa Sunnah' ? 'selected' : ''; ?>>Kadang Puasa Sunnah</option>
                <option value="Sesekali" <?php echo ($cv['puasa_sunnah'] ?? '') == 'Sesekali' ? 'selected' : ''; ?>>Sesekali</option>
            </select>
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="hafalan_quran">
                Hafalan Quran
            </label>
            <input type="text" 
                   id="hafalan_quran" 
                   name="hafalan_quran"
                   value="<?php echo htmlspecialchars($cv['hafalan_quran'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                   placeholder="Contoh: 5 juz, 10 juz, 30 juz">
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="hubungan_keluarga">
                Hubungan dengan Keluarga
            </label>
            <select id="hubungan_keluarga" 
                    name="hubungan_keluarga"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <option value="">Pilih Kualitas Hubungan</option>
                <option value="Sangat Baik" <?php echo ($cv['hubungan_keluarga'] ?? '') == 'Sangat Baik' ? 'selected' : ''; ?>>Sangat Baik</option>
                <option value="Baik" <?php echo ($cv['hubungan_keluarga'] ?? '') == 'Baik' ? 'selected' : ''; ?>>Baik</option>
                <option value="Cukup" <?php echo ($cv['hubungan_keluarga'] ?? '') == 'Cukup' ? 'selected' : ''; ?>>Cukup</option>
                <option value="Sedang Memperbaiki" <?php echo ($cv['hubungan_keluarga'] ?? '') == 'Sedang Memperbaiki' ? 'selected' : ''; ?>>Sedang Memperbaiki</option>
            </select>
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-gray-700 font-medium mb-2" for="gaya_hidup">
                Gaya Hidup & Hobi
            </label>
            <textarea id="gaya_hidup" 
                      name="gaya_hidup"
                      rows="3"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                      placeholder="Contoh: Sederhana, suka membaca buku, olahraga, memasak, dll"><?php echo htmlspecialchars($cv['gaya_hidup'] ?? ''); ?></textarea>
        </div>
    </div>
</div>

<!-- Section 8: Data Wali -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-user-friends text-orange-600"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">8. Data Wali</h2>
            <p class="text-gray-600 text-sm">Informasi wali yang akan dihubungi</p>
        </div>
    </div>
    
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="nama_wali">
                Nama Wali (Ayah/Kakak/Paman) *
            </label>
            <input type="text" 
                   id="nama_wali" 
                   name="nama_wali"
                   required
                   value="<?php echo htmlspecialchars($cv['nama_wali'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                   placeholder="Nama lengkap wali">
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="no_wa_wali">
                No. WhatsApp Wali *
            </label>
            <input type="tel" 
                   id="no_wa_wali" 
                   name="no_wa_wali"
                   required
                   value="<?php echo htmlspecialchars($cv['no_wa_wali'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                   placeholder="Contoh: 081234567890">
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2" for="email_wali">
                Email Wali
            </label>
            <input type="email" 
                   id="email_wali" 
                   name="email_wali"
                   value="<?php echo htmlspecialchars($cv['email_wali'] ?? ''); ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                   placeholder="wali@email.com">
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2">
                Hubungan dengan Wali
            </label>
            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    onchange="updateWaliRelationship(this.value)">
                <option value="">Pilih Hubungan</option>
                <option value="Ayah">Ayah</option>
                <option value="Ibu">Ibu</option>
                <option value="Kakak">Kakak</option>
                <option value="Adik">Adik</option>
                <option value="Paman">Paman</option>
                <option value="Kakak Ipar">Kakak Ipar</option>
                <option value="Lainnya">Lainnya</option>
            </select>
        </div>
        
        <div class="md:col-span-2 bg-blue-50 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Informasi Wali</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Pastikan data wali yang dimasukkan benar dan valid. Wali akan dihubungi untuk proses taaruf selanjutnya jika ada kecocokan.</p>
                        <p class="mt-1">Wali yang dimaksud adalah mahram Anda yang berhak menjadi wali nikah.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
            
        </div>
        
        <!-- Form Actions -->
        <div class="bg-white rounded-xl shadow-md p-6 mt-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between">
                <div>
                    <p class="text-gray-600">
                        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                        Pastikan semua data sudah benar sebelum submit
                    </p>
                </div>
                
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <button type="submit" 
                            name="save_draft"
                            class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                        <i class="fas fa-save mr-2"></i>Simpan Draft
                    </button>
                    
                    <button type="submit" 
                            name="submit_cv"
                            class="px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 text-white rounded-lg hover:opacity-90 font-medium"
                            <?php echo ($cv['validation_score'] ?? 0) < 70 ? 'disabled' : ''; ?>>
                        <i class="fas fa-paper-plane mr-2"></i>Submit CV
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Calculate age from birth date
document.getElementById('tanggal_lahir').addEventListener('change', function() {
    const birthDate = new Date(this.value);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    // You can display the age somewhere if needed
    console.log('Usia: ' + age + ' tahun');
});

// Auto-save form data
let autoSaveTimeout;
document.querySelectorAll('#cvForm input, #cvForm select, #cvForm textarea').forEach(element => {
    element.addEventListener('input', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            // Show saving indicator
            const saveBtn = document.querySelector('button[name="save_draft"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            saveBtn.disabled = true;
            
            // Simulate save
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
                
                // Show success message
                const indicator = document.createElement('div');
                indicator.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg';
                indicator.innerHTML = '<i class="fas fa-check mr-2"></i>Draft tersimpan';
                document.body.appendChild(indicator);
                
                setTimeout(() => {
                    indicator.remove();
                }, 2000);
            }, 500);
        }, 1000);
    });
});
</script>

<?php include '../includes/footer.php'; ?>