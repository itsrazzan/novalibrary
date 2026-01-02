<?php
/**
 * Borrowed Books Page
 * Displays books currently being borrowed by the user
 * Requires: Member session authentication
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include helpers and database
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . getRedirectUrl('views/login.php'));
    exit;
}

// Check if user is member
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'member' && $_SESSION['role'] !== 'user')) {
    header('Location: ' . getRedirectUrl('views/login.php'));
    exit;
}

// Get user info from session
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['username'] ?? 'Member';
$userInitial = strtoupper(substr($userName, 0, 1));

// Verify user ID exists
if (!$userId) {
    session_destroy();
    header('Location: ' . getRedirectUrl('views/login.php'));
    exit;
}

// Fetch borrowed books from database
$borrowedBooks = [];
$totalBorrowed = 0;
$dueToday = 0;
$overdue = 0;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        // Query to get active borrowed books (return_date is NULL)
        $query = "
            SELECT 
                bl.loan_id,
                bl.book_id,
                bl.loan_date,
                bl.due_date,
                b.book_title,
                b.author,
                b.publisher,
                b.image_path,
                bc.category_name
            FROM booklending bl
            INNER JOIN book b ON bl.book_id = b.book_id
            LEFT JOIN bookcategory bc ON b.category_id = bc.category_id
            WHERE bl.id = :user_id 
            AND bl.return_date IS NULL
            ORDER BY bl.due_date ASC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalBorrowed = count($borrowedBooks);
        
        // Calculate due today and overdue
        $today = date('Y-m-d');
        foreach ($borrowedBooks as $book) {
            if ($book['due_date'] === $today) {
                $dueToday++;
            } elseif ($book['due_date'] < $today) {
                $overdue++;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching borrowed books: " . $e->getMessage());
}

// Helper function to calculate days left
function getDaysLeft($dueDate) {
    $today = new DateTime();
    $due = new DateTime($dueDate);
    $diff = $today->diff($due);
    return $due >= $today ? $diff->days : -$diff->days;
}

// Helper function to get status info
function getStatusInfo($daysLeft) {
    if ($daysLeft < 0) {
        return [
            'text' => 'Terlambat ' . abs($daysLeft) . ' hari',
            'class' => 'bg-red-100 text-red-700',
            'icon' => '⚠️'
        ];
    } elseif ($daysLeft === 0) {
        return [
            'text' => 'Jatuh tempo hari ini',
            'class' => 'bg-orange-100 text-orange-700',
            'icon' => '⏰'
        ];
    } elseif ($daysLeft <= 3) {
        return [
            'text' => 'Jatuh tempo ' . $daysLeft . ' hari lagi',
            'class' => 'bg-yellow-100 text-yellow-700',
            'icon' => '⏳'
        ];
    } else {
        return [
            'text' => 'Jatuh tempo ' . $daysLeft . ' hari lagi',
            'class' => 'bg-green-100 text-green-700',
            'icon' => '✓'
        ];
    }
}

// Helper function to calculate progress
function calculateProgress($loanDate, $dueDate) {
    $loan = new DateTime($loanDate);
    $due = new DateTime($dueDate);
    $today = new DateTime();
    
    $total = $due->diff($loan)->days;
    $elapsed = $today->diff($loan)->days;
    
    if ($total == 0) return 100;
    
    $progress = min(100, max(0, ($elapsed / $total) * 100));
    return round($progress);
}

// Helper function to get progress color
function getProgressColor($progress) {
    if ($progress >= 90) return '#ef4444';
    if ($progress >= 70) return '#f59e0b';
    return '#10b981';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku yang Sedang Dipinjam - Nova Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f3e8ff 100%);
            min-height: 100vh;
        }

        .gradient-purple {
            background: linear-gradient(135deg, #7c3aed 0%, #3b82f6 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #7c3aed 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .book-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .book-card:nth-child(1) { animation-delay: 0.1s; }
        .book-card:nth-child(2) { animation-delay: 0.2s; }
        .book-card:nth-child(3) { animation-delay: 0.3s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -12px rgba(124, 58, 237, 0.35);
        }

        .btn-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #3b82f6 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -8px rgba(124, 58, 237, 0.5);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .progress-bar {
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-lg shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <!-- Back Button -->
                    <a href="<?php echo getRedirectUrl('views/user/dashboard.php'); ?>" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 gradient-purple rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold gradient-text">Nova Academy</span>
                    </div>
                </div>

                <!-- User Info -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 gradient-purple rounded-full flex items-center justify-center shadow-lg">
                        <span class="text-white font-bold"><?php echo $userInitial; ?></span>
                    </div>
                    <span class="hidden md:block text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($userName); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-3">
                    Buku yang Sedang <span class="gradient-text">Dipinjam</span>
                </h1>
                <p class="text-xl text-gray-600">Kelola dan pantau buku yang sedang Anda pinjam</p>
            </div>

            <!-- Summary Cards -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Total Dipinjam</h3>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $totalBorrowed; ?></p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">Jatuh Tempo Hari Ini</h3>
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $dueToday; ?></p>
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
                    <p class="text-3xl font-bold text-gray-900"><?php echo $overdue; ?></p>
                </div>
            </div>

            <?php if (empty($borrowedBooks)): ?>
            <!-- Empty State -->
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Belum Ada Buku yang Dipinjam</h3>
                <p class="text-gray-600 mb-6">Mulai pinjam buku untuk melanjutkan perjalanan belajar Anda</p>
                <a href="<?php echo getRedirectUrl('views/user/dashboard.php'); ?>" class="btn-primary inline-block px-8 py-3 text-white rounded-xl font-semibold">
                    Cari Buku
                </a>
            </div>
            <?php else: ?>
            <!-- Books List -->
            <div class="space-y-6">
                <?php foreach ($borrowedBooks as $book): 
                    $daysLeft = getDaysLeft($book['due_date']);
                    $status = getStatusInfo($daysLeft);
                    $progress = calculateProgress($book['loan_date'], $book['due_date']);
                    $progressColor = getProgressColor($progress);
                    $imagePath = $book['image_path'] ? getAssetUrl($book['image_path']) : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&h=600&fit=crop';
                ?>
                <div class="book-card bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                    <div class="flex flex-col md:flex-row gap-6">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($book['book_title']); ?>" class="w-full md:w-32 h-48 object-cover rounded-xl shadow-md">
                        <div class="flex-1">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-4">
                                <div class="flex-1 mb-4 md:mb-0">
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($book['book_title']); ?></h3>
                                    <p class="text-gray-600 mb-1">Oleh: <span class="font-semibold"><?php echo htmlspecialchars($book['author'] ?? 'Unknown'); ?></span></p>
                                    <p class="text-sm text-gray-500">Kategori: <?php echo htmlspecialchars($book['category_name'] ?? 'Umum'); ?></p>
                                </div>
                                <span class="status-badge px-4 py-2 rounded-full text-sm font-semibold <?php echo $status['class']; ?>">
                                    <?php echo $status['icon'] . ' ' . $status['text']; ?>
                                </span>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-4 mb-4">
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">Tanggal Pinjam</p>
                                    <p class="font-semibold text-gray-900"><?php echo date('d F Y', strtotime($book['loan_date'])); ?></p>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">Tanggal Jatuh Tempo</p>
                                    <p class="font-semibold text-gray-900"><?php echo date('d F Y', strtotime($book['due_date'])); ?></p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <p class="text-sm font-semibold text-gray-700">Progress Peminjaman</p>
                                    <p class="text-sm font-bold" style="color: <?php echo $progressColor; ?>"><?php echo $progress; ?>%</p>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%; background-color: <?php echo $progressColor; ?>"></div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <button onclick="extendLoan(<?php echo $book['loan_id']; ?>)" class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Perpanjang Pinjaman
                                </button>
                                <button onclick="returnBook(<?php echo $book['loan_id']; ?>)" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Kembalikan Buku
                                </button>
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
        function extendLoan(loanId) {
            if (confirm('Apakah Anda yakin ingin memperpanjang peminjaman buku ini?')) {
                // TODO: Integrate with backend API
                alert('Fitur perpanjangan akan segera tersedia.\nLoan ID: ' + loanId);
            }
        }

        function returnBook(loanId) {
            if (confirm('Apakah Anda yakin ingin mengembalikan buku ini?')) {
                // TODO: Integrate with backend API
                alert('Fitur pengembalian akan segera tersedia.\nLoan ID: ' + loanId);
            }
        }
    </script>
</body>
</html>
