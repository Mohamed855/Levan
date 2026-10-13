<?php

namespace App\Exports\Tenants\Reports\Executive;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ExecutiveReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $data,
        private readonly array $filters,
        private readonly array $sections,
    ) {}

    public function sheets(): array
    {
        $dateLabel = ($this->filters['date_from'] ?? '').' - '.($this->filters['date_to'] ?? '');
        $all = empty($this->sections);
        $has = fn (string $s) => $all || in_array($s, $this->sections);
        $sheets = [];

        if ($has('kpis')) {
            $sheets[] = new ExecutiveKpiSheet($this->data['kpis'] ?? [], $dateLabel);
        }

        if ($has('summary')) {
            $sheets[] = new ExecutiveTableSheet(
                title: $this->data['tableTitles'][0] ?? 'الملخص',
                head: $this->data['summaryHead'] ?? [],
                rows: $this->data['summaryRows'] ?? [],
            );
        }

        if ($has('details')) {
            $sheets[] = new ExecutiveTableSheet(
                title: $this->data['tableTitles'][1] ?? 'التفاصيل',
                head: $this->data['detailsHead'] ?? [],
                rows: $this->data['detailsRows'] ?? [],
            );
        }

        if ($has('employee_perf')) {
            $emp = $this->data['employeePerf'] ?? [];
            if (! empty($emp['categories'])) {
                $head = ['الموظف', 'عدد المتابعات', 'عدد السدادات'];
                $rows = array_map(
                    fn ($name, $follows, $payments) => [$name, $follows, $payments],
                    $emp['categories'],
                    $emp['followsSeries'],
                    $emp['paymentsSeries'],
                );
                $sheets[] = new ExecutiveTableSheet('أداء الموظفين', $head, $rows);
            }
        }

        if ($has('user_collections')) {
            $uc = $this->data['userCollections'] ?? [];
            if (! empty($uc['categories'])) {
                $head = ['المستخدم', 'إجمالي المحصّل (ر.س)'];
                $rows = array_map(
                    fn ($name, $amount) => [$name, number_format($amount)],
                    $uc['categories'],
                    $uc['series'],
                );
                $sheets[] = new ExecutiveTableSheet('تحصيلات المستخدمين', $head, $rows);
            }
        }

        if ($has('top_debtors')) {
            $td = $this->data['topDebtors'] ?? [];
            if (! empty($td['categories'])) {
                $head = ['العميل', 'المتبقي (ر.س)'];
                $rows = array_map(
                    fn ($name, $amount) => [$name, number_format($amount)],
                    $td['categories'],
                    $td['series'],
                );
                $sheets[] = new ExecutiveTableSheet('أعلى العملاء مديونية', $head, $rows);
            }
        }

        if ($has('debt_vs_remaining')) {
            $dvr = $this->data['debtVsRemaining'] ?? [];
            if (! empty($dvr['categories'])) {
                $head = ['العميل', 'إجمالي المديونية (ر.س)', 'المتبقي (ر.س)'];
                $rows = array_map(
                    fn ($name, $debit, $rem) => [$name, number_format($debit), number_format($rem)],
                    $dvr['categories'],
                    $dvr['debitSeries'],
                    $dvr['remainingSeries'],
                );
                $sheets[] = new ExecutiveTableSheet('مقارنة المديونية بالمتبقي', $head, $rows);
            }
        }

        return $sheets ?: [new ExecutiveKpiSheet($this->data['kpis'] ?? [], $dateLabel)];
    }
}
