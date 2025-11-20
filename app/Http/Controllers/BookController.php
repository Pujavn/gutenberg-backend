<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        // Base query with eager-loaded relations to avoid N+1
        $query = Book::with([
            'authors',
            'languages',
            'subjects',
            'bookshelves',
            'formats',
        ]);

        /*
         |-------------------------------------------------------
         | 1. Filter by Gutenberg IDs: ?ids=11,12,13
         |-------------------------------------------------------
         */
        if ($request->filled('ids')) {
            $ids = $this->csvToArray($request->query('ids'));
            $query->whereIn('gutenberg_id', $ids);
        }

        /*
         |-------------------------------------------------------
         | 2. Filter by language: ?language=en,fr
         |   matches books_book_languages + books_language.code
         |-------------------------------------------------------
         */
        if ($request->filled('language')) {
            $languages = $this->csvToArray($request->query('language'));

            $query->whereHas('languages', function ($q) use ($languages) {
                $q->whereIn('code', $languages);
            });
        }

        /*
         |-------------------------------------------------------
         | 3. Filter by mime_type: ?mime_type=text/,text/html
         |   books_format.mime_type LIKE 'text/%'
         |-------------------------------------------------------
         */
        if ($request->filled('mime_type')) {
            $mimeTypes = $this->csvToArray($request->query('mime_type'));

            $query->whereHas('formats', function ($q) use ($mimeTypes) {
                $q->where(function ($q2) use ($mimeTypes) {
                    foreach ($mimeTypes as $mime) {
                        $q2->orWhere('mime_type', 'like', $mime . '%');
                    }
                });
            });
        }

        /*
         |-------------------------------------------------------
         | 4. Filter by topic: ?topic=child,infant
         |   - partial, case-insensitive
         |   - applies on subjects.name OR bookshelves.name
         |-------------------------------------------------------
         */
        if ($request->filled('topic')) {
            $topics = $this->csvToArray($request->query('topic'));

            $query->where(function ($q) use ($topics) {
                foreach ($topics as $topic) {
                    $like = '%' . $topic . '%';

                    // subject match OR bookshelf match for each topic
                    $q->orWhereHas('subjects', function ($qs) use ($like) {
                        $qs->where('name', 'ILIKE', $like);
                    })->orWhereHas('bookshelves', function ($qb) use ($like) {
                        $qb->where('name', 'ILIKE', $like);
                    });
                }
            });
        }

        /*
         |-------------------------------------------------------
         | 5. Filter by author: ?author=austen,doe
         |   - partial, case-insensitive on books_author.name
         |-------------------------------------------------------
         */
        if ($request->filled('author')) {
            $authors = $this->csvToArray($request->query('author'));

            $query->whereHas('authors', function ($qa) use ($authors) {
                foreach ($authors as $author) {
                    $like = '%' . mb_strtolower($author) . '%';
                    $qa->orWhereRaw('LOWER(name) LIKE ?', [$like]);
                }
            });
        }

        /*
         |-------------------------------------------------------
         | 6. Filter by title: ?title=pride,war
         |   - partial, case-insensitive on books_book.title
         |-------------------------------------------------------
         */
        if ($request->filled('title')) {
            $titles = $this->csvToArray($request->query('title'));

            $query->where(function ($qt) use ($titles) {
                foreach ($titles as $title) {
                    $like = '%' . mb_strtolower($title) . '%';
                    $qt->orWhereRaw('LOWER(title) LIKE ?', [$like]);
                }
            });
        }

        /*
         |-------------------------------------------------------
         | 7. Sorting by popularity: download_count DESC
         |-------------------------------------------------------
         */
        $query->orderByDesc('download_count')
            ->orderBy('id'); // tie-breaker for stable order

        /*
         |-------------------------------------------------------
         | 8. Pagination with max 25 results per page
         |-------------------------------------------------------
         */
        $pageSize = (int) $request->query('page_size', 25);
        if ($pageSize <= 0) {
            $pageSize = 25;
        }
        if ($pageSize > 25) {
            $pageSize = 25;
        }

        $paginator = $query->paginate($pageSize);

        /*
         |-------------------------------------------------------
         | 9. Transform result into clean JSON
         |-------------------------------------------------------
         */
        $results = $paginator->map(function (Book $book) {
            $subjects    = $book->subjects->pluck('name')->values();
            $bookshelves = $book->bookshelves->pluck('name')->values();
            $genre = $bookshelves->first() ?? $subjects->first() ?? null;
            return [
                'id'           => $book->gutenberg_id,
                'title'        => $book->title,
                'genre'         => $genre,
                'authors'       => $book->authors->map(function ($author) {
                    return [
                        'name'       => $author->name,
                        'birth_year' => $author->birth_year,
                        'death_year' => $author->death_year,
                    ];
                })->values(),
                'languages'    => $book->languages->pluck('code')->values(),
                'subjects'      => $subjects,
                'bookshelves'   => $bookshelves,
                'download_count'=> $book->download_count,
                'formats'       => $book->formats->map(function ($f) {
                    return [
                        'mime_type' => $f->mime_type,
                        'url'       => $f->url,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'count'     => $paginator->total(),
            'next' => $paginator->nextPageUrl(),
            'previous' => $paginator->previousPageUrl(),
            'page'      => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
            'results'   => $results,
        ]);
    }

    /**
     * Convert a comma-separated query param into a clean array.
     * Example: "en, fr,  de " => ["en","fr","de"]
     */
    protected function csvToArray(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn($v) => trim($v))
            ->filter()
            ->values()
            ->all();
    }
}
