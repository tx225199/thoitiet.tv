<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip',
        'username',
        'password',
        'rotate_url',
        'active',
        'last_used_at',
        'last_rotated_at',
        'rotate_cooldown',
    ];
}
