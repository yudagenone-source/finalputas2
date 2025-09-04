<?php
$page_title = 'Dokumentasi Siswa';
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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documentation_file'])) {
    $upload_dir = '../uploads/documentation/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['documentation_file'];
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? 'general';
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'video/mp4', 'video/webm', 'application/pdf', 'audio/mpeg', 'audio/wav'];
        if (in_array($file['type'], $allowed_types)) {
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'doc_' . $siswa_id . '_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save to database - create documentation table if not exists
                try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS student_documentation (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            siswa_id INT NOT NULL,
                            guru_id INT NOT NULL,
                            file_path VARCHAR(255) NOT NULL,
                            file_type VARCHAR(50) NOT NULL,
                            category VARCHAR(50) DEFAULT 'general',
                            description TEXT,
                            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
                            FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE
                        )
                    ");
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO student_documentation (siswa_id, guru_id, file_path, file_type, category, description) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $file_type = 'other';
                    if (strpos($file['type'], 'image') !== false) $file_type = 'image';
                    elseif (strpos($file['type'], 'video') !== false) $file_type = 'video';
                    elseif (strpos($file['type'], 'audio') !== false) $file_type = 'audio';
                    elseif ($file['type'] === 'application/pdf') $file_type = 'pdf';
                    
                    $stmt->execute([$siswa_id, $_SESSION['guru_id'], 'uploads/documentation/' . $filename, $file_type, $category, $description]);
                    
                    $upload_message = 'File berhasil diupload!';
                } catch (Exception $e) {
                    $upload_message = 'Gagal menyimpan ke database: ' . $e->getMessage();
                }
            } else {
                $upload_message = 'Gagal mengupload file.';
            }
        } else {
            $upload_message = 'Tipe file tidak didukung.';
        }
    } else {
        $upload_message = 'Error upload: ' . $file['error'];
    }
}

