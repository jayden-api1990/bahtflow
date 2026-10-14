<?php

namespace App\Services;

use App\Models\PaymentOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PaymentReportService
{
    public function depositReportQuery(Carbon $from, Carbon $to, ?int $merchantId = null): Builder
    {
        $query = PaymentOrder::query()
            ->from('payment_orders')
            ->join('merchant as m', function ($join): void {
                $join->on('m.id', '=', 'payment_orders.merchant_id')
                    ->whereNull('m.deleted_at');
            })
            ->where('payment_orders.type', PaymentOrder::TYPE_DEPOSIT)
            ->where('payment_orders.created_at', '>=', $from)
            ->where('payment_orders.created_at', '<=', $to)
            ->groupBy('payment_orders.merchant_id', 'm.name')
            ->orderBy('m.name')
            ->selectRaw('
                payment_orders.merchant_id as id,
                payment_orders.merchant_id,
                m.name as merchant_name,
                COUNT(*) as total_count,
                COALESCE(SUM(payment_orders.amount), 0) as total_amount,
                SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as completed_count,
                COALESCE(SUM(CASE WHEN payment_orders.status = ? THEN payment_orders.amount ELSE 0 END), 0) as success_amount,
                SUM(CASE WHEN payment_orders.status IN (?, ?) THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN payment_orders.status IN (?, ?) THEN 1 ELSE 0 END) as cancelled_count,
                SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as timeout_count,
                COALESCE(SUM(CASE WHEN payment_orders.status = ? THEN payment_orders.amount_fee ELSE 0 END), 0) as fee_amount
            ', [
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_PROCESSING,
                PaymentOrder::STATUS_CANCELLED,
                PaymentOrder::STATUS_CANCELLED_BY_ADMIN,
                PaymentOrder::STATUS_TIMEOUT,
                PaymentOrder::STATUS_COMPLETE,
            ]);

        if ($merchantId !== null) {
            $query->where('payment_orders.merchant_id', $merchantId);
        }

        return $query;
    }

    public function withdrawReportQuery(Carbon $from, Carbon $to, ?int $merchantId = null): Builder
    {
        $query = PaymentOrder::query()
            ->from('payment_orders')
            ->join('merchant as m', function ($join): void {
                $join->on('m.id', '=', 'payment_orders.merchant_id')
                    ->whereNull('m.deleted_at');
            })
            ->where('payment_orders.type', PaymentOrder::TYPE_WITHDRAW)
            ->where('payment_orders.created_at', '>=', $from)
            ->where('payment_orders.created_at', '<=', $to)
            ->groupBy('payment_orders.merchant_id', 'm.name')
            ->orderBy('m.name')
            ->selectRaw('
                payment_orders.merchant_id as id,
                payment_orders.merchant_id,
                m.name as merchant_name,
                COUNT(*) as total_count,
                COALESCE(SUM(payment_orders.amount), 0) as total_amount,
                SUM(CASE WHEN payment_orders.status IN (?, ?) THEN 1 ELSE 0 END) as success_count,
                COALESCE(SUM(CASE WHEN payment_orders.status IN (?, ?) THEN payment_orders.amount ELSE 0 END), 0) as success_amount,
                COALESCE(SUM(payment_orders.completed_amount), 0) as actual_flow,
                COALESCE(SUM(CASE WHEN payment_orders.status IN (?, ?, ?) THEN payment_orders.remaining_amount ELSE 0 END), 0) as remaining_amount,
                SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as full_count,
                SUM(CASE WHEN payment_orders.completed_amount > 0 AND payment_orders.completed_amount < payment_orders.amount THEN 1 ELSE 0 END) as partial_count,
                SUM(CASE WHEN payment_orders.status IN (?, ?) THEN 1 ELSE 0 END) as cancelled_count,
                SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as timeout_count,
                COALESCE(SUM(CASE WHEN payment_orders.status IN (?, ?) THEN payment_orders.amount_fee ELSE 0 END), 0) as fee_amount
            ', [
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE_BELOW_MIN,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE_BELOW_MIN,
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_PROCESSING,
                PaymentOrder::STATUS_UPDATE,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_CANCELLED,
                PaymentOrder::STATUS_CANCELLED_BY_ADMIN,
                PaymentOrder::STATUS_TIMEOUT,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE_BELOW_MIN,
            ]);

        if ($merchantId !== null) {
            $query->where('payment_orders.merchant_id', $merchantId);
        }

        return $query;
    }

    /**
     * @return array{rows: Collection<int, object>, totals: array<string, float|int|string>}
     */
    public function depositByMerchant(Carbon $from, Carbon $to, ?int $merchantId = null): array
    {
        $rows = $this->depositReportQuery($from, $to, $merchantId)->get()->map(function (object $row): object {
            $total = (int) $row->total_count;
            $completed = (int) $row->completed_count;
            $row->success_rate = $total > 0 ? round($completed * 100 / $total, 2) : 0;

            return $row;
        });

        return [
            'rows' => $rows,
            'totals' => $this->sumDepositRows($rows),
        ];
    }

    /**
     * @return array{rows: Collection<int, object>, totals: array<string, float|int|string>}
     */
    public function withdrawByMerchant(Carbon $from, Carbon $to, ?int $merchantId = null): array
    {
        $rows = $this->withdrawReportQuery($from, $to, $merchantId)->get()->map(function (object $row): object {
            $total = (int) $row->total_count;
            $success = (int) $row->success_count;
            $row->success_rate = $total > 0 ? round($success * 100 / $total, 2) : 0;

            return $row;
        });

        return [
            'rows' => $rows,
            'totals' => $this->sumWithdrawRows($rows),
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, float|int|string>
     */
    private function sumDepositRows(Collection $rows): array
    {
        $totalCount = (int) $rows->sum(fn ($row) => (int) $row->total_count);
        $completedCount = (int) $rows->sum(fn ($row) => (int) $row->completed_count);

        return [
            'merchant_name' => '合计',
            'total_count' => $totalCount,
            'total_amount' => (string) $rows->reduce(fn ($carry, $row) => bcadd($carry, (string) $row->total_amount, 4), '0'),
            'completed_count' => $completedCount,
            'success_amount' => (string) $rows->reduce(fn ($carry, $row) => bcadd($carry, (string) $row->success_amount, 4), '0'),
            'pending_count' => (int) $rows->sum(fn ($row) => (int) $row->pending_count),
            'cancelled_count' => (int) $rows->sum(fn ($row) => (int) $row->cancelled_count),
            'timeout_count' => (int) $rows->sum(fn ($row) => (int) $row->timeout_count),
            'success_rate' => $totalCount > 0 ? round($completedCount * 100 / $totalCount, 2) : 0,
            'fee_amount' => (string) $rows->reduce(fn ($carry, $row) => bcadd($carry, (string) $row->fee_amount, 4), '0'),
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, float|int|string>
     */
    private function sumWithdrawRows(Collection $rows): array
    {
        $totalCount = (int) $rows->sum(fn ($row) => (int) $row->total_count);
        $successCount = (int) $rows->sum(fn ($row) => (int) $row->success_count);

        return [
            'merchant_name' => '合计',
            'total_count' => $totalCount,
            'total_amount' => (string) $rows->reduce(fn ($carry, $row) => bcadd($carry, (string) $row->total_amount, 4), '0'),
            'success_count' => $successCount,
            'success_amount' => (string) $rows->reduce(fn ($carry, $row) => bcadd($carry, (string) $row->success_amount, 4), '0'),
            'actual_flow' => (string) $rows->reduce(fn ($carry, $row) => bcadd($carry, (string) $row->actual_flow, 4), '0'),
            'remaining_amount' => (string) $rows->reduce(fn ($carry, $row) => bcadd($carry, (string) $row->remaining_amount, 4), '0'),
            'full_count' => (int) $rows->sum(fn ($row) => (int) $row->full_count),
            'partial_count' => (int) $rows->sum(fn ($row) => (int) $row->partial_count),
            'cancelled_count' => (int) $rows->sum(fn ($row) => (int) $row->cancelled_count),
            'timeout_count' => (int) $rows->sum(fn ($row) => (int) $row->timeout_count),
            'success_rate' => $totalCount > 0 ? round($successCount * 100 / $totalCount, 2) : 0,
            'fee_amount' => (string) $rows->reduce(fn ($carry, $row) => bcadd($carry, (string) $row->fee_amount, 4), '0'),
        ];
    }
}
