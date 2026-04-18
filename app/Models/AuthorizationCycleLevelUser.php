<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorizationCycleLevelUser extends Model
{
    use HasFactory;

    protected $table = 'authorization_cycle_level_users';
    protected $fillable = ['level_id', 'user_id'];

    public function level()
    {
        return $this->belongsTo(AuthorizationCycleLevel::class, 'level_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
