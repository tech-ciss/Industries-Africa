<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('create', \App\Models\Category::class); }
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:3', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'max:1000'],
        ];
    }
}
