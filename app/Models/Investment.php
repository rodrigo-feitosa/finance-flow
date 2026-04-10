<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    public $table = 'investments';
    public $fillable = [
        'user',
        'date',
        'description',
        'value',
        'type',
        'category',
        'institution',
        'status',
    ];

    public $timestamps = true;
}
