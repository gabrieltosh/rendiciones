<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rondas de envío: cada vez que el solicitante envía (o reenvía tras un rechazo) la
 * rendición empieza una ronda nueva. Las aprobaciones guardan la ronda en la que se
 * hicieron, para no mezclar la cadena actual con la de envíos anteriores.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Protegido con hasColumn: si el relleno de datos falla, la migración se puede reintentar
        if (!Schema::hasColumn('accountabilities', 'submission_round')) {
            Schema::table('accountabilities', function (Blueprint $table) {
                $table->unsignedInteger('submission_round')->default(0);
                $table->dateTime('submitted_at')->nullable();
            });
        }

        if (!Schema::hasColumn('accountability_level_approvals', 'round')) {
            Schema::table('accountability_level_approvals', function (Blueprint $table) {
                $table->unsignedInteger('round')->default(1);
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('accountability_level_approvals', function (Blueprint $table) {
            $table->dropColumn('round');
        });

        Schema::table('accountabilities', function (Blueprint $table) {
            $table->dropColumn(['submission_round', 'submitted_at']);
        });
    }

    /**
     * Reconstruye las rondas de los datos existentes a partir de la auditoría:
     * cada cambio de estado a "Pendiente" es un envío. Si no hay auditoría, una
     * rendición ya enviada cuenta como una sola ronda.
     */
    private function backfill(): void
    {
        $submissions = [];
        DB::table('audits')
            ->where('auditable_type', 'App\\Models\\Accountability')
            ->where('new_values', 'like', '%"status":"Pendiente"%')
            ->orderBy('created_at')
            ->orderBy('id')
            ->select('id', 'auditable_id', 'created_at')
            ->chunk(500, function ($rows) use (&$submissions) {
                foreach ($rows as $row) {
                    $submissions[$row->auditable_id][] = $row->created_at;
                }
            });

        DB::table('accountabilities')
            ->whereNotNull('status')
            ->select('id', 'updated_at')
            ->chunkById(500, function ($rows) use ($submissions) {
                foreach ($rows as $row) {
                    $dates = $submissions[$row->id] ?? [];
                    DB::table('accountabilities')->where('id', $row->id)->update([
                        'submission_round' => max(1, count($dates)),
                        // ISO 8601: SQL Server en español lee 'Y-m-d H:i:s' como año-día-mes
                        'submitted_at' => $dates ? Carbon::parse(end($dates))->format('Y-m-d\TH:i:s') : null,
                    ]);

                    // Cada aprobación pertenece a la ronda del último envío anterior a ella
                    if (count($dates) > 1) {
                        $approvals = DB::table('accountability_level_approvals')
                            ->where('accountability_id', $row->id)
                            ->select('id', 'acted_at')
                            ->get();
                        foreach ($approvals as $approval) {
                            $round = 0;
                            foreach ($dates as $date) {
                                if (strtotime($date) <= strtotime($approval->acted_at)) {
                                    $round++;
                                }
                            }
                            DB::table('accountability_level_approvals')
                                ->where('id', $approval->id)
                                ->update(['round' => max(1, $round)]);
                        }
                    }
                }
            });
    }
};
