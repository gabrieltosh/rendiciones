<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Management extends Model
{
    use HasFactory;
    protected $table='management';
    protected $fillable=[
        'group',
        'name',
        'label',
        'value',
        'type'
    ];
}
