/**
 * Dashboard JavaScript
 * Handles user profile, logout, book search, and navigation
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

        if (query.length < 3) {
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
 * Search books from database
 * @param {string} query - Search query
 */
async function searchBooks(query) {
    try {
        const response = await fetch('/NOVA-Library/api/search-books.php?q=' + encodeURIComponent(query));
        if (!response.ok) throw new Error('Search failed');
        
        const books = await response.json();
        displaySearchResults(books);
    } catch (error) {
        console.error('Search error:', error);
        displaySearchResults([]);
    }
}

/**
 * Display search results
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
        return `<div class="book-result-item flex items-start space-x-4 p-4 rounded-xl cursor-pointer mb-2">
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-900 mb-1">${book.title}</h4>
                        <p class="text-sm text-gray-600 mb-2">${book.author}</p>
                        <p class="text-xs text-gray-500 mb-2">ISBN: ${book.isbn}</p>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${book.available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                            ${book.available ? '✓ Tersedia' : '✗ Dipinjam'}
                        </span>
                    </div>
                </div>`;
    }).join('');

    searchResults.classList.remove('hidden');
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
        // Fetch from API endpoint
        const response = await fetch('/NOVA-Library/api/dashboard-stats.php');
        if (!response.ok) throw new Error('Failed to load stats');
        
        const stats = await response.json();
        document.getElementById('borrowedCount').textContent = stats.borrowed || '0';
        document.getElementById('waitingCount').textContent = stats.waiting || '0';
        document.getElementById('historyCount').textContent = stats.history || '0';
    } catch (error) {
        console.error('Error loading stats:', error);
        // Fallback to demo data
        document.getElementById('borrowedCount').textContent = '0';
        document.getElementById('waitingCount').textContent = '0';
        document.getElementById('historyCount').textContent = '0';
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
