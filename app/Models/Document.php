<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    protected $table='documents';
    protected $fillable=[
        'name',
        'type_document_sap',
        'type_calculation',
        'profile_id',
    ];
    public function detail(){
        return $this->hasMany(DocumentDetail::class,'document_id','id');
    }
}
