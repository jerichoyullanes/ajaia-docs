<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreShareRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('share', $this->route('document')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'permission' => ['required', 'in:view,edit'],
        ];
    }

    /**
     * Validate that the document owner is not added as a collaborator.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $document = $this->route('document');
                $email = $this->input('email');

                if (
                    $document instanceof Document
                    && is_string($email)
                    && Str::lower($email) === Str::lower($document->owner->email)
                ) {
                    $validator->errors()->add('email', __('You cannot share a document with its owner.'));

                    return;
                }

                if ($validator->errors()->has('email') || ! is_string($email)) {
                    return;
                }

                $user = User::query()
                    ->where('email', $email)
                    ->first();

                if (
                    $document instanceof Document
                    && $user !== null
                    && $document->sharedWith()->whereKey($user->getKey())->exists()
                ) {
                    $validator->errors()->add('email', __('This document is already shared with this user.'));
                }
            },
        ];
    }

    /**
     * Get custom validation messages for sharing.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('Enter the email address of the person to share with.'),
            'email.email' => __('Enter a valid email address.'),
            'email.exists' => __('No user was found with this email address.'),
        ];
    }
}
