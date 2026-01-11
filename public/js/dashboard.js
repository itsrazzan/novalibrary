/**
 * Dashboard JavaScript
 * Handles user profile, logout, book search, borrow, and navigation
 */

// ===== Profile Dropdown =====
document.getElementById('profileBtn')?.addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
});

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('profileDropdown');
    const profileBtn = document.getElementById('profileBtn');
    if (profileBtn && !profileBtn.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// ===== Logout Function =====
function logout() {
    if (confirm('Anda yakin ingin keluar?')) {
        window.location.href = '/NOVA-Library/controllers/logout.php';
    }
}

// ===== Book Search =====
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const resultsContainer = document.getElementById('resultsContainer');
const searchLoading = document.getElementById('searchLoading');
let searchTimeout;

if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            searchResults.classList.add('hidden');
            searchLoading.classList.add('hidden');
            return;
        }

        searchLoading.classList.remove('hidden');
        searchResults.classList.add('hidden');

        searchTimeout = setTimeout(function() {
            searchBooks(query);
        }, 500);
    });
}

/**
 * Search books from database using server-side indexed search
 * @param {string} query - Search query
 */
async function searchBooks(query) {
    try {
        const searchUrl = '../../controllers/SearchController.php?q=' + encodeURIComponent(query) + '&limit=10';
        const response = await fetch(searchUrl);
        
        if (!response.ok) throw new Error('Search failed');
        
        const result = await response.json();
        if (result.success) {
            displaySearchResults(result.data);
        } else {
            displaySearchResults([]);
        }
    } catch (error) {
        console.error('Search error:', error);
        displaySearchResults([]);
    }
}

/**
 * Display search results with borrow button
 * @param {Array} books - Array of book objects
 */
function displaySearchResults(books) {
    searchLoading.classList.add('hidden');

    if (!books || books.length === 0) {
        resultsContainer.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">Buku tidak ditemukan</p></div>';
        searchResults.classList.remove('hidden');
        return;
    }

    resultsContainer.innerHTML = books.map(function(book) {
        const isAvailable = book.book_status === true || book.book_status === 't' || book.book_status === 1;
        const imagePath = book.image_url || book.image_path || '/NOVA-Library/public/img/books/default-book.jpg';
        
        const borrowBtn = isAvailable 
            ? `<button onclick="borrowBook(${book.book_id}, '${book.book_title.replace(/'/g, "\\'")}')" 
                       class="ml-2 px-3 py-1 bg-purple-600 text-white rounded-full text-xs font-semibold hover:bg-purple-700 transition">
                   Pinjam
               </button>`
            : '';
        
        return `<div class="book-result-item flex items-start space-x-4 p-4 rounded-xl mb-2 hover:bg-purple-50 transition">
                    <img src="${imagePath.startsWith('/') ? imagePath : '/NOVA-Library/' + imagePath}" 
                         alt="${book.book_title}" 
                         class="w-12 h-16 object-cover rounded shadow"
                         onerror="this.src='/NOVA-Library/public/img/books/default-book.jpg'">
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-900 mb-1">${book.book_title}</h4>
                        <p class="text-sm text-gray-600 mb-2">Oleh: ${book.author || 'Unknown'}</p>
                        <p class="text-xs text-gray-500 mb-2">${book.category_name || 'Umum'}</p>
                        <div class="flex items-center">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${isAvailable ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                ${isAvailable ? '✓ Tersedia' : '✗ Dipinjam'}
                            </span>
                            ${borrowBtn}
                        </div>
                    </div>
                </div>`;
    }).join('');

    searchResults.classList.remove('hidden');
}

/**
 * Borrow a book
 * @param {number} bookId
 * @param {string} bookTitle
 */
async function borrowBook(bookId, bookTitle) {
    if (!confirm(`Pinjam buku "${bookTitle}"?`)) return;
    
    try {
        const formData = new FormData();
        formData.append('book_id', bookId);
        
        const response = await fetch('../../controllers/BorrowController.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ ' + result.message + '\nJatuh tempo: ' + result.due_date);
            searchResults.classList.add('hidden');
            searchInput.value = '';
            loadDashboardStats(); // Refresh stats
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Borrow error:', error);
        alert('❌ Terjadi kesalahan saat meminjam buku');
    }
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (searchInput && !e.target.closest('#searchInput') && !e.target.closest('#searchResults')) {
        searchResults.classList.add('hidden');
    }
});

// ===== Dashboard Stats =====
async function loadDashboardStats() {
    try {
        const response = await fetch('../../controllers/UserStatsController.php');
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('borrowedCount').textContent = result.borrowed;
            document.getElementById('waitingCount').textContent = result.waiting;
            document.getElementById('historyCount').textContent = result.history;
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading stats:', error);
        document.getElementById('borrowedCount').textContent = '-';
        document.getElementById('waitingCount').textContent = '-';
        document.getElementById('historyCount').textContent = '-';
    }
}

// ===== Navigation =====
function navigateTo(page) {
    window.location.href = `/NOVA-Library/views/user/${page}`;
}

// ===== Initialize =====
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
});
