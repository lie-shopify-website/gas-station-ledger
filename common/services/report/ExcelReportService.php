<?php

namespace common\services\report;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;

class ExcelReportService
{
    public function build(string $from, string $to): string
    {
        return $this->withEnglish(function () use ($from, $to) {
            return $this->buildInCurrentLanguage($from, $to);
        });
    }

    public function filename(string $from, string $to): string
    {
        return $this->withEnglish(static function () use ($from, $to) {
            return $from === $to
                ? Yii::t('app', '账本汇总') . '-' . $from . '.xlsx'
                : Yii::t('app', '账本汇总') . '-' . $from . '_' . $to . '.xlsx';
        });
    }

    public function buildCompact(string $from, string $to): string
    {
        return $this->withEnglish(function () use ($from, $to) {
            return $this->buildCompactInCurrentLanguage($from, $to);
        });
    }

    public function filenameCompact(string $from, string $to): string
    {
        return $this->withEnglish(static function () use ($from, $to) {
            return $from === $to
                ? Yii::t('app', '账本精简汇总') . '-' . $from . '.xlsx'
                : Yii::t('app', '账本精简汇总') . '-' . $from . '_' . $to . '.xlsx';
        });
    }

    private function withEnglish(callable $callback)
    {
        $previous = Yii::$app->language;
        Yii::$app->language = 'en';
        try {
            return $callback();
        } finally {
            Yii::$app->language = $previous;
        }
    }

    /**
     * Monthly sheet uses the calendar month of the selected end date.
     *
     * @return array{0:string,1:string}
     */
    private function monthBounds(string $to): array
    {
        $monthFrom = substr($to, 0, 7) . '-01';

        return [$monthFrom, date('Y-m-t', strtotime($monthFrom))];
    }

