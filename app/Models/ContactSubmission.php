<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'contact_submissions';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'subject',
        'message',
        'captcha_code',
        'status',
        'ip_address',
        'user_agent',
        'read_at',
        'replied_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];
}