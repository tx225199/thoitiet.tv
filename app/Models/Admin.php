<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Model implements AuthenticatableContract
{
    use Authenticatable, SoftDeletes;
    protected $table = 'admins';

    protected $fillable = [
        'name','email','password'
    ];
}
