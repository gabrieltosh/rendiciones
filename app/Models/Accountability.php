<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

class Accountability extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $table='accountabilities';
    protected $dateFormat = 'Y-m-d\TH:i:s';
    protected $fillable=[
        'profile_id',
        'user_id',
        'employee_code',
        'employee_name',
        'account_code',
        'account_name',
        'total',
        'description',
        'preliminary',
        'start_date',
        'status',
        'end_date',
        'comments',
        'sap_exported',
        'sap_trans_id',
        'sap_exported_at',
        'sap_export_mode',
        'current_level_id',
    ];

    protected $casts = [
        'sap_exported' => 'boolean',
        // Sin el cast, la fecha se envía como 'Y-m-d H:i:s.v' y SQL Server en español
        // la interpreta como año-día-mes; con el cast usa $dateFormat (ISO 8601).
        'sap_exported_at' => 'datetime',
        'current_level_id' => 'integer',
    ];
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($value)->setTimezone('America/La_Paz')->format('Y-m-d g:i A');
            }
        );
    }
    public function profile(){
        return $this->belongsTo(Profile::class,'profile_id','id');
    }
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function detail(){
        return $this->hasMany(AccountabilityDetail::class,'accountability_id','id');
    }
    public function currentLevel(){
        return $this->belongsTo(AuthorizationCycleLevel::class,'current_level_id','id');
    }
    public function levelApprovals(){
        return $this->hasMany(AccountabilityLevelApproval::class,'accountability_id','id');
    }

    /**
     * Rendiciones pendientes que el usuario debe autorizar: las que están en un
     * nivel del ciclo donde él es autorizador, o (sistema legado, sin ciclo) las
     * de usuarios que tiene asignados en UserAuthorization.
     */
    public function scopePendingAuthorizationFor($query, $userId)
    {
        $levelIds = AuthorizationCycleLevel::whereHas('levelUsers', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->pluck('id');

        $legacyUserIds = UserAuthorization::where('auth_user_id', $userId)->pluck('user_id');

        return $query->where('status', 'Pendiente')
            ->where(function ($q) use ($levelIds, $legacyUserIds) {
                $q->whereIn('current_level_id', $levelIds)
                  ->orWhere(function ($q2) use ($legacyUserIds) {
                      $q2->whereNull('current_level_id')
                         ->whereIn('user_id', $legacyUserIds);
                  });
            });
    }

    public function scopePendingSapExport($query)
    {
        return $query->where('status', 'Autorizado')->where('sap_exported', 0);
    }
}