// Handle delete documentation
if (isset($_GET['delete']) && isset($_GET['doc_id'])) {
    $doc_id = $_GET['delete'];
    
    // Get file path first
    $stmt = $pdo->prepare("SELECT file_path FROM student_documentation WHERE id = ? AND siswa_id = ?");
    $stmt->execute([$doc_id, $siswa_id]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        // Delete file
        if (file_exists('../' . $doc['file_path'])) {
            unlink('../' . $doc['file_path']);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM student_documentation WHERE id = ? AND siswa_id = ?");
        $stmt->execute([$doc_id, $siswa_id]);
    }
    
    header("Location: siswa_dokumentasi.php?id=$siswa_id");
    exit;
}

// Get existing documentation
$stmt = $pdo->prepare("
    SELECT d.*, g.nama_lengkap as guru_name
    FROM student_documentation d
    JOIN guru g ON d.guru_id = g.id
    WHERE d.siswa_id = ?
    ORDER BY d.upload_date DESC
");
$stmt->execute([$siswa_id]);
$documentation_list = $stmt->fetchAll();
?>

<title>Dokumentasi - <?php echo htmlspecialchars($siswa['nama_lengkap']); ?></title>
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
                        <h1 class="font-bold text-xl text-cream drop-shadow-sm">Dokumentasi</h1>
                        <p class="text-sm text-cream/80 font-medium"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4 pb-24">
        <?php if (isset($upload_message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo strpos($upload_message, 'berhasil') !== false ? 'bg-green-100 border border-green-300 text-green-800' : 'bg-red-100 border border-red-300 text-red-800'; ?>">
                <?php echo $upload_message; ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="glass-effect p-6 rounded-2xl shadow-lg mb-6">
            <h3 class="text-lg font-semibold text-pink-dark mb-4">Upload Dokumentasi Baru</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-pink-dark mb-2">Kategori</label>
                        <select name="category" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-accent focus:border-pink-accent">
                            <option value="general">General</option>
                            <option value="performance">Performance</option>
                            <option value="homework">Homework</option>
                            <option value="achievement">Achievement</option>
                            <option value="practice">Practice Session</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-pink-dark mb-2">File</label>
                        <input type="file" name="documentation_file" required
                               accept="image/*,video/*,audio/*,.pdf"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-accent focus:border-pink-accent">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-pink-dark mb-2">Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Deskripsi dokumentasi..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-accent focus:border-pink-accent"></textarea>
                </div>
                
                <button type="submit" class="bg-gradient-to-r from-pink-accent to-pink-dark text-cream px-6 py-2 rounded-lg hover:shadow-lg transition-all font-medium">
                    <i data-lucide="upload" class="w-4 h-4 inline mr-2"></i>Upload Dokumentasi
                </button>
            </form>
        </div>

        <!-- Documentation Grid -->
        <div class="glass-effect p-6 rounded-2xl shadow-lg">
            <h3 class="text-lg font-semibold text-pink-dark mb-4">Dokumentasi Siswa</h3>
            
            <?php if (empty($documentation_list)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-pink-light/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="folder-open" class="w-8 h-8 text-pink-dark/50"></i>
                    </div>
                    <p class="text-pink-dark/60">Belum ada dokumentasi</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($documentation_list as $doc): ?>
                        <div class="border border-pink-light/30 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="relative">
                                <?php if ($doc['file_type'] === 'image'): ?>
                                    <img src="../<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                         alt="Documentation" class="w-full h-32 object-cover">
                                    <div class="absolute top-2 right-2 bg-blue-500 text-white px-2 py-1 rounded-full text-xs">
                                        <i data-lucide="image" class="w-3 h-3 inline mr-1"></i>Image
                                    </div>
                                <?php elseif ($doc['file_type'] === 'video'): ?>
                                    <video class="w-full h-32 object-cover">
                                        <source src="../<?php echo htmlspecialchars($doc['file_path']); ?>" type="video/mp4">
                                    </video>
                                    <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs">
                                        <i data-lucide="video" class="w-3 h-3 inline mr-1"></i>Video
                                    </div>
                                <?php elseif ($doc['file_type'] === 'audio'): ?>
                                    <div class="w-full h-32 bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center">
                                        <i data-lucide="music" class="w-12 h-12 text-purple-600"></i>
                                    </div>
                                    <div class="absolute top-2 right-2 bg-purple-500 text-white px-2 py-1 rounded-full text-xs">
                                        <i data-lucide="music" class="w-3 h-3 inline mr-1"></i>Audio
                                    </div>
                                <?php else: ?>
                                    <div class="w-full h-32 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                        <i data-lucide="file" class="w-12 h-12 text-gray-600"></i>
                                    </div>
                                    <div class="absolute top-2 right-2 bg-gray-500 text-white px-2 py-1 rounded-full text-xs">
                                        <i data-lucide="file" class="w-3 h-3 inline mr-1"></i>File
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="inline-block px-2 py-1 text-xs rounded-full bg-pink-100 text-pink-800">
                                        <?php echo ucfirst($doc['category']); ?>
                                    </span>
                                    <a href="siswa_dokumentasi.php?id=<?php echo $siswa_id; ?>&delete=<?php echo $doc['id']; ?>" 
                                       onclick="return confirm('Hapus dokumentasi ini?')"
                                       class="text-red-500 hover:text-red-700">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                </div>
                                
                                <?php if ($doc['description']): ?>
                                    <p class="text-sm text-pink-dark mb-3"><?php echo htmlspecialchars($doc['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="flex justify-between items-center text-xs text-pink-dark/70 mb-3">
                                    <span>By: <?php echo htmlspecialchars($doc['guru_name']); ?></span>
                                    <span><?php echo date('d M Y', strtotime($doc['upload_date'])); ?></span>
                                </div>
                                
                                <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                   target="_blank" download
                                   class="block w-full text-center bg-gradient-to-r from-pink-accent to-pink-dark text-cream py-2 px-4 rounded-lg hover:shadow-lg transition-all text-sm font-medium">
                                    <i data-lucide="download" class="w-4 h-4 inline mr-2"></i>Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'partials/footer.php'; ?>
</body>
</html>