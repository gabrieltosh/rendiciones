<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAuthorization extends Model
{
    use HasFactory;
    protected $table='user_authorizations';
    protected $fillable=[
            'user_id',
            'auth_user_id'
    ];
}
