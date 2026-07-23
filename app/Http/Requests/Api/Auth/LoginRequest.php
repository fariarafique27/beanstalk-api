<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;


//This class is called a Form Request in Laravel.Instead of writing validation inside your controller, Laravel lets you put it into a separate class.
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * For login, anyone should be able to attempt logging in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     *  This method returns the validation rules.
     * Laravel automatically validates the incoming request against these rules.
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required'],
        ];
    }
}
