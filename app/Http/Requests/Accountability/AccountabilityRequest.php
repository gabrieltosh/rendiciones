<?php

namespace App\Http\Requests\Accountability;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Profile;
use App\Models\Accountability;

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
        // En autorización la ruta no trae profile_id: se toma el perfil de la rendición
        $profileId = $this->route('profile_id')
            ?? Accountability::where('id', $this->input('id'))->value('profile_id');
        $profile = $profileId ? Profile::where('id', $profileId)->first() : null;
        $employeeRule = ($profile && !$profile->sin_empleado) ? 'required' : 'nullable';
        return [
            'employee' => $employeeRule,
            'account'=>'required',
            'total'=>'required|numeric',
            'description'=>'required',
            'start_date'=>'required',
            'end_date'=>'required|after_or_equal:start_date',
        ];
    }
    public function messages(): array
    {
        return [
            'account.required'=>'El campo Cuenta de la rendición es obligatorio',
            'total.required'=>'El campo Monto recibido es obligatorio',
            'total.numeric'=>'El Monto recibido debe ser un número',
            'description.required'=>'El campo Descripción es obligatorio',
            'start_date.required'=>'El campo Fecha de inicio es obligatorio',
            'end_date.required'=>'El campo Fecha final es obligatorio',
            'end_date.after_or_equal'=>'La Fecha final no puede ser anterior a la Fecha de inicio',
            'employee.required'=>'El campo Empleado es obligatorio'
        ];
    }
}
