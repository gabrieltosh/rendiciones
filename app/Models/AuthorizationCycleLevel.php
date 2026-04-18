<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorizationCycleLevel extends Model
{
    use HasFactory;

    protected $table = 'authorization_cycle_levels';
    protected $fillable = ['cycle_id', 'order', 'name'];

    public function cycle()
    {
        return $this->belongsTo(AuthorizationCycle::class, 'cycle_id');
    }

    public function levelUsers()
    {
        return $this->hasMany(AuthorizationCycleLevelUser::class, 'level_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'authorization_cycle_level_users', 'level_id', 'user_id');
    }

    public function nextLevel()
    {
        return AuthorizationCycleLevel::where('cycle_id', $this->cycle_id)
            ->where('order', '>', $this->order)
            ->orderBy('order')
            ->first();
    }
}
