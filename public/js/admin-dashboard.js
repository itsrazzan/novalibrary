// API Base URL
const API_BASE = '../../controllers/admin/';

// Mobile Menu Toggle
document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('open');
});

// Show Section Function
function showSection(section) {
    // Hide all sections
    document.querySelectorAll('.section-content').forEach(function(el) {
        el.classList.add('hidden');
    });

    // Remove active from all menu items
    document.querySelectorAll('.sidebar-item').forEach(function(el) {
        el.classList.remove('active');
    });

    // Show selected section
    document.getElementById('section-' + section).classList.remove('hidden');
    document.getElementById('menu-' + section).classList.add('active');

    // Close mobile menu
    document.getElementById('sidebar').classList.remove('open');

    // Load section data
    loadSectionData(section);
}

// Load data based on section
function loadSectionData(section) {
    switch(section) {
        case 'dashboard':
            loadDashboardStats();
            loadRecentBorrowings();
            loadOverdueReturns();
            break;
        case 'books':
            loadBooks();
            loadCategories();
            break;
        case 'borrowing':
            loadBorrowings();
            break;
        case 'return':
            loadReturns();
            break;
        case 'fines':
            loadFines();
            break;
        case 'members':
            loadMembers();
            break;
    }
}

// Logout Function
function logout() {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        window.location.href = '../../controllers/logout.php';
    }
}

// Load Admin Info on page load
window.addEventListener('DOMContentLoaded', function() {
    const adminName = 'Admin';
    const initial = adminName.charAt(0).toUpperCase();
    
    document.getElementById('adminInitial').textContent = initial;
    document.getElementById('adminName').textContent = adminName;

    // Load initial dashboard data
    loadDashboardStats();
    loadRecentBorrowings();
    loadOverdueReturns();
});

// ==================== DASHBOARD FUNCTIONS ====================

function loadDashboardStats() {
    fetch(API_BASE + 'AdminDashboardController.php?action=stats')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                document.getElementById('totalBooks').textContent = result.data.total_books.toLocaleString();
                document.getElementById('totalMembers').textContent = result.data.total_members.toLocaleString();
                document.getElementById('activeBorrowings').textContent = result.data.active_borrowings.toLocaleString();
                document.getElementById('totalFines').textContent = result.data.total_fines;
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function loadRecentBorrowings() {
    fetch(API_BASE + 'AdminDashboardController.php?action=recent_borrowings')
        .then(response => response.json())
        .then(result => {
            const container = document.getElementById('recentBorrowings');
            if (result.success && result.data.length > 0) {
                container.innerHTML = result.data.map(item => `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">${item.book_title}</p>
                            <p class="text-sm text-gray-600">${item.author}</p>
                        </div>
                        <span class="text-xs text-gray-500">${formatDate(item.loan_date)}</span>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">Tidak ada peminjaman aktif</p>';
            }
        })
        .catch(error => console.error('Error loading recent borrowings:', error));
}

function loadOverdueReturns() {
    fetch(API_BASE + 'AdminDashboardController.php?action=overdue')
        .then(response => response.json())
        .then(result => {
            const container = document.getElementById('overdueReturns');
            if (result.success && result.data.length > 0) {
                container.innerHTML = result.data.map(item => `
                    <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl border-l-4 border-red-400">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">${item.book_title}</p>
                            <p class="text-sm text-gray-600">${item.author}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-red-600">${item.days_overdue} hari</p>
                            <p class="text-xs text-gray-600">${item.fine_formatted}</p>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">Tidak ada keterlambatan</p>';
            }
        })
        .catch(error => console.error('Error loading overdue returns:', error));
}

// ==================== BOOKS FUNCTIONS ====================

let allBooks = [];
let allCategories = [];

function loadBooks() {
    fetch(API_BASE + 'AdminBookController.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                allBooks = result.data;
                renderBooksTable(allBooks);
            }
        })
        .catch(error => console.error('Error loading books:', error));
}

