<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountabilityDetailSplit extends Model
{
    protected $table='accountability_detail_splits';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'accountability_detail_id',
        'dimension',
        'code',
        'percentage',
    ];
    protected $casts = [
        'dimension' => 'integer',
        'percentage' => 'float',
    ];
}
