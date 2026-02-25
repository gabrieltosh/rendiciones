<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', Rule::unique('areas')->ignore($this->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo Nombre es obligatorio',
            'name.unique'   => 'El nombre del área ya está registrado',
        ];
    }
}
