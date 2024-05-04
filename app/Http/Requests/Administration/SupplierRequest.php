<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SupplierRequest extends FormRequest
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
            'business'=>['required',Rule::unique('suppliers')->ignore($this->id)],
            'nit'=>['required',Rule::unique('suppliers')->ignore($this->id)],
        ];
    }
    public function messages(): array
    {
        return [
            'business.required'=>'El campo Razón Social es obligatorio',
            'business.unique'=>'La Razón Social ya esta registrada',
            'nit.required'=>'El campo NIT es obligatorio',
            'nit.unique'=>'El NIT ya esta registrado',
        ];
    }
}
