<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public $table = 'expenses';

    public $fillable = [
        'user',
        'date',
        'description',
        'value',
        'type',
        'payment_method',
        'status',
    ];

    public $timestamps = true;
}
