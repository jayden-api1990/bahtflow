@php
    $formatAmount = static function (mixed $value): string {
        return number_format((float) $value, 4, '.', '');
    };
    $formatRate = static function (mixed $value): string {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
    };
@endphp

<div class="row">
    @foreach ($columns as $column)
        @php
            $key = $column['key'];
            if ($key === 'merchant_name') {
                continue;
            }
            $value = $totals[$key] ?? '';
            $display = match ($key) {
                'total_amount', 'success_amount', 'fee_amount', 'actual_flow', 'remaining_amount' => $formatAmount($value),
                'success_rate' => $formatRate($value),
                default => (string) $value,
            };
        @endphp
        <div class="col-md-2 col-sm-4 col-6 mb-1">
            <div class="text-muted" style="font-size:12px">{{ $column['label'] }}</div>
            <div style="font-size:16px;font-weight:600">{{ $display }}</div>
        </div>
    @endforeach
</div>
