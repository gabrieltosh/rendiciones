<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Accountability extends Model
{
    use HasFactory;
    protected $table='accountabilities';
    protected $fillable=[
        'profile_id',
        'user_id',
        'employee',
        'account',
        'total',
        'description',
        'preliminary',
        'start_date',
        'end_date',
    ];
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($value)->setTimezone('America/La_Paz')->format('Y-m-d g:i A');
            }
        );
    }
}
