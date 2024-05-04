<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use App\Models\Document;
class Profile extends Model
{
    use HasFactory;
    protected $table='profiles';
    protected $fillable=[
        'name',
        'employee_code',
        'supplier_code',
        'type_currency'
    ];
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($value)->setTimezone('America/La_Paz')->format('Y-m-d g:i A');
            }
        );
    }
    public function documents(){
        return $this->hasMany(Document::class,'profile_id','id');
    }
}
