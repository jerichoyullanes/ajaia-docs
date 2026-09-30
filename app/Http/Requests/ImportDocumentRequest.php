<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class ImportDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:1024', 'extensions:txt,md', 'mimes:txt,md'],
        ];
    }

    /**
     * Get custom validation messages for document imports.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Choose a TXT or Markdown file to import.'),
            'file.max' => __('The file may not be larger than 1 MB.'),
            'file.extensions' => __('Choose a file with a .txt or .md extension.'),
            'file.mimes' => __('The file contents must be plain text or Markdown.'),
        ];
    }

    /**
     * Ensure the imported filename can be stored as the document title.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $file = $this->file('file');

                if (
                    ! $validator->errors()->has('file')
                    && $file instanceof UploadedFile
                    && mb_strlen($file->getClientOriginalName()) > 255
                ) {
                    $validator->errors()->add('file', __('The filename may not exceed 255 characters.'));
                }
            },
        ];
    }
}
