<?php
/**
 * User Dashboard
 * Displays member profile and library functions
 * Requires: Member session authentication
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include helpers
require_once __DIR__ . '/../../config/helpers.php';

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
$userRole = $_SESSION['role'] ?? 'member';

// Verify user ID exists
if (!$userId) {
    session_destroy();
    header('Location: ' . getRedirectUrl('views/login.php'));
    exit;
}

// Get user name (can be stored in session or fetched from DB)
$userName = $_SESSION['username'] ?? 'Member';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Nova Academy Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo getAssetUrl('public/css/dashboard.css'); ?>">
</head>
<body>
    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-lg shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 gradient-purple rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold gradient-text">Nova Academy</span>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative">
                    <button id="profileBtn" class="flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-100 transition-all">
                        <div class="w-10 h-10 gradient-purple rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold"><?php echo $userInitial; ?></span>
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($userName); ?></p>
                            <p class="text-xs text-gray-500">Member</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="profileDropdown" class="profile-dropdown absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 py-2">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($userName); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($userName); ?>@novaacademy.id</p>
                        </div>
                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 transition">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profile Saya
                        </a>
                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 transition">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Pengaturan
                        </a>
                        <hr class="my-2 border-gray-100">
                        <button onclick="logout()" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Keluar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <!-- Logo Section -->
            <div class="logo-container text-center mb-12">
                <div class="w-32 h-32 gradient-purple rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                    <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-3">Nova Academy Library</h1>
                <p class="text-xl text-gray-600">Selamat datang di perpustakaan digital</p>
            </div>

            <!-- Search Book Section -->
            <div class="mb-12">
                <div class="relative">
                    <div class="search-box relative bg-white rounded-2xl shadow-lg border-2 border-gray-200 overflow-hidden">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="searchInput"
                            placeholder="Cari buku berdasarkan judul, penulis, atau ISBN..."
                            class="w-full pl-16 pr-6 py-5 text-lg focus:outline-none focus:border-purple-500 border-2 border-transparent transition-all rounded-2xl"
                        >
                    </div>

                    <!-- Search Results Dropdown -->
                    <div id="searchResults" class="hidden absolute w-full mt-2 bg-white rounded-2xl search-results border border-gray-200 z-10 shadow-xl">
                        <div id="resultsContainer" class="p-4">
                            <!-- Results will be inserted here -->
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="searchLoading" class="hidden absolute w-full mt-2 bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                        <div class="flex items-center justify-center space-x-3">
                            <svg class="animate-spin h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-gray-600">Mencari buku...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Buku yang Sedang Dipinjam -->
                <div class="dashboard-card">
                    <button onclick="navigateTo('borrowed-books.html')" class="card-hover w-full bg-white rounded-2xl shadow-lg p-8 text-left border-2 border-gray-100 hover:border-purple-300 h-full flex flex-col">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-purple-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Buku yang Sedang Dipinjam</h3>
                        <p class="text-gray-600 mb-4 flex-grow">Lihat daftar buku yang sedang Anda pinjam</p>
                        <div class="flex items-center text-purple-600 font-semibold mt-auto">
                            <span id="borrowedCount">0</span>
                            <span class="ml-1">Buku</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Buku Waiting List -->
                <div class="dashboard-card">
                    <button onclick="navigateTo('waiting-list.html')" class="card-hover w-full bg-white rounded-2xl shadow-lg p-8 text-left border-2 border-gray-100 hover:border-purple-300 h-full flex flex-col">
                        <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-orange-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Buku Waiting List</h3>
                        <p class="text-gray-600 mb-4 flex-grow">Daftar buku yang Anda tunggu</p>
                        <div class="flex items-center text-orange-600 font-semibold mt-auto">
                            <span id="waitingCount">0</span>
                            <span class="ml-1">Buku</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Riwayat Peminjaman -->
                <div class="dashboard-card">
                    <button onclick="navigateTo('history.html')" class="card-hover w-full bg-white rounded-2xl shadow-lg p-8 text-left border-2 border-gray-100 hover:border-purple-300 h-full flex flex-col">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-100 to-teal-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Riwayat Peminjaman</h3>
                        <p class="text-gray-600 mb-4 flex-grow">Lihat semua riwayat peminjaman Anda</p>
                        <div class="flex items-center text-green-600 font-semibold mt-auto">
                            <span id="historyCount">0</span>
                            <span class="ml-1">Riwayat</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo getAssetUrl('public/js/dashboard.js'); ?>"></script>
</body>
</html>
