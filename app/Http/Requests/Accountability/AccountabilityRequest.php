<?php

namespace App\Http\Requests\Accountability;

use Illuminate\Foundation\Http\FormRequest;

class AccountabilityRequest extends FormRequest
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
            'employee'=>'required',
            'account'=>'required',
            'total'=>'required',
            'description'=>'required',
            'start_date'=>'required',
            'end_date'=>'required',
        ];
    }
    public function messages(): array
    {
        return [
            'account.required'=>'El campo Cuenta es obligatorio',
            'total.required'=>'El campo Monto Recepcionado es obligatorio',
            'description.required'=>'El campo Description es obligatorio',
            'start_date.required'=>'El campo Fecha Inicio es obligatorio',
            'end_date.required'=>'El campo Fecha Final es obligatorio',
            'employee.required'=>'El campo Empleado es obligatorio'
        ];
    }
}
