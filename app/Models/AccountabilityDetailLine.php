<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountabilityDetailLine extends Model
{
    protected $table='accountability_detail_lines';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'accountability_detail_id',
        'combination',
        'percentage',
        'account',
        'account_name',
        'concept',
        'distribution_rule_one',
        'distribution_rule_second',
        'distribution_rule_three',
        'distribution_rule_four',
        'distribution_rule_five',
    ];
    protected $casts = [
        'percentage' => 'float',
    ];
}
