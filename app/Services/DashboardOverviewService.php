<?php

namespace App\Services;

use App\Models\MerchantCredit;
use App\Models\PaymentOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardOverviewService
{
    public const LOW_CREDIT_THRESHOLD = '1000';

    /**
     * @return array{
     *     today: array<string, float|int|string>,
     *     in_progress: array<string, float|int|string>,
     *     credit: array<string, float|int|string|null>,
     *     trend: array{labels: list<string>, deposit_success_amount: list<float>, withdraw_actual_flow: list<float>},
     *     admin: array{active_merchants: int, top_merchants: list<array<string, mixed>>}|null
     * }
     */
    public function overview(?int $merchantId = null, bool $isAdministrator = false): array
    {
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        return [
            'today' => $this->todayStats($from, $to, $merchantId),
            'in_progress' => $this->inProgressStats($from, $to, $merchantId),
            'credit' => $this->creditSnapshot($merchantId, $isAdministrator),
            'trend' => $this->last7DaysTrend($merchantId),
            'admin' => $isAdministrator ? $this->adminExtras($from, $to) : null,
        ];
    }

    /**
     * @return array<string, float|int|string>
     */
    public function todayStats(Carbon $from, Carbon $to, ?int $merchantId = null): array
    {
        $deposit = $this->baseOrderQuery($merchantId)
            ->where('type', PaymentOrder::TYPE_DEPOSIT)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success_count,
                COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as success_amount,
                COALESCE(SUM(CASE WHEN status = ? THEN amount_fee ELSE 0 END), 0) as fee_amount
            ', [
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE,
            ])
            ->first();

        $withdraw = $this->baseOrderQuery($merchantId)
            ->where('type', PaymentOrder::TYPE_WITHDRAW)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as success_count,
                COALESCE(SUM(CASE WHEN status IN (?, ?) THEN amount ELSE 0 END), 0) as success_amount,
                COALESCE(SUM(completed_amount), 0) as actual_flow,
                COALESCE(SUM(CASE WHEN status IN (?, ?) THEN amount_fee ELSE 0 END), 0) as fee_amount
            ', [
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE_BELOW_MIN,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE_BELOW_MIN,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::STATUS_COMPLETE_BELOW_MIN,
            ])
            ->first();

        $depositTotal = (int) ($deposit->total_count ?? 0);
        $depositSuccess = (int) ($deposit->success_count ?? 0);
        $withdrawTotal = (int) ($withdraw->total_count ?? 0);
        $withdrawSuccess = (int) ($withdraw->success_count ?? 0);

        return [
            'deposit_success_amount' => (string) ($deposit->success_amount ?? '0'),
            'withdraw_success_amount' => (string) ($withdraw->success_amount ?? '0'),
            'withdraw_actual_flow' => (string) ($withdraw->actual_flow ?? '0'),
            'fee_amount' => bcadd((string) ($deposit->fee_amount ?? '0'), (string) ($withdraw->fee_amount ?? '0'), 4),
            'deposit_total_count' => $depositTotal,
            'deposit_success_count' => $depositSuccess,
            'withdraw_total_count' => $withdrawTotal,
            'withdraw_success_count' => $withdrawSuccess,
            'deposit_success_rate' => $depositTotal > 0 ? round($depositSuccess * 100 / $depositTotal, 2) : 0,
            'withdraw_success_rate' => $withdrawTotal > 0 ? round($withdrawSuccess * 100 / $withdrawTotal, 2) : 0,
        ];
    }

    /**
     * @return array<string, float|int|string>
     */
    public function inProgressStats(Carbon $todayFrom, Carbon $todayTo, ?int $merchantId = null): array
    {
        $depositPending = (int) $this->baseOrderQuery($merchantId)
            ->where('type', PaymentOrder::TYPE_DEPOSIT)
            ->whereIn('status', [PaymentOrder::STATUS_PENDING, PaymentOrder::STATUS_PROCESSING])
            ->count();

        $withdrawOpen = $this->baseOrderQuery($merchantId)
            ->where('type', PaymentOrder::TYPE_WITHDRAW)
            ->whereIn('status', [
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_PROCESSING,
                PaymentOrder::STATUS_UPDATE,
            ])
            ->selectRaw('
                COUNT(*) as pending_count,
                COALESCE(SUM(remaining_amount), 0) as remaining_amount
            ')
            ->first();

        $anomalyCount = (int) $this->baseOrderQuery($merchantId)
            ->whereBetween('created_at', [$todayFrom, $todayTo])
            ->whereIn('status', [
                PaymentOrder::STATUS_TIMEOUT,
                PaymentOrder::STATUS_CANCELLED,
                PaymentOrder::STATUS_CANCELLED_BY_ADMIN,
            ])
            ->count();

        return [
            'deposit_pending_count' => $depositPending,
            'withdraw_pending_count' => (int) ($withdrawOpen->pending_count ?? 0),
            'withdraw_remaining_amount' => (string) ($withdrawOpen->remaining_amount ?? '0'),
            'anomaly_count' => $anomalyCount,
        ];
    }

    /**
     * @return array<string, float|int|string|null>
     */
    public function creditSnapshot(?int $merchantId = null, bool $isAdministrator = false): array
    {
        if ($merchantId !== null) {
            $balance = MerchantCredit::query()
                ->where('merchant_id', $merchantId)
                ->value('credit_balance');

            $locked = $this->baseOrderQuery($merchantId)
                ->where('type', PaymentOrder::TYPE_WITHDRAW)
                ->whereIn('status', [
                    PaymentOrder::STATUS_PENDING,
                    PaymentOrder::STATUS_PROCESSING,
                    PaymentOrder::STATUS_UPDATE,
                ])
                ->selectRaw('COALESCE(SUM(GREATEST(credit_locked - credit_released, 0)), 0) as locked_amount')
                ->value('locked_amount');

            return [
                'balance' => (string) ($balance ?? '0'),
                'locked' => (string) ($locked ?? '0'),
                'low_balance_merchants' => null,
            ];
        }

        $balance = (string) (MerchantCredit::query()->sum('credit_balance') ?: '0');

        $locked = $this->baseOrderQuery(null)
            ->where('type', PaymentOrder::TYPE_WITHDRAW)
            ->whereIn('status', [
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_PROCESSING,
                PaymentOrder::STATUS_UPDATE,
            ])
            ->selectRaw('COALESCE(SUM(GREATEST(credit_locked - credit_released, 0)), 0) as locked_amount')
            ->value('locked_amount');

        $lowBalanceMerchants = $isAdministrator
            ? (int) MerchantCredit::query()
                ->where('credit_balance', '<', self::LOW_CREDIT_THRESHOLD)
                ->count()
            : null;

        return [
            'balance' => $balance,
            'locked' => (string) ($locked ?? '0'),
            'low_balance_merchants' => $lowBalanceMerchants,
        ];
    }

    /**
     * @return array{labels: list<string>, deposit_success_amount: list<float>, withdraw_actual_flow: list<float>}
     */
    public function last7DaysTrend(?int $merchantId = null): array
    {
        $from = now()->subDays(6)->startOfDay();
        $to = now()->endOfDay();

        $depositRows = $this->baseOrderQuery($merchantId)
            ->where('type', PaymentOrder::TYPE_DEPOSIT)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', PaymentOrder::STATUS_COMPLETE)
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(amount), 0) as amount')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('amount', 'day');

        $withdrawRows = $this->baseOrderQuery($merchantId)
            ->where('type', PaymentOrder::TYPE_WITHDRAW)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(completed_amount), 0) as amount')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('amount', 'day');

        $labels = [];
        $deposit = [];
        $withdraw = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('m-d');
            $deposit[] = round((float) ($depositRows[$day] ?? 0), 4);
            $withdraw[] = round((float) ($withdrawRows[$day] ?? 0), 4);
        }

        return [
            'labels' => $labels,
            'deposit_success_amount' => $deposit,
            'withdraw_actual_flow' => $withdraw,
        ];
    }

    /**
     * @return array{active_merchants: int, top_merchants: list<array<string, mixed>>}
     */
    public function adminExtras(Carbon $from, Carbon $to): array
    {
        $activeMerchants = (int) PaymentOrder::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(DISTINCT merchant_id) as aggregate')
            ->value('aggregate');

        /** @var Collection<int, object> $rows */
        $rows = PaymentOrder::query()
            ->from('payment_orders')
            ->join('merchant as m', function ($join): void {
                $join->on('m.id', '=', 'payment_orders.merchant_id')
                    ->whereNull('m.deleted_at');
            })
            ->whereBetween('payment_orders.created_at', [$from, $to])
            ->groupBy('payment_orders.merchant_id', 'm.name')
            ->selectRaw('
                payment_orders.merchant_id,
                m.name as merchant_name,
                COALESCE(SUM(CASE
                    WHEN payment_orders.type = ? AND payment_orders.status = ?
                        THEN payment_orders.amount
                    ELSE 0
                END), 0) as deposit_success_amount,
                COALESCE(SUM(CASE
                    WHEN payment_orders.type = ?
                        THEN payment_orders.completed_amount
                    ELSE 0
                END), 0) as withdraw_actual_flow,
                (
                    COALESCE(SUM(CASE
                        WHEN payment_orders.type = ? AND payment_orders.status = ?
                            THEN payment_orders.amount
                        ELSE 0
                    END), 0)
                    +
                    COALESCE(SUM(CASE
                        WHEN payment_orders.type = ?
                            THEN payment_orders.completed_amount
                        ELSE 0
                    END), 0)
                ) as rank_amount
            ', [
                PaymentOrder::TYPE_DEPOSIT,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::TYPE_WITHDRAW,
                PaymentOrder::TYPE_DEPOSIT,
                PaymentOrder::STATUS_COMPLETE,
                PaymentOrder::TYPE_WITHDRAW,
            ])
            ->orderByDesc('rank_amount')
            ->limit(5)
            ->get();

        return [
            'active_merchants' => $activeMerchants,
            'top_merchants' => $rows->map(fn (object $row): array => [
                'merchant_id' => (int) $row->merchant_id,
                'merchant_name' => (string) $row->merchant_name,
                'deposit_success_amount' => (string) $row->deposit_success_amount,
                'withdraw_actual_flow' => (string) $row->withdraw_actual_flow,
            ])->all(),
        ];
    }

    private function baseOrderQuery(?int $merchantId): Builder
    {
        $query = PaymentOrder::query();

        if ($merchantId !== null) {
            $query->where('merchant_id', $merchantId);
        }

        return $query;
    }
}
