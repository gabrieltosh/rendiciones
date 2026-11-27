<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Un intento de exportación de una rendición a SAP.
 */
class SapExportLog extends Model
{
    protected $table = 'sap_export_logs';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable = [
        'accountability_id',
        'user_id',
        'success',
        'mode',
        'sap_key',
        'message',
        'payload',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    /** El asiento enviado no se manda a la vista (puede ser grande). */
    protected $hidden = ['payload'];

    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ? Carbon::parse($value)->setTimezone('America/La_Paz')->format('Y-m-d g:i A')
                : null
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
