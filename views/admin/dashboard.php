<?php
// Admin Dashboard - Nova Library
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Nova Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../public/css/admin-dashboard.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-lg shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuBtn" class="md:hidden p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 gradient-purple rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xl font-bold gradient-text">Nova Library</span>
                            <p class="text-xs text-gray-600">Admin Panel</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <button class="relative p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 gradient-purple rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold" id="adminInitial">A</span>
                        </div>
                        <div class="hidden md:block">
                            <p class="text-sm font-semibold text-gray-900" id="adminName">Admin</p>
                            <p class="text-xs text-purple-600">Administrator</p>
                        </div>
                    </div>
                    
                    <button onclick="logout()" class="p-2 hover:bg-red-50 rounded-lg transition text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex pt-16">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar w-64 bg-white shadow-xl h-screen overflow-y-auto">
            <div class="p-6">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-4">Menu Utama</h3>
                <nav class="space-y-2">
                    <a href="#" onclick="showSection('dashboard')" class="sidebar-item active flex items-center space-x-3 px-4 py-3 rounded-xl" id="menu-dashboard">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <a href="#" onclick="showSection('books')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl" id="menu-books">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span class="font-medium">Kelola Buku</span>
                    </a>
                    <a href="#" onclick="showSection('borrowing')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl" id="menu-borrowing">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span class="font-medium">Peminjaman</span>
                    </a>
                    <a href="#" onclick="showSection('return')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl" id="menu-return">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">Pengembalian</span>
                    </a>
                    <a href="#" onclick="showSection('fines')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl" id="menu-fines">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">Denda</span>
                    </a>
                </nav>

                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-4 mt-8">Lainnya</h3>
                <nav class="space-y-2">
                    <a href="#" onclick="showSection('members')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl" id="menu-members">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="font-medium">Anggota</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 overflow-y-auto">
            <!-- Dashboard Section -->
            <div id="section-dashboard" class="section-content">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Admin</h1>
                    <p class="text-gray-600">Selamat datang di panel admin Nova Library</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Buku</h3>
                        <p class="text-3xl font-bold text-gray-900" id="totalBooks">-</p>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Anggota</h3>
                        <p class="text-3xl font-bold text-gray-900" id="totalMembers">-</p>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Sedang Dipinjam</h3>
                        <p class="text-3xl font-bold text-gray-900" id="activeBorrowings">-</p>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Denda</h3>
                        <p class="text-3xl font-bold text-gray-900" id="totalFines">-</p>
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Peminjaman Terbaru</h3>
                            <button onclick="showSection('borrowing')" class="text-sm text-purple-600 hover:text-purple-700 font-semibold">
                                Lihat Semua →
                            </button>
                        </div>
                        <div id="recentBorrowings" class="space-y-4">
                            <p class="text-gray-500 text-center py-4">Memuat data...</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Terlambat Dikembalikan</h3>
                            <button onclick="showSection('return')" class="text-sm text-purple-600 hover:text-purple-700 font-semibold">
                                Lihat Semua →
                            </button>
                        </div>
                        <div id="overdueReturns" class="space-y-4">
                            <p class="text-gray-500 text-center py-4">Memuat data...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Books Section -->
            <div id="section-books" class="section-content hidden">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Kelola Buku</h1>
                        <p class="text-gray-600">Manage all books in the library</p>
                    </div>
                    <button onclick="openAddBookModal()" class="btn-primary px-6 py-3 text-white rounded-xl font-semibold flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Buku
                    </button>
                </div>

                <!-- Search & Filter -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="relative md:col-span-2">
                            <input 
                                type="text" 
                                id="searchBooks" 
                                placeholder="Cari judul buku atau penulis..." 
                                class="w-full px-4 py-3 pl-12 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition"
                                oninput="searchBooks()"
                            >
                            <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <select id="filterCategory" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition" onchange="filterBooks()">
                            <option value="">Semua Kategori</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Cover</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Judul</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Penulis</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Kategori</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="booksTableBody">
                                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Borrowing Section -->
            <div id="section-borrowing" class="section-content hidden">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Kelola Peminjaman</h1>
                    <p class="text-gray-600">Daftar peminjaman buku aktif</p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Judul Buku</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Penulis</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Tgl Pinjam</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Jatuh Tempo</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody id="borrowingsTableBody">
                                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Return Section -->
            <div id="section-return" class="section-content hidden">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Pengembalian Buku</h1>
                    <p class="text-gray-600">Daftar buku yang telah dikembalikan</p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Judul Buku</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Tgl Pinjam</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Tgl Kembali</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody id="returnsTableBody">
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Fines Section -->
            <div id="section-fines" class="section-content hidden">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Kelola Denda</h1>
                    <p class="text-gray-600">Daftar denda keterlambatan</p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Nama Anggota</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Judul Buku</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Keterlambatan</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Jumlah Denda</th>
                                </tr>
                            </thead>
                            <tbody id="finesTableBody">
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Members Section -->
            <div id="section-members" class="section-content hidden">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Kelola Anggota</h1>
                    <p class="text-gray-600">Daftar semua anggota perpustakaan</p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Nama</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Username</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">No. HP</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Provider</th>
                                </tr>
                            </thead>
                            <tbody id="membersTableBody">
                                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Tambah/Edit Buku -->
    <div id="addBookModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                <h2 class="text-2xl font-bold gradient-text" id="modalTitle">Tambah Buku Baru</h2>
                <button onclick="closeAddBookModal()" class="p-2 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <form id="addBookForm" onsubmit="handleAddBook(event)">
                    <input type="hidden" id="bookId" value="">
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Judul Buku -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Judul Buku <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="bookTitle" 
                                required
                                placeholder="Masukkan judul buku"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition"
                            >
                        </div>

                        <!-- Penulis -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Penulis <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="bookAuthor" 
                                required
                                placeholder="Nama penulis"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition"
                            >
                        </div>

                        <!-- Penerbit -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Penerbit
                            </label>
                            <input 
                                type="text" 
                                id="bookPublisher" 
                                placeholder="Nama penerbit"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition"
                            >
                        </div>

                        <!-- Tahun Terbit -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Tahun Terbit
                            </label>
                            <input 
                                type="number" 
                                id="bookYear" 
                                min="1900"
                                max="2025"
                                placeholder="2024"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition"
                            >
                        </div>

                        <!-- Kategori -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="bookCategory" 
                                required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition"
                            >
                                <option value="">Pilih Kategori</option>
                            </select>
                        </div>

                        <!-- Cover Buku (File Upload) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Cover Buku (PNG/JPEG)
                            </label>
                            <div class="flex items-start gap-4">
                                <img id="coverPreview" src="" alt="Preview" class="hidden w-20 h-28 object-cover rounded shadow border">
                                <div class="flex-1">
                                    <input 
                                        type="file" 
                                        id="bookCover" 
                                        accept="image/png,image/jpeg"
                                        onchange="previewCover(this)"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100"
                                    >
                                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada, akan menggunakan cover default</p>
                                </div>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Deskripsi Buku
                            </label>
                            <textarea 
                                id="bookDescription" 
                                rows="4"
                                placeholder="Tulis deskripsi singkat tentang buku..."
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-4 mt-6">
                        <button 
                            type="button" 
                            onclick="closeAddBookModal()"
                            class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit"
                            class="flex-1 btn-primary px-6 py-3 text-white rounded-xl font-semibold"
                        >
                            Simpan Buku
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../public/js/admin-dashboard.js"></script>
</body>
</html>