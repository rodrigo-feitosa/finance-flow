<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    public $table = 'revenues';
    public $fillable = [
        'user',
        'date',
        'description',
        'value',
        'status',
    ];

    public $timestamps = true;
}
