<?php

namespace App\Http\Requests\Identity;

use App\Enums\IdentityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdentityVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->identity_status, [
            IdentityStatus::Unverified,
            IdentityStatus::Rejected,
        ], true);
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(['national_id', 'passport'])],
            'document_front' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'document_back' => [
                Rule::requiredIf($this->input('document_type') === 'national_id'),
                Rule::prohibitedIf($this->input('document_type') === 'passport'),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:8192',
            ],
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_consent' => ['accepted'],
        ];
    }
}
