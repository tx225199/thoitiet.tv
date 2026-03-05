<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'hidden',
        'sort'
    ];


    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_genres', 'genre_id', 'article_id');
    }
}
