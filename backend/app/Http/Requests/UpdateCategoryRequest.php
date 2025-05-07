<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateCategoryRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'parent_id' => [
                'nullable',
                'numeric',
                Rule::exists('categories', 'id'),
                function ($attribute, $value, $fail) use ($category): void {
                    if ($value && $value === $category->id) {
                        $fail('A category cannot be its own parent.');
                    }

                    if ($value) {
                        $descendants = Category::where('path', 'like', "{$category->path}%")
                            ->pluck('id');

                        if ($descendants->contains($value)) {
                            $fail('A category cannot be assigned under its descendant.');
                        }
                    }
                },
            ],
            'name' => ['required', 'string'],
            'slug' => [
                'required',
                'string',
                Rule::unique('categories', 'slug')->ignore($category->id),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('name')),
        ]);
    }
}
