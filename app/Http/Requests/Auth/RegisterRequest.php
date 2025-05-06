<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Rules\ValidPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class RegisterRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:225'],
            'email' => ['required', 'email:rfc', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed'],
            'phone' => ['required', new ValidPhone()],
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $phoneRaw = $this->input('phone');

        $phoneUtil = PhoneNumberUtil::getInstance();
        $number = $phoneUtil->parse($phoneRaw, null);
        $formatted = $phoneUtil->format($number, PhoneNumberFormat::INTERNATIONAL);

        $this->merge([
            'phone' => $formatted,
        ]);
    }
}
