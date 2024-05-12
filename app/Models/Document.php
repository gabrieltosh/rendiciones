<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Document extends Model
{
    use HasFactory;
    protected $table='documents';
    protected $fillable=[
        'name',
        'type_document_sap',
        'type_calculation',
        'profile_id',
        'ice',
        'tasas',
        'exento',
        'ice_status',
        'tasas_status',
        'exento_status',
        'authorization_number_status',
        'cuf_status',
        'control_code_status',
        'business_name_status',
        'nit_status',
        'discount_status',
        'gift_card_status',
        'rate_zero_status',
    ];
    public function detail(){
        return $this->hasMany(DocumentDetail::class,'document_id','id');
    }
    public function rateZeroStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function giftCardStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function discountStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function nitStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function businessNameStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function controlCodeStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function cufStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function authorizationNumberStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function iceStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function tasasStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function exentoStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (bool)$value;
            }
        );
    }
    public function exento(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (float)$value;
            },
            set: function ($value) {
                return (float)$value;
            }
        );
    }
    public function ice(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (float)$value;
            },
            set: function ($value) {
                return (float)$value;
            }
        );
    }
    public function tasas(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (float)$value;
            },
            set: function ($value) {
                return (float)$value;
            }
        );
    }
}
