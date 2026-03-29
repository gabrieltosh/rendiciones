<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class UserAuthorization extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $table='user_authorizations';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
            'user_id',
            'auth_user_id'
    ];
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
}
