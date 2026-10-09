<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if (Route::is('api.update-password')) {
            return [
                'current_password' => ['required', 'min:6', function ($attribute, $value, $fail) {
                    $user = auth()->user() ?? auth('api')->user();
                    if (!Hash::check($value, $user->password)) {
                        $fail('Invalid Current Password');
                    }
                }],
                'new_password'              => 'required|min:6|confirmed',
                'new_password_confirmation' => 'required|min:6',
            ];
        }

        
        return [
            'name'      => 'nullable|string|max:191',
            'firstname' => 'nullable|string|max:50',
            'lastname'  => 'nullable|string|max:50',
            'dns'       => ['nullable', 'string', 'max:50', function ($attribute, $value, $fail) {
                if (!\App\Http\Controllers\Api\ServerController::isValidDnsList($value)) {
                    $fail(__('DNS must be one or more IP addresses separated by commas.'));
                }
            }],
        ];
    }
}
