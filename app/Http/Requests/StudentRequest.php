<?php

namespace App\Http\Requests;

use App\Support\Countries;
use App\Support\Languages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * * BASE REQUEST: Shared student validation (abstract)
 * ? Extended by CreateStudentRequest & UpdateStudentRequest
 */
abstract class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session()->has('admin_authenticated');
    }

    /**
     * A multi-select submits no key when nothing is chosen; default it to an
     * empty array so de-selecting all languages clears them on update.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('languages')) {
            $this->merge(['languages' => []]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'country' => ['nullable', Rule::in(Countries::codes())],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', Rule::in(Languages::codes())],
            'goal' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }
}
