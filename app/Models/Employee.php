<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $table='employees';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable=[
        'profile_id',
        'card_code',
        'card_name'
    ];
}
