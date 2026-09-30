<?php

namespace common\services\import;

use common\models\GslCard;
use common\models\GslCompany;
use common\models\GslCounter;
use common\models\GslDailyTicketLog;
use common\models\GslFill;
use common\models\GslPlate;
use common\models\GslPriceDiscount;
use common\models\GslPricePeriod;
use common\models\GslSetting;
use common\models\GslSwipe;
use common\services\FillAmountCalculator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Yii;
use yii\db\Connection;

class ExcelImportService
{
    private bool $dryRun = false;
    private array $stats = [];
    private array $companyMap = [];
    private array $cardMap = [];
    private array $counterMap = [];

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function import(string $filePath, bool $truncate = true): void
    {
        if (!is_readable($filePath)) {
            throw new \RuntimeException("Cannot read Excel file: {$filePath}");
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($filePath);

        if ($truncate && !$this->dryRun) {
            $this->truncateBusinessTables();
        }

        $this->importSettings($spreadsheet->getSheetByName('Settings'));
        $this->importPlates($spreadsheet->getSheetByName('Plates'));
        $this->importPrices($spreadsheet->getSheetByName('Prices'));
        $this->importCards($spreadsheet->getSheetByName('Cards'));
        $this->importFills($spreadsheet->getSheetByName('Fills'));
        $this->importSwipes($spreadsheet->getSheetByName('Swipes'));
        $this->importDailyTickets($spreadsheet->getSheetByName('Daily'));
    }

    private function truncateBusinessTables(): void
    {
        /** @var Connection $db */
        $db = Yii::$app->db;
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach ([
            '{{%gsl_swipe}}',
            '{{%gsl_fill}}',
            '{{%gsl_daily_ticket_log}}',
            '{{%gsl_price_discount}}',
            '{{%gsl_price_period}}',
            '{{%gsl_card}}',
            '{{%gsl_plate}}',
            '{{%gsl_company}}',
            '{{%gsl_setting}}',
        ] as $table) {
            $db->createCommand()->truncateTable($table)->execute();
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
    }

    private function importSettings(?Worksheet $sheet): void
    {
        if (!$sheet) {
            return;
        }
        $month = null;
        $cost = null;
        foreach ($sheet->getRowIterator() as $row) {
            $key = trim((string) $this->cellVal($sheet, 'A' . $row->getRowIndex()));
            $val = trim((string) $this->cellVal($sheet, 'B' . $row->getRowIndex()));
            if (strcasecmp($key, 'Month') === 0) {
                $month = $val;
            }
            if (stripos($key, 'Cost') !== false) {
                $cost = $val;
            }
        }
        if ($month) {
            $this->saveSetting('ledger_month', $month);
        }
        if ($cost !== null && $cost !== '') {
            $this->saveSetting('cost_per_liter', (string) FillAmountCalculator::roundPrice((float) $cost));
        }
        $this->stats['settings'] = 1;
    }

    private function importPlates(?Worksheet $sheet): void
    {
        if (!$sheet) {
            return;
        }
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $count = 0;
        foreach ($cols as $i => $col) {
            $name = trim((string) $this->cellVal($sheet, $col . '2'));
            if ($name === '') {
                continue;
            }
            $payment = trim((string) $this->cellVal($sheet, $col . '3'));
            if (!in_array($payment, ['Cash', 'MCM'], true)) {
                $payment = 'Cash';
            }
            $company = $this->saveCompany($name, $payment, $i + 1);
            $this->companyMap[$name] = $company->id;

            for ($row = 4; $row <= 200; $row++) {
                $plateNo = trim((string) $this->cellVal($sheet, $col . $row));
                if ($plateNo === '') {
                    break;
                }
                $this->savePlate($company->id, $plateNo);
                $count++;
            }
        }
        $this->stats['plates'] = $count;
        $this->stats['companies'] = count($this->companyMap);
    }

    private function importPrices(?Worksheet $sheet): void
    {
        if (!$sheet) {
            return;
        }
        $companies = GslCompany::find()->orderBy('sort_order')->all();
        if (!$this->dryRun && empty($companies)) {
            $companies = array_map(fn($id) => GslCompany::findOne($id), $this->companyMap);
        }
        $periodCount = 0;
        $discountCount = 0;
        for ($row = 3; $row <= 32; $row++) {
            $from = $this->parseDate($this->cellVal($sheet, 'A' . $row));
            if (!$from) {
                continue;
            }
            $to = $this->parseDate($this->cellVal($sheet, 'B' . $row));
            $listPrice = FillAmountCalculator::roundPrice((float) $this->cellVal($sheet, 'C' . $row));
            $period = $this->savePricePeriod($from, $to ?: $from, $listPrice);
            $periodCount++;

            $cols = ['D', 'E', 'F', 'G', 'H', 'I'];
            foreach ($cols as $j => $col) {
                $company = $companies[$j] ?? null;
                if (!$company) {
                    continue;
                }
                $discount = $this->cellVal($sheet, $col . $row);
                if ($discount === null || $discount === '') {
                    continue;
                }
                $this->savePriceDiscount($period->id, $company->id, FillAmountCalculator::roundPrice((float) $discount));
                $discountCount++;
            }
        }
        $this->stats['price_periods'] = $periodCount;
        $this->stats['price_discounts'] = $discountCount;
    }

    private function importCards(?Worksheet $sheet): void
    {
        if (!$sheet) {
            return;
        }
        $count = 0;
        for ($row = 3; $row <= 12; $row++) {
            $code = trim((string) $this->cellVal($sheet, 'A' . $row));
            if ($code === '') {
                continue;
            }
            $sort = (int) $this->cellVal($sheet, 'D' . $row);
            $this->saveCard($code, $sort ?: ($row - 2));
            $count++;
        }
        $this->stats['cards'] = $count;
    }

    private function importFills(?Worksheet $sheet): void
    {
        if (!$sheet) {
            return;
        }
        $count = 0;
        for ($row = 3; $row <= 202; $row++) {
            $litersRaw = $this->cellVal($sheet, 'D' . $row);
            if ($litersRaw === null || $litersRaw === '') {
                break;
            }

            $workDate = $this->parseDate($this->cellVal($sheet, 'A' . $row));
            $companyName = trim((string) $this->cellVal($sheet, 'B' . $row));
            $company = GslCompany::findByName($companyName);
            if (!$company) {
                throw new \RuntimeException("Unknown company at Fills row {$row}: {$companyName}");
            }

            $plateNo = trim((string) $this->cellVal($sheet, 'C' . $row));
            $plateId = null;
            if ($plateNo !== '') {
                $plate = GslPlate::findOrCreate($company->id, $plateNo);
                $plateId = $plate->id;
            }

            $counterCode = trim((string) $this->cellVal($sheet, 'E' . $row));
            $counterId = null;
            if ($counterCode !== '') {
                $counter = GslCounter::findByCode($counterCode);
                if (!$counter) {
                    throw new \RuntimeException("Unknown counter at Fills row {$row}: {$counterCode}");
                }
                $counterId = $counter->id;
            }

            $fill = new GslFill([
                'work_date' => $workDate,
                'company_id' => $company->id,
                'plate_id' => $plateId,
                'liters' => FillAmountCalculator::truncLiters($litersRaw),
                'counter_id' => $counterId,
                'ticket_no' => trim((string) $this->cellVal($sheet, 'F' . $row)),
                'note' => trim((string) $this->cellVal($sheet, 'G' . $row)),
                'list_price' => $this->numOrNull($this->cellVal($sheet, 'H' . $row)),
                'list_amount' => $this->numOrNull($this->cellVal($sheet, 'I' . $row)),
                'discount_price' => $this->numOrNull($this->cellVal($sheet, 'J' . $row)),
                'amount_due' => $this->numOrNull($this->cellVal($sheet, 'K' . $row)),
                'cost' => $this->numOrNull($this->cellVal($sheet, 'L' . $row)),
            ]);

            if (!$this->dryRun) {
                $fill->save(false);
            }
            $count++;
        }
        $this->stats['fills'] = $count;
    }

    private function importSwipes(?Worksheet $sheet): void
    {
        if (!$sheet) {
            return;
        }
        $count = 0;
        for ($row = 16; $row <= 215; $row++) {
            $dateRaw = $this->cellVal($sheet, 'A' . $row);
            $litersRaw = $this->cellVal($sheet, 'D' . $row);
            if (($dateRaw === null || $dateRaw === '') && ($litersRaw === null || $litersRaw === '')) {
                continue;
            }
            if ($litersRaw === null || $litersRaw === '' || (float) $litersRaw == 0) {
                continue;
            }

            $workDate = $this->parseDate($dateRaw);
            $companyName = trim((string) $this->cellVal($sheet, 'B' . $row));
            $company = GslCompany::findByName($companyName);
            if (!$company) {
                throw new \RuntimeException("Unknown company at Swipes row {$row}: {$companyName}");
            }

            $cardCode = trim((string) $this->cellVal($sheet, 'C' . $row));
            $card = GslCard::findByCode($cardCode);
            if (!$card) {
                throw new \RuntimeException("Unknown card at Swipes row {$row}: {$cardCode}");
            }

            $swipe = new GslSwipe([
                'work_date' => $workDate,
                'company_id' => $company->id,
                'card_id' => $card->id,
                'liters' => FillAmountCalculator::truncLiters($litersRaw),
                'swipe_receipt' => trim((string) $this->cellVal($sheet, 'E' . $row)),
            ]);

            if (!$this->dryRun) {
                $swipe->save(false);
            }
            $count++;
        }
        $this->stats['swipes'] = $count;
    }

    private function importDailyTickets(?Worksheet $sheet): void
    {
        if (!$sheet) {
            return;
        }
        $count = 0;
        for ($row = 2; $row <= 31; $row++) {
            $manual = $this->cellVal($sheet, 'H' . $row);
            if ($manual === null || $manual === '') {
                continue;
            }
            $workDate = $this->parseDate($this->cellVal($sheet, 'G' . $row));
            if (!$this->dryRun) {
                $log = GslDailyTicketLog::findOne(['work_date' => $workDate]) ?? new GslDailyTicketLog(['work_date' => $workDate]);
                $log->tickets_manual = (int) $manual;
                $log->updated_at = time();
                $log->save(false);
            }
            $count++;
        }
        $this->stats['daily_tickets'] = $count;
    }

    private function cellVal(Worksheet $sheet, string $coord)
    {
        $cell = $sheet->getCell($coord);
        $val = $cell->getCalculatedValue();
        if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return $val->getPlainText();
        }
        return $val;
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        $ts = strtotime((string) $value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function numOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return FillAmountCalculator::roundPrice((float) $value);
    }

    private function saveSetting(string $key, string $value): void
    {
        if ($this->dryRun) {
            return;
        }
        GslSetting::setValue($key, $value);
    }

    private function saveCompany(string $name, string $payment, int $sortOrder): GslCompany
    {
        if ($this->dryRun) {
            $c = new GslCompany(['name' => $name, 'payment_type' => $payment, 'sort_order' => $sortOrder]);
            $c->id = count($this->companyMap) + 1;
            return $c;
        }
        $company = GslCompany::findByName($name) ?? new GslCompany();
        $company->name = $name;
        $company->payment_type = $payment;
        $company->sort_order = $sortOrder;
        $company->is_active = 1;
        $company->save(false);
        return $company;
    }

    private function savePlate(int $companyId, string $plateNo): void
    {
        if ($this->dryRun) {
            return;
        }
        GslPlate::findOrCreate($companyId, $plateNo);
    }

    private function savePricePeriod(string $from, string $to, float $listPrice): GslPricePeriod
    {
        $period = new GslPricePeriod([
            'valid_from' => $from,
            'valid_to' => $to,
            'list_price' => $listPrice,
        ]);
        if (!$this->dryRun) {
            $period->save(false);
        }
        return $period;
    }

    private function savePriceDiscount(int $periodId, int $companyId, float $price): void
    {
        if ($this->dryRun) {
            return;
        }
        $d = new GslPriceDiscount([
            'price_period_id' => $periodId,
            'company_id' => $companyId,
            'discount_price' => $price,
        ]);
        $d->save(false);
    }

    private function saveCard(string $code, int $sort): void
    {
        if ($this->dryRun) {
            $this->cardMap[$code] = count($this->cardMap) + 1;
            return;
        }
        $card = GslCard::findByCode($code) ?? new GslCard();
        $card->card_code = $code;
        $card->sort_order = $sort;
        $card->monthly_quota = 500;
        $card->is_active = 1;
        $card->save(false);
        $this->cardMap[$code] = $card->id;
    }
}
