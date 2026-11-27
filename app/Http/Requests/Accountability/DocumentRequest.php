<?php

namespace App\Http\Requests\Accountability;

use App\Models\Accountability;
use App\Models\Document;
use App\Services\Accountability\ExpenseDistribution;
use App\Services\Accountability\ExpenseItems;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

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
            'date'=>array_merge(['required', 'date'], $this->HandlePeriodRules()),
            'document_id'=>'required',
            'concept'=>'required',
            'document_number'=>'required',
            //'authorization_number'=>'required',
            //'control_code'=>'required',
            //'business_name'=>'required',
            //'nit'=>'required',
            'amount'=>'required|numeric',
            'field.*.value'=>'required',
            'splits'=>'nullable|array',
            'splits.*.*.code'=>'required',
            'splits.*.*.percentage'=>'required|numeric|gt:0|max:100',
            'lines'=>'nullable|array',
            'items'=>'nullable|array',
        ];
    }

    /**
     * La fecha del documento debe caer dentro del periodo de la rendición. En las rutas
     * del solicitante y del autorizador el parámetro {id} es la rendición.
     */
    private function HandlePeriodRules(): array
    {
        $accountability = Accountability::select('start_date', 'end_date')->find($this->route('id'));
        if (!$accountability) {
            return [];
        }
        $rules = [];
        foreach (['after_or_equal' => $accountability->start_date, 'before_or_equal' => $accountability->end_date] as $rule => $value) {
            if (!$value) {
                continue;
            }
            try {
                $rules[] = $rule . ':' . Carbon::parse($value)->toDateString();
            } catch (Throwable $e) {
                // Fecha inválida en la rendición: no se valida ese extremo
            }
        }
        return $rules;
    }

    /**
     * Cada centro de costo repartido debe sumar 100 % y el reparto no puede generar
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
        $accountability = Accountability::select('start_date', 'end_date')->find($this->route('id'));
        $period = $accountability
            ? ' (' . $this->HandleFormatDate($accountability->start_date) . ' al ' . $this->HandleFormatDate($accountability->end_date) . ')'
            : '';

        return [
            'account.required'=>'El campo Tipo de gasto (cuenta) es obligatorio',
            'date.required'=>'El campo Fecha del documento es obligatorio',
            'date.date'=>'La Fecha del documento no es una fecha válida',
            'date.after_or_equal'=>'La Fecha del documento debe estar dentro del periodo de la rendición' . $period,
            'date.before_or_equal'=>'La Fecha del documento debe estar dentro del periodo de la rendición' . $period,
            'document_id.required'=>'El campo Tipo de documento es obligatorio',
            'concept.required'=>'El campo Concepto / glosa es obligatorio',
            'document_number.required'=>'El campo N° de documento es obligatorio',
            'authorization_number.required'=>'El campo N° de autorización es obligatorio',
            'control_code.required'=>'El campo Código de control es obligatorio',
            'business_name.required'=>'El campo Razón social es obligatorio',
            'nit.required'=>'El campo NIT / CI del proveedor es obligatorio',
            'amount.required'=>'El campo Importe es obligatorio',
            'amount.numeric'=>'El Importe debe ser un número',
            'field.*.value'=>'El campo es obligatorio',
            'splits.*.*.code.required'=>'Seleccione el centro de costo',
            'splits.*.*.percentage.required'=>'Ingrese el porcentaje',
            'splits.*.*.percentage.numeric'=>'El porcentaje debe ser un número',
            'splits.*.*.percentage.gt'=>'El porcentaje debe ser mayor a 0',
            'splits.*.*.percentage.max'=>'El porcentaje no puede ser mayor a 100',
        ];
    }

    private function HandleFormatDate($value): string
    {
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }
}
