/**
 * Main application entry point
 */

import { API } from './api.js';

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', async () => {
    // Check authentication first
    await checkAuth();
    initializeAuth();
    initializeNavigation();
    initializeBooksView();
    initializeAddBookView();
    initializeImportView();
    initializeModal();
    
    // Listen for auth-required events
    window.addEventListener('auth-required', () => {
        showAuth();
    });
});

// Authentication state
let currentUser = null;

async function checkAuth() {
    try {
        currentUser = await API.getCurrentUser();
        showApp();
    } catch (error) {
        showAuth();
    }
}

function showAuth() {
    document.getElementById('auth-view').classList.add('active');
    document.getElementById('books-view').classList.remove('active');
    document.getElementById('add-view').classList.remove('active');
    document.getElementById('import-view').classList.remove('active');
    document.getElementById('main-nav').classList.add('hidden');
    document.getElementById('user-info').classList.add('hidden');
}

function showApp() {
    document.getElementById('auth-view').classList.remove('active');
    document.getElementById('books-view').classList.add('active');
    document.getElementById('main-nav').classList.remove('hidden');
    if (currentUser) {
        document.getElementById('user-info').textContent = `Logged in as: ${currentUser.username}`;
        document.getElementById('user-info').classList.remove('hidden');
    }
}

function initializeAuth() {
    // Auth tab switching
    const authTabButtons = document.querySelectorAll('#auth-view .tab-btn');
    const authTabContents = document.querySelectorAll('#auth-view .tab-content');

    authTabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.dataset.tab;

            authTabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            authTabContents.forEach(tab => tab.classList.remove('active'));
            document.getElementById(`${tabName}-tab`).classList.add('active');
        });
    });

    // Login form
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        loginError.classList.add('hidden');
        
        const username = document.getElementById('login-username').value.trim();
        const password = document.getElementById('login-password').value;

        try {
            currentUser = await API.login({ username, password });
            showApp();
            booksManager.loadBooks();
        } catch (error) {
            loginError.textContent = error.message || 'Login failed';
            loginError.classList.remove('hidden');
        }
    });

    // Register form
    const registerForm = document.getElementById('register-form');
    const registerError = document.getElementById('register-error');
    
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        registerError.classList.add('hidden');
        
        const username = document.getElementById('register-username').value.trim();
        const email = document.getElementById('register-email').value.trim();
        const password = document.getElementById('register-password').value;

        try {
            currentUser = await API.register({ username, email, password });
            showApp();
            booksManager.loadBooks();
        } catch (error) {
            registerError.textContent = error.message || 'Registration failed';
            registerError.classList.remove('hidden');
        }
    });

    // Logout button
    const logoutBtn = document.getElementById('logout-btn');
    logoutBtn.addEventListener('click', async () => {
        try {
            await API.logout();
            currentUser = null;
            showAuth();
        } catch (error) {
            console.error('Logout failed:', error);
        }
    });
}

function initializeNavigation() {
    const navButtons = document.querySelectorAll('.nav-btn');
    const views = document.querySelectorAll('.view');

    navButtons.forEach(button => {
        button.addEventListener('click', () => {
            const viewName = button.dataset.view;

            // Update active nav button
            navButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Show corresponding view
            views.forEach(view => view.classList.remove('active'));
            document.getElementById(`${viewName}-view`).classList.add('active');
        });
    });
}

function initializeBooksView() {
    // Load books on view show
    const booksView = document.getElementById('books-view');
    const statusFilter = document.getElementById('status-filter');
    const searchInput = document.getElementById('search-input');

    statusFilter.addEventListener('change', () => {
        booksManager.loadBooks(statusFilter.value);
    });

    searchInput.addEventListener('input', () => {
        booksManager.filterBooks(searchInput.value);
    });

    // Load initial books
    booksManager.loadBooks();
}

