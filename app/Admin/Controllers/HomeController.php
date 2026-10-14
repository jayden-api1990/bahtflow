<?php

namespace App\Admin\Controllers;

use App\Admin\Traits\ScopesByMerchant;
use App\Http\Controllers\Controller;
use App\Services\DashboardOverviewService;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\ApexCharts\Chart;
use Dcat\Admin\Widgets\MarqueeNotice;

class HomeController extends Controller
{
    use ScopesByMerchant;

    public function index(Content $content, DashboardOverviewService $dashboard)
    {
        $merchantId = $this->scopedMerchantId();
        if ($merchantId === -1) {
            abort(403, __('dashboard.messages.forbidden'));
        }

        $isAdministrator = $this->isAdministratorUser();
        $data = $dashboard->overview($merchantId, $isAdministrator);
        $yesterday = $dashboard->todayStats(
            now()->subDay()->startOfDay(),
            now()->subDay()->endOfDay(),
            $merchantId
        );

        return $content
            ->header(__('dashboard.title'))
            ->description(__('dashboard.subtitle'))
            ->body(view('admin.dashboard.overview', [
                'data' => $data,
                'isAdministrator' => $isAdministrator,
                'period' => $this->periodLabel(),
                'overviewCards' => $this->overviewCards($data, $yesterday),
                'marqueeNotice' => $this->marqueeNotice($data, $isAdministrator),
                'trendChart' => $this->buildTrendChart($data['trend']),
                'mixChart' => $this->buildMixChart($data['today']),
                'successChart' => $this->buildSuccessChart($data['today']),
            ]));
    }

