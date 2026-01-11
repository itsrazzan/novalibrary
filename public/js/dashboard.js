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
 * Display search results with borrow/waiting list buttons
 * @param {Array} books - Array of book objects
 * @param {boolean} isAllBooks - Whether showing all books (for header text)
 */
function displaySearchResults(books, isAllBooks = false) {
    searchLoading.classList.add('hidden');

    if (!books || books.length === 0) {
        resultsContainer.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">Buku tidak ditemukan</p></div>';
        searchResults.classList.remove('hidden');
        return;
    }

    // Header for all books mode
    const header = isAllBooks 
        ? `<div class="flex justify-between items-center mb-4 pb-3 border-b">
               <h3 class="font-bold text-gray-900">Koleksi Buku (${books.length})</h3>
               <button onclick="searchResults.classList.add('hidden')" class="text-gray-500 hover:text-gray-700 text-sm">Tutup</button>
           </div>`
        : '';

    resultsContainer.innerHTML = header + `<div class="max-h-96 overflow-y-auto">` + books.map(function(book) {
        const isAvailable = book.book_status === true || book.book_status === 't' || book.book_status === 1;
        const imagePath = book.image_url || book.image_path || '/NOVA-Library/public/img/books/default-book.jpg';
        const safeTitle = book.book_title.replace(/'/g, "\\'").replace(/"/g, '\\"');
        
        // Action button based on availability
        let actionBtn = '';
        if (isAvailable) {
            actionBtn = `<button onclick="event.stopPropagation(); borrowBook(${book.book_id}, '${safeTitle}')" 
                               class="ml-2 px-3 py-1 bg-purple-600 text-white rounded-full text-xs font-semibold hover:bg-purple-700 transition">
                           Pinjam
                       </button>`;
        } else {
            actionBtn = `<button onclick="event.stopPropagation(); addToWaitingList(${book.book_id}, '${safeTitle}')" 
                               class="ml-2 px-3 py-1 bg-orange-500 text-white rounded-full text-xs font-semibold hover:bg-orange-600 transition">
                           Waiting List
                       </button>`;
        }
        
        return `<div class="book-result-item flex items-start space-x-4 p-4 rounded-xl mb-2 hover:bg-purple-50 transition cursor-pointer" 
                     onclick="showBookDetail(${book.book_id})">
                    <img src="${imagePath.startsWith('/') ? imagePath : '/NOVA-Library/' + imagePath}" 
                         alt="${book.book_title}" 
                         class="w-12 h-16 object-cover rounded shadow"
                         onerror="this.src='/NOVA-Library/public/img/books/default-book.jpg'">
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-900 mb-1">${book.book_title}</h4>
                        <p class="text-sm text-gray-600 mb-2">Oleh: ${book.author || 'Unknown'}</p>
                        <p class="text-xs text-gray-500 mb-2">${book.category_name || 'Umum'}</p>
                        <div class="flex items-center flex-wrap gap-2">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${isAvailable ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                ${isAvailable ? '✓ Tersedia' : '✗ Dipinjam'}
                            </span>
                            ${actionBtn}
                        </div>
                    </div>
                </div>`;
    }).join('') + `</div>`;

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

// ===== Load All Books =====
async function loadAllBooks() {
    console.log('loadAllBooks called'); // Debug log
    
    const loadingEl = document.getElementById('searchLoading');
    const resultsEl = document.getElementById('searchResults');
    const inputEl = document.getElementById('searchInput');
    
    if (loadingEl) loadingEl.classList.remove('hidden');
    if (resultsEl) resultsEl.classList.add('hidden');
    if (inputEl) inputEl.value = '';
    
    try {
        const response = await fetch('../../controllers/SearchController.php?all=true&limit=50');
        const result = await response.json();
        
        console.log('All books response:', result); // Debug log
        
        if (result.success) {
            displaySearchResults(result.data, true);
        } else {
            displaySearchResults([], true);
        }
    } catch (error) {
        console.error('Load all books error:', error);
        displaySearchResults([], true);
    }
}

// ===== Book Detail Modal =====
let currentBookData = null;

async function showBookDetail(bookId) {
    const modal = document.getElementById('bookDetailModal');
    
    try {
        const response = await fetch(`../../controllers/BookDetailController.php?book_id=${bookId}`);
        const result = await response.json();
        
        if (!result.success) {
            alert('❌ ' + result.message);
            return;
        }
        
        currentBookData = result.data;
        const book = result.data;
        
        // Populate modal
        document.getElementById('modalBookCover').src = book.image_path.startsWith('/') 
            ? book.image_path 
            : '/NOVA-Library/' + book.image_path;
        document.getElementById('modalBookTitle').textContent = book.book_title;
        document.getElementById('modalBookAuthor').textContent = 'Oleh: ' + book.author;
        document.getElementById('modalBookPublisher').textContent = book.publisher || '-';
        document.getElementById('modalBookYear').textContent = book.published_year || '-';
        document.getElementById('modalBookCategory').textContent = book.category_name;
        document.getElementById('modalBookSinopsis').textContent = book.sinopsis || 'Tidak ada sinopsis.';
        
        // Status badge
        const statusEl = document.getElementById('modalBookStatus');
        if (book.is_available) {
            statusEl.textContent = '✓ Tersedia';
            statusEl.className = 'px-4 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-700';
        } else {
            statusEl.textContent = '✗ Dipinjam';
            statusEl.className = 'px-4 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-700';
        }
        
        // Action buttons
        const borrowBtn = document.getElementById('modalBorrowBtn');
        const waitingBtn = document.getElementById('modalWaitingBtn');
        const alreadyWaitingBtn = document.getElementById('modalAlreadyWaitingBtn');
        const waitingInfo = document.getElementById('modalWaitingInfo');
        
        // Hide all first
        borrowBtn.classList.add('hidden');
        waitingBtn.classList.add('hidden');
        alreadyWaitingBtn.classList.add('hidden');
        waitingInfo.classList.add('hidden');
        
        if (book.is_available) {
            borrowBtn.classList.remove('hidden');
        } else {
            if (book.in_waiting_list) {
                alreadyWaitingBtn.classList.remove('hidden');
                waitingInfo.classList.remove('hidden');
                document.getElementById('modalWaitingText').textContent = 
                    `Anda di posisi #${book.waiting_position} dari ${book.total_queue} antrian`;
            } else {
                waitingBtn.classList.remove('hidden');
                if (book.total_queue > 0) {
                    waitingInfo.classList.remove('hidden');
                    document.getElementById('modalWaitingText').textContent = 
                        `${book.total_queue} orang sedang menunggu buku ini`;
                }
            }
        }
        
        // Show modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
    } catch (error) {
        console.error('Show detail error:', error);
        alert('❌ Gagal memuat detail buku');
    }
}

function closeBookModal() {
    document.getElementById('bookDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentBookData = null;
}

// Close modal on overlay click
document.getElementById('bookDetailModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeBookModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBookModal();
    }
});

