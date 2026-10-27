<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
    ];
}
