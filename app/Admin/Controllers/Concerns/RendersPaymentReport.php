<?php

namespace App\Admin\Controllers\Concerns;

use App\Admin\Traits\ScopesByMerchant;
use App\Services\PaymentReportService;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait RendersPaymentReport
{
    use ScopesByMerchant;

    abstract protected function reportTitle(): string;

    abstract protected function reportType(): string;

    /**
     * @return list<array{key: string, label: string}>
     */
    abstract protected function reportColumns(): array;

    abstract protected function reportQuery(PaymentReportService $reports, Carbon $from, Carbon $to, ?int $merchantId): Builder;

    /**
     * @return array{rows: Collection<int, object>, totals: array<string, float|int|string>}
     */
    abstract protected function reportData(PaymentReportService $reports, Carbon $from, Carbon $to, ?int $merchantId): array;

    public function index(Content $content)
    {
        return $content
            ->translation($this->translation())
            ->title($this->reportTitle())
            ->description(trans('admin.list'))
            ->body($this->summaryCard())
            ->body($this->grid());
    }

    protected function title(): string
    {
        return $this->reportTitle();
    }

    protected function grid(): Grid
    {
        [$from, $to] = $this->dateRange(request());
        $merchantId = $this->scopedMerchantId();
        if ($merchantId === -1) {
            abort(403, __('payment-report.messages.forbidden'));
        }

        $reports = app(PaymentReportService::class);
        $query = $this->reportQuery($reports, $from, $to, $merchantId);
        $columns = $this->reportColumns();
        $exportUrl = admin_url($this->reportType().'/export').'?from='.urlencode($from->toDateString()).'&to='.urlencode($to->toDateString());

        return Grid::make($query, function (Grid $grid) use ($from, $to, $columns, $exportUrl): void {
            foreach ($columns as $column) {
                $key = $column['key'];
                $label = $column['label'];

                if (in_array($key, ['total_amount', 'success_amount', 'fee_amount', 'actual_flow', 'remaining_amount'], true)) {
                    $col = $grid->column($key, $label)->display(fn ($value) => self::plainAmount($value));
                } elseif ($key === 'success_rate') {
                    $col = $grid->column($key, $label)->display(function () {
                        $total = (int) ($this->total_count ?? 0);
                        $success = (int) ($this->completed_count ?? $this->success_count ?? 0);

                        return self::plainRate($total > 0 ? round($success * 100 / $total, 2) : 0);
                    });
                } else {
                    $col = $grid->column($key, $label);
                }

                if (! empty($column['help'])) {
                    $col->help((string) $column['help']);
                }

                if ($key !== 'merchant_name') {
                    $col->style('text-align:right')->setHeaderAttributes(['style' => 'text-align:right']);
                }
            }

            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableDeleteButton();
            $grid->disableViewButton();
            $grid->disableBatchActions();
            $grid->disableRowSelector();
            $grid->disableActions();

            $grid->filter(function (Grid\Filter $filter) use ($from, $to): void {
                $filter->panel();
                $filter->expand();
                $filter->between('created_at', __('payment-report.fields.date'))
                    ->date()
                    ->default([
                        'start' => $from->toDateString(),
                        'end' => $to->toDateString(),
                    ])
                    ->ignore();
            });

            $grid->tools(function (Grid\Tools $tools) use ($exportUrl): void {
                $tools->append(
                    '<a class="btn btn-primary" href="'.e($exportUrl).'">'
                    .'<i class="feather icon-download"></i> '.__('payment-report.actions.export')
                    .'</a>'
                );
            });
        });
    }

    protected function summaryCard(): Card
    {
        [$from, $to] = $this->dateRange(request());
        $merchantId = $this->scopedMerchantId();
        if ($merchantId === -1) {
            abort(403, __('payment-report.messages.forbidden'));
        }

        $data = $this->reportData(app(PaymentReportService::class), $from, $to, $merchantId);

        return Card::make(
            __('payment-report.labels.summary'),
            view('admin.reports.summary', [
                'columns' => $this->reportColumns(),
                'totals' => $data['totals'],
            ])
        );
    }

    public function export(Request $request, PaymentReportService $reports): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);
        $merchantId = $this->scopedMerchantId();
        if ($merchantId === -1) {
            abort(403, __('payment-report.messages.forbidden'));
        }

        $data = $this->reportData($reports, $from, $to, $merchantId);
        $columns = $this->reportColumns();
        $headings = array_column($columns, 'label');
        $keys = array_column($columns, 'key');

        $exportRows = [];
        foreach ($data['rows'] as $row) {
            $exportRows[] = $this->exportValues($row, $keys);
        }
        $exportRows[] = $this->exportValues((object) $data['totals'], $keys);

        $export = new class($exportRows, $headings) implements FromArray, WithHeadings
        {
            public function __construct(private array $rows, private array $headings) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return $this->headings;
            }
        };

        $filename = $this->reportType().'_'.$from->format('Ymd').'_'.$to->format('Ymd').'.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(Request $request): array
    {
        $fromInput = $request->input('created_at.start', $request->input('from'));
        $toInput = $request->input('created_at.end', $request->input('to'));

        $from = filled($fromInput)
            ? Carbon::parse((string) $fromInput)->startOfDay()
            : now()->startOfDay();
        $to = filled($toInput)
            ? Carbon::parse((string) $toInput)->endOfDay()
            : now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * @param  list<string>  $keys
     * @return list<float|int|string>
     */
    protected function exportValues(object $row, array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $value = $row->{$key} ?? '';
            if (in_array($key, ['total_amount', 'success_amount', 'fee_amount', 'actual_flow', 'remaining_amount'], true)) {
                $values[] = self::plainAmount($value);
            } elseif ($key === 'success_rate') {
                $values[] = self::plainRate($value);
            } else {
                $values[] = $value;
            }
        }

        return $values;
    }

    protected static function plainAmount(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    protected static function plainRate(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
    }
}
