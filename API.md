# OkayReads API Documentation

This document describes the REST API endpoints for OkayReads.

## Base URL

All API endpoints are prefixed with `/api`

## Response Format

All responses are JSON formatted. Successful responses follow this structure:

```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

Error responses:

```json
{
  "success": false,
  "error": "Error message",
  "errors": [ "Optional validation errors" ]
}
```

## Authentication

Currently, the API uses a single default user (user ID 1) for MVP. Multi-user authentication will be added in future versions.

## Endpoints

### Books

#### List Books
`GET /api/books`

Query parameters:
- `search` (optional) - Search query
- `author_id` (optional) - Filter by author ID
- `tag_id` (optional) - Filter by tag ID

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Example Book",
      "isbn": "9781234567890",
      "description": "Book description",
      "page_count": 300,
      "published_date": "2020-01-01",
      "cover_image_url": "https://example.com/cover.jpg",
      "authors": [
        { "id": 1, "name": "Author Name" }
      ],
      "tags": []
    }
  ]
}
```

#### Get Book
`GET /api/books/{id}`

Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Example Book",
    ...
  }
}
```

#### Create Book
`POST /api/books`

Request body:
```json
{
  "title": "Book Title",
  "isbn": "9781234567890",
  "description": "Description",
  "page_count": 300,
  "published_date": "2020-01-01",
  "cover_image_url": "https://example.com/cover.jpg",
  "authors": ["Author Name", "Another Author"]
}
```

#### Update Book
`PUT /api/books/{id}`

Request body: Same as create, but all fields are optional.

#### Delete Book
`DELETE /api/books/{id}`

#### Search Books Online
`GET /api/books/search?q={query}`

Searches Google Books API and Open Library for book metadata.

### User Books (Reading Status)

#### Get User's Books
`GET /api/user-books`

Query parameters:
- `status` (optional) - Filter by status: `to_read`, `reading`, `finished`, `dropped`

Response includes book details along with reading status.

#### Add Book to User's List
`POST /api/user-books`

Request body:
```json
{
  "book_id": 1,
  "status": "to_read",
  "date_started": "2024-01-01",
  "date_finished": null,
  "rating": 5,
  "review": "Great book!"
}
```

#### Update User Book
`PUT /api/user-books/{id}`

Request body: Same as create, but all fields are optional.

#### Remove Book from List
`DELETE /api/user-books/{id}`

### Authors

#### List Authors
`GET /api/authors`

#### Get Author with Books
`GET /api/authors/{id}`

### Tags

#### List Tags
`GET /api/tags`

#### Add Tag to Book
`POST /api/books/{id}/tags`

Request body:
```json
{
  "tag_id": 1
}
```

or

```json
{
  "tag_name": "Science Fiction"
}
```

### Import/Export

#### Import Goodreads CSV
`POST /api/import/goodreads`

Content-Type: `multipart/form-data`

Request: File upload with field name `file`

Response:
```json
{
  "success": true,
  "data": {
    "imported": 10,
    "skipped": 2,
    "errors": []
  }
}
```

#### Import ISBN List
`POST /api/import/isbn-list`

Content-Type: `multipart/form-data`

Request: Text file with one ISBN per line

#### Export CSV
`GET /api/export/csv`

Returns a CSV file download with all user's books.

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `500` - Internal Server Error

## Examples

### cURL Examples

**Get all books:**
```bash
curl http://localhost:8000/api/books
```

**Create a book:**
```bash
curl -X POST http://localhost:8000/api/books \
  -H "Content-Type: application/json" \
  -d '{"title":"My Book","authors":["Author Name"]}'
```

**Import Goodreads CSV:**
```bash
curl -X POST http://localhost:8000/api/import/goodreads \
  -F "file=@goodreads_export.csv"
```

