<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
class DetailAccounts extends Model
{
    use HasFactory;
    protected $table='detail_accounts';
    protected $fillable=[
        'profile_id',
        'account_code',
        'format_code',
        'account_name',
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
