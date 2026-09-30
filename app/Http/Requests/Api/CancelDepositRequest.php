<?php

namespace App\Http\Requests\Api;

class CancelDepositRequest extends MerchantApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchantOrderNo' => ['required', 'string', 'max:64'],
        ];
    }
}
