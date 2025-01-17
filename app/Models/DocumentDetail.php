<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentDetail extends Model
{
    use HasFactory;
    protected $table='document_details';
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
