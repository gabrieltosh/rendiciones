<?php

namespace App\Http\Requests\Administration;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'name'=>['required',Rule::unique('profiles')->ignore($this->id)],
            'type_currency'=>'required',
            'documents.*.name'=>'required',
            'documents.*.type_document_sap'=>'required',
            'documents.*.type_calculation'=>'required',
            'documents.*.detail.*.type'=>'required',
            'documents.*.detail.*.percentage'=>'required',
            'documents.*.detail.*.account'=>'required',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required'=>'El campo Nombre Perfil es obligatorio',
            'employee_code.required'=>'El campo Codigo Empleado es obligatorio',
            'supplier_code.required'=>'El campo Codigo Proveedor es obligatorio',
            'type_currency.required'=>'El campo Moneda es obligatorio',
            'name.unique'=>'El Nombre del Perfil ya esta registrado',
            'documents.*.name.required'=>'El Nombre Documento es obligatorio',
            'documents.*.type_document_sap.required'=>'El Tipo Documento es obligatorio',
            'documents.*.type_calculation.required'=>'El Tipo Calculo es obligatorio',
            'documents.*.detail.*.type.required'=>'El Tipo es Obligatorio',
            'documents.*.detail.*.percentage.required'=>'El Porcentaje es Obligatorio',
            'documents.*.detail.*.account.required'=>'La Cuenta es Obligatorio',
        ];
    }
}
