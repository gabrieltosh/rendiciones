<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DocumentDetail extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $table='document_details';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'document_id',
        'type',
        'percentage',
        'account',
        'exento',
        'type_calculation',
        'calculation'
    ];
    public function getCalculationAttribute($value)
    {
        return (bool) $value;
    }
}
