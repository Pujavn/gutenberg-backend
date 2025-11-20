<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $table = 'books_language';
    public $timestamps = false;

    protected $fillable = ['code'];

    public function books()
    {
        return $this->belongsToMany(
            Book::class,
            'books_book_languages',
            'language_id',
            'book_id'
        );
    }
}
