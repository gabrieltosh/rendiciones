<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
class UserRequest extends FormRequest
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
            'name'=>'required',
            'email'=>['required','email',Rule::unique('users')->ignore($this->id)],
            'username'=>['required',Rule::unique('users')->ignore($this->id)],
            'password'=>[$this->method() === 'PUT' ? '':'required',Password::min(5)->letters()->mixedCase()->numbers()],
            'type'=>'required'
        ];
    }
    public function messages(): array
    {
        return [
            'name.required'=>'El nombre es obligatorio',
            'email.required'=>'El correo electrónico es obligatorio',
            'email.email'=>'Ingresa un correo electrónico válido',
            'username.required'=>'El usuario es obligatorio',
            'username.unique'=>'Este usuario ya está registrado',
            'email.unique'=>'Este correo electrónico ya está registrado',
            'password.required'=>'La contraseña es obligatoria',
            'password'=>'La contraseña debe tener al menos 5 caracteres, con mayúsculas, minúsculas y números',
            'password.min'=>'La contraseña debe tener al menos :min caracteres',
            'type.required'=>'Selecciona el rol del usuario',
        ];
    }
}
