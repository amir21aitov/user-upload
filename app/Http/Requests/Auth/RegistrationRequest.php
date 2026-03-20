<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\RegisterDTO;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ];
    }

    public function toDTO(): RegisterDTO
    {
        return new RegisterDTO(
            name: $this->validated('name'),
            email: $this->validated('email'),
            password: $this->validated('password'),
        );
    }
}
