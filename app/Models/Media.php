<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id','type','original_url','stored_path','filename','position','meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
