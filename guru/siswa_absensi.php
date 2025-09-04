<?php
$page_title = 'Absensi Siswa';
include 'partials/header.php';

$siswa_id = $_GET['id'] ?? null;
if (!$siswa_id) {
    header('Location: siswa.php');
    exit;
}

// Get student info
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ? AND status_pembayaran = 'paid'");
$stmt->execute([$siswa_id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    header('Location: siswa.php');
    exit;
}

// Handle manual attendance entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_attendance'])) {
    $tanggal = $_POST['tanggal'];
    $waktu = $_POST['waktu'];
    $keterangan = $_POST['keterangan'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO absensi (siswa_id, tanggal, waktu, keterangan) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE waktu = VALUES(waktu), keterangan = VALUES(keterangan)
        ");
        $stmt->execute([$siswa_id, $tanggal, $waktu, $keterangan]);
        $success_message = "Absensi berhasil ditambahkan";
    } catch (Exception $e) {
        $error_message = "Gagal menambahkan absensi: " . $e->getMessage();
    }
}

// Handle delete attendance
if (isset($_GET['delete']) && isset($_GET['attendance_id'])) {
    $attendance_id = $_GET['attendance_id'];
    $stmt = $pdo->prepare("DELETE FROM absensi WHERE id = ? AND siswa_id = ?");
    $stmt->execute([$attendance_id, $siswa_id]);
    header("Location: siswa_absensi.php?id=$siswa_id");
    exit;
}

// Get attendance history
$stmt = $pdo->prepare("
    SELECT * FROM absensi 
    WHERE siswa_id = ? 
    ORDER BY tanggal DESC, waktu DESC
    LIMIT 50
");
$stmt->execute([$siswa_id]);
$absensi_list = $stmt->fetchAll();

// Calculate attendance stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_hadir,
        COUNT(CASE WHEN MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE()) THEN 1 END) as bulan_ini
    FROM absensi 
    WHERE siswa_id = ?
");
$stmt->execute([$siswa_id]);
$stats = $stmt->fetch();
?>

<title>Absensi - <?php echo htmlspecialchars($siswa['nama_lengkap']); ?></title>
</head>
<body class="bg-gray-100" style="margin-bottom: 130px;">
    <header class="relative bg-gradient-to-br from-pink-accent via-pink-dark to-pink-light rounded-b-[35px] shadow-2xl p-6 text-cream z-10 mb-5 animate-slide-in">
        <div class="absolute inset-0 bg-gradient-to-br from-pink-accent/90 to-pink-dark/90 rounded-b-[35px] backdrop-blur-sm"></div>
        <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-bright/20 rounded-full -translate-y-16 translate-x-16 animate-pulse-soft"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-blue-soft/20 rounded-full translate-y-12 -translate-x-12 animate-float"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center">
                <a href="siswa.php" class="group mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-cream group-hover:scale-110 transition-transform duration-300">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </a>
                <div class="flex items-center">
                    <img src="<?php echo htmlspecialchars($siswa['foto_profil'] ? '../' . $siswa['foto_profil'] : '../avaaset/logo-ava.png'); ?>" 
                         alt="Profile" class="h-16 w-16 rounded-2xl border-3 border-cream/50 object-cover shadow-lg" />
                    <div class="ml-4">
                        <h1 class="font-bold text-xl text-cream drop-shadow-sm">Absensi</h1>
                        <p class="text-sm text-cream/80 font-medium"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4 pb-24">
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="glass-effect p-4 rounded-2xl shadow-lg">
                <div class="text-center">
                    <div class="text-2xl font-bold text-pink-dark"><?php echo $stats['total_hadir']; ?></div>
                    <div class="text-sm text-pink-dark/70">Total Kehadiran</div>
                </div>
            </div>
            <div class="glass-effect p-4 rounded-2xl shadow-lg">
                <div class="text-center">
                    <div class="text-2xl font-bold text-pink-dark"><?php echo $stats['bulan_ini']; ?></div>
                    <div class="text-sm text-pink-dark/70">Bulan Ini</div>
                </div>
            </div>
        </div>

        <!-- Add Attendance Form -->
        <div class="glass-effect p-6 rounded-2xl shadow-lg mb-6">
            <h3 class="text-lg font-semibold text-pink-dark mb-4">Tambah Absensi Manual</h3>
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-pink-dark mb-2">Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-accent focus:border-pink-accent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-pink-dark mb-2">Waktu</label>
                        <input type="time" name="waktu" value="<?php echo date('H:i'); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-accent focus:border-pink-accent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-pink-dark mb-2">Keterangan</label>
                        <select name="keterangan" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-accent focus:border-pink-accent">
                            <option value="Hadir">Hadir</option>
                            <option value="Terlambat">Terlambat</option>
                            <option value="Izin">Izin</option>
                            <option value="Sakit">Sakit</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_attendance" 
                        class="bg-gradient-to-r from-pink-accent to-pink-dark text-cream px-6 py-2 rounded-lg hover:shadow-lg transition-all font-medium">
                    <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>Tambah Absensi
                </button>
            </form>
        </div>

        <!-- Attendance History -->
        <div class="glass-effect p-6 rounded-2xl shadow-lg">
            <h3 class="text-lg font-semibold text-pink-dark mb-4">Riwayat Absensi</h3>
            
            <?php if (empty($absensi_list)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-pink-light/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="calendar-x" class="w-8 h-8 text-pink-dark/50"></i>
                    </div>
                    <p class="text-pink-dark/60">Belum ada data absensi</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($absensi_list as $absensi): ?>
                        <div class="flex items-center justify-between bg-cream/50 p-4 rounded-xl border border-pink-light/30">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-pink-accent to-pink-dark rounded-xl flex items-center justify-center mr-4">
                                    <i data-lucide="calendar" class="w-6 h-6 text-cream"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-pink-dark"><?php echo date('d M Y', strtotime($absensi['tanggal'])); ?></p>
                                    <p class="text-sm text-pink-dark/70"><?php echo date('H:i', strtotime($absensi['waktu'])); ?> • <?php echo htmlspecialchars($absensi['keterangan']); ?></p>
                                </div>
                            </div>
                            <a href="siswa_absensi.php?id=<?php echo $siswa_id; ?>&delete=1&attendance_id=<?php echo $absensi['id']; ?>" 
                               onclick="return confirm('Hapus data absensi ini?')"
                               class="text-red-500 hover:text-red-700 p-2">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'partials/footer.php'; ?>
</body>
</html>