    private function buildInCurrentLanguage(string $from, string $to): string
    {
        $dashboard = new DashboardService();
        $daily = new DailySummaryService();
        $payment = new PaymentTypeSummaryService();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Gas Station Ledger')
            ->setTitle(Yii::t('app', '账本汇总'));

        $dailyTotals = $dashboard->getDailyTotalsForRange($from, $to);
        $this->writeMonthSheet(
            $spreadsheet->getActiveSheet(),
            $dashboard->getKpisForRange($from, $to),
            $daily->getCompanyForRange($from, $to),
            $dashboard->getCounterForRange($from, $to),
            $dailyTotals
        );

        $paymentSummary = $payment->getSummaryForRange($from, $to);

        $dailySummarySheet = $spreadsheet->createSheet();
        $this->writeDailySummarySheet(
            $dailySummarySheet,
            $daily->getCompanyForRange($from, $to),
            $paymentSummary,
            $dailyTotals
        );

        $counter = new CounterSummaryService();

        $dailyDetailSheet = $spreadsheet->createSheet();
        $this->writeDailyDetailSheet($dailyDetailSheet, $daily->getFillsForRange($from, $to));

        $counterSheet = $spreadsheet->createSheet();
        $this->writeCounterSheet(
            $counterSheet,
            $dashboard->getCounterForRange($from, $to),
            $paymentSummary,
            $counter->getDailyForRange($from, $to)
        );

        $detailSheet = $spreadsheet->createSheet();
        $this->writeCounterDetailSheet($detailSheet, $counter->getDetailsForRange($from, $to));

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    private function buildCompactInCurrentLanguage(string $from, string $to): string
    {
        $dashboard = new DashboardService();
        $daily = new DailySummaryService();
        $payment = new PaymentTypeSummaryService();
        $counter = new CounterSummaryService();
        [$monthFrom, $monthTo] = $this->monthBounds($to);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Gas Station Ledger')
            ->setTitle(Yii::t('app', '账本精简汇总'));

        $this->writeDailySummarySheet(
            $spreadsheet->getActiveSheet(),
            $daily->getCompanyForRange($monthFrom, $monthTo),
            $payment->getSummaryForRange($monthFrom, $monthTo),
            $dashboard->getDailyTotalsForRange($monthFrom, $monthTo),
            true
        );

        $dailySheet = $spreadsheet->createSheet();
        $this->writeDailySummarySheet(
            $dailySheet,
            $daily->getCompanyForRange($from, $to),
            $payment->getSummaryForRange($from, $to),
            $dashboard->getDailyTotalsForRange($from, $to)
        );

        $counterSheet = $spreadsheet->createSheet();
        $this->writeCompactCounterSheet($counterSheet, $counter->getDailyWithPaymentForRange($from, $to));

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * @param array{date_from:string,date_to:string,fill_count:int,total_liters:float,amount_due:float,list_amount:float,total_cost:float,swipe_liters:float} $kpis
     * @param array{date_from:string,date_to:string,companies:array,totals:array} $company
     * @param array{date_from:string,date_to:string,counters:array,totals:array} $counter
     * @param array{date_from:string,date_to:string,days:array,totals:array} $daily
     */
    private function writeMonthSheet(Worksheet $sheet, array $kpis, array $company, array $counter, array $daily): void
    {
        $sheet->setTitle(Yii::t('app', '月汇总'));
        $range = $kpis['date_from'] === $kpis['date_to']
            ? $kpis['date_from']
            : $kpis['date_from'] . ' ~ ' . $kpis['date_to'];

        $sheet->setCellValue('A1', Yii::t('app', '月汇总'));
        $sheet->setCellValue('B1', $range);
        $sheet->setCellValue('A3', Yii::t('app', '指标'));
        $sheet->setCellValue('B3', Yii::t('app', '数值'));

        $rows = [
            [Yii::t('app', '加油笔数'), $kpis['fill_count'], 0],
            [Yii::t('app', '总升数'), $kpis['total_liters'], 3],
            [Yii::t('app', '柜台金额'), $kpis['list_amount'], 2],
            [Yii::t('app', '应收金额'), $kpis['amount_due'], 2],
            [Yii::t('app', '总成本'), $kpis['total_cost'], 2],
            [Yii::t('app', '刷卡升数'), $kpis['swipe_liters'], 3],
        ];

        $rowNum = 4;
        foreach ($rows as [$label, $value, $decimals]) {
            $sheet->setCellValue('A' . $rowNum, $label);
            $sheet->setCellValue('B' . $rowNum, $value);
            $sheet->getStyle('B' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat($decimals));
            $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        $this->styleHeader($sheet, 'A3:B3');
        $this->styleTitle($sheet, 'A1:B1');
        $this->applyBorder($sheet, 'A3:B9');

        $rowNum += 2;
        $sheet->setCellValue('A' . $rowNum, Yii::t('app', '按公司'));
        $this->styleTitle($sheet, 'A' . $rowNum);
        $rowNum++;
        $headerRow = $rowNum;
        $sheet->fromArray([
            Yii::t('app', '公司'),
            Yii::t('app', '付款方式'),
            Yii::t('app', '笔数'),
            Yii::t('app', '升数'),
            Yii::t('app', '应收'),
        ], null, 'A' . $rowNum);
        $this->styleHeader($sheet, 'A' . $rowNum . ':E' . $rowNum);
        $rowNum++;

        foreach ($company['companies'] as $row) {
            $sheet->fromArray([
                $row['company_name'],
                $row['payment_type'],
                $row['count'],
                $row['liters'],
                $row['amount_due'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('C' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }
        $sheet->fromArray([
            Yii::t('app', '合计'),
            '',
            $company['totals']['count'],
            $company['totals']['liters'],
            $company['totals']['amount_due'],
        ], null, 'A' . $rowNum);
        $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
        $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
        $sheet->getStyle('A' . $rowNum . ':E' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('C' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->applyBorder($sheet, 'A' . $headerRow . ':E' . $rowNum);

        $rowNum += 2;
        $sheet->setCellValue('A' . $rowNum, Yii::t('app', '按柜台'));
        $this->styleTitle($sheet, 'A' . $rowNum);
        $rowNum++;
        $headerRow = $rowNum;
        $sheet->fromArray([
            Yii::t('app', '柜台'),
            Yii::t('app', '笔数'),
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌金额'),
        ], null, 'A' . $rowNum);
        $this->styleHeader($sheet, 'A' . $rowNum . ':D' . $rowNum);
        $rowNum++;

        foreach ($counter['counters'] as $row) {
            if ((int) $row['count'] === 0) {
                continue;
            }
            $sheet->fromArray([
                $row['counter_code'],
                $row['count'],
                $row['liters'],
                $row['list_amount'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }
        $sheet->fromArray([
            Yii::t('app', '合计'),
            $counter['totals']['count'],
            $counter['totals']['liters'],
            $counter['totals']['list_amount'],
        ], null, 'A' . $rowNum);
        $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
        $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
        $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->applyBorder($sheet, 'A' . $headerRow . ':D' . $rowNum);

        $rowNum += 2;
        $this->writeDailyTotalsBlock($sheet, $daily, $rowNum);

        $sheet->freezePane('A4');
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param array{date_from:string,date_to:string,companies:array,totals:array} $company
     * @param array{rows:array,totals:array} $payment
     * @param array{date_from:string,date_to:string,days:array,totals:array} $daily
     */
    private function writeDailySummarySheet(
        Worksheet $sheet,
        array $company,
        array $payment,
        array $daily,
        bool $monthly = false
    ): void {
        $title = Yii::t('app', $monthly ? '月汇总' : '日报汇总');
        $range = $company['date_from'] === $company['date_to']
            ? $company['date_from']
            : $company['date_from'] . ' ~ ' . $company['date_to'];

        $sheet->setTitle($title);
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('B1', $range);
        $this->styleTitle($sheet, 'A1:B1');

        $rowNum = $this->writePaymentBlock($sheet, $payment, 3);

        $rowNum += 2;
        $headerRow = $rowNum;
        $sheet->fromArray([
            Yii::t('app', '公司'),
            Yii::t('app', '付款方式'),
            Yii::t('app', '笔数'),
            Yii::t('app', '升数'),
            Yii::t('app', '应收'),
        ], null, 'A' . $rowNum);
        $this->styleHeader($sheet, 'A' . $rowNum . ':E' . $rowNum);
        $rowNum++;

        foreach ($company['companies'] as $row) {
            $sheet->fromArray([
                $row['company_name'],
                $row['payment_type'],
                $row['count'],
                $row['liters'],
                $row['amount_due'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('C' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }
        $sheet->fromArray([
            Yii::t('app', '合计'),
            '',
            $company['totals']['count'],
            $company['totals']['liters'],
            $company['totals']['amount_due'],
        ], null, 'A' . $rowNum);
        $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
        $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
        $sheet->getStyle('A' . $rowNum . ':E' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('C' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->applyBorder($sheet, 'A' . $headerRow . ':E' . $rowNum);

        $rowNum += 2;
        $this->writeDailyTotalsBlock($sheet, $daily, $rowNum);

        $sheet->freezePane('A4');
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param \common\models\GslFill[] $fills
     */
    private function writeDailyDetailSheet(Worksheet $sheet, array $fills): void
    {
        $sheet->setTitle(Yii::t('app', '日报明细'));
        $sheet->fromArray([
            Yii::t('app', '序号'),
            Yii::t('app', '工作日期'),
            Yii::t('app', '公司'),
            Yii::t('app', '车牌'),
            Yii::t('app', '柜台'),
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌金额'),
            Yii::t('app', '应收'),
            Yii::t('app', '票号'),
        ], null, 'A1');
        $this->styleHeader($sheet, 'A1:I1');

        $rowNum = 2;
        $totals = ['liters' => 0.0, 'list_amount' => 0.0, 'amount_due' => 0.0];
        $index = 1;

        foreach ($fills as $fill) {
            $liters = (float) $fill->liters;
            $listAmount = $fill->list_amount !== null ? (float) $fill->list_amount : 0.0;
            $amountDue = $fill->amount_due !== null ? (float) $fill->amount_due : 0.0;
            $sheet->fromArray([
                $index,
                $fill->work_date,
                $fill->company->name ?? '',
                $fill->plate->plate_no ?? '',
                $fill->counter->code ?? '',
                $liters,
                $fill->list_amount !== null ? $listAmount : null,
                $fill->amount_due !== null ? $amountDue : null,
                $fill->ticket_no,
            ], null, 'A' . $rowNum);
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('H' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('F' . $rowNum . ':H' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $totals['liters'] += $liters;
            $totals['list_amount'] += $listAmount;
            $totals['amount_due'] += $amountDue;
            $index++;
            $rowNum++;
        }

        if ($fills) {
            $sheet->setCellValue('A' . $rowNum, Yii::t('app', '合计'));
            $sheet->setCellValue('F' . $rowNum, round($totals['liters'], 3));
            $sheet->setCellValue('G' . $rowNum, round($totals['list_amount'], 2));
            $sheet->setCellValue('H' . $rowNum, round($totals['amount_due'], 2));
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('H' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('F' . $rowNum . ':H' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A1:I' . $rowNum);
        } else {
            $sheet->setCellValue('A2', Yii::t('app', '无明细数据'));
        }

        $sheet->freezePane('A2');
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param array{date_from:string,date_to:string,counters:array,totals:array} $counter
     * @param array{rows:array,totals:array} $payment
     * @param array{date_from:string,date_to:string,rows:array,totals:array} $dailyByCounter
     */
    private function writeCounterSheet(Worksheet $sheet, array $counter, array $payment, array $dailyByCounter): void
    {
        $sheet->setTitle(Yii::t('app', '柜台汇总'));
        $range = $counter['date_from'] === $counter['date_to']
            ? $counter['date_from']
            : $counter['date_from'] . ' ~ ' . $counter['date_to'];

        $sheet->setCellValue('A1', Yii::t('app', '柜台汇总'));
        $sheet->setCellValue('B1', $range);
        $this->styleTitle($sheet, 'A1:B1');

        $rowNum = $this->writePaymentBlock($sheet, $payment, 3);

        $rowNum += 2;
        $headerRow = $rowNum;
        $sheet->fromArray([
            Yii::t('app', '柜台'),
            Yii::t('app', '笔数'),
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌金额'),
        ], null, 'A' . $rowNum);
        $this->styleHeader($sheet, 'A' . $rowNum . ':D' . $rowNum);
        $rowNum++;

        foreach ($counter['counters'] as $row) {
            $sheet->fromArray([
                $row['counter_code'],
                $row['count'],
                $row['liters'],
                $row['list_amount'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }
        $sheet->fromArray([
            Yii::t('app', '合计'),
            $counter['totals']['count'],
            $counter['totals']['liters'],
            $counter['totals']['list_amount'],
        ], null, 'A' . $rowNum);
        $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
        $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
        $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->applyBorder($sheet, 'A' . $headerRow . ':D' . $rowNum);

        $rowNum += 2;
        $sheet->setCellValue('A' . $rowNum, Yii::t('app', '按日按柜台'));
        $this->styleTitle($sheet, 'A' . $rowNum);
        $rowNum++;
        $headerRow = $rowNum;
        $sheet->fromArray([
            Yii::t('app', '工作日期'),
            Yii::t('app', '柜台'),
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌金额'),
            Yii::t('app', '笔数'),
        ], null, 'A' . $rowNum);
        $this->styleHeader($sheet, 'A' . $rowNum . ':E' . $rowNum);
        $rowNum++;

        if ($dailyByCounter['rows']) {
            foreach ($dailyByCounter['rows'] as $row) {
                $sheet->fromArray([
                    $row['work_date'],
                    $row['counter_code'],
                    $row['liters'],
                    $row['list_amount'],
                    $row['count'],
                ], null, 'A' . $rowNum);
                $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
                $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
                $sheet->getStyle('C' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $rowNum++;
            }
            $sheet->fromArray([
                Yii::t('app', '合计'),
                '',
                $dailyByCounter['totals']['liters'],
                $dailyByCounter['totals']['list_amount'],
                $dailyByCounter['totals']['count'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':E' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('C' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A' . $headerRow . ':E' . $rowNum);
        } else {
            $sheet->setCellValue('A' . $rowNum, Yii::t('app', '无有效日数据'));
        }

        $sheet->freezePane('A4');
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param array{date_from:string,date_to:string,rows:array,totals:array} $daily
     */
    private function writeCompactCounterSheet(Worksheet $sheet, array $daily): void
    {
        $sheet->setTitle(Yii::t('app', '柜台汇总'));
        $range = $daily['date_from'] === $daily['date_to']
            ? $daily['date_from']
            : $daily['date_from'] . ' ~ ' . $daily['date_to'];

        $sheet->setCellValue('A1', Yii::t('app', '柜台汇总'));
        $sheet->setCellValue('B1', $range);
        $this->styleTitle($sheet, 'A1:B1');

        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        $sheet->mergeCells('C3:E3');
        $sheet->mergeCells('F3:H3');
        $sheet->mergeCells('I3:K3');
        $sheet->setCellValue('A3', Yii::t('app', '工作日期'));
        $sheet->setCellValue('B3', Yii::t('app', '柜台'));
        $sheet->setCellValue('C3', 'Cash');
        $sheet->setCellValue('F3', 'MCM');
        $sheet->setCellValue('I3', Yii::t('app', '合计'));
        $sheet->fromArray([
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌金额'),
            Yii::t('app', '票数'),
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌金额'),
            Yii::t('app', '票数'),
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌金额'),
            Yii::t('app', '票数'),
        ], null, 'C4');
        $this->styleHeader($sheet, 'A3:K4');
        $sheet->getStyle('A3:K4')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $rowNum = 5;
        foreach ($daily['rows'] as $row) {
            $sheet->fromArray([
                $row['work_date'],
                $row['counter_code'],
                $row['cash']['liters'],
                $row['cash']['list_amount'],
                $row['cash']['count'],
                $row['mcm']['liters'],
                $row['mcm']['list_amount'],
                $row['mcm']['count'],
                $row['liters'],
                $row['list_amount'],
                $row['count'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('I' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('J' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('C' . $rowNum . ':K' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        if ($daily['rows']) {
            $sheet->fromArray([
                Yii::t('app', '合计'),
                '',
                $daily['totals']['cash']['liters'],
                $daily['totals']['cash']['list_amount'],
                $daily['totals']['cash']['count'],
                $daily['totals']['mcm']['liters'],
                $daily['totals']['mcm']['list_amount'],
                $daily['totals']['mcm']['count'],
                $daily['totals']['liters'],
                $daily['totals']['list_amount'],
                $daily['totals']['count'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('I' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('J' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':K' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('C' . $rowNum . ':K' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A3:K' . $rowNum);
        } else {
            $sheet->setCellValue('A5', Yii::t('app', '无有效日数据'));
        }

        $sheet->freezePane('A5');
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param array{rows:array,totals:array} $payment
     */
    private function writePaymentBlock(Worksheet $sheet, array $payment, int $startRow): int
    {
        $sheet->fromArray([
            Yii::t('app', '付款方式'),
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌金额'),
            Yii::t('app', '票数'),
        ], null, 'A' . $startRow);
        $this->styleHeader($sheet, 'A' . $startRow . ':D' . $startRow);

        $rowNum = $startRow + 1;
        foreach ($payment['rows'] as $row) {
            $sheet->fromArray([
                $row['payment_type'],
                $row['liters'],
                $row['list_amount'],
                $row['count'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('B' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }
        $sheet->fromArray([
            Yii::t('app', '合计'),
            $payment['totals']['liters'],
            $payment['totals']['list_amount'],
            $payment['totals']['count'],
        ], null, 'A' . $rowNum);
        $sheet->getStyle('B' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
        $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
        $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->applyBorder($sheet, 'A' . $startRow . ':D' . $rowNum);

        return $rowNum;
    }

    /**
     * @param array{date_from:string,date_to:string,days:array,totals:array} $daily
     */
    private function writeDailyTotalsBlock(Worksheet $sheet, array $daily, int $rowNum): int
    {
        $sheet->setCellValue('A' . $rowNum, Yii::t('app', '每日汇总'));
        $this->styleTitle($sheet, 'A' . $rowNum);
        $rowNum++;
        $headerRow = $rowNum;
        $sheet->fromArray([
            Yii::t('app', '工作日期'),
            Yii::t('app', '票数'),
            Yii::t('app', '升数'),
            Yii::t('app', '应收'),
            Yii::t('app', '挂牌金额'),
        ], null, 'A' . $rowNum);
        $this->styleHeader($sheet, 'A' . $rowNum . ':E' . $rowNum);
        $rowNum++;

        if ($daily['days']) {
            foreach ($daily['days'] as $row) {
                $this->writeDailyTotalRow($sheet, $rowNum, $row['work_date'], $row);
                $rowNum++;
            }
            $this->writeDailyTotalRow($sheet, $rowNum, Yii::t('app', '合计'), $daily['totals'], true);
            $this->applyBorder($sheet, 'A' . $headerRow . ':E' . $rowNum);
        } else {
            $sheet->setCellValue('A' . $rowNum, Yii::t('app', '无有效日数据'));
        }

        return $rowNum;
    }

    /**
     * @param array{count:int,liters:float,amount_due:float,list_amount:float} $totals
     */
    private function writeDailyTotalRow(
        Worksheet $sheet,
        int $rowNum,
        string $label,
        array $totals,
        bool $bold = false
    ): void {
        $sheet->fromArray([
            $label,
            $totals['count'],
            $totals['liters'],
            $totals['amount_due'],
            $totals['list_amount'],
        ], null, 'A' . $rowNum);
        $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
        $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
        $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
        $sheet->getStyle('B' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        if ($bold) {
            $sheet->getStyle('A' . $rowNum . ':E' . $rowNum)->getFont()->setBold(true);
        }
    }

    /**
     * @param \common\models\GslFill[] $fills
     */
    private function writeCounterDetailSheet(Worksheet $sheet, array $fills): void
    {
        $sheet->setTitle(Yii::t('app', '柜台明细'));
        $headers = [
            Yii::t('app', '工作日期'),
            Yii::t('app', '柜台'),
            Yii::t('app', '公司'),
            Yii::t('app', '车牌'),
            Yii::t('app', '升数'),
            Yii::t('app', '挂牌价格'),
            Yii::t('app', '挂牌金额'),
            Yii::t('app', '票号'),
        ];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeader($sheet, 'A1:H1');

        $rowNum = 2;
        $totals = ['liters' => 0.0, 'list_amount' => 0.0];

        foreach ($fills as $fill) {
            $liters = (float) $fill->liters;
            $listAmount = $fill->list_amount !== null ? (float) $fill->list_amount : 0.0;
            $sheet->fromArray([
                $fill->work_date,
                $fill->counter->code ?? '',
                $fill->company->name ?? '',
                $fill->plate->plate_no ?? '',
                $liters,
                $fill->list_price !== null ? (float) $fill->list_price : null,
                $fill->list_amount !== null ? $listAmount : null,
                $fill->ticket_no,
            ], null, 'A' . $rowNum);
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('E' . $rowNum . ':G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $totals['liters'] += $liters;
            $totals['list_amount'] += $listAmount;
            $rowNum++;
        }

        if ($fills) {
            $sheet->setCellValue('A' . $rowNum, Yii::t('app', '合计'));
            $sheet->setCellValue('E' . $rowNum, round($totals['liters'], 3));
            $sheet->setCellValue('G' . $rowNum, round($totals['list_amount'], 2));
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':H' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('E' . $rowNum . ':G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A1:H' . $rowNum);
        } else {
            $sheet->setCellValue('A2', Yii::t('app', '无明细数据'));
        }

        $sheet->freezePane('A2');
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function styleTitle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(14);
    }

    private function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E2F3');
    }

    private function applyBorder(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function numberFormat(int $decimals): string
    {
        if ($decimals <= 0) {
            return '#,##0';
        }

        return '#,##0.' . str_repeat('0', $decimals);
    }
}
