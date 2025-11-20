<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'books_book';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'gutenberg_id',
        'title',
        'media_type',
        'download_count',
    ];

    public function authors()
    {
        return $this->belongsToMany(
            Author::class,
            'books_book_authors',
            'book_id',
            'author_id'
        );
    }

    public function languages()
    {
        return $this->belongsToMany(
            Language::class,
            'books_book_languages',
            'book_id',
            'language_id'
        );
    }

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'books_book_subjects',
            'book_id',
            'subject_id'
        );
    }

    public function bookshelves()
    {
        return $this->belongsToMany(
            Bookshelf::class,
            'books_book_bookshelves',
            'book_id',
            'bookshelf_id'
        );
    }

    public function formats()
    {
        return $this->hasMany(
            Format::class,
            'book_id',
            'id'
        );
    }
}
