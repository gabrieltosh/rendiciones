<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserArea extends Model
{
    use HasFactory;

    protected $table = 'user_areas';
    protected $dateFormat = 'Y-m-d\TH:i:s';

    protected $fillable = [
        'user_id',
        'area_id',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }
}
