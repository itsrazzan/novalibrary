<?php
/**
 * Borrowing History Page
 * Displays user's borrowing history
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// Auth check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . getRedirectUrl('views/login.php'));
    exit;
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'member' && $_SESSION['role'] !== 'user')) {
    header('Location: ' . getRedirectUrl('views/login.php'));
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['username'] ?? 'Member';
$userInitial = strtoupper(substr($userName, 0, 1));

if (!$userId) {
    session_destroy();
    header('Location: ' . getRedirectUrl('views/login.php'));
    exit;
}

// Fetch history
$history = [];
$totalHistory = 0;
$returnedCount = 0;
$onTimeCount = 0;
$lateCount = 0;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        $query = "
            SELECT bl.loan_id, bl.book_id, bl.loan_date, bl.due_date, bl.return_date,
                b.book_title, b.author, b.image_path, bc.category_name
            FROM booklending bl
            INNER JOIN book b ON bl.book_id = b.book_id
            LEFT JOIN bookcategory bc ON b.category_id = bc.category_id
            WHERE bl.id = :user_id
            ORDER BY bl.loan_date DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalHistory = count($history);
        
        foreach ($history as $item) {
            if ($item['return_date']) {
                $returnedCount++;
                if ($item['return_date'] <= $item['due_date']) {
                    $onTimeCount++;
                } else {
                    $lateCount++;
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

function getStatusInfo($returnDate, $dueDate) {
    if (!$returnDate) {
        return ['text' => 'Sedang Dipinjam', 'class' => 'bg-blue-100 text-blue-700', 'icon' => '📖', 'type' => 'borrowed'];
    }
    if ($returnDate > $dueDate) {
        $days = (new DateTime($returnDate))->diff(new DateTime($dueDate))->days;
        return ['text' => 'Terlambat ' . $days . ' hari', 'class' => 'bg-red-100 text-red-700', 'icon' => '⚠️', 'type' => 'late'];
    }
    return ['text' => 'Tepat Waktu', 'class' => 'bg-green-100 text-green-700', 'icon' => '✓', 'type' => 'ontime'];
}

function calcDuration($start, $end) {
    $s = new DateTime($start);
    $e = new DateTime($end ?: date('Y-m-d'));
    return $s->diff($e)->days;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Nova Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo getAssetUrl('public/css/dashboard.css'); ?>">
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-purple-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-lg shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="<?php echo getRedirectUrl('views/user/dashboard.php'); ?>" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 gradient-purple rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold gradient-text">Nova Academy</span>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 gradient-purple rounded-full flex items-center justify-center shadow-lg">
                        <span class="text-white font-bold"><?php echo $userInitial; ?></span>
                    </div>
                    <span class="hidden md:block text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($userName); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-3">Riwayat <span class="gradient-text">Peminjaman</span></h1>
                <p class="text-xl text-gray-600">Lihat semua aktivitas peminjaman buku Anda</p>
            </div>

            <!-- Summary Cards -->
            <div class="grid md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Total Peminjaman</h3>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $totalHistory; ?></p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Dikembalikan</h3>
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $returnedCount; ?></p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Tepat Waktu</h3>
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $onTimeCount; ?></p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Terlambat</h3>
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $lateCount; ?></p>
                </div>
            </div>

            <?php if (empty($history)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Belum Ada Riwayat Peminjaman</h3>
                <p class="text-gray-600 mb-6">Mulai pinjam buku untuk melihat riwayat peminjaman Anda</p>
                <a href="<?php echo getRedirectUrl('views/user/dashboard.php'); ?>" class="btn-primary inline-block px-8 py-3 text-white rounded-xl font-semibold">Cari Buku</a>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($history as $item): 
                    $status = getStatusInfo($item['return_date'], $item['due_date']);
                    $duration = calcDuration($item['loan_date'], $item['return_date']);
                    $img = $item['image_path'] ? getAssetUrl($item['image_path']) : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&h=600&fit=crop';
                ?>
                <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 hover:border-purple-200 overflow-hidden transition hover:shadow-xl">
                    <div class="flex flex-col md:flex-row">
                        <div class="md:w-1/4 relative">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($item['book_title']); ?>" class="w-full h-64 md:h-full object-cover">
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold border-2 <?php echo $status['class']; ?>">
                                    <?php echo $status['icon'] . ' ' . $status['text']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 p-6">
                            <div class="mb-4">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($item['book_title']); ?></h3>
                                <p class="text-gray-600 mb-1">Oleh: <span class="font-semibold"><?php echo htmlspecialchars($item['author'] ?? 'Unknown'); ?></span></p>
                                <p class="text-sm text-gray-500">Kategori: <?php echo htmlspecialchars($item['category_name'] ?? 'Umum'); ?></p>
                            </div>
                            
                            <div class="grid md:grid-cols-3 gap-4 mb-4">
                                <div class="bg-gray-50 rounded-xl p-4 relative border-l-4 border-blue-400">
                                    <p class="text-xs text-gray-500 mb-1">Tanggal Pinjam</p>
                                    <p class="font-semibold text-gray-900"><?php echo date('d M Y', strtotime($item['loan_date'])); ?></p>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4 relative border-l-4 <?php echo $item['return_date'] ? 'border-green-400' : 'border-orange-400'; ?>">
                                    <p class="text-xs text-gray-500 mb-1"><?php echo $item['return_date'] ? 'Tanggal Dikembalikan' : 'Jatuh Tempo'; ?></p>
                                    <p class="font-semibold text-gray-900"><?php echo date('d M Y', strtotime($item['return_date'] ?: $item['due_date'])); ?></p>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">Durasi Peminjaman</p>
                                    <p class="font-semibold text-gray-900"><?php echo $duration; ?> hari</p>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <?php if ($item['return_date']): ?>
                                <button onclick="borrowAgain(<?php echo $item['book_id']; ?>)" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Pinjam Lagi
                                </button>
                                <?php else: ?>
                                <button disabled class="flex-1 px-6 py-3 bg-gray-300 text-gray-500 rounded-xl font-semibold cursor-not-allowed">
                                    Sedang Dipinjam
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <script>
        function borrowAgain(bookId) {
            if (confirm('Pinjam lagi buku ini?')) {
                alert('Fitur akan segera tersedia. Book ID: ' + bookId);
            }
        }
    </script>
</body>
</html>
