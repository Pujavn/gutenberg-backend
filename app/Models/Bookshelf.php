<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookshelf extends Model
{
    protected $table = 'books_bookshelf';
    public $timestamps = false;

    protected $fillable = ['name'];

    public function books()
    {
        return $this->belongsToMany(
            Book::class,
            'books_book_bookshelves',
            'bookshelf_id',
            'book_id'
        );
    }
}
