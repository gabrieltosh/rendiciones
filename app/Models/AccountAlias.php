<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountAlias extends Model
{
    protected $table = 'account_aliases';
    protected $fillable = ['acct_code', 'format_code', 'acct_name', 'alias'];
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $casts = ['id' => 'integer'];
}
