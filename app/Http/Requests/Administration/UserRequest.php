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
            'name.required'=>'El campo Nombre es obligatorio',
            'email.required'=>'El campo Correo Electronico es obligatorio',
            'username.required'=>'El campo Username es obligatorio',
            'username.unique'=>'El Username ya esta registrado',
            'email.unique'=>'La Correo Electronico ya esta registrado',
            'password.required'=>'El campo Contraseña es obligatorio',
            'password'=>'La Contraseña debe contener al menos 5 caracteres entre números, letras, mayúsculas y minúsculas',
            'password.min'=>'El campo Contraseña debe tener al menos :min caracteres'
        ];
    }
}
