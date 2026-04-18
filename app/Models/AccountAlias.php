<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountAlias extends Model
{
    protected $table = 'account_aliases';
    protected $fillable = ['acct_code', 'alias'];
}
