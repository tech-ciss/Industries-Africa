<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('comment', $this->route('ticket')); }
    public function rules(): array
    {
        return [
            'message' => ['required', 'min:3'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
