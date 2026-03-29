<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

class Accountability extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $table='accountabilities';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'profile_id',
        'user_id',
        'employee_code',
        'employee_name',
        'account_code',
        'account_name',
        'total',
        'description',
        'preliminary',
        'start_date',
        'status',
        'end_date',
        'comments',
        'sap_exported',
    ];

    protected $casts = [
        'sap_exported' => 'boolean',
    ];
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($value)->setTimezone('America/La_Paz')->format('Y-m-d g:i A');
            }
        );
    }
    public function profile(){
        return $this->belongsTo(Profile::class,'profile_id','id');
    }
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function detail(){
        return $this->hasMany(AccountabilityDetail::class,'accountability_id','id');
    }
}
