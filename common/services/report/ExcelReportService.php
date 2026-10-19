<?php

namespace common\services\report;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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

    public function buildChitSummary(string $from, string $to): string
    {
        return $this->withEnglish(function () use ($from, $to) {
            return $this->buildChitSummaryInCurrentLanguage($from, $to);
        });
    }

    public function chitSummaryFilename(string $from, string $to): string
    {
        return $this->withEnglish(static function () use ($from, $to) {
            $base = Yii::t('app', '油票汇总');

            return $from === $to
                ? $base . '-' . $from . '.xlsx'
                : $base . '-' . $from . '_' . $to . '.xlsx';
        });
    }

    /**
     * 月度刷卡汇总工作簿（6 个 sheet，与刷卡状态页的表格一一对应）。
     */
    public function buildSwipeSummary(string $month): string
    {
        return $this->withEnglish(function () use ($month) {
            return $this->buildSwipeSummaryInCurrentLanguage($month);
        });
    }

    public function swipeSummaryFilename(string $month): string
    {
        return $this->withEnglish(static function () use ($month) {
            return Yii::t('app', '刷卡汇总') . '-' . $month . '.xlsx';
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
            $daily->getDailyCompanyForRange($from, $to)
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
            true,
            $daily->getDailyCompanyForRange($monthFrom, $monthTo)
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

    private function buildChitSummaryInCurrentLanguage(string $from, string $to): string
    {
        $chits = new ChitSummaryService();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Gas Station Ledger')
            ->setTitle(Yii::t('app', '油票汇总'));

        $this->writeChitBalancesSheet(
            $spreadsheet->getActiveSheet(),
            $chits->getBalancesForRange($from, $to),
            $from,
            $to
        );

        $recordsSheet = $spreadsheet->createSheet();
        $this->writeChitRecordsSheet($recordsSheet, $chits->getRecordsForRange($from, $to));

        $dailySheet = $spreadsheet->createSheet();
        $this->writeChitDailySheet($dailySheet, $chits->getDailyByCustomerRange($from, $to), $from, $to);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    private function buildSwipeSummaryInCurrentLanguage(string $month): string
    {
        $swipeService = new SwipeStatusService();
        $status = $swipeService->getStatus($month);
        $swipes = $swipeService->getSwipes($month);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Gas Station Ledger')
            ->setTitle(Yii::t('app', '刷卡汇总'));

        $this->writeSwipeKpiSheet($spreadsheet->getActiveSheet(), $month, $status);
        $this->writeSwipeOwnerSheet($spreadsheet->createSheet(), $status['owners']);
        $this->writeSwipeTypeSheet($spreadsheet->createSheet(), $status['types']);
        $this->writeSwipeRateSheet($spreadsheet->createSheet(), $status['rates']);
        $this->writeSwipeCardSheet($spreadsheet->createSheet(), $status['cards']);
        $this->writeSwipeDetailSheet($spreadsheet->createSheet(), $swipes);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * 汇总 sheet：与页面顶部四个信息卡一致。
     *
     * @param array{fill_liters:float,swiped_liters:float,left_liters:float,owner_payout:float} $status
     */
    private function writeSwipeKpiSheet(Worksheet $sheet, string $month, array $status): void
    {
        $sheet->setTitle(Yii::t('app', '汇总'));
        $sheet->setCellValue('A1', Yii::t('app', '刷卡汇总 — {month}', ['month' => $month]));
        $this->styleTitle($sheet, 'A1:B1');

        $rows = [
            [Yii::t('app', '加油升数'), (float) $status['fill_liters'], 3],
            [Yii::t('app', '已刷升数'), (float) $status['swiped_liters'], 3],
            [Yii::t('app', '待刷升数'), (float) $status['left_liters'], 3],
            [Yii::t('app', '应付持卡人'), (float) $status['owner_payout'], 2],
        ];

        $rowNum = 3;
        foreach ($rows as [$label, $value, $decimals]) {
            $sheet->setCellValue('A' . $rowNum, $label);
            $sheet->setCellValue('B' . $rowNum, round($value, $decimals));
            $sheet->getStyle('B' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat($decimals));
            $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        $this->applyBorder($sheet, 'A3:B' . ($rowNum - 1));
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * 按卡主汇总 sheet。
     *
     * @param array<int,array{owner_name:string,cards:int,liters:float,payout:float}> $owners
     */
    private function writeSwipeOwnerSheet(Worksheet $sheet, array $owners): void
    {
        $sheet->setTitle(Yii::t('app', '按卡主汇总'));
        $sheet->fromArray([
            Yii::t('app', '卡所属人'),
            Yii::t('app', '卡数'),
            Yii::t('app', '已刷升数'),
            Yii::t('app', '应付持卡人'),
        ], null, 'A1');
        $this->styleHeader($sheet, 'A1:D1');

        $rowNum = 2;
        $totalCards = 0;
        $totalLiters = 0.0;
        $totalPayout = 0.0;
        foreach ($owners as $owner) {
            $sheet->fromArray([
                $owner['owner_name'] !== '' ? $owner['owner_name'] : '—',
                (int) $owner['cards'],
                (float) $owner['liters'],
                (float) $owner['payout'],
            ], '', 'A' . $rowNum);
            $sheet->getStyle('B' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(0));
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $totalCards += (int) $owner['cards'];
            $totalLiters += (float) $owner['liters'];
            $totalPayout += (float) $owner['payout'];
            $rowNum++;
        }

        if ($owners) {
            $sheet->fromArray([
                Yii::t('app', '合计'),
                $totalCards,
                round($totalLiters, 3),
                round($totalPayout, 2),
            ], '', 'A' . $rowNum);
            $sheet->getStyle('B' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(0));
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A1:D' . $rowNum);
        } else {
            $sheet->setCellValue('A2', Yii::t('app', '暂无数据'));
        }

        $sheet->freezePane('A2');
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * 按卡类型汇总 sheet。
     *
     * @param array<int,array{name:string,cards:int,quota:float,used:float}> $types
     */
    private function writeSwipeTypeSheet(Worksheet $sheet, array $types): void
    {
        $sheet->setTitle(Yii::t('app', '按卡类型汇总'));
        $sheet->fromArray([
            Yii::t('app', '卡类型'),
            Yii::t('app', '卡数'),
            Yii::t('app', '可刷升数'),
            Yii::t('app', '已刷升数'),
        ], null, 'A1');
        $this->styleHeader($sheet, 'A1:D1');

        $rowNum = 2;
        foreach ($types as $type) {
            $sheet->fromArray([
                $type['name'],
                (int) $type['cards'],
                (float) $type['quota'],
                (float) $type['used'],
            ], '', 'A' . $rowNum);
            $sheet->getStyle('B' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(0));
            $sheet->getStyle('C' . $rowNum . ':D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        if ($types) {
            $this->applyBorder($sheet, 'A1:D' . ($rowNum - 1));
        } else {
            $sheet->setCellValue('A2', Yii::t('app', '暂无数据'));
        }

        $sheet->freezePane('A2');
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * 按费率汇总 sheet。
     *
     * @param array<int,array{rate:float,liters:float,payout:float}> $rates
     */
    private function writeSwipeRateSheet(Worksheet $sheet, array $rates): void
    {
        $sheet->setTitle(Yii::t('app', '按费率汇总'));
        $sheet->fromArray([
            Yii::t('app', '费率'),
            Yii::t('app', '已刷升数'),
            Yii::t('app', '应付持卡人'),
        ], null, 'A1');
        $this->styleHeader($sheet, 'A1:C1');

        $rowNum = 2;
        foreach ($rates as $rate) {
            $sheet->fromArray([
                (float) $rate['rate'],
                (float) $rate['liters'],
                (float) $rate['payout'],
            ], '', 'A' . $rowNum);
            $sheet->getStyle('A' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('B' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        if ($rates) {
            $this->applyBorder($sheet, 'A1:C' . ($rowNum - 1));
        } else {
            $sheet->setCellValue('A2', Yii::t('app', '暂无数据'));
        }

        $sheet->freezePane('A2');
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * 卡明细 sheet（当月每张启用卡的额度与使用情况）。
     *
     * @param array<int,array{card_no:string,quota:float,used:float,remaining:float}> $cards
     */
    private function writeSwipeCardSheet(Worksheet $sheet, array $cards): void
    {
        $sheet->setTitle(Yii::t('app', '卡明细'));
        $sheet->fromArray([
            Yii::t('app', '油卡'),
            Yii::t('app', '可刷升数'),
            Yii::t('app', '已用'),
            Yii::t('app', '剩余'),
        ], null, 'A1');
        $this->styleHeader($sheet, 'A1:D1');

        $rowNum = 2;
        foreach ($cards as $card) {
            $sheet->fromArray([
                $card['card_no'],
                (float) $card['quota'],
                (float) $card['used'],
                (float) $card['remaining'],
            ], '', 'A' . $rowNum);
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        if ($cards) {
            $this->applyBorder($sheet, 'A1:D' . ($rowNum - 1));
        } else {
            $sheet->setCellValue('A2', Yii::t('app', '暂无油卡'));
        }

        $sheet->freezePane('A2');
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * 刷卡明细 sheet（列与刷卡记录页一致，含合计行）。
     *
     * @param \common\models\GslSwipe[] $swipes
     */
    private function writeSwipeDetailSheet(Worksheet $sheet, array $swipes): void
    {
        $sheet->setTitle(Yii::t('app', '刷卡明细'));
        $sheet->fromArray([
            Yii::t('app', '序号'),
            Yii::t('app', '日期'),
            Yii::t('app', '油卡'),
            Yii::t('app', '卡类型'),
            Yii::t('app', '升数'),
            Yii::t('app', '费率'),
            Yii::t('app', '应付持卡人'),
            Yii::t('app', '批准号'),
            Yii::t('app', '备注'),
        ], null, 'A1');
        $this->styleHeader($sheet, 'A1:I1');

        $rowNum = 2;
        $index = 1;
        $totalLiters = 0.0;
        $totalPayout = 0.0;
        foreach ($swipes as $swipe) {
            $liters = (float) $swipe->liters;
            $payout = (float) $swipe->owner_payout;
            $sheet->fromArray([
                $index,
                $swipe->work_date,
                $swipe->card ? $swipe->card->getDisplayNo() : '',
                $swipe->card && $swipe->card->cardType ? $swipe->card->cardType->name : '',
                $liters,
                (float) $swipe->owner_rate,
                $payout,
                $swipe->swipe_receipt,
                $swipe->note,
            ], '', 'A' . $rowNum);
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('E' . $rowNum . ':G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $totalLiters += $liters;
            $totalPayout += $payout;
            $index++;
            $rowNum++;
        }

        if ($swipes) {
            $sheet->setCellValue('A' . $rowNum, Yii::t('app', '合计'));
            $sheet->setCellValue('E' . $rowNum, round($totalLiters, 3));
            $sheet->setCellValue('G' . $rowNum, round($totalPayout, 2));
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('E' . $rowNum . ':G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A1:I' . $rowNum);
        } else {
            $sheet->setCellValue('A2', Yii::t('app', '暂无刷卡记录'));
        }

        $sheet->freezePane('A2');
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param array{rows:array<int,array{name:string,deposit:float,used:float,balance:float}>,totals:array{deposit:float,used:float,balance:float}} $balances
     */
    private function writeChitBalancesSheet(Worksheet $sheet, array $balances, string $from, string $to): void
    {
        $sheet->setTitle(Yii::t('app', '客户余额'));
        $range = $from === $to ? $from : $from . ' ~ ' . $to;

        $sheet->setCellValue('A1', Yii::t('app', '客户余额'));
        $sheet->setCellValue('B1', $range);
        $this->styleTitle($sheet, 'A1:B1');

        $sheet->fromArray([
            Yii::t('app', '客户'),
            Yii::t('app', '预存金额'),
            Yii::t('app', '已用金额'),
            Yii::t('app', '剩余金额'),
        ], null, 'A3');
        $this->styleHeader($sheet, 'A3:D3');

        $rowNum = 4;
        foreach ($balances['rows'] as $row) {
            $sheet->fromArray([
                $row['name'],
                $row['deposit'],
                $row['used'],
                $row['balance'],
            ], '', 'A' . $rowNum);
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        $sheet->fromArray([
            Yii::t('app', '合计'),
            $balances['totals']['deposit'],
            $balances['totals']['used'],
            $balances['totals']['balance'],
        ], '', 'A' . $rowNum);
        $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
        $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('B' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->applyBorder($sheet, 'A3:D' . $rowNum);

        $sheet->freezePane('A4');
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param \common\models\GslChit[] $records
     */
    private function writeChitRecordsSheet(Worksheet $sheet, array $records): void
    {
        $sheet->setTitle(Yii::t('app', '使用记录'));
        $sheet->fromArray([
            Yii::t('app', '序号'),
            Yii::t('app', '日期'),
            Yii::t('app', '柜台'),
            Yii::t('app', '客户'),
            Yii::t('app', '油类型'),
            Yii::t('app', '柜员号'),
            Yii::t('app', '金额'),
            Yii::t('app', '票根号'),
            Yii::t('app', '收据号'),
        ], null, 'A1');
        $this->styleHeader($sheet, 'A1:I1');

        $rowNum = 2;
        $total = 0.0;
        $index = 1;
        foreach ($records as $record) {
            $amount = (float) $record->amount;
            $sheet->fromArray([
                $index,
                $record->work_date,
                $record->counter ? $record->counter->getLabel() : '',
                $record->customer ? $record->customer->name : '',
                $record->oil_type,
                $record->operator_no,
                $amount,
                $record->chit_stub_no,
                $record->receipt_no,
            ], '', 'A' . $rowNum);
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $total += $amount;
            $index++;
            $rowNum++;
        }

        if ($records) {
            $sheet->setCellValue('A' . $rowNum, Yii::t('app', '合计'));
            $sheet->setCellValue('G' . $rowNum, round($total, 2));
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A1:I' . $rowNum);
        } else {
            $sheet->setCellValue('A2', Yii::t('app', '暂无数据'));
        }

        $sheet->freezePane('A2');
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param array{
     *   customers: array<int,array{id:int,name:string}>,
     *   dates: string[],
     *   matrix: array<int,array<string,float>>,
     *   rowTotals: array<int,float>,
     *   colTotals: array<string,float>,
     *   grandTotal: float
     * } $daily
     */
    private function writeChitDailySheet(Worksheet $sheet, array $daily, string $from, string $to): void
    {
        $sheet->setTitle(Yii::t('app', '每日消费明细'));
        $range = $from === $to ? $from : $from . ' ~ ' . $to;

        $sheet->setCellValue('A1', Yii::t('app', '每日消费明细'));
        $sheet->setCellValue('B1', $range);
        $this->styleTitle($sheet, 'A1:B1');

        $header = [Yii::t('app', '客户')];
        foreach ($daily['dates'] as $date) {
            $header[] = substr($date, 5);
        }
        $header[] = Yii::t('app', '合计');
        $lastCol = Coordinate::stringFromColumnIndex(count($header));

        $sheet->fromArray($header, null, 'A3');
        $this->styleHeader($sheet, 'A3:' . $lastCol . '3');

        $rowNum = 4;
        foreach ($daily['customers'] as $cust) {
            $row = [$cust['name']];
            foreach ($daily['dates'] as $date) {
                $value = (float) ($daily['matrix'][$cust['id']][$date] ?? 0.0);
                $row[] = $value > 0 ? $value : null;
            }
            $row[] = (float) ($daily['rowTotals'][$cust['id']] ?? 0.0);
            $sheet->fromArray($row, '', 'A' . $rowNum);
            $sheet->getStyle('B' . $rowNum . ':' . $lastCol . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('B' . $rowNum . ':' . $lastCol . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        if ($daily['customers']) {
            $totalRow = [Yii::t('app', '合计')];
            foreach ($daily['dates'] as $date) {
                $totalRow[] = (float) ($daily['colTotals'][$date] ?? 0.0);
            }
            $totalRow[] = (float) $daily['grandTotal'];
            $sheet->fromArray($totalRow, '', 'A' . $rowNum);
            $sheet->getStyle('B' . $rowNum . ':' . $lastCol . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('B' . $rowNum . ':' . $lastCol . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A3:' . $lastCol . $rowNum);
        } else {
            $sheet->setCellValue('A4', Yii::t('app', '暂无数据'));
        }

        $sheet->freezePane('B4');
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param array{date_from:string,date_to:string,fill_count:int,total_liters:float,amount_due:float,list_amount:float,total_cost:float,swipe_liters:float} $kpis
     * @param array{date_from:string,date_to:string,companies:array,totals:array} $company
     * @param array{date_from:string,date_to:string,counters:array,totals:array} $counter
     * @param array{date_from:string,date_to:string,days:array,totals:array} $dailyCompany
     */
    private function writeMonthSheet(Worksheet $sheet, array $kpis, array $company, array $counter, array $dailyCompany): void
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
        $this->writeDailyCompanyBlock($sheet, $dailyCompany, $rowNum);

        $sheet->freezePane('A4');
        foreach (range('A', 'F') as $col) {
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
        bool $monthly = false,
        ?array $dailyCompany = null
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
        if ($monthly && $dailyCompany !== null) {
            $this->writeDailyCompanyBlock($sheet, $dailyCompany, $rowNum);
        } else {
            $this->writeDailyTotalsBlock($sheet, $daily, $rowNum);
        }

        $sheet->freezePane('A4');
        foreach (range('A', 'F') as $col) {
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
     * @param array{date_from:string,date_to:string,days:array,totals:array} $dailyCompany
     */
    private function writeDailyCompanyBlock(Worksheet $sheet, array $dailyCompany, int $rowNum): int
    {
        $sheet->setCellValue('A' . $rowNum, Yii::t('app', '按日按公司'));
        $this->styleTitle($sheet, 'A' . $rowNum);
        $rowNum++;
        $headerRow = $rowNum;
        $sheet->fromArray([
            Yii::t('app', '工作日期'),
            Yii::t('app', '公司'),
            Yii::t('app', '付款方式'),
            Yii::t('app', '票数'),
            Yii::t('app', '升数'),
            Yii::t('app', '应收'),
        ], null, 'A' . $rowNum);
        $this->styleHeader($sheet, 'A' . $rowNum . ':F' . $rowNum);
        $rowNum++;

        if ($dailyCompany['days']) {
            foreach ($dailyCompany['days'] as $day) {
                foreach ($day['companies'] as $index => $row) {
                    $sheet->fromArray([
                        $index === 0 ? $day['work_date'] : '',
                        $row['company_name'],
                        $row['payment_type'],
                        $row['count'],
                        $row['liters'],
                        $row['amount_due'],
                    ], null, 'A' . $rowNum);
                    $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
                    $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
                    $sheet->getStyle('D' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $rowNum++;
                }
                $sheet->fromArray([
                    $day['work_date'],
                    Yii::t('app', '当日合计'),
                    '',
                    $day['totals']['count'],
                    $day['totals']['liters'],
                    $day['totals']['amount_due'],
                ], null, 'A' . $rowNum);
                $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
                $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
                $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
                $sheet->getStyle('D' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $rowNum++;
            }
            $sheet->fromArray([
                Yii::t('app', '合计'),
                '',
                '',
                $dailyCompany['totals']['count'],
                $dailyCompany['totals']['liters'],
                $dailyCompany['totals']['amount_due'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(3));
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode($this->numberFormat(2));
            $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('D' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $this->applyBorder($sheet, 'A' . $headerRow . ':F' . $rowNum);
        } else {
            $sheet->setCellValue('A' . $rowNum, Yii::t('app', '无有效日数据'));
        }

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
