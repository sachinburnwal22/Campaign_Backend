<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopilotChatRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'prompt' => 'nullable|string',
            'query' => 'nullable|string',
            'message' => 'nullable|string',
        ];
    }

    /**
     * Extract the prompt query from standard key formats.
     */
    public function getPrompt(): string
    {
        return $this->input('prompt') 
            ?? $this->input('query') 
            ?? $this->input('message') 
            ?? '';
    }
}
