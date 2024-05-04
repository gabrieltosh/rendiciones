<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
class AccountabilityDetail extends Model
{
    use HasFactory;
    protected $table='accountability_details';
    protected $fillable=[
        'accountability_id',
        'account',
        'account_name',
        'date',
        'document_id',
        'document_number',
        'authorization_number',
        'cuf',
        'control_code',
        'supplier_code',
        'business_name',
        'nit',
        'concept',
        'amount',
        'discount',
        'excento',
        'rate',
        'gift_card',
        'rate_zero',
        'ice',
        'project_code',
        'distribution_rule_one',
        'distribution_rule_second',
        'distribution_rule_three',
        'distribution_rule_four',
        'distribution_rule_five',
    ];
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($value)->setTimezone('America/La_Paz')->format('Y-m-d g:i A');
            }
        );
    }
}