    protected function periodLabel(): string
    {
        $now = now();
        $weeks = [
            0 => __('dashboard.weekdays.sun'),
            1 => __('dashboard.weekdays.mon'),
            2 => __('dashboard.weekdays.tue'),
            3 => __('dashboard.weekdays.wed'),
            4 => __('dashboard.weekdays.thu'),
            5 => __('dashboard.weekdays.fri'),
            6 => __('dashboard.weekdays.sat'),
        ];

        return $now->format('Y-m-d').' · '.$weeks[$now->dayOfWeek];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, float|int|string>  $yesterday
     * @return list<array<string, mixed>>
     */
    protected function overviewCards(array $data, array $yesterday): array
    {
        $today = $data['today'];
        $trend = $data['trend'];

        return [
            [
                'label' => __('dashboard.fields.deposit_success_amount'),
                'value' => $this->plainAmount($today['deposit_success_amount']),
                'subtext' => $this->compareSubtext((float) $today['deposit_success_amount'], (float) $yesterday['deposit_success_amount']),
                'trend' => $this->compareTrend((float) $today['deposit_success_amount'], (float) $yesterday['deposit_success_amount']),
                'icon' => 'feather icon-download',
                'color' => 'primary',
                'sparkline' => $trend['deposit_success_amount'],
            ],
            [
                'label' => __('dashboard.fields.withdraw_actual_flow'),
                'value' => $this->plainAmount($today['withdraw_actual_flow']),
                'subtext' => $this->compareSubtext((float) $today['withdraw_actual_flow'], (float) $yesterday['withdraw_actual_flow']),
                'trend' => $this->compareTrend((float) $today['withdraw_actual_flow'], (float) $yesterday['withdraw_actual_flow']),
                'icon' => 'feather icon-upload',
                'color' => 'success',
                'sparkline' => $trend['withdraw_actual_flow'],
            ],
            [
                'label' => __('dashboard.fields.fee_amount'),
                'value' => $this->plainAmount($today['fee_amount']),
                'subtext' => $this->compareSubtext((float) $today['fee_amount'], (float) $yesterday['fee_amount']),
                'trend' => $this->compareTrend((float) $today['fee_amount'], (float) $yesterday['fee_amount']),
                'icon' => 'feather icon-dollar-sign',
                'color' => 'warning',
                'sparkline' => $this->blendSparklines($trend['deposit_success_amount'], $trend['withdraw_actual_flow']),
            ],
            [
                'label' => __('dashboard.fields.credit_balance'),
                'value' => $this->plainAmount($data['credit']['balance']),
                'subtext' => __('dashboard.fields.credit_locked').': '.$this->plainAmount($data['credit']['locked']),
                'trend' => 'neutral',
                'icon' => 'feather icon-credit-card',
                'color' => 'info',
                'sparkline' => $trend['withdraw_actual_flow'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function marqueeNotice(array $data, bool $isAdministrator): MarqueeNotice
    {
        $notices = [
            __('dashboard.notices.anomaly', ['count' => $data['in_progress']['anomaly_count']]),
            __('dashboard.notices.deposit_pending', ['count' => $data['in_progress']['deposit_pending_count']]),
            __('dashboard.notices.withdraw_pending', [
                'count' => $data['in_progress']['withdraw_pending_count'],
                'amount' => $this->plainAmount($data['in_progress']['withdraw_remaining_amount']),
            ]),
        ];

        if ($isAdministrator) {
            $notices[] = __('dashboard.notices.active_merchants', [
                'count' => $data['admin']['active_merchants'] ?? 0,
            ]);
        }

        return (new MarqueeNotice($notices))->autoplay(3200);
    }

    /**
     * @param  array{labels: list<string>, deposit_success_amount: list<float>, withdraw_actual_flow: list<float>}  $trend
     */
    protected function buildTrendChart(array $trend): Chart
    {
        $chart = new Chart;
        $chart->chart([
            'type' => 'area',
            'height' => 300,
            'toolbar' => ['show' => false],
        ]);
        $chart->series([
            [
                'name' => __('dashboard.fields.deposit_success_amount'),
                'data' => $trend['deposit_success_amount'],
            ],
            [
                'name' => __('dashboard.fields.withdraw_actual_flow'),
                'data' => $trend['withdraw_actual_flow'],
            ],
        ]);
        $chart->xaxis(['categories' => $trend['labels']]);
        $chart->stroke(['curve' => 'smooth', 'width' => 2]);
        $chart->dataLabels(false);
        $chart->colors(['#5b6df9', '#18b67a']);

        return $chart;
    }

    /**
     * @param  array<string, float|int|string>  $today
     */
    protected function buildMixChart(array $today): Chart
    {
        $deposit = round((float) $today['deposit_success_amount'], 4);
        $withdraw = round((float) $today['withdraw_actual_flow'], 4);

        $chart = new Chart;
        $chart->chart([
            'type' => 'donut',
            'height' => 240,
            'toolbar' => ['show' => false],
        ]);
        $chart->series([$deposit, $withdraw]);
        $chart->labels([
            __('dashboard.fields.deposit_success_amount'),
            __('dashboard.fields.withdraw_actual_flow'),
        ]);
        $chart->colors(['#5b6df9', '#18b67a']);
        $chart->dataLabels(false);
        $chart->options([
            'legend' => ['position' => 'bottom'],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '65%',
                    ],
                ],
            ],
        ]);

        return $chart;
    }

    /**
     * @param  array<string, float|int|string>  $today
     */
    protected function buildSuccessChart(array $today): Chart
    {
        $depositRate = (float) $today['deposit_success_rate'];
        $withdrawRate = (float) $today['withdraw_success_rate'];
        $avg = round(($depositRate + $withdrawRate) / 2, 2);

        $chart = new Chart;
        $chart->chart([
            'type' => 'radialBar',
            'height' => 240,
            'toolbar' => ['show' => false],
        ]);
        $chart->series([$avg]);
        $chart->labels([__('dashboard.fields.avg_success_rate')]);
        $chart->colors(['#5b6df9']);
        $chart->options([
            'plotOptions' => [
                'radialBar' => [
                    'hollow' => ['size' => '58%'],
                    'dataLabels' => [
                        'name' => ['fontSize' => '13px'],
                        'value' => ['fontSize' => '22px'],
                    ],
                ],
            ],
        ]);

        return $chart;
    }

    protected function plainAmount(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    protected function compareTrend(float $today, float $yesterday): string
    {
        if ($today > $yesterday) {
            return 'up';
        }
        if ($today < $yesterday) {
            return 'down';
        }

        return 'neutral';
    }

    protected function compareSubtext(float $today, float $yesterday): string
    {
        if ($yesterday <= 0 && $today <= 0) {
            return __('dashboard.compare.flat');
        }
        if ($yesterday <= 0) {
            return __('dashboard.compare.new');
        }

        $change = round((($today - $yesterday) / $yesterday) * 100, 1);
        $prefix = $change > 0 ? '+' : '';

        return __('dashboard.compare.vs_yesterday', ['value' => $prefix.$change.'%']);
    }

    /**
     * @param  list<float>  $left
     * @param  list<float>  $right
     * @return list<float>
     */
    protected function blendSparklines(array $left, array $right): array
    {
        $out = [];
        foreach ($left as $i => $value) {
            $out[] = round(((float) $value + (float) ($right[$i] ?? 0)) / 2, 4);
        }

        return $out === [] ? [0] : $out;
    }
}
