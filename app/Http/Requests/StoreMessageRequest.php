<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ConversationPolicy handles authorization in the controller
    }

    public function rules(): array
    {
        return [
            'message'    => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'type'       => ['nullable', 'string', 'in:text,image,file'],
            'attachment' => [
                'nullable',
                'file',
                'max:10240', // 10 MB
                'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required_without' => 'Please enter a message or attach a file.',
            'attachment.max'           => 'File size must not exceed 10 MB.',
        ];
    }
}
