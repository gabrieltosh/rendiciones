<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
class GeneralAccounts extends Model
{
    use HasFactory;
    protected $table='general_accounts';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'profile_id',
        'account_code',
        'format_code',
        'account_name',
        'associated_account',
    ];
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($value)->setTimezone('America/La_Paz')->format('Y-m-d g:i A');
            }
        );
    }
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }
}
