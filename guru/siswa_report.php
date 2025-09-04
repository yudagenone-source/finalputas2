<?php
$page_title = 'Report Siswa';
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

// Get comprehensive report data
$stmt = $pdo->prepare("
    SELECT 
        sp.*,
        a.tanggal as absensi_tanggal,
        a.keterangan as absensi_keterangan
    FROM student_progress sp
    LEFT JOIN absensi a ON sp.siswa_id = a.siswa_id AND sp.session_date = a.tanggal
    WHERE sp.siswa_id = ? AND sp.status = 'completed'
    ORDER BY sp.session_date DESC
");
$stmt->execute([$siswa_id]);
$report_data = $stmt->fetchAll();

// Calculate comprehensive stats
$stmt = $pdo->prepare("
    SELECT 
        AVG(nilai_perkembangan) as avg_progress,
        COUNT(*) as total_sessions,
        MAX(nilai_perkembangan) as best_score,
        MIN(nilai_perkembangan) as lowest_score,
        COUNT(CASE WHEN nilai_perkembangan >= 80 THEN 1 END) as excellent_sessions,
        COUNT(CASE WHEN nilai_perkembangan >= 60 AND nilai_perkembangan < 80 THEN 1 END) as good_sessions,
        COUNT(CASE WHEN nilai_perkembangan < 60 THEN 1 END) as needs_improvement
    FROM student_progress 
    WHERE siswa_id = ? AND status = 'completed' AND nilai_perkembangan IS NOT NULL
");
$stmt->execute([$siswa_id]);
$comprehensive_stats = $stmt->fetch();

// Get attendance stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_attendance,
        COUNT(CASE WHEN keterangan = 'Hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN keterangan = 'Terlambat' THEN 1 END) as terlambat,
        COUNT(CASE WHEN keterangan = 'Izin' THEN 1 END) as izin,
        COUNT(CASE WHEN keterangan = 'Sakit' THEN 1 END) as sakit
    FROM absensi 
    WHERE siswa_id = ?
");
$stmt->execute([$siswa_id]);
$attendance_stats = $stmt->fetch();
?>

<title>Report - <?php echo htmlspecialchars($siswa['nama_lengkap']); ?></title>
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
                        <h1 class="font-bold text-xl text-cream drop-shadow-sm">Student Report</h1>
                        <p class="text-sm text-cream/80 font-medium"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></p>
                    </div>
                </div>
            </div>
            <button onclick="generatePDF()" class="bg-cream/20 text-cream px-4 py-2 rounded-xl hover:bg-cream/30 transition-all">
                <i data-lucide="download" class="w-4 h-4 inline mr-2"></i>PDF
            </button>
        </div>
    </header>

    <main class="container mx-auto p-4 pb-24" id="report-content">
        <!-- Overall Performance -->
        <div class="glass-effect p-6 rounded-2xl shadow-lg mb-6">
            <h3 class="text-lg font-semibold text-pink-dark mb-4">Overall Performance</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-pink-accent"><?php echo $comprehensive_stats['avg_progress'] ? round($comprehensive_stats['avg_progress'], 1) : 0; ?></div>
                    <div class="text-sm text-pink-dark/70">Average Score</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600"><?php echo $comprehensive_stats['excellent_sessions'] ?? 0; ?></div>
                    <div class="text-sm text-pink-dark/70">Excellent (80+)</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600"><?php echo $comprehensive_stats['good_sessions'] ?? 0; ?></div>
                    <div class="text-sm text-pink-dark/70">Good (60-79)</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-orange-600"><?php echo $comprehensive_stats['needs_improvement'] ?? 0; ?></div>
                    <div class="text-sm text-pink-dark/70">Needs Work (<60)</div>
                </div>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="glass-effect p-6 rounded-2xl shadow-lg mb-6">
            <h3 class="text-lg font-semibold text-pink-dark mb-4">Attendance Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $attendance_stats['hadir'] ?? 0; ?></div>
                    <div class="text-sm text-pink-dark/70">Hadir</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo $attendance_stats['terlambat'] ?? 0; ?></div>
                    <div class="text-sm text-pink-dark/70">Terlambat</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $attendance_stats['izin'] ?? 0; ?></div>
                    <div class="text-sm text-pink-dark/70">Izin</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600"><?php echo $attendance_stats['sakit'] ?? 0; ?></div>
                    <div class="text-sm text-pink-dark/70">Sakit</div>
                </div>
            </div>
        </div>

        <!-- Detailed Session Reports -->
        <div class="glass-effect p-6 rounded-2xl shadow-lg">
            <h3 class="text-lg font-semibold text-pink-dark mb-4">Detailed Session Reports</h3>
            
            <?php if (empty($report_data)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-pink-light/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="file-text" class="w-8 h-8 text-pink-dark/50"></i>
                    </div>
                    <p class="text-pink-dark/60">Belum ada data report</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($report_data as $session): ?>
                        <div class="border border-pink-light/30 rounded-xl p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-semibold text-pink-dark"><?php echo date('d F Y', strtotime($session['session_date'])); ?></h4>
                                    <p class="text-sm text-pink-dark/70">
                                        Session: <?php echo date('H:i', strtotime($session['checkin_time'])); ?> - 
                                        <?php echo date('H:i', strtotime($session['checkout_time'])); ?>
                                    </p>
                                    <?php if ($session['absensi_keterangan']): ?>
                                        <span class="inline-block px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 mt-1">
                                            Attendance: <?php echo $session['absensi_keterangan']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-pink-accent"><?php echo $session['nilai_perkembangan']; ?>/100</div>
                                    <div class="text-xs text-pink-dark/70">Performance Score</div>
                                </div>
                            </div>
                            
                            <?php if ($session['keterangan']): ?>
                                <div class="bg-cream/50 p-3 rounded-lg">
                                    <h5 class="font-medium text-pink-dark mb-2">Teacher's Notes:</h5>
                                    <p class="text-sm text-pink-dark/80"><?php echo nl2br(htmlspecialchars($session['keterangan'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Header
            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            doc.text('STUDENT PROGRESS REPORT', 105, 20, { align: 'center' });
            
            doc.setFontSize(14);
            doc.text('Anastasya Vocal Arts', 14, 35);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(12);
            doc.text('Student: <?php echo addslashes($siswa["nama_lengkap"]); ?>', 14, 45);
            doc.text('Generated: <?php echo date("d M Y"); ?>', 14, 55);
            
            // Stats
            let yPos = 70;
            doc.setFont('helvetica', 'bold');
            doc.text('Performance Summary:', 14, yPos);
            yPos += 10;
            
            doc.setFont('helvetica', 'normal');
            doc.text('Average Score: <?php echo $comprehensive_stats["avg_progress"] ? round($comprehensive_stats["avg_progress"], 1) : 0; ?>/100', 14, yPos);
            yPos += 8;
            doc.text('Total Sessions: <?php echo $comprehensive_stats["total_sessions"] ?? 0; ?>', 14, yPos);
            yPos += 8;
            doc.text('Best Score: <?php echo $comprehensive_stats["best_score"] ?? 0; ?>/100', 14, yPos);
            yPos += 8;
            doc.text('Attendance Rate: <?php echo $attendance_stats["hadir"] ?? 0; ?>/<?php echo $attendance_stats["total_attendance"] ?? 0; ?>', 14, yPos);
            
            doc.save('student-report-<?php echo $siswa["nama_lengkap"]; ?>-<?php echo date("Y-m-d"); ?>.pdf');
        }
    </script>

    <?php include 'partials/footer.php'; ?>
</body>
</html>