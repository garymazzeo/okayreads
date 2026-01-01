# OkayReads

A self-hosted Goodreads alternative for tracking your reading. Built with PHP 8.5, SQLite, and vanilla JavaScript.

## Features

- Track books you've read, are reading, or want to read
- Import from Goodreads (CSV) or ISBN lists
- Export your data as CSV
- Search books online via Google Books API
- Rate and review books
- Organize with tags
- Simple, self-hosted setup with single-file database

## Requirements

- PHP 8.5 or higher
- SQLite support (usually included with PHP)
- Web server (Apache, Nginx, or PHP built-in server)

## Installation

1. Clone this repository:
```bash
git clone https://github.com/garymazzeo/okayreads.git
cd okayreads
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Edit `.env` if needed (database path, etc.)

4. Initialize the database:
```bash
php database/init.php
```

5. Start the PHP built-in server (for development):
```bash
php -S localhost:8000 -t public
```

Or configure your web server to point to the `public` directory.

6. Open your browser to `http://localhost:8000`

## Database

The application uses SQLite by default, which stores everything in a single file at `database/okayreads.db`. This file is automatically created when you run the initialization script.

To use MySQL instead, update your `.env` file with MySQL connection details.

## API Documentation

The API is RESTful and returns JSON responses.

### Books
- `GET /api/books` - List books (query params: status, author_id, tag_id, search)
- `GET /api/books/{id}` - Get book details
- `POST /api/books` - Create book
- `PUT /api/books/{id}` - Update book
- `DELETE /api/books/{id}` - Delete book
- `GET /api/books/search?q={query}` - Search books online

### User Books (Reading Status)
- `GET /api/user-books` - Get user's books (query param: status)
- `POST /api/user-books` - Add book to user's list
- `PUT /api/user-books/{id}` - Update reading status/rating/review
- `DELETE /api/user-books/{id}` - Remove from list

### Import/Export
- `POST /api/import/goodreads` - Import Goodreads CSV
- `POST /api/import/isbn-list` - Import ISBN list (one per line)
- `GET /api/export/csv` - Export data as CSV

### Authors
- `GET /api/authors` - List authors
- `GET /api/authors/{id}` - Get author with books

### Tags
- `GET /api/tags` - List tags
- `POST /api/books/{id}/tags` - Add tag to book

## Importing from Goodreads

1. Export your Goodreads library as CSV from Goodreads settings
2. Use the import feature in the web UI or POST to `/api/import/goodreads` with the CSV file

## License

MIT License - see LICENSE file for details

