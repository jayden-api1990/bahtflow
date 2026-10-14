<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\RendersPaymentReport;
use App\Services\PaymentReportService;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PaymentWithdrawReportController extends AdminController
{
    use RendersPaymentReport;

    protected $translation = 'payment-report';

    protected function reportTitle(): string
    {
        return __('menu.titles.payment-withdraw-report');
    }

    protected function reportType(): string
    {
        return 'payment-withdraw-report';
    }

    protected function reportColumns(): array
    {
        $helps = __('payment-report.helps.withdraw');

        return [
            ['key' => 'merchant_name', 'label' => __('payment-report.fields.merchant_name'), 'help' => $helps['merchant_name']],
            ['key' => 'total_count', 'label' => __('payment-report.fields.total_count'), 'help' => $helps['total_count']],
            ['key' => 'total_amount', 'label' => __('payment-report.fields.total_amount'), 'help' => $helps['total_amount']],
            ['key' => 'success_count', 'label' => __('payment-report.fields.success_count'), 'help' => $helps['success_count']],
            ['key' => 'success_amount', 'label' => __('payment-report.fields.success_amount'), 'help' => $helps['success_amount']],
            ['key' => 'actual_flow', 'label' => __('payment-report.fields.actual_flow'), 'help' => $helps['actual_flow']],
            ['key' => 'remaining_amount', 'label' => __('payment-report.fields.remaining_amount'), 'help' => $helps['remaining_amount']],
            ['key' => 'full_count', 'label' => __('payment-report.fields.full_count'), 'help' => $helps['full_count']],
            ['key' => 'partial_count', 'label' => __('payment-report.fields.partial_count'), 'help' => $helps['partial_count']],
            ['key' => 'cancelled_count', 'label' => __('payment-report.fields.cancelled_count'), 'help' => $helps['cancelled_count']],
            ['key' => 'timeout_count', 'label' => __('payment-report.fields.timeout_count'), 'help' => $helps['timeout_count']],
            ['key' => 'success_rate', 'label' => __('payment-report.fields.success_rate'), 'help' => $helps['success_rate']],
            ['key' => 'fee_amount', 'label' => __('payment-report.fields.fee_amount'), 'help' => $helps['fee_amount']],
        ];
    }

    protected function reportQuery(PaymentReportService $reports, Carbon $from, Carbon $to, ?int $merchantId): Builder
    {
        return $reports->withdrawReportQuery($from, $to, $merchantId);
    }

    protected function reportData(PaymentReportService $reports, Carbon $from, Carbon $to, ?int $merchantId): array
    {
        return $reports->withdrawByMerchant($from, $to, $merchantId);
    }
}
