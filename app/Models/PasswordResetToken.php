<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    public $table = 'password_reset_tokens';
    public $fillable = [
        'email',
        'token',
        'created_at',
    ];

    public $timestamps = false;
}