function loadCategories() {
    fetch(API_BASE + 'AdminBookController.php?action=categories')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                allCategories = result.data;
                populateCategoryDropdowns();
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

function populateCategoryDropdowns() {
    const filterSelect = document.getElementById('filterCategory');
    const formSelect = document.getElementById('bookCategory');
    
    // Populate filter dropdown
    filterSelect.innerHTML = '<option value="">Semua Kategori</option>' + 
        allCategories.map(c => `<option value="${c.category_name}">${c.category_name}</option>`).join('');
    
    // Populate form dropdown
    formSelect.innerHTML = '<option value="">Pilih Kategori</option>' + 
        allCategories.map(c => `<option value="${c.category_id}">${c.category_name}</option>`).join('');
}

function renderBooksTable(books) {
    const tbody = document.getElementById('booksTableBody');
    if (books.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">Tidak ada buku ditemukan</td></tr>';
        return;
    }

    const categoryColors = {
        'Novel Fiksi Populer Indonesia': 'purple',
        'Buku Teknologi dan Sistem Informasi': 'blue',
        'Pengembangan Diri dan Psikologi Populer': 'green',
        'Sastra Indonesia': 'orange',
        'Buku Anak dan Cerita Rakyat': 'pink'
    };

    tbody.innerHTML = books.map(book => {
        const color = categoryColors[book.category_name] || 'gray';
        const status = book.book_status ? 'Tersedia' : 'Dipinjam';
        const statusColor = book.book_status ? 'green' : 'red';
        
        return `
            <tr class="table-row border-b border-gray-100" data-id="${book.book_id}" data-title="${book.book_title.toLowerCase()}" data-author="${book.author?.toLowerCase() || ''}" data-category="${book.category_name}">
                <td class="px-6 py-4">
                    <img src="../../${book.image_path}" alt="${book.book_title}" class="w-12 h-16 object-cover rounded shadow" onerror="this.src='../../public/img/books/default-book.jpg'">
                </td>
                <td class="px-6 py-4 font-semibold text-gray-900">${book.book_title}</td>
                <td class="px-6 py-4 text-gray-600">${book.author || '-'}</td>
                <td class="px-6 py-4"><span class="px-3 py-1 bg-${color}-100 text-${color}-700 rounded-full text-xs font-semibold">${book.category_name || '-'}</span></td>
                <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-${statusColor}-100 text-${statusColor}-700">${status}</span></td>
                <td class="px-6 py-4">
                    <div class="flex gap-2">
                        <button onclick="editBook(${book.book_id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button onclick="deleteBook(${book.book_id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Search Books Function
function searchBooks() {
    const searchValue = document.getElementById('searchBooks').value.toLowerCase();
    const categoryFilter = document.getElementById('filterCategory').value;
    
    const filtered = allBooks.filter(book => {
        const matchSearch = book.book_title.toLowerCase().includes(searchValue) || 
                          (book.author && book.author.toLowerCase().includes(searchValue));
        const matchCategory = !categoryFilter || book.category_name === categoryFilter;
        return matchSearch && matchCategory;
    });
    
    renderBooksTable(filtered);
}

// Filter Books by Category
function filterBooks() {
    searchBooks();
}

// Open Add Book Modal
function openAddBookModal() {
    document.getElementById('addBookModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Tambah Buku Baru';
    document.getElementById('addBookForm').reset();
    document.getElementById('bookId').value = '';
    document.getElementById('coverPreview').classList.add('hidden');
    document.body.style.overflow = 'hidden';
}

// Close Add Book Modal
function closeAddBookModal() {
    document.getElementById('addBookModal').classList.add('hidden');
    document.getElementById('addBookForm').reset();
    document.body.style.overflow = 'auto';
}

// Edit Book
function editBook(bookId) {
    fetch(API_BASE + `AdminBookController.php?action=get&id=${bookId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const book = result.data;
                document.getElementById('bookId').value = book.book_id;
                document.getElementById('bookTitle').value = book.book_title;
                document.getElementById('bookAuthor').value = book.author || '';
                document.getElementById('bookPublisher').value = book.publisher || '';
                document.getElementById('bookYear').value = book.published_year ? new Date(book.published_year).getFullYear() : '';
                document.getElementById('bookCategory').value = book.category_id;
                document.getElementById('bookDescription').value = book.description || '';
                
                // Show cover preview
                const preview = document.getElementById('coverPreview');
                if (book.image_path) {
                    preview.src = '../../' + book.image_path;
                    preview.classList.remove('hidden');
                }
                
                document.getElementById('modalTitle').textContent = 'Edit Buku';
                document.getElementById('addBookModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        })
        .catch(error => console.error('Error loading book:', error));
}

// Delete Book
function deleteBook(bookId) {
    if (confirm('Apakah Anda yakin ingin menghapus buku ini?')) {
        const formData = new FormData();
        formData.append('book_id', bookId);
        
        fetch(API_BASE + 'AdminBookController.php?action=delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('✅ Buku berhasil dihapus!');
                loadBooks();
                loadDashboardStats();
            } else {
                alert('❌ Gagal menghapus buku: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error deleting book:', error);
            alert('❌ Terjadi kesalahan saat menghapus buku');
        });
    }
}

// Handle Add/Edit Book Form Submit
function handleAddBook(event) {
    event.preventDefault();

    const bookId = document.getElementById('bookId').value;
    const isEdit = bookId !== '';
    
    const formData = new FormData();
    formData.append('book_title', document.getElementById('bookTitle').value);
    formData.append('author', document.getElementById('bookAuthor').value);
    formData.append('publisher', document.getElementById('bookPublisher').value);
    formData.append('published_year', document.getElementById('bookYear').value);
    formData.append('category_id', document.getElementById('bookCategory').value);
    formData.append('description', document.getElementById('bookDescription').value);
    
    // Handle file upload
    const coverInput = document.getElementById('bookCover');
    if (coverInput.files.length > 0) {
        formData.append('cover', coverInput.files[0]);
    }
    
    if (isEdit) {
        formData.append('book_id', bookId);
    }

    const action = isEdit ? 'update' : 'create';
    
    fetch(API_BASE + `AdminBookController.php?action=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(`✅ Buku berhasil ${isEdit ? 'diperbarui' : 'ditambahkan'}!`);
            closeAddBookModal();
            loadBooks();
            loadDashboardStats();
        } else {
            alert('❌ Gagal: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error saving book:', error);
        alert('❌ Terjadi kesalahan saat menyimpan buku');
    });
}

// Preview uploaded cover
function previewCover(input) {
    const preview = document.getElementById('coverPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ==================== BORROWING FUNCTIONS ====================

function loadBorrowings() {
    fetch(API_BASE + 'AdminDashboardController.php?action=borrowings')
        .then(response => response.json())
        .then(result => {
            const tbody = document.getElementById('borrowingsTableBody');
            if (result.success && result.data.length > 0) {
                tbody.innerHTML = result.data.map((item, index) => `
                    <tr class="table-row border-b border-gray-100">
                        <td class="px-6 py-4 font-semibold text-gray-900">#${String(item.loan_id).padStart(3, '0')}</td>
                        <td class="px-6 py-4 text-gray-900">${item.book_title}</td>
                        <td class="px-6 py-4 text-gray-600">${item.author || '-'}</td>
                        <td class="px-6 py-4 text-gray-600">${formatDate(item.loan_date)}</td>
                        <td class="px-6 py-4 text-gray-600">${formatDate(item.due_date)}</td>
                        <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Aktif</span></td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada peminjaman aktif</td></tr>';
            }
        })
        .catch(error => console.error('Error loading borrowings:', error));
}

// ==================== RETURNS FUNCTIONS ====================

function loadReturns() {
    fetch(API_BASE + 'AdminDashboardController.php?action=returns')
        .then(response => response.json())
        .then(result => {
            const tbody = document.getElementById('returnsTableBody');
            if (result.success && result.data.length > 0) {
                tbody.innerHTML = result.data.map(item => {
                    const statusColor = item.was_overdue ? 'red' : 'green';
                    const statusText = item.was_overdue ? `Terlambat ${item.days_late} hari` : 'Tepat waktu';
                    return `
                        <tr class="table-row border-b border-gray-100">
                            <td class="px-6 py-4 font-semibold text-gray-900">#${String(item.return_id).padStart(3, '0')}</td>
                            <td class="px-6 py-4 text-gray-900">${item.book_title}</td>
                            <td class="px-6 py-4 text-gray-600">${formatDate(item.loan_date)}</td>
                            <td class="px-6 py-4 text-gray-600">${formatDate(item.return_date)}</td>
                            <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-${statusColor}-100 text-${statusColor}-700">${statusText}</span></td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada data pengembalian</td></tr>';
            }
        })
        .catch(error => console.error('Error loading returns:', error));
}

// ==================== FINES FUNCTIONS ====================

function loadFines() {
    fetch(API_BASE + 'AdminDashboardController.php?action=fines')
        .then(response => response.json())
        .then(result => {
            const tbody = document.getElementById('finesTableBody');
            if (result.success && result.data.length > 0) {
                tbody.innerHTML = result.data.map(item => `
                    <tr class="table-row border-b border-gray-100">
                        <td class="px-6 py-4 font-semibold text-gray-900">#${String(item.penalty_id).padStart(3, '0')}</td>
                        <td class="px-6 py-4 text-gray-900">${item.user_name || item.username}</td>
                        <td class="px-6 py-4 text-gray-600">${item.book_title || '-'}</td>
                        <td class="px-6 py-4 text-gray-600">${item.days_late || 0} hari</td>
                        <td class="px-6 py-4 font-semibold text-red-600">${item.fines_formatted}</td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada data denda</td></tr>';
            }
        })
        .catch(error => console.error('Error loading fines:', error));
}

// ==================== MEMBERS FUNCTIONS ====================

function loadMembers() {
    fetch(API_BASE + 'AdminDashboardController.php?action=members')
        .then(response => response.json())
        .then(result => {
            const tbody = document.getElementById('membersTableBody');
            if (result.success && result.data.length > 0) {
                tbody.innerHTML = result.data.map(item => `
                    <tr class="table-row border-b border-gray-100">
                        <td class="px-6 py-4 font-semibold text-gray-900">${item.id}</td>
                        <td class="px-6 py-4 text-gray-900">${item.name || item.username}</td>
                        <td class="px-6 py-4 text-gray-600">${item.username}</td>
                        <td class="px-6 py-4 text-gray-600">${item.email || '-'}</td>
                        <td class="px-6 py-4 text-gray-600">${item.phone_number || '-'}</td>
                        <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">${item.auth_provider}</span></td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada data anggota</td></tr>';
            }
        })
        .catch(error => console.error('Error loading members:', error));
}

// ==================== UTILITY FUNCTIONS ====================

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Close modal when clicking outside
document.getElementById('addBookModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddBookModal();
    }
});
