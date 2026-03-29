<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class UserProfile extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table='user_profiles';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'user_id',
        'profile_id'
    ];
    public function profile(){
        return $this->belongsTo(Profile::class,'profile_id','id');
    }
}
