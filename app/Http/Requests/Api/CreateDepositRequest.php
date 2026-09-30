<?php

namespace App\Http\Requests\Api;

class CreateDepositRequest extends MerchantApiRequest
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
            'fromAccount' => ['required', 'string', 'max:32'],
            'fromBankCode' => ['required', 'string', 'max:16'],
            'fullName' => ['required', 'string', 'max:128'],
            'fullNameEn' => ['nullable', 'string', 'max:128'],
            'callbackUrl' => ['nullable', 'url', 'max:500'],
            'returnUrl' => ['nullable', 'url', 'max:500'],
        ];
    }
}
