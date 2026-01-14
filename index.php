<?php
session_start();
require_once 'config/database.php';

// Get landing page content from database
$db = new Database();
$conn = $db->getConnection();

// Get hero section
$hero_query = "SELECT * FROM landing_content WHERE section='hero' AND is_active=1 ORDER BY display_order LIMIT 1";
$hero_result = $conn->query($hero_query);
$hero = $hero_result->fetch_assoc();

// Get about section
$about_query = "SELECT * FROM landing_content WHERE section='about' AND is_active=1 ORDER BY display_order LIMIT 1";
$about_result = $conn->query($about_query);
$about = $about_result->fetch_assoc();

// Get features
$features_query = "SELECT * FROM landing_content WHERE section='features' AND is_active=1 ORDER BY display_order";
$features_result = $conn->query($features_query);

// Get ustadz data
$ustadz_query = "SELECT * FROM ustadz_data WHERE is_active=1 ORDER BY id";
$ustadz_result = $conn->query($ustadz_query);

// Get testimonials
$testimonial_query = "SELECT * FROM testimonials WHERE is_active=1 ORDER BY id";
$testimonial_result = $conn->query($testimonial_query);

// Get pricing section
$pricing_query = "SELECT * FROM landing_content WHERE section='pricing' AND is_active=1 ORDER BY display_order LIMIT 1";
$pricing_result = $conn->query($pricing_query);
$pricing = $pricing_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taaruf Islami - Jalan Menuju Pernikahan Barakah</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .font-arabic {
            font-family: 'Amiri', serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #fdf2f8 0%, #f0f9ff 100%);
        }
        .islamic-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23fbb6ce' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-section {
            min-height: 80vh;
            position: relative;
            overflow: hidden;
        }
        .floating-element {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .card-hover:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header/Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="text-2xl font-bold text-pink-600">
                        <i class="fas fa-heart mr-2"></i>
                        <span class="font-arabic">تعارف</span>
                        <span class="text-gray-800">Islami</span>
                    </div>
                </div>
                
                <div class="hidden md:flex space-x-8">
                    <a href="#home" class="text-gray-700 hover:text-pink-600 transition">Beranda</a>
                    <a href="#about" class="text-gray-700 hover:text-pink-600 transition">Tentang</a>
                    <a href="#features" class="text-gray-700 hover:text-pink-600 transition">Fitur</a>
                    <a href="#ustadz" class="text-gray-700 hover:text-pink-600 transition">Ustadz</a>
                    <a href="#testimonial" class="text-gray-700 hover:text-pink-600 transition">Testimoni</a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="auth/login.php" 
                       class="text-gray-700 hover:text-pink-600 transition">
                       <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="#register" 
                       class="bg-pink-600 text-white px-6 py-2 rounded-full hover:bg-pink-700 transition shadow-lg">
                       <i class="fas fa-user-plus mr-2"></i>Daftar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section gradient-bg islamic-pattern pt-20">
        <div class="container mx-auto px-4 py-16">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-12 md:mb-0">
                    <div class="floating-element">
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-6">
                            <?php echo htmlspecialchars($hero['title'] ?? 'Temukan Pasangan Hidup yang Soleh/Solehah'); ?>
                        </h1>
                        <p class="text-lg text-gray-600 mb-8">
                            <?php echo htmlspecialchars($hero['content'] ?? 'Platform taaruf Islami yang amanah dan profesional. Membantu Anda menemukan calon pasangan dengan nilai-nilai Islam.'); ?>
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <a href="#register" 
                               class="bg-pink-600 text-white px-8 py-3 rounded-full hover:bg-pink-700 transition shadow-lg text-lg">
                               <i class="fas fa-heart mr-2"></i>Mulai Taaruf
                            </a>
                            <a href="#features" 
                               class="border-2 border-pink-600 text-pink-600 px-8 py-3 rounded-full hover:bg-pink-50 transition text-lg">
                               <i class="fas fa-play-circle mr-2"></i>Lihat Fitur
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="md:w-1/2 relative">
                    <div class="relative">
                        <!-- Decorative elements -->
                        <div class="absolute -top-6 -left-6 w-24 h-24 bg-yellow-400 rounded-full opacity-20"></div>
                        <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-blue-400 rounded-full opacity-20"></div>
                        
                        <!-- Main image/illustration -->
                        <div class="relative bg-white rounded-2xl shadow-2xl p-8 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                            <div class="text-center">
                                <i class="fas fa-heart text-pink-600 text-6xl mb-4"></i>
                                <h3 class="text-2xl font-bold text-gray-800 mb-2">Proses Syar'i</h3>
                                <p class="text-gray-600">Dibimbing oleh ustadz berpengalaman</p>
                            </div>
                            
                            <!-- Stats -->
                            <div class="grid grid-cols-2 gap-4 mt-8">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-pink-600">100+</div>
                                    <div class="text-gray-600">Anggota</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-blue-600">20+</div>
                                    <div class="text-gray-600">Pernikahan</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Floating element -->
                        <div class="absolute -bottom-4 -right-4 bg-white rounded-xl shadow-xl p-4 w-32">
                            <div class="flex items-center">
                                <div class="bg-green-100 p-2 rounded-lg">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="font-bold text-sm">Amanah</div>
                                    <div class="text-xs text-gray-500">Terpercaya</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                    <?php echo htmlspecialchars($about['title'] ?? 'Mengapa Memilih Kami?'); ?>
                </h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    <?php echo htmlspecialchars($about['content'] ?? 'Kami hadir dengan sistem yang syar\'i, didampingi ustadz berkompeten, dan proses yang transparan.'); ?>
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="card-hover bg-pink-50 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-mosque text-pink-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Bimbingan Syar'i</h3>
                    <p class="text-gray-600">Dipandu oleh ustadz yang kompeten dalam bidang pernikahan Islam</p>
                </div>
                
                <div class="card-hover bg-blue-50 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Privasi Terjaga</h3>
                    <p class="text-gray-600">Data pribadi Anda aman dan hanya dibagikan dengan izin</p>
                </div>
                
                <div class="card-hover bg-yellow-50 rounded-xl p-8 text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-users text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Kualitas Anggota</h3>
                    <p class="text-gray-600">Sistem screening yang ketat untuk menjaga kualitas peserta</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gradient-to-b from-white to-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                    <?php 
                        $features_title = $features_result->fetch_assoc()['title'] ?? 'Fitur Utama Kami';
                        echo htmlspecialchars($features_title);
                    ?>
                </h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Kami menyediakan berbagai fitur untuk mendukung perjalanan taaruf Anda</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="card-hover bg-white rounded-xl p-6 shadow-lg">
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-graduation-cap text-pink-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Kuis Pernikahan</h3>
                    <p class="text-gray-600 text-sm">Test pemahaman tentang pernikahan dalam Islam</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="card-hover bg-white rounded-xl p-6 shadow-lg">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">CV Taaruf</h3>
                    <p class="text-gray-600 text-sm">Buat profil lengkap dengan panduan Islami</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="card-hover bg-white rounded-xl p-6 shadow-lg">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-handshake text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Sistem Matching</h3>
                    <p class="text-gray-600 text-sm">Pencocokan oleh admin yang berpengalaman</p>
                </div>
                
                <!-- Feature 4 -->
                <div class="card-hover bg-white rounded-xl p-6 shadow-lg">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-user-shield text-yellow-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Pendampingan Ustadz</h3>
                    <p class="text-gray-600 text-sm">Konsultasi dengan ustadz selama proses</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Ustadz Section -->
    <section id="ustadz" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Ustadz Pendamping Kami</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Tim ustadz kami siap membimbing proses taaruf Anda sesuai syariat Islam</p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <?php while($ustadz = $ustadz_result->fetch_assoc()): ?>
                <div class="card-hover bg-gradient-to-br from-pink-50 to-white rounded-xl p-8 shadow-lg">
                    <div class="flex items-start">
                        <div class="w-20 h-20 bg-gray-200 rounded-full overflow-hidden mr-6">
                            <?php if(!empty($ustadz['foto'])): ?>
                                <img src="assets/images/<?php echo htmlspecialchars($ustadz['foto']); ?>" 
                                     alt="<?php echo htmlspecialchars($ustadz['nama']); ?>" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-pink-100 flex items-center justify-center">
                                    <i class="fas fa-user text-pink-400 text-3xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($ustadz['nama']); ?></h3>
                            <p class="text-pink-600 mb-3"><?php echo htmlspecialchars($ustadz['bidang'] ?? 'Pernikahan Islami'); ?></p>
                            <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($ustadz['description']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonial" class="py-20 bg-gradient-to-b from-white to-pink-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Testimonial</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Kisah sukses para peserta yang telah menemukan pasangan melalui sistem kami</p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 max-w-6xl mx-auto">
                <?php while($testimonial = $testimonial_result->fetch_assoc()): ?>
                <div class="card-hover bg-white rounded-xl p-8 shadow-lg">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user text-pink-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($testimonial['nama']); ?></h4>
                            <div class="flex text-yellow-400">
                                <?php for($i = 0; $i < $testimonial['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"<?php echo htmlspecialchars($testimonial['content']); ?>"</p>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section id="register" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="bg-gradient-to-r from-pink-500 to-purple-600 rounded-2xl shadow-2xl overflow-hidden">
                    <div class="md:flex">
                        <!-- Left Side - Form -->
                        <div class="md:w-1/2 bg-white p-8 md:p-12">
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">Bergabung Sekarang</h2>
                            <p class="text-gray-600 mb-8">Mulai perjalanan taaruf Anda dengan cara yang syar'i</p>
                            
                            <form action="auth/register.php" method="POST" id="registrationForm">
                                <div class="grid md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nama">Nama Lengkap *</label>
                                        <input type="text" 
                                               id="nama" 
                                               name="nama"
                                               required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email *</label>
                                        <input type="email" 
                                               id="email" 
                                               name="email"
                                               required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password *</label>
                                    <input type="password" 
                                           id="password" 
                                           name="password"
                                           required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="jenis_kelamin">Jenis Kelamin *</label>
                                        <select id="jenis_kelamin" 
                                                name="jenis_kelamin"
                                                required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                            <option value="">Pilih</option>
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="no_wa">Nomor WhatsApp *</label>
                                        <input type="tel" 
                                               id="no_wa" 
                                               name="no_wa"
                                               required
                                               placeholder="628123456789"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               required
                                               class="rounded text-pink-600 focus:ring-pink-500">
                                        <span class="ml-2 text-sm text-gray-700">
                                            Saya menyetujui <a href="#" class="text-pink-600 hover:underline">syarat dan ketentuan</a>
                                        </span>
                                    </label>
                                </div>
                                
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-pink-600 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:opacity-90 transition shadow-lg">
                                    <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                                </button>
                                
                                <p class="text-center text-gray-600 mt-4 text-sm">
                                    Sudah punya akun? 
                                    <a href="auth/login.php" class="text-pink-600 hover:underline">Login disini</a>
                                </p>
                            </form>
                        </div>
                        
                        <!-- Right Side - Info -->
                        <div class="md:w-1/2 bg-gradient-to-br from-pink-500 to-purple-600 p-8 md:p-12 text-white">
                            <div class="h-full flex flex-col justify-center">
                                <h3 class="text-2xl font-bold mb-6">Mengapa Berbayar?</h3>
                                <p class="mb-6">
                                    <?php echo htmlspecialchars($pricing['content'] ?? 'Biaya untuk operasional sistem, konsultasi ustadz, dan fasilitas pertemuan yang syar\'i'); ?>
                                </p>
                                
                                <div class="space-y-4 mb-8">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-check text-sm"></i>
                                        </div>
                                        <span>Konsultasi dengan ustadz berpengalaman</span>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-check text-sm"></i>
                                        </div>
                                        <span>Proses matching yang terpercaya</span>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-check text-sm"></i>
                                        </div>
                                        <span>Pendampingan hingga proses pernikahan</span>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-check text-sm"></i>
                                        </div>
                                        <span>Privasi dan keamanan data terjamin</span>
                                    </div>
                                </div>
                                
                                <div class="bg-white/10 rounded-xl p-6">
                                    <p class="text-sm mb-2">Investasi untuk masa depan yang barakah:</p>
                                    <div class="text-3xl font-bold">Rp 500.000<span class="text-lg font-normal">/proses</span></div>
                                    <p class="text-sm mt-2 opacity-90">* Biaya sekali seumur hidup</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="text-2xl font-bold text-white mb-4">
                        <i class="fas fa-heart mr-2"></i>
                        <span class="font-arabic">تعارف</span>
                        Islami
                    </div>
                    <p class="text-gray-400 text-sm">Platform taaruf Islami yang amanah dan profesional.</p>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Tautan Cepat</h4>
                    <ul class="space-y-2">
                        <li><a href="#home" class="text-gray-400 hover:text-white transition">Beranda</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white transition">Tentang</a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-white transition">Fitur</a></li>
                        <li><a href="#ustadz" class="text-gray-400 hover:text-white transition">Ustadz</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Kontak</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3"></i>
                            <span>+62 812 3456 7890</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3"></i>
                            <span>info@taarufislami.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-3"></i>
                            <span>Jakarta, Indonesia</span>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Ikuti Kami</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-pink-600 transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-blue-600 transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-blue-400 transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-green-500 transition">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; <?php echo date('Y'); ?> Taaruf Islami. Hak cipta dilindungi.</p>
                <p class="mt-2">Dibuat dengan <i class="fas fa-heart text-pink-500 mx-1"></i> untuk ummat Islam</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript for smooth scrolling -->
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if(password.length < 6) {
                e.preventDefault();
                alert('Password harus minimal 6 karakter');
                return false;
            }
            
            const whatsapp = document.getElementById('no_wa').value;
            if(!whatsapp.match(/^[0-9]+$/)) {
                e.preventDefault();
                alert('Nomor WhatsApp harus berupa angka');
                return false;
            }
        });
    </script>
</body>
</html>