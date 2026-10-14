@php
    $formatAmount = static fn (mixed $value): string => number_format((float) $value, 4, '.', '');
    $formatRate = static function (mixed $value): string {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
    };
    $sparkColors = [
        'primary' => '#5b6df9',
        'warning' => '#f6a23c',
        'danger' => '#df5b69',
        'success' => '#18b67a',
        'info' => '#41b6f6',
    ];
@endphp

<style>
.erp-dashboard { --erp-gap: 1rem; width:100%; max-width:100%; overflow-x:hidden; display:flex; flex-direction:column; gap:var(--erp-gap); }
.erp-dashboard > .row { margin-left:-15px; margin-right:-15px; margin-bottom:0; row-gap:var(--erp-gap); }
.erp-dashboard__intro { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:0; }
.erp-dashboard__intro h2 { margin:0; font-size:1.5rem; font-weight:700; }
.erp-dashboard__intro p { margin:.25rem 0 0; color:#7c8295; }
.erp-dashboard__kicker { display:inline-flex; align-items:center; gap:.4rem; font-size:.75rem; font-weight:700; letter-spacing:.04em; color:#5b6df9; text-transform:uppercase; }
.erp-dashboard__date { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .75rem; border-radius:999px; background:#f4f5fb; color:#5f6578; font-size:.85rem; white-space:nowrap; }
.erp-notice-bar { display:flex; align-items:center; gap:.75rem; margin-bottom:0; padding:.35rem .75rem; border:1px solid #e4e7ed; border-radius:10px; background:#fff; }
.erp-notice-bar__label { display:flex; align-items:center; gap:.4rem; color:#e6a23c; white-space:nowrap; }
.erp-notice-bar__content { flex:1; min-width:0; }
.erp-notice-bar__content .dcat-marquee-notice { border:0; height:36px; background:transparent; }
.erp-notice-bar__state { color:#18b67a; font-size:.8rem; white-space:nowrap; }
.erp-overview-card { border:0; border-radius:14px; box-shadow:0 8px 24px rgba(31,41,55,.06); margin-bottom:0; overflow:hidden; height:100%; }
.erp-overview-label { color:#7c8295; font-size:.85rem; }
.erp-overview-icon { width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; }
.erp-overview-value { margin-top:.55rem; font-size:1.45rem; font-weight:700; color:#1f2430; }
.erp-overview-sub { margin-top:.25rem; color:#8a91a5; font-size:.8rem; }
.erp-overview-sub .up { color:#18b67a; }
.erp-overview-sub .down { color:#df5b69; }
.erp-overview-sub .neutral { color:#8a91a5; }
.erp-sparkline { width:100%; height:42px; margin-top:.75rem; display:block; }
.erp-dashboard-card { border:0; border-radius:14px; box-shadow:0 8px 24px rgba(31,41,55,.06); margin-bottom:0; height:100%; }
.erp-dashboard-card .card-header { border-bottom:0; padding-bottom:0; }
.erp-panel-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; width:100%; }
.erp-muted { color:#8a91a5; font-size:.82rem; }
.erp-panel-value { font-size:1.2rem; font-weight:700; }
.erp-panel-hint { color:#18b67a; font-size:.8rem; }
.erp-chart-wrap { min-height:300px; }
.erp-channel-chart, .erp-category-chart { min-height:220px; }
.erp-side-panel { height:100%; }
.erp-fulfillment { text-align:center; }
.erp-fulfillment-meta { display:flex; justify-content:space-between; margin-top:.75rem; color:#7c8295; font-size:.85rem; }
.erp-fulfillment-meta strong { display:block; color:#1f2430; margin-top:.2rem; }
.erp-action-grid { display:grid; grid-template-columns:1fr; gap:.55rem; }
.erp-action { display:flex; flex-direction:row; align-items:center; gap:.65rem; padding:.7rem .8rem; border-radius:12px; background:#f7f8fc; min-height:auto; text-decoration:none; color:inherit; transition:transform .15s ease, box-shadow .15s ease; }
.erp-action:hover { transform:translateY(-2px); box-shadow:0 8px 18px rgba(31,41,55,.08); text-decoration:none; color:inherit; }
.erp-action-icon { width:32px; height:32px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
.erp-action-copy { min-width:0; display:flex; flex-direction:column; gap:.1rem; }
.erp-action strong { font-size:.88rem; }
.erp-action span { color:#8a91a5; font-size:.72rem; line-height:1.3; }
.erp-table-card .table { margin-bottom:0; }
.erp-table-card th { border-top:0; color:#8a91a5; font-weight:600; font-size:.8rem; }
.erp-risk-item { display:flex; justify-content:space-between; align-items:center; padding:.75rem 0; border-bottom:1px solid #eef0f6; }
.erp-risk-item:last-child { border-bottom:0; }
.erp-risk-item strong { display:block; font-size:1.1rem; }
.erp-risk-item span { color:#8a91a5; font-size:.8rem; }
@media (max-width: 767.98px) {
    .erp-dashboard__intro { flex-direction:column; align-items:flex-start; }
}
body.dark-mode .erp-dashboard__date,
body.dark-mode .erp-action { background:#2a2a3c; }
body.dark-mode .erp-notice-bar,
body.dark-mode .erp-overview-card,
body.dark-mode .erp-dashboard-card { background:#242436; border-color:#484864; }
body.dark-mode .erp-overview-value,
body.dark-mode .erp-panel-value,
body.dark-mode .erp-fulfillment-meta strong { color:#f0f0f5; }
</style>

<div class="erp-dashboard">
    <div class="erp-dashboard__intro">
        <div>
        </div>
        <div class="erp-dashboard__date"><i class="feather icon-calendar"></i>{{ $period }}</div>
    </div>

    <div class="erp-notice-bar">
        <div class="erp-notice-bar__label"><i class="feather icon-bell"></i><strong>{{ __('dashboard.sections.notices') }}</strong></div>
        <div class="erp-notice-bar__content">{!! $marqueeNotice !!}</div>
        <span class="erp-notice-bar__state"><i class="feather icon-radio"></i> {{ __('dashboard.live_sync') }}</span>
    </div>

    <div class="row">
        @foreach ($overviewCards as $metric)
            <div class="col-xl-3 col-md-6">
                <div class="card erp-overview-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="erp-overview-label">{{ $metric['label'] }}</span>
                            <span class="erp-overview-icon bg-light-{{ $metric['color'] }} text-{{ $metric['color'] }}">
                                <i class="{{ $metric['icon'] }}"></i>
                            </span>
                        </div>
                        <div class="erp-overview-value">{{ $metric['value'] }}</div>
                        <div class="erp-overview-sub">
                            <span class="{{ $metric['trend'] }}">
                                <i class="feather icon-trending-{{ $metric['trend'] === 'down' ? 'down' : ($metric['trend'] === 'up' ? 'up' : 'right') }}"></i>
                            </span>
                            {{ $metric['subtext'] }}
                        </div>
                        @php
                            $spark = $metric['sparkline'];
                            $min = min($spark);
                            $max = max($spark);
                            $range = max(1, $max - $min);
                            $count = max(1, count($spark) - 1);
                            $points = collect($spark)->map(function ($value, $index) use ($min, $range, $count) {
                                return round($index * (160 / $count), 2).','.round(43 - (($value - $min) / $range * 28), 2);
                            })->implode(' ');
                        @endphp
                        <svg class="erp-sparkline" viewBox="0 0 160 48" preserveAspectRatio="none" aria-hidden="true">
                            <polyline points="{{ $points }}" fill="none" stroke="{{ $sparkColors[$metric['color']] ?? '#5b6df9' }}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card erp-dashboard-card erp-side-panel">
                <div class="card-header">
                    <span class="card-box-title">{{ __('dashboard.sections.credit') }}</span>
                    <div class="erp-muted mt-1">{{ __('dashboard.helps.credit') }}</div>
                </div>
                <div class="card-body">
                    <div class="erp-risk-item">
                        <div>
                            <span>{{ __('dashboard.fields.credit_balance') }}</span>
                            <strong>{{ $formatAmount($data['credit']['balance']) }}</strong>
                        </div>
                    </div>
                    <div class="erp-risk-item">
                        <div>
                            <span>{{ __('dashboard.fields.credit_locked') }}</span>
                            <strong>{{ $formatAmount($data['credit']['locked']) }}</strong>
                        </div>
                    </div>
                    @if ($isAdministrator && $data['credit']['low_balance_merchants'] !== null)
                        <div class="erp-risk-item">
                            <div>
                                <span>{{ __('dashboard.fields.low_balance_merchants') }}</span>
                                <strong>{{ $data['credit']['low_balance_merchants'] }}</strong>
                            </div>
                        </div>
                    @endif
                    <div class="erp-risk-item">
                        <div>
                            <span>{{ __('dashboard.fields.anomaly_count') }}</span>
                            <strong>{{ $data['in_progress']['anomaly_count'] }}</strong>
                        </div>
                        <div class="text-right">
                            <span>{{ __('dashboard.fields.deposit_pending_count') }} / {{ __('dashboard.fields.withdraw_pending_count') }}</span>
                            <strong>{{ $data['in_progress']['deposit_pending_count'] }} / {{ $data['in_progress']['withdraw_pending_count'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card erp-dashboard-card erp-side-panel">
                <div class="card-header">
                    <span class="card-box-title">{{ __('dashboard.sections.mix') }}</span>
                    <div class="erp-muted mt-1">{{ __('dashboard.helps.mix') }}</div>
                </div>
                <div class="card-body pt-1"><div class="erp-channel-chart">{!! $mixChart !!}</div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card erp-dashboard-card erp-side-panel">
                <div class="card-header">
                    <span class="card-box-title">{{ __('dashboard.sections.success_gauge') }}</span>
                    <div class="erp-muted mt-1">{{ __('dashboard.helps.success_gauge') }}</div>
                </div>
                <div class="card-body erp-fulfillment">
                    {!! $successChart !!}
                    <div class="erp-fulfillment-meta">
                        <div>
                            <span>{{ __('dashboard.fields.deposit_success_rate') }}</span>
                            <strong>{{ $formatRate($data['today']['deposit_success_rate']) }}</strong>
                        </div>
                        <div class="text-right">
                            <span>{{ __('dashboard.fields.withdraw_success_rate') }}</span>
                            <strong class="text-warning">{{ $formatRate($data['today']['withdraw_success_rate']) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card erp-dashboard-card">
                <div class="card-header">
                    <div class="erp-panel-head">
                        <div>
                            <span class="card-box-title">{{ __('dashboard.sections.trend') }}</span>
                            <div class="erp-muted mt-1">{{ __('dashboard.helps.trend') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="erp-panel-value">{{ $formatAmount($data['today']['deposit_success_amount']) }}</div>
                            <div class="erp-panel-hint"><i class="feather icon-trending-up"></i> {{ __('dashboard.fields.deposit_success_amount') }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-2"><div class="erp-chart-wrap">{!! $trendChart !!}</div></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="{{ $isAdministrator ? 'col-xl-9' : 'col-xl-8' }}">
            <div class="card erp-dashboard-card erp-table-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="card-box-title">{{ $isAdministrator ? __('dashboard.sections.top_merchants') : __('dashboard.sections.in_progress') }}</span>
                        <div class="erp-muted mt-1">
                            @if ($isAdministrator)
                                {{ __('dashboard.fields.active_merchants') }}: {{ $data['admin']['active_merchants'] ?? 0 }}
                            @else
                                {{ __('dashboard.helps.in_progress') }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if ($isAdministrator)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                <tr>
                                    <th>{{ __('dashboard.fields.merchant_name') }}</th>
                                    <th class="text-right">{{ __('dashboard.fields.rank_deposit') }}</th>
                                    <th class="text-right">{{ __('dashboard.fields.rank_withdraw') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse (($data['admin']['top_merchants'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['merchant_name'] }}</td>
                                        <td class="text-right">{{ $formatAmount($row['deposit_success_amount']) }}</td>
                                        <td class="text-right">{{ $formatAmount($row['withdraw_actual_flow']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">{{ __('dashboard.empty_top_merchants') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="card-body">
                            <div class="erp-risk-item">
                                <div>
                                    <span>{{ __('dashboard.fields.deposit_pending_count') }}</span>
                                    <strong>{{ $data['in_progress']['deposit_pending_count'] }}</strong>
                                </div>
                            </div>
                            <div class="erp-risk-item">
                                <div>
                                    <span>{{ __('dashboard.fields.withdraw_pending_count') }}</span>
                                    <strong>{{ $data['in_progress']['withdraw_pending_count'] }}</strong>
                                </div>
                            </div>
                            <div class="erp-risk-item">
                                <div>
                                    <span>{{ __('dashboard.fields.withdraw_remaining_amount') }}</span>
                                    <strong>{{ $formatAmount($data['in_progress']['withdraw_remaining_amount']) }}</strong>
                                </div>
                            </div>
                            <div class="erp-risk-item">
                                <div>
                                    <span>{{ __('dashboard.fields.anomaly_count') }}</span>
                                    <strong>{{ $data['in_progress']['anomaly_count'] }}</strong>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($isAdministrator)
            <div class="col-xl-3">
                <div class="card erp-dashboard-card">
                    <div class="card-header">
                        <span class="card-box-title">{{ __('dashboard.sections.shortcuts') }}</span>
                        <div class="erp-muted mt-1">{{ __('dashboard.helps.shortcuts') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="erp-action-grid">
                            <a class="erp-action" href="{{ admin_url('payment-deposit') }}">
                                <div class="erp-action-icon bg-light-primary text-primary"><i class="feather icon-download"></i></div>
                                <div class="erp-action-copy">
                                    <strong>{{ __('dashboard.shortcuts.deposit') }}</strong>
                                    <span>{{ __('dashboard.shortcut_hints.deposit') }}</span>
                                </div>
                            </a>
                            <a class="erp-action" href="{{ admin_url('payment-withdraw') }}">
                                <div class="erp-action-icon bg-light-warning text-warning"><i class="feather icon-upload"></i></div>
                                <div class="erp-action-copy">
                                    <strong>{{ __('dashboard.shortcuts.withdraw') }}</strong>
                                    <span>{{ __('dashboard.shortcut_hints.withdraw') }}</span>
                                </div>
                            </a>
                            <a class="erp-action" href="{{ admin_url('payment-deposit-report') }}">
                                <div class="erp-action-icon bg-light-success text-success"><i class="feather icon-bar-chart-2"></i></div>
                                <div class="erp-action-copy">
                                    <strong>{{ __('dashboard.shortcuts.deposit_report') }}</strong>
                                    <span>{{ __('dashboard.shortcut_hints.deposit_report') }}</span>
                                </div>
                            </a>
                            <a class="erp-action" href="{{ admin_url('payment-withdraw-report') }}">
                                <div class="erp-action-icon bg-light-info text-info"><i class="feather icon-pie-chart"></i></div>
                                <div class="erp-action-copy">
                                    <strong>{{ __('dashboard.shortcuts.withdraw_report') }}</strong>
                                    <span>{{ __('dashboard.shortcut_hints.withdraw_report') }}</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="col-xl-4">
                <div class="card erp-dashboard-card">
                    <div class="card-header">
                        <span class="card-box-title">{{ __('dashboard.sections.today') }}</span>
                        <div class="erp-muted mt-1">{{ __('dashboard.helps.today_detail') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="erp-risk-item">
                            <div>
                                <span>{{ __('dashboard.fields.withdraw_success_amount') }}</span>
                                <strong>{{ $formatAmount($data['today']['withdraw_success_amount']) }}</strong>
                            </div>
                        </div>
                        <div class="erp-risk-item">
                            <div>
                                <span>{{ __('dashboard.fields.deposit_success_count') }}</span>
                                <strong>{{ $data['today']['deposit_success_count'] }} / {{ $data['today']['deposit_total_count'] }}</strong>
                            </div>
                        </div>
                        <div class="erp-risk-item">
                            <div>
                                <span>{{ __('dashboard.fields.withdraw_success_count') }}</span>
                                <strong>{{ $data['today']['withdraw_success_count'] }} / {{ $data['today']['withdraw_total_count'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
