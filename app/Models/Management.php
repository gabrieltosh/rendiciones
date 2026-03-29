<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Management extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $table='management';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'group',
        'name',
        'label',
        'value',
        'type'
    ];
}
