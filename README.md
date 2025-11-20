# Bookstore API (Project Gutenberg / Gutendex)

This is a Laravel-based API built on top of a PostgreSQL database dump from Project Gutenberg (Gutendex).
It exposes a `/api/books` endpoint with multiple filters, pagination, and sorting by download count.

## Tech Stack

- Laravel 12
- PostgreSQL (Gutendex dump)
- OpenAPI 3.0 (Swagger) – `openapi.yaml`

## Running Locally

1. Clone the repo
2. Install dependencies:

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
