<?php
$page_title = 'Manajemen Siswa';
include 'partials/header.php';

// Get all students with their schedule info
$stmt = $pdo->query("
    SELECT s.*, j.hari, j.jam_mulai, j.jam_selesai,
           COUNT(sp.id) as total_sessions,
           AVG(sp.nilai_perkembangan) as avg_progress
    FROM siswa s 
    LEFT JOIN jadwal j ON s.jadwal_id = j.id 
    LEFT JOIN student_progress sp ON s.id = sp.siswa_id AND sp.status = 'completed'
    WHERE s.status_pembayaran = 'paid'
    GROUP BY s.id
    ORDER BY s.nama_lengkap
");
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<title>Manajemen Siswa</title>
</head>
<body class="bg-gray-100">
    <header class="relative bg-gradient-to-br from-pink-accent via-pink-dark to-pink-light rounded-b-[35px] shadow-2xl p-6 text-cream z-10 mb-5 animate-slide-in">
        <div class="absolute inset-0 bg-gradient-to-br from-pink-accent/90 to-pink-dark/90 rounded-b-[35px] backdrop-blur-sm"></div>
        <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-bright/20 rounded-full -translate-y-16 translate-x-16 animate-pulse-soft"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-blue-soft/20 rounded-full translate-y-12 -translate-x-12 animate-float"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center">
                <a href="dashboard.php" class="group mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-cream group-hover:scale-110 transition-transform duration-300">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </a>
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-bright to-pink-accent rounded-2xl flex items-center justify-center shadow-lg">
                        <i data-lucide="users" class="w-8 h-8 text-pink-dark"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="font-bold text-xl text-cream drop-shadow-sm">Manajemen Siswa</h1>
                        <p class="text-sm text-cream/80 font-medium">Kelola data dan progress siswa</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow overflow-y-auto p-4 space-y-6 pb-20" style="margin-bottom: 130px;">
        <div class="container mx-auto max-w-6xl">
            
            <!-- Student Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($siswa_list)): ?>
                    <div class="col-span-full text-center py-16">
                        <div class="glass-effect p-8 rounded-2xl">
                            <div class="w-24 h-24 bg-gradient-to-br from-pink-light to-blue-soft rounded-full flex items-center justify-center mx-auto mb-4 opacity-50">
                                <i data-lucide="user-x" class="w-12 h-12 text-pink-dark"></i>
                            </div>
                            <h3 class="text-xl font-bold text-pink-dark mb-2">Belum Ada Siswa</h3>
                            <p class="text-pink-dark/60">Belum ada siswa yang terdaftar</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($siswa_list as $siswa): ?>
                    <div class="glass-effect p-6 rounded-2xl shadow-lg card-hover animate-fade-in">
                        <div class="flex items-center mb-4">
                            <img src="<?php echo htmlspecialchars($siswa['foto_profil'] ? '../' . $siswa['foto_profil'] : '../avaaset/logo-ava.png'); ?>" 
                                 alt="Profile" class="w-16 h-16 rounded-full object-cover border-2 border-pink-accent mr-4">
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-pink-dark"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></h3>
                                <p class="text-sm text-pink-dark/70"><?php echo htmlspecialchars($siswa['email']); ?></p>
                                <?php if ($siswa['hari']): ?>
                                    <p class="text-xs text-blue-600 font-medium">
                                        <?php echo $siswa['hari']; ?> • <?php echo date('H:i', strtotime($siswa['jam_mulai'])); ?>-<?php echo date('H:i', strtotime($siswa['jam_selesai'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Progress Stats -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-gradient-to-r from-green-100 to-green-200 p-3 rounded-xl">
                                <div class="text-xs text-green-700 font-medium">Sessions</div>
                                <div class="text-lg font-bold text-green-800"><?php echo $siswa['total_sessions'] ?? 0; ?></div>
                            </div>
                            <div class="bg-gradient-to-r from-blue-100 to-blue-200 p-3 rounded-xl">
                                <div class="text-xs text-blue-700 font-medium">Avg Score</div>
                                <div class="text-lg font-bold text-blue-800"><?php echo $siswa['avg_progress'] ? round($siswa['avg_progress'], 1) : 0; ?></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-2">
                            <a href="siswa_absensi.php?id=<?php echo $siswa['id']; ?>" 
                               class="bg-gradient-to-r from-blue-500 to-blue-600 text-white text-center py-2 px-3 rounded-lg hover:shadow-lg transition-all text-sm font-medium">
                                <i data-lucide="calendar-check" class="w-4 h-4 inline mr-1"></i>Absensi
                            </a>
                            <a href="siswa_progress.php?id=<?php echo $siswa['id']; ?>" 
                               class="bg-gradient-to-r from-green-500 to-green-600 text-white text-center py-2 px-3 rounded-lg hover:shadow-lg transition-all text-sm font-medium">
                                <i data-lucide="trending-up" class="w-4 h-4 inline mr-1"></i>Progress
                            </a>
                            <a href="siswa_report.php?id=<?php echo $siswa['id']; ?>" 
                               class="bg-gradient-to-r from-purple-500 to-purple-600 text-white text-center py-2 px-3 rounded-lg hover:shadow-lg transition-all text-sm font-medium">
                                <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>Report
                            </a>
                            <a href="siswa_dokumentasi.php?id=<?php echo $siswa['id']; ?>" 
                               class="bg-gradient-to-r from-orange-500 to-orange-600 text-white text-center py-2 px-3 rounded-lg hover:shadow-lg transition-all text-sm font-medium">
                                <i data-lucide="folder" class="w-4 h-4 inline mr-1"></i>Docs
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'partials/footer.php'; ?>
</body>
</html>