function initializeAddBookView() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.dataset.tab;

            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            tabContents.forEach(tab => tab.classList.remove('active'));
            document.getElementById(`${tabName}-tab`).classList.add('active');
        });
    });

    // Search form
    const searchForm = document.getElementById('search-form');
    const searchResults = document.getElementById('search-results');

    searchForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const query = document.getElementById('search-query').value.trim();

        if (!query) return;

        searchResults.innerHTML = '<div class="loading">Searching...</div>';

        try {
            const results = await API.searchBooks(query);
            renderSearchResults(results);
        } catch (error) {
            console.error('Search failed:', error);
            searchResults.innerHTML = `<div class="error">Error searching: ${error.message}</div>`;
        }
    });

    function renderSearchResults(results) {
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="empty-state">No results found</div>';
            return;
        }

        searchResults.innerHTML = results.map(book => {
            const authors = (book.authors || []).join(', ') || 'Unknown Author';
            const coverUrl = book.cover_image_url || '/assets/no-cover.svg';

            return `
                <div class="search-result">
                    <img src="${coverUrl}" alt="${book.title}" onerror="this.src='/assets/no-cover.png'">
                    <div class="search-result-info">
                        <h4>${escapeHtml(book.title)}</h4>
                        <p>${escapeHtml(authors)}</p>
                        ${book.published_date ? `<p class="meta">${escapeHtml(book.published_date)}</p>` : ''}
                        <button class="btn btn-sm btn-primary" onclick="addBookFromSearch(${JSON.stringify(book).replace(/"/g, '&quot;')})">Add Book</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Manual entry form
    const manualForm = document.getElementById('manual-book-form');
    manualForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const authors = document.getElementById('authors').value
            .split(',')
            .map(a => a.trim())
            .filter(a => a);

        const bookData = {
            title: document.getElementById('title').value.trim(),
            authors: authors,
            isbn: document.getElementById('isbn').value.trim() || null,
            description: document.getElementById('description').value.trim() || null,
            page_count: document.getElementById('page-count').value ? parseInt(document.getElementById('page-count').value) : null,
            published_date: document.getElementById('published-date').value.trim() || null,
            cover_image_url: document.getElementById('cover-url').value.trim() || null,
        };

        try {
            const book = await API.createBook(bookData);
            
            // Add to user's list
            await API.addUserBook({
                book_id: book.id,
                status: 'to_read'
            });

            alert('Book added successfully!');
            manualForm.reset();
            
            // Switch to books view and reload
            document.querySelector('[data-view="books"]').click();
            booksManager.loadBooks();
        } catch (error) {
            console.error('Failed to add book:', error);
            alert('Error adding book: ' + error.message);
        }
    });
}

async function addBookFromSearch(bookData) {
    try {
        const authors = bookData.authors || [];
        
        const book = await API.createBook({
            title: bookData.title,
            authors: authors,
            isbn: bookData.isbn || null,
            description: bookData.description || null,
            page_count: bookData.page_count || null,
            published_date: bookData.published_date || null,
            cover_image_url: bookData.cover_image_url || null,
        });

        await API.addUserBook({
            book_id: book.id,
            status: 'to_read'
        });

        alert('Book added successfully!');
        
        // Switch to books view and reload
        document.querySelector('[data-view="books"]').click();
        booksManager.loadBooks();
    } catch (error) {
        console.error('Failed to add book:', error);
        alert('Error adding book: ' + error.message);
    }
}

function initializeImportView() {
    // Goodreads import
    const goodreadsForm = document.getElementById('goodreads-import-form');
    const goodreadsResult = document.getElementById('goodreads-import-result');

    goodreadsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const file = document.getElementById('goodreads-file').files[0];

        if (!file) return;

        goodreadsResult.innerHTML = '<div class="loading">Importing...</div>';

        try {
            const result = await API.importGoodreads(file);
            goodreadsResult.innerHTML = `
                <div class="success">
                    <p>Import completed!</p>
                    <ul>
                        <li>Imported: ${result.data.imported || result.imported}</li>
                        <li>Skipped: ${result.data.skipped || result.skipped}</li>
                        ${(result.data.errors || result.errors || []).length > 0 
                            ? `<li>Errors: ${(result.data.errors || result.errors).length}</li>` 
                            : ''}
                    </ul>
                </div>
            `;
            
            // Reload books
            booksManager.loadBooks();
        } catch (error) {
            console.error('Import failed:', error);
            goodreadsResult.innerHTML = `<div class="error">Error importing: ${error.message}</div>`;
        }
    });

    // ISBN list import
    const isbnForm = document.getElementById('isbn-import-form');
    const isbnResult = document.getElementById('isbn-import-result');

    isbnForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const file = document.getElementById('isbn-file').files[0];

        if (!file) return;

        isbnResult.innerHTML = '<div class="loading">Importing...</div>';

        try {
            const result = await API.importIsbnList(file);
            isbnResult.innerHTML = `
                <div class="success">
                    <p>Import completed!</p>
                    <ul>
                        <li>Imported: ${result.data.imported || result.imported}</li>
                        <li>Skipped: ${result.data.skipped || result.skipped}</li>
                        ${(result.data.errors || result.errors || []).length > 0 
                            ? `<li>Errors: ${(result.data.errors || result.errors).length}</li>` 
                            : ''}
                    </ul>
                </div>
            `;
            
            // Reload books
            booksManager.loadBooks();
        } catch (error) {
            console.error('Import failed:', error);
            isbnResult.innerHTML = `<div class="error">Error importing: ${error.message}</div>`;
        }
    });
}

function initializeModal() {
    const modal = document.getElementById('book-modal');
    const closeBtn = document.querySelector('.close-modal');

    closeBtn.addEventListener('click', () => {
        booksManager.closeModal();
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            booksManager.closeModal();
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Make functions global for inline handlers
window.addBookFromSearch = addBookFromSearch;

