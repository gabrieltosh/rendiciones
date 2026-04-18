<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorizationCycle extends Model
{
    use HasFactory;

    protected $table = 'authorization_cycles';
    protected $fillable = ['name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function levels()
    {
        return $this->hasMany(AuthorizationCycleLevel::class, 'cycle_id')->orderBy('order');
    }
}
