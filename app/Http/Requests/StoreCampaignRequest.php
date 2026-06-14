<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'segment_id' => 'required|exists:segments,id',
            'channel' => 'required|string|in:whatsapp,email,sms,rcs',
            'message' => 'required|string',
            'status' => 'nullable|string|in:draft,scheduled,running,completed',
            'expected_revenue' => 'nullable|numeric|min:0',
        ];
    }
}
