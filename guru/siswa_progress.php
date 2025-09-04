<?php
$page_title = 'Progress Siswa';
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

// Handle progress update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $progress_id = $_POST['progress_id'];
    $nilai_perkembangan = $_POST['nilai_perkembangan'];
    $keterangan = $_POST['keterangan'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE student_progress 
            SET nilai_perkembangan = ?, keterangan = ? 
            WHERE id = ? AND siswa_id = ?
        ");
        $stmt->execute([$nilai_perkembangan, $keterangan, $progress_id, $siswa_id]);
        $success_message = "Progress berhasil diperbarui";
    } catch (Exception $e) {
        $error_message = "Gagal memperbarui progress: " . $e->getMessage();
    }
}

// Handle delete progress
if (isset($_GET['delete']) && isset($_GET['progress_id'])) {
    $progress_id = $_GET['progress_id'];
    $stmt = $pdo->prepare("DELETE FROM student_progress WHERE id = ? AND siswa_id = ?");
    $stmt->execute([$progress_id, $siswa_id]);
    header("Location: siswa_progress.php?id=$siswa_id");
    exit;
}

// Get progress data
$stmt = $pdo->prepare("
    SELECT * FROM student_progress 
    WHERE siswa_id = ? 
    ORDER BY session_date DESC, checkin_time DESC
");
$stmt->execute([$siswa_id]);
$progress_list = $stmt->fetchAll();

// Calculate stats
$stmt = $pdo->prepare("
    SELECT 
        AVG(nilai_perkembangan) as avg_progress,
        COUNT(*) as total_sessions,
        MAX(nilai_perkembangan) as best_score,
        MIN(nilai_perkembangan) as lowest_score
    FROM student_progress 
    WHERE siswa_id = ? AND status = 'completed' AND nilai_perkembangan IS NOT NULL
");
$stmt->execute([$siswa_id]);
$stats = $stmt->fetch();
?>

<title>Progress - <?php echo htmlspecialchars($siswa['nama_lengkap']); ?></title>
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
                        <h1 class="font-bold text-xl text-cream drop-shadow-sm">Progress Data</h1>
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

        <!-- Progress Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="glass-effect p-4 rounded-2xl shadow-lg text-center">
                <div class="text-2xl font-bold text-pink-dark"><?php echo $stats['avg_progress'] ? round($stats['avg_progress'], 1) : 0; ?></div>
                <div class="text-sm text-pink-dark/70">Rata-rata</div>
            </div>
            <div class="glass-effect p-4 rounded-2xl shadow-lg text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['best_score'] ?? 0; ?></div>
                <div class="text-sm text-pink-dark/70">Tertinggi</div>
            </div>
            <div class="glass-effect p-4 rounded-2xl shadow-lg text-center">
                <div class="text-2xl font-bold text-orange-600"><?php echo $stats['lowest_score'] ?? 0; ?></div>
                <div class="text-sm text-pink-dark/70">Terendah</div>
            </div>
            <div class="glass-effect p-4 rounded-2xl shadow-lg text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['total_sessions'] ?? 0; ?></div>
                <div class="text-sm text-pink-dark/70">Total Sesi</div>
            </div>
        </div>

        <!-- Progress History -->
        <div class="glass-effect p-6 rounded-2xl shadow-lg">
            <h3 class="text-lg font-semibold text-pink-dark mb-4">Riwayat Progress</h3>
            
            <?php if (empty($progress_list)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-pink-light/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="trending-up" class="w-8 h-8 text-pink-dark/50"></i>
                    </div>
                    <p class="text-pink-dark/60">Belum ada data progress</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($progress_list as $progress): ?>
                        <div class="border border-pink-light/30 rounded-xl p-4 hover:bg-pink-light/10 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-semibold text-pink-dark"><?php echo date('d F Y', strtotime($progress['session_date'])); ?></h4>
                                    <p class="text-sm text-pink-dark/70">
                                        <?php echo date('H:i', strtotime($progress['checkin_time'])); ?> - 
                                        <?php echo $progress['checkout_time'] ? date('H:i', strtotime($progress['checkout_time'])) : 'Ongoing'; ?>
                                    </p>
                                    <span class="inline-block px-2 py-1 text-xs rounded-full <?php echo $progress['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($progress['status']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <?php if ($progress['nilai_perkembangan']): ?>
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-pink-accent"><?php echo $progress['nilai_perkembangan']; ?></div>
                                            <div class="text-xs text-pink-dark/70">Score</div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($progress['status'] === 'completed'): ?>
                                        <button onclick="editProgress(<?php echo htmlspecialchars(json_encode($progress)); ?>)" 
                                                class="text-blue-500 hover:text-blue-700 p-1">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        <a href="siswa_progress.php?id=<?php echo $siswa_id; ?>&delete=1&progress_id=<?php echo $progress['id']; ?>" 
                                           onclick="return confirm('Hapus data progress ini?')"
                                           class="text-red-500 hover:text-red-700 p-1">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($progress['keterangan']): ?>
                                <div class="bg-cream/50 p-3 rounded-lg mt-3">
                                    <p class="text-sm text-pink-dark"><?php echo htmlspecialchars($progress['keterangan']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Edit Progress Modal -->
    <div id="editProgressModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Edit Progress</h3>
            </div>
            <form method="POST">
                <div class="px-6 py-4">
                    <input type="hidden" name="progress_id" id="edit_progress_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Perkembangan (1-100)</label>
                        <input type="number" name="nilai_perkembangan" id="edit_nilai" min="1" max="100" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-accent focus:border-pink-accent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" rows="4" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-accent focus:border-pink-accent"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button type="button" onclick="closeEditModal()" 
                            class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Batal
                    </button>
                    <button type="submit" name="update_progress"
                            class="bg-pink-accent text-white px-4 py-2 rounded hover:bg-pink-dark">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editProgress(progress) {
            document.getElementById('edit_progress_id').value = progress.id;
            document.getElementById('edit_nilai').value = progress.nilai_perkembangan;
            document.getElementById('edit_keterangan').value = progress.keterangan;
            
            document.getElementById('editProgressModal').classList.remove('hidden');
            document.getElementById('editProgressModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editProgressModal').classList.add('hidden');
            document.getElementById('editProgressModal').classList.remove('flex');
        }
    </script>

    <?php include 'partials/footer.php'; ?>
</body>
</html>