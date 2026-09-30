<?php

namespace App\Http\Requests\Api;

class CreateWithdrawRequest extends MerchantApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchantOrderNo' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'toAccount' => ['required', 'string', 'max:32'],
            'toBankCode' => ['required', 'string', 'max:16'],
            'toAccountName' => ['required', 'string', 'max:128'],
            'toAccountNameEn' => ['nullable', 'string', 'max:128'],
            'callbackUrl' => ['nullable', 'url', 'max:500'],
        ];
    }
}
