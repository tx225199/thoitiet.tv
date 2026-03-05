<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'genre_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'thumbnail',
        'avatar',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'highlight',
        'hidden',
        'published_at',
        'url',
        'copyright',
        'copy_at',
        'post_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Scope: chỉ lấy bài đã publish
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('hidden', 0);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tags', 'article_id', 'tag_id');
    }

    public function genre()
    {
        return $this->belongsTo(Genre::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function getIsVideoAttribute()
    {
        return $this->type === 'video' || filled($this->video_embed) || filled($this->video_url);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'article_genres', 'article_id', 'genre_id');
    }

    public function scopeInAnyGenre($q, $genreId)
    {
        return $q->whereHas('genres', fn($s) => $s->where('genres.id', $genreId));
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by')->withDefault();
    }

    public function updatedBy()
    {
        return $this->belongsTo(Admin::class, 'updated_by')->withDefault();
    }
}
