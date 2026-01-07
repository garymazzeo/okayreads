/**
 * API Client
 * Handles all API communication
 */

const API_BASE = '/api';

export class API {
    /**
     * Generic fetch wrapper
     */
    static async request(endpoint, options = {}) {
        const url = API_BASE + endpoint;
        const defaults = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const config = { ...defaults, ...options };
        
        // Handle FormData (for file uploads)
        if (options.body instanceof FormData) {
            delete config.headers['Content-Type'];
        } else if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                // Handle 401 (unauthorized) - redirect to login
                if (response.status === 401) {
                    // Trigger auth check in app.js
                    if (window.location.hash !== '#login') {
                        window.dispatchEvent(new CustomEvent('auth-required'));
                    }
                }
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            }

            return data.data || data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    // Books
    static async getBooks(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request('/books?' + params);
    }

    static async getBook(id) {
        return this.request(`/books/${id}`);
    }

    static async createBook(bookData) {
        return this.request('/books', {
            method: 'POST',
            body: bookData,
        });
    }

    static async updateBook(id, bookData) {
        return this.request(`/books/${id}`, {
            method: 'PUT',
            body: bookData,
        });
    }

    static async deleteBook(id) {
        return this.request(`/books/${id}`, {
            method: 'DELETE',
        });
    }

    static async searchBooks(query) {
        return this.request(`/books/search?q=${encodeURIComponent(query)}`);
    }

    // User Books
    static async getUserBooks(status = null) {
        const params = status ? `?status=${status}` : '';
        return this.request('/user-books' + params);
    }

    static async addUserBook(userBookData) {
        return this.request('/user-books', {
            method: 'POST',
            body: userBookData,
        });
    }

    static async updateUserBook(id, userBookData) {
        return this.request(`/user-books/${id}`, {
            method: 'PUT',
            body: userBookData,
        });
    }

    static async deleteUserBook(id) {
        return this.request(`/user-books/${id}`, {
            method: 'DELETE',
        });
    }

    // Authors
    static async getAuthors() {
        return this.request('/authors');
    }

    static async getAuthor(id) {
        return this.request(`/authors/${id}`);
    }

    // Tags
    static async getTags() {
        return this.request('/tags');
    }

    static async addTagToBook(bookId, tagIdOrName) {
        const body = tagIdOrName.match(/^\d+$/) 
            ? { tag_id: parseInt(tagIdOrName) }
            : { tag_name: tagIdOrName };
        
        return this.request(`/books/${bookId}/tags`, {
            method: 'POST',
            body: body,
        });
    }

    // Import
    static async importGoodreads(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        return this.request('/import/goodreads', {
            method: 'POST',
            body: formData,
        });
    }

    static async importIsbnList(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        return this.request('/import/isbn-list', {
            method: 'POST',
            body: formData,
        });
    }

    // Authentication
    static async register(userData) {
        return this.request('/auth/register', {
            method: 'POST',
            body: userData,
        });
    }

    static async login(credentials) {
        return this.request('/auth/login', {
            method: 'POST',
            body: credentials,
        });
    }

    static async logout() {
        return this.request('/auth/logout', {
            method: 'POST',
        });
    }

    static async getCurrentUser() {
        return this.request('/auth/me');
    }
}

