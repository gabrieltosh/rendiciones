<?php

namespace App\Http\Requests\Accountability;

use App\Models\Document;
use App\Services\Accountability\ExpenseDistribution;
use App\Services\Accountability\ExpenseItems;
use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account'=>'required',
            'date'=>'required',
            'document_id'=>'required',
            'concept'=>'required',
            'document_number'=>'required',
            //'authorization_number'=>'required',
            //'control_code'=>'required',
            //'business_name'=>'required',
            //'nit'=>'required',
            'amount'=>'required',
            'field.*.value'=>'required',
            'splits'=>'nullable|array',
            'splits.*.*.code'=>'required',
            'splits.*.*.percentage'=>'required|numeric|gt:0|max:100',
            'lines'=>'nullable|array',
            'items'=>'nullable|array',
        ];
    }
    /**
     * Cada dimensión repartida debe sumar 100 % y el reparto no puede generar
     * más líneas que el máximo. En una factura con ítems, los artículos deben
     * sumar el gasto del documento sin IVA y no se admite reparto.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $splits = ExpenseDistribution::normalize($this->input('splits'));
            foreach (ExpenseDistribution::errors($splits) as $key => $message) {
                $validator->errors()->add($key, $message);
            }

            $document = Document::find($this->input('document_id'));
            if ($document && $document->items_status) {
                $items = ExpenseItems::normalize($this->input('items'));
                $expense = ExpenseItems::expenseFromInput($this->all());
                foreach (ExpenseItems::errors($items, $expense, (bool) $splits) as $key => $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        });
    }
    public function messages(): array
    {
        return [
            'account.required'=>'El campo Cuenta es obligatorio',
            'date.required'=>'El campo Fecha del documento es obligatorio',
            'document_id.required'=>'El campo Tipo de Documento es obligatorio',
            'concept.required'=>'El campo Concepto es obligatorio',
            'document_number.required'=>'El campo N° Documento / N° Factura / N° Recibo es obligatorio',
            'authorization_number.required'=>'El campo Nº Autorización es obligatorio',
            'control_code.required'=>'El campo Codigo de Control es obligatorio',
            'business_name.required'=>'El campo Razón Social es obligatorio',
            'nit.required'=>'El campo NIT / CI / Doc. Identif. es obligatorio',
            'amount.required'=>'El campo Monto es obligatorio',
            'field.*.value'=>'El campo es obligatorio',
            'splits.*.*.code.required'=>'Seleccione el centro de costo',
            'splits.*.*.percentage.required'=>'Ingrese el porcentaje',
            'splits.*.*.percentage.numeric'=>'El porcentaje debe ser un número',
            'splits.*.*.percentage.gt'=>'El porcentaje debe ser mayor a 0',
            'splits.*.*.percentage.max'=>'El porcentaje no puede ser mayor a 100',
        ];
    }
}
