<?php

namespace App\Http\Requests;

use App\Models\Document;
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
                }
            },
        ];
    }
}
