<?php

namespace App\Http\Requests\Accountability;

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
            'amount'=>'required'
        ];
    }
    public function messages(): array
    {
        return [
            'account.required'=>'El campo Cuenta es obligatorio',
            'date.required'=>'El campo Fecha es obligatorio',
            'document_id.required'=>'El campo Tipo de Documento es obligatorio',
            'concept.required'=>'El campo Concepto es obligatorio',
            'document_number.required'=>'El campo Nº Documento es obligatorio',
            'authorization_number.required'=>'El campo Nº Autorización es obligatorio',
            'control_code.required'=>'El campo Codigo de Control es obligatorio',
            'business_name.required'=>'El campo Razón Social es obligatorio',
            'nit.required'=>'El campo NIT es obligatorio',
            'amount.required'=>'El campo Monto es obligatorio'
        ];
    }
}