// ===== Waiting List Functions =====
async function addToWaitingList(bookId, bookTitle) {
    console.log('addToWaitingList called:', bookId, bookTitle);
    
    if (!confirm(`Masuk waiting list untuk buku "${bookTitle}"?`)) return;
    
    try {
        const formData = new FormData();
        formData.append('book_id', bookId);
        
        const response = await fetch('../../controllers/WaitingListController.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Waiting list response status:', response.status);
        const result = await response.json();
        console.log('Waiting list result:', result);
        
        if (result.success) {
            alert(`✅ ${result.message}\nPosisi antrian: #${result.position}`);
            loadDashboardStats();
            const resultsEl = document.getElementById('searchResults');
            if (resultsEl) resultsEl.classList.add('hidden');
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Waiting list error:', error);
        alert('❌ Gagal menambahkan ke waiting list: ' + error.message);
    }
}

async function borrowFromModal() {
    console.log('borrowFromModal called, currentBookData:', currentBookData);
    if (!currentBookData) {
        alert('❌ Data buku tidak ditemukan');
        return;
    }
    // Simpan data SEBELUM closeBookModal yang akan null-kan currentBookData
    const bookId = currentBookData.book_id;
    const bookTitle = currentBookData.book_title;
    closeBookModal();
    await borrowBook(bookId, bookTitle);
}

async function addToWaitingListFromModal() {
    console.log('addToWaitingListFromModal called, currentBookData:', currentBookData);
    if (!currentBookData) {
        alert('❌ Data buku tidak ditemukan');
        return;
    }
    // Simpan data SEBELUM closeBookModal yang akan null-kan currentBookData
    const bookId = currentBookData.book_id;
    const bookTitle = currentBookData.book_title;
    closeBookModal();
    await addToWaitingList(bookId, bookTitle);
}

// ===== Navigation =====
function navigateTo(page) {
    window.location.href = `/NOVA-Library/views/user/${page}`;
}

// ===== Initialize =====
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
});
