<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in(array_keys(Ticket::STATUS_LABELS))],
            'priority' => ['nullable', Rule::in(array_keys(Ticket::PRIORITY_LABELS))],
            'category_id' => ['nullable', 'exists:categories,id'],
            'technician_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'technician')],
        ];
    }

    public function messages(): array
    {
        return [
            'month.date_format' => 'Le mois doit être au format AAAA-MM.',
            'date_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
