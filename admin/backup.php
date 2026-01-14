<?php
include 'includes/header.php';

// Handle backup actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'download') {
        // Get all tables
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        // Create SQL dump
        $sql_dump = "-- Taaruf Islami Database Backup\n";
        $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "-- \n\n";
        
        foreach($tables as $table) {
            // Drop table if exists
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Create table
            $create_table = $conn->query("SHOW CREATE TABLE `$table`");
            $row = $create_table->fetch_array();
            $sql_dump .= $row[1] . ";\n\n";
            
            // Insert data
            $result = $conn->query("SELECT * FROM `$table`");
            if ($result->num_rows > 0) {
                $sql_dump .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                while($row = $result->fetch_assoc()) {
                    $values = array_map(function($value) use ($conn) {
                        if ($value === null) return 'NULL';
                        return "'" . $conn->real_escape_string($value) . "'";
                    }, $row);
                    $rows[] = "(" . implode(', ', $values) . ")";
                }
                $sql_dump .= implode(",\n", $rows) . ";\n\n";
            }
        }
        
        // Set headers for download
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="taaruf_islami_backup_' . date('Y-m-d_H-i-s') . '.sql"');
        echo $sql_dump;
        exit;
    }
}

// Get backup stats
$backup_dir = '../../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Get existing backups
$backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $filepath = $backup_dir . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => date('Y-m-d H:i:s', filemtime($filepath))
            ];
        }
    }
}

