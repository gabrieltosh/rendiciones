<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAuthorizationCycle extends Model
{
    use HasFactory;

    protected $table = 'user_authorization_cycles';
    protected $fillable = ['user_id', 'cycle_id'];
    protected $dateFormat = 'Y-m-d\TH:i:s';
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cycle()
    {
        return $this->belongsTo(AuthorizationCycle::class, 'cycle_id');
    }
}
