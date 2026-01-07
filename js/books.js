/**
 * Books management module
 */

import { API } from './api.js';

class BooksManager {
    constructor() {
        this.books = [];
        this.currentFilter = '';
        this.currentStatus = '';
    }

    async loadBooks(status = '') {
        this.currentStatus = status;
        const loadingEl = document.getElementById('loading');
        const emptyStateEl = document.getElementById('empty-state');
        const booksListEl = document.getElementById('books-list');

        loadingEl.classList.remove('hidden');
        booksListEl.innerHTML = '';

        try {
            this.books = await API.getUserBooks(status);
            this.renderBooks();
        } catch (error) {
            console.error('Failed to load books:', error);
            booksListEl.innerHTML = `<div class="error">Error loading books: ${error.message}</div>`;
        } finally {
            loadingEl.classList.add('hidden');
        }
    }

    filterBooks(query) {
        this.currentFilter = query.toLowerCase();
        this.renderBooks();
    }

    renderBooks() {
        const booksListEl = document.getElementById('books-list');
        const emptyStateEl = document.getElementById('empty-state');

        let filteredBooks = this.books;

        if (this.currentFilter) {
            filteredBooks = this.books.filter(book => {
                const title = (book.title || '').toLowerCase();
                const authors = (book.authors || []).map(a => a.name || '').join(' ').toLowerCase();
                return title.includes(this.currentFilter) || authors.includes(this.currentFilter);
            });
        }

        if (filteredBooks.length === 0) {
            booksListEl.innerHTML = '';
            emptyStateEl.classList.remove('hidden');
            return;
        }

        emptyStateEl.classList.add('hidden');
        booksListEl.innerHTML = filteredBooks.map(book => this.renderBookCard(book)).join('');
    }

    renderBookCard(book) {
        const authors = (book.authors || []).map(a => a.name).join(', ') || 'Unknown Author';
        const coverUrl = book.cover_image_url || '/assets/no-cover.png';
        const statusClass = `status-${book.status || 'to_read'}`;
        const statusLabel = (book.status || 'to_read').replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        const rating = book.rating ? '★'.repeat(book.rating) + '☆'.repeat(5 - book.rating) : '';

        return `
            <div class="book-card" data-book-id="${book.book_id}">
                <div class="book-cover">
                    <img src="${coverUrl}" alt="${book.title}" onerror="this.src='/assets/no-cover.svg'">
                </div>
                <div class="book-info">
                    <h3 class="book-title">${this.escapeHtml(book.title || 'Untitled')}</h3>
                    <p class="book-author">${this.escapeHtml(authors)}</p>
                    <div class="book-meta">
                        <span class="book-status ${statusClass}">${statusLabel}</span>
                        ${rating ? `<span class="book-rating">${rating}</span>` : ''}
                    </div>
                    <div class="book-actions">
                        <button class="btn btn-sm" onclick="booksManager.showBookDetail(${book.book_id})">View</button>
                        <button class="btn btn-sm btn-danger" onclick="booksManager.deleteBook(${book.id})">Remove</button>
                    </div>
                </div>
            </div>
        `;
    }

    async showBookDetail(bookId) {
        const modal = document.getElementById('book-modal');
        const detailEl = document.getElementById('book-detail');

        try {
            const book = await API.getBook(bookId);
            const userBook = this.books.find(ub => ub.book_id === bookId);
            
            const authors = (book.authors || []).map(a => a.name).join(', ') || 'Unknown Author';
            const coverUrl = book.cover_image_url || '/assets/no-cover.png';
            const status = userBook?.status || 'to_read';
            const rating = userBook?.rating || 0;
            const review = userBook?.review || '';

            detailEl.innerHTML = `
                <div class="book-detail-content">
                    <div class="book-detail-cover">
                        <img src="${coverUrl}" alt="${book.title}" onerror="this.src='/assets/no-cover.svg'">
                    </div>
                    <div class="book-detail-info">
                        <h2>${this.escapeHtml(book.title || 'Untitled')}</h2>
                        <p class="book-detail-author">${this.escapeHtml(authors)}</p>
                        ${book.isbn ? `<p><strong>ISBN:</strong> ${this.escapeHtml(book.isbn)}</p>` : ''}
                        ${book.page_count ? `<p><strong>Pages:</strong> ${book.page_count}</p>` : ''}
                        ${book.published_date ? `<p><strong>Published:</strong> ${this.escapeHtml(book.published_date)}</p>` : ''}
                        ${book.description ? `<div class="book-description"><p>${this.escapeHtml(book.description)}</p></div>` : ''}
                        
                        <form id="book-detail-form" onsubmit="booksManager.updateBookStatus(event, ${bookId}, ${userBook?.id || 'null'})">
                            <div class="form-group">
                                <label for="detail-status">Status</label>
                                <select id="detail-status" required>
                                    <option value="to_read" ${status === 'to_read' ? 'selected' : ''}>To Read</option>
                                    <option value="reading" ${status === 'reading' ? 'selected' : ''}>Reading</option>
                                    <option value="finished" ${status === 'finished' ? 'selected' : ''}>Finished</option>
                                    <option value="dropped" ${status === 'dropped' ? 'selected' : ''}>Dropped</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="detail-rating">Rating</label>
                                <select id="detail-rating">
                                    <option value="">No rating</option>
                                    <option value="1" ${rating === 1 ? 'selected' : ''}>1 Star</option>
                                    <option value="2" ${rating === 2 ? 'selected' : ''}>2 Stars</option>
                                    <option value="3" ${rating === 3 ? 'selected' : ''}>3 Stars</option>
                                    <option value="4" ${rating === 4 ? 'selected' : ''}>4 Stars</option>
                                    <option value="5" ${rating === 5 ? 'selected' : ''}>5 Stars</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="detail-review">Review</label>
                                <textarea id="detail-review" rows="4">${this.escapeHtml(review)}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
        } catch (error) {
            console.error('Failed to load book detail:', error);
            alert('Error loading book details: ' + error.message);
        }
    }

    async updateBookStatus(event, bookId, userBookId) {
        event.preventDefault();
        
        const status = document.getElementById('detail-status').value;
        const rating = document.getElementById('detail-rating').value;
        const review = document.getElementById('detail-review').value;

        const data = {
            book_id: bookId,
            status: status,
            rating: rating ? parseInt(rating) : null,
            review: review.trim() || null,
        };

        try {
            if (userBookId) {
                await API.updateUserBook(userBookId, data);
            } else {
                await API.addUserBook(data);
            }
            
            this.closeModal();
            await this.loadBooks(this.currentStatus);
            alert('Book updated successfully!');
        } catch (error) {
            console.error('Failed to update book:', error);
            alert('Error updating book: ' + error.message);
        }
    }

    async deleteBook(userBookId) {
        if (!confirm('Are you sure you want to remove this book from your list?')) {
            return;
        }

        try {
            await API.deleteUserBook(userBookId);
            await this.loadBooks(this.currentStatus);
        } catch (error) {
            console.error('Failed to delete book:', error);
            alert('Error removing book: ' + error.message);
        }
    }

    closeModal() {
        const modal = document.getElementById('book-modal');
        modal.classList.add('hidden');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instance
const booksManager = new BooksManager();

