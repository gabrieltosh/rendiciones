<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class AuthorizationCycle extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'authorization_cycles';
    protected $fillable = ['name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    protected $dateFormat = 'Y-m-d\TH:i:s';
    public function levels()
    {
        return $this->hasMany(AuthorizationCycleLevel::class, 'cycle_id')->orderBy('order');
    }
}
