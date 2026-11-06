<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Artículo de un documento con ítems. El precio es sin IVA: el total de los ítems
 * es igual al gasto del documento y se exporta como entrada de mercancías.
 */
class AccountabilityDetailItem extends Model
{
    protected $table = 'accountability_detail_items';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable = [
        'accountability_detail_id',
        'item_code',
        'item_name',
        'quantity',
        'price',
        'warehouse_code',
    ];
    protected $casts = [
        'quantity' => 'float',
        'price' => 'float',
    ];
}