// Sort by date (newest first)
usort($backups, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Handle manual backup
if (isset($_POST['create_backup'])) {
    $filename = 'taaruf_islami_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    // Create SQL dump
    $sql_dump = "-- Taaruf Islami Database Backup\n";
    $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql_dump .= "-- \n\n";
    
    foreach($tables as $table) {
        // Drop table if exists
        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create table
        $create_table = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $create_table->fetch_array();
        $sql_dump .= $row[1] . ";\n\n";
        
        // Insert data
        $result = $conn->query("SELECT * FROM `$table`");
        if ($result->num_rows > 0) {
            $sql_dump .= "INSERT INTO `$table` VALUES\n";
            $rows = [];
            while($row = $result->fetch_assoc()) {
                $values = array_map(function($value) use ($conn) {
                    if ($value === null) return 'NULL';
                    return "'" . $conn->real_escape_string($value) . "'";
                }, $row);
                $rows[] = "(" . implode(', ', $values) . ")";
            }
            $sql_dump .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    // Save to file
    if (file_put_contents($filepath, $sql_dump)) {
        $success = "Backup berhasil dibuat: $filename";
        
        // Update backups list
        $backups = array_merge([[
            'name' => $filename,
            'size' => filesize($filepath),
            'date' => date('Y-m-d H:i:s')
        ]], $backups);
    } else {
        $error = "Gagal membuat backup!";
    }
}

// Handle delete backup
if (isset($_POST['delete_backup'])) {
    $filename = $_POST['filename'];
    $filepath = $backup_dir . $filename;
    
    if (file_exists($filepath) && unlink($filepath)) {
        $success = "Backup berhasil dihapus: $filename";
        
        // Remove from list
        $backups = array_filter($backups, function($backup) use ($filename) {
            return $backup['name'] != $filename;
        });
    } else {
        $error = "Gagal menghapus backup!";
    }
}

// Handle restore backup
if (isset($_POST['restore_backup'])) {
    $filename = $_POST['filename'];
    $filepath = $backup_dir . $filename;
    
    if (file_exists($filepath)) {
        // Read SQL file
        $sql = file_get_contents($filepath);
        
        // Execute SQL statements
        $conn->multi_query($sql);
        
        // Clear remaining results
        while($conn->more_results()) {
            $conn->next_result();
        }
        
        $success = "Backup berhasil direstore: $filename";
    } else {
        $error = "File backup tidak ditemukan!";
    }
}

// Calculate total backup size
$total_size = 0;
foreach($backups as $backup) {
    $total_size += $backup['size'];
}
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Database Backup & Restore</h1>
        <p class="text-gray-600">Kelola backup database untuk keamanan data</p>
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

    <!-- Backup Stats & Actions -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo count($backups); ?></div>
                    <div class="text-gray-600">Total Backup</div>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-database text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800"><?php echo round($total_size / 1024 / 1024, 2); ?> MB</div>
                    <div class="text-gray-600">Total Size</div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-hdd text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php 
                        $db_size = $conn->query("
                            SELECT SUM(data_length + index_length) as size
                            FROM information_schema.TABLES 
                            WHERE table_schema = DATABASE()
                        ")->fetch_assoc()['size'];
                        echo round($db_size / 1024 / 1024, 2); 
                        ?> MB
                    </div>
                    <div class="text-gray-600">Database Size</div>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-server text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <form method="POST" action="">
                <button type="submit" 
                        name="create_backup"
                        class="w-full h-full flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-plus text-yellow-600 text-xl"></i>
                    </div>
                    <div class="text-gray-800 font-medium">Buat Backup</div>
                </button>
            </form>
        </div>
    </div>

    <!-- Backup Actions -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Aksi Backup</h3>
        
        <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center">
                <a href="backup.php?action=download" 
                   class="inline-flex flex-col items-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-download text-green-600 text-2xl"></i>
                    </div>
                    <div class="font-medium text-gray-800">Download Backup</div>
                    <div class="text-sm text-gray-600">Download file SQL terbaru</div>
                </a>
            </div>
            
            <div class="text-center">
                <button type="button" 
                        onclick="document.getElementById('restoreModal').classList.remove('hidden')"
                        class="inline-flex flex-col items-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-upload text-blue-600 text-2xl"></i>
                    </div>
                    <div class="font-medium text-gray-800">Restore Backup</div>
                    <div class="text-sm text-gray-600">Restore dari file SQL</div>
                </button>
            </div>
            
            <div class="text-center">
                <button type="button" 
                        onclick="document.getElementById('autoBackupModal').classList.remove('hidden')"
                        class="inline-flex flex-col items-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-robot text-purple-600 text-2xl"></i>
                    </div>
                    <div class="font-medium text-gray-800">Auto Backup</div>
                    <div class="text-sm text-gray-600">Jadwal backup otomatis</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Backups List -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Daftar Backup</h2>
                    <p class="text-gray-600 text-sm">Total: <?php echo count($backups); ?> backup</p>
                </div>
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                    Backup otomatis setiap hari jam 00:00
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 text-sm">
                        <th class="px-6 py-3 font-medium">Nama File</th>
                        <th class="px-6 py-3 font-medium">Ukuran</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($backups as $backup): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">
                                <i class="fas fa-file-archive text-gray-400 mr-2"></i>
                                <?php echo $backup['name']; ?>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo round($backup['size'] / 1024, 2); ?> KB
                        </td>
                        
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo date('d/m/Y H:i', strtotime($backup['date'])); ?>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="<?php echo $backup_dir . $backup['name']; ?>" 
                                   download
                                   class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm hover:bg-blue-200">
                                   <i class="fas fa-download"></i>
                                </a>
                                
                                <form method="POST" action="" class="delete-form inline">
                                    <input type="hidden" name="filename" value="<?php echo $backup['name']; ?>">
                                    <button type="submit" 
                                            name="delete_backup"
                                            class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="filename" value="<?php echo $backup['name']; ?>">
                                    <button type="submit" 
                                            name="restore_backup"
                                            onclick="return confirm('PERINGATAN: Restore akan mengganti semua data dengan data backup. Lanjutkan?')"
                                            class="px-3 py-1 bg-green-100 text-green-700 rounded text-sm hover:bg-green-200">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($backups)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-database text-3xl mb-3 text-gray-300"></i>
                            <p>Belum ada backup</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div id="restoreModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Restore Backup</h3>
            <button type="button" 
                    onclick="document.getElementById('restoreModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="p-6 space-y-4">
                <div class="text-center py-4">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800 mb-2">Peringatan!</h4>
                    <p class="text-gray-600 text-sm">
                        Restore backup akan mengganti SEMUA data di database dengan data dari file backup. 
                        Pastikan Anda sudah melakukan backup terbaru sebelum melanjutkan.
                    </p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Pilih Backup File</label>
                    <select name="filename" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">-- Pilih File Backup --</option>
                        <?php foreach($backups as $backup): ?>
                        <option value="<?php echo $backup['name']; ?>">
                            <?php echo $backup['name']; ?> (<?php echo date('d/m/Y H:i', strtotime($backup['date'])); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>Data yang akan hilang:</strong> Semua user baru, pembayaran baru, dan perubahan setelah backup dibuat.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button type="button" 
                        onclick="document.getElementById('restoreModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" 
                        name="restore_backup"
                        onclick="return confirm('APAKAH ANDA YAKIN? Ini akan menghapus semua data saat ini dan mengganti dengan data backup.')"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-upload mr-2"></i>Restore
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Auto Backup Modal -->
<div id="autoBackupModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Auto Backup Settings</h3>
            <button type="button" 
                    onclick="document.getElementById('autoBackupModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <div class="p-6 space-y-6">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" 
                               checked
                               class="rounded text-purple-600 focus:ring-purple-500">
                        <span class="ml-2 text-gray-700">Aktifkan Auto Backup</span>
                    </label>
                    <p class="text-gray-500 text-sm mt-1">Backup otomatis akan dibuat sesuai jadwal</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Frekuensi Backup</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="daily">Setiap Hari</option>
                        <option value="weekly">Setiap Minggu</option>
                        <option value="monthly">Setiap Bulan</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Waktu Backup</label>
                    <input type="time" 
                           value="00:00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Simpan Backup Selama</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="7">7 Hari</option>
                        <option value="30" selected>30 Hari</option>
                        <option value="90">90 Hari</option>
                        <option value="365">1 Tahun</option>
                    </select>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button type="button" 
                        onclick="document.getElementById('autoBackupModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Close modals when clicking outside
document.getElementById('restoreModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

document.getElementById('autoBackupModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Format file sizes
function formatSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Auto backup info
document.addEventListener('DOMContentLoaded', function() {
    const nextBackup = new Date();
    nextBackup.setDate(nextBackup.getDate() + 1);
    nextBackup.setHours(0, 0, 0, 0);
    
    console.log('Auto backup berikutnya:', nextBackup.toLocaleString());
});
</script>

<?php include 'includes/footer.php'; ?>