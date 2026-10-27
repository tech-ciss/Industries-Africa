<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'technician_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($q) => $q->where('role', 'technician')->where('status', 'active')
                ),
            ],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.exists' => 'Le technicien sélectionné est invalide ou inactif.',
            'deadline.after_or_equal' => 'La date limite ne peut pas être dans le passé.',
        ];
    }
}
