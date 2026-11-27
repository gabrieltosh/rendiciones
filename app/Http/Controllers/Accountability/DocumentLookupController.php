<?php

namespace App\Http\Controllers\Accountability;

use App\Http\Controllers\Controller;
use App\Models\AccountabilityDetail;
use Illuminate\Http\Request;

/**
 * Consultas livianas de apoyo a los formularios de documentos.
 */
class DocumentLookupController extends Controller
{
    /**
     * GET panel/document-duplicate
     * ¿La factura ya está registrada en alguna rendición? Se busca por CUF o, sin
     * CUF, por NIT + número de documento. exclude_id omite el documento que se edita.
     * Devuelve {duplicate, accountability_id, status, own}: no expone datos de
     * rendiciones de otros usuarios más allá de su número.
     */
    public function HandleCheckDuplicateDocument(Request $request)
    {
        $cuf = trim((string) $request->query('cuf', ''));
        $nit = trim((string) $request->query('nit', ''));
        $number = trim((string) $request->query('document_number', ''));
        $excludeId = $request->query('exclude_id');

        if ($cuf === '' && ($nit === '' || $number === '')) {
            return response()->json(['duplicate' => false]);
        }

        $query = AccountabilityDetail::query()
            ->join('accountabilities', 'accountabilities.id', '=', 'accountability_details.accountability_id')
            ->select(
                'accountability_details.id',
                'accountability_details.accountability_id',
                'accountabilities.status',
                'accountabilities.user_id'
            )
            // Una rendición anulada ya no cuenta la factura
            ->where(fn ($q) => $q->whereNull('accountabilities.status')->orWhere('accountabilities.status', '!=', 'Anulado'));

        if ($cuf !== '') {
            $query->where('accountability_details.cuf', $cuf);
        } else {
            $query->where('accountability_details.nit', $nit)
                ->where('accountability_details.document_number', $number);
        }
        if ($excludeId !== null && $excludeId !== '') {
            $query->where('accountability_details.id', '!=', (int) $excludeId);
        }

        $match = $query->orderByDesc('accountability_details.id')->first();
        if (!$match) {
            return response()->json(['duplicate' => false]);
        }

        return response()->json([
            'duplicate' => true,
            'accountability_id' => $match->accountability_id,
            'status' => $match->status ?? 'Borrador',
            'own' => (int) $match->user_id === (int) $request->user()->id,
        ]);
    }
}
