<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'min:5', 'max:255'],
            'description' => ['required', 'min:20'],
            'category_id' => ['required', 'exists:categories,id'],
            'priority' => ['required', 'in:low,normal,high,critical'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
