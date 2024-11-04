<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountabilityField extends Model
{
    use HasFactory;
    protected $table='accountability_fields';
    protected $fillable=[
        'value',
        'field_id',
        'accountability_detail_id',
        'name'
    ];
    public function document_field(){
        return $this->belongsTo(DocumentField::class,'field_id','id');
    }
}
