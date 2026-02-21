<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentField extends Model
{
    use HasFactory;
    protected $table='document_fields';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable=[
        'document_id',
        'account',
        'name',
        'type_calculation'
    ];
}
