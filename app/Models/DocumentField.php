<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DocumentField extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $table='document_fields';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'document_id',
        'account',
        'name',
        'type_calculation'
    ];
}
