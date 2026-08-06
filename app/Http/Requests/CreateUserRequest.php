<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'organization_id' => 'required|integer|exists:organizations,id',
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered',
            'email.email' => 'Please enter a valid email address',
            'password.min' => 'Password must be at least 6 characters',
            'organization_id.exists' => 'Selected organization does not exist',
        ];
    }
}