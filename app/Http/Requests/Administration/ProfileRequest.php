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
            'documents.*.detail.*.type'=>'required',
            'documents.*.detail.*.percentage'=>'required',
            'documents.*.detail.*.account'=>'required',
            'documents.*.detail.*.type_calculation'=>'required',
            'documents.*.fields.*.account'=>'required',
            'documents.*.fields.*.name'=>'required',
            'documents.*.fields.*.type_calculation'=>'required',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required'=>'El campo Nombre Perfil es obligatorio',
            'employee_code.required'=>'El campo Código Empleado es obligatorio',
            'supplier_code.required'=>'El campo Código Proveedor es obligatorio',
            'type_currency.required'=>'El campo Moneda es obligatorio',
            'name.unique'=>'El nombre del perfil ya está registrado',
            'documents.*.name.required'=>'El Nombre Documento es obligatorio',
            'documents.*.type_document_sap.required'=>'El Tipo Documento es obligatorio',
            'documents.*.type_calculation.required'=>'El cálculo es obligatorio',
            'documents.*.detail.*.type.required'=>'El tipo es obligatorio',
            'documents.*.detail.*.percentage.required'=>'El porcentaje es obligatorio',
            'documents.*.detail.*.account.required'=>'La cuenta es obligatoria',
            'documents.*.detail.*.type_calculation'=>'El cálculo es obligatorio',
            'documents.*.fields.*.account'=>'La cuenta es obligatoria',
            'documents.*.fields.*.name'=>'El nombre es obligatorio',
            'documents.*.fields.*.type_calculation'=>'El tipo es obligatorio',
        ];
    }
}
