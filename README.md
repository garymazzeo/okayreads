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

## Deployment

### Automatic Deployment via GitHub Actions

The project includes a GitHub Actions workflow that automatically deploys to your FTP server when you push to the `main` branch.

**Setup Instructions:**

1. Go to your GitHub repository settings
2. Navigate to **Secrets and variables** → **Actions**
3. Add the following secrets:

   - `FTP_SERVER`: Your FTP server hostname (e.g., `ftp.example.com`)
   - `FTP_USERNAME`: Your FTP username
   - `FTP_PASSWORD`: Your FTP password
   - `FTP_REMOTE_DIR`: Remote directory path (e.g., `/public_html` or `/www`)

4. Push to the `main` branch to trigger deployment

**Note:** The workflow excludes sensitive files (`.env`, database files, etc.) from deployment. Make sure to configure your `.env` file directly on the server.

## Security Notes

### .env File Location

For maximum security, place your `.env` file outside the web root directory:

```
/home/youruser/
├── .env                    ← Recommended: Outside web root
└── public_html/            ← Your FTP_REMOTE_DIR
    ├── api/
    ├── public/
    └── ...
```

If you must place it in the project root, ensure:
1. File permissions are set to `600` (read/write for owner only): `chmod 600 .env`
2. The `.htaccess` file protects it from web access (already configured)

The application will automatically look for `.env` in the parent directory first, then fall back to the project root.

### Database Auto-Initialization

The database will automatically initialize on first use. You don't need to run `php database/init.php` manually - it happens automatically when the application first connects to the database. This means after deployment, simply visit your site and the database will be created automatically.

## License

MIT License - see LICENSE file for details
