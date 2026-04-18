<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountabilityLevelApproval extends Model
{
    use HasFactory;

    protected $table = 'accountability_level_approvals';
    protected $fillable = ['accountability_id', 'level_id', 'user_id', 'status', 'comments', 'acted_at'];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function accountability()
    {
        return $this->belongsTo(Accountability::class, 'accountability_id');
    }

    public function level()
    {
        return $this->belongsTo(AuthorizationCycleLevel::class, 'level_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
