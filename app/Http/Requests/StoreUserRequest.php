<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('create', \App\Models\User::class); }
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:255'],
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'max:30'],
            'role' => ['required', 'in:admin,employee,technician'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
