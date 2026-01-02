<?php
/**
 * Waiting List Page
 * Displays books the user is waiting for
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

// Fetch waiting list
$waitingList = [];
$totalWaiting = 0;
$topPosition = 0;
$avgWait = 0;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        $query = "
            SELECT wl.waiting_id, wl.book_id, wl.request_date, b.book_title, b.author, b.image_path, bc.category_name,
                (SELECT COUNT(*) FROM waiting_list w2 WHERE w2.book_id = wl.book_id AND w2.request_date <= wl.request_date) as queue_position,
                (SELECT COUNT(*) FROM waiting_list w3 WHERE w3.book_id = wl.book_id) as total_queue,
                (SELECT u.name FROM booklending bl INNER JOIN username u ON bl.id = u.id WHERE bl.book_id = wl.book_id AND bl.return_date IS NULL LIMIT 1) as current_borrower,
                (SELECT bl.due_date FROM booklending bl WHERE bl.book_id = wl.book_id AND bl.return_date IS NULL LIMIT 1) as expected_return_date
            FROM waiting_list wl
            INNER JOIN book b ON wl.book_id = b.book_id
            LEFT JOIN bookcategory bc ON b.category_id = bc.category_id
            WHERE wl.id = :user_id ORDER BY wl.request_date ASC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $waitingList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalWaiting = count($waitingList);
        
        if ($totalWaiting > 0) {
            $positions = array_column($waitingList, 'queue_position');
            $topPosition = min($positions);
            $totalDays = 0;
            foreach ($waitingList as $item) {
                $totalDays += (new DateTime())->diff(new DateTime($item['request_date']))->days;
            }
            $avgWait = round($totalDays / $totalWaiting);
        }
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

function getDaysWaiting($date) { return (new DateTime())->diff(new DateTime($date))->days; }
function getQueueColor($p) {
    if ($p === 1) return ['bg' => 'bg-green-100', 'text' => 'text-green-700'];
    if ($p <= 3) return ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
    if ($p <= 5) return ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
    return ['bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
}
function getEstimated($p) {
    $e = $p * 5;
    if ($e <= 7) return 'Sekitar ' . $e . ' hari';
    if ($e <= 30) return 'Sekitar ' . ceil($e / 7) . ' minggu';
    return 'Sekitar ' . ceil($e / 30) . ' bulan';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Waiting List - Nova Academy</title>
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
                <h1 class="text-4xl font-bold text-gray-900 mb-3">Buku <span class="gradient-text">Waiting List</span></h1>
                <p class="text-xl text-gray-600">Daftar buku yang sedang Anda tunggu ketersediaannya</p>
            </div>

            <!-- Summary Cards -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Total Waiting</h3>
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $totalWaiting; ?></p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Posisi Terdepan</h3>
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $topPosition; ?></p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Rata-rata Waktu</h3>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $avgWait; ?> <span class="text-base font-normal text-gray-600">hari</span></p>
                </div>
            </div>

            <?php if (empty($waitingList)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Belum Ada Buku di Waiting List</h3>
                <p class="text-gray-600 mb-6">Tambahkan buku yang tidak tersedia ke waiting list</p>
                <a href="<?php echo getRedirectUrl('views/user/dashboard.php'); ?>" class="btn-primary inline-block px-8 py-3 text-white rounded-xl font-semibold">Cari Buku</a>
            </div>
            <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($waitingList as $item): 
                    $daysWaiting = getDaysWaiting($item['request_date']);
                    $qc = getQueueColor($item['queue_position']);
                    $est = getEstimated($item['queue_position']);
                    $first = $item['queue_position'] == 1;
                    $img = $item['image_path'] ? getAssetUrl($item['image_path']) : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&h=600&fit=crop';
                ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 <?php echo $first ? 'border-green-300' : 'border-gray-100'; ?> hover:shadow-xl transition">
                    <?php if ($first): ?>
                    <div class="mb-4 px-4 py-2 bg-green-50 border-l-4 border-green-400 rounded-lg">
                        <p class="text-sm font-semibold text-green-700">🟢 Anda berikutnya! Segera tersedia</p>
                    </div>
                    <?php endif; ?>
                    <div class="flex flex-col md:flex-row gap-6">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($item['book_title']); ?>" class="w-full md:w-32 h-48 object-cover rounded-xl shadow-md">
                        <div class="flex-1">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-4">
                                <div class="flex-1 mb-4 md:mb-0">
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($item['book_title']); ?></h3>
                                    <p class="text-gray-600">Oleh: <span class="font-semibold"><?php echo htmlspecialchars($item['author'] ?? 'Unknown'); ?></span></p>
                                </div>
                                <div class="w-12 h-12 <?php echo $qc['bg'] . ' ' . $qc['text']; ?> rounded-xl flex items-center justify-center font-bold text-xl"><?php echo $item['queue_position']; ?></div>
                            </div>
                            <div class="grid md:grid-cols-3 gap-4 mb-4">
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">Posisi Antrian</p>
                                    <p class="font-bold text-gray-900">#<?php echo $item['queue_position']; ?> dari <?php echo $item['total_queue']; ?></p>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">Menunggu Sejak</p>
                                    <p class="font-semibold text-gray-900"><?php echo $daysWaiting; ?> hari</p>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">Estimasi Tersedia</p>
                                    <p class="font-semibold text-gray-900"><?php echo $est; ?></p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <button onclick="cancelWaiting(<?php echo $item['waiting_id']; ?>)" class="flex-1 px-6 py-3 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition">Batalkan</button>
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
        function cancelWaiting(id) {
            if (confirm('Batalkan waiting list?')) {
                alert('Fitur akan segera tersedia. ID: ' + id);
            }
        }
    </script>
</body>
</html>
