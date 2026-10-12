<?php

namespace common\services\report;

use common\models\GslCompany;
use common\models\GslFill;
use common\models\GslPriceDiscount;
use common\models\GslPricePeriod;
use common\models\GslSwipe;

class DailySummaryService
{
    public function getSummary(string $workDate): array
    {
        $companies = GslCompany::find()->where(['is_active' => 1])->orderBy('sort_order')->all();
        $rows = [];
        $totals = ['liters' => 0, 'amount_due' => 0, 'cost' => 0, 'count' => 0];

        foreach ($companies as $company) {
            $agg = GslFill::find()
                ->select([
                    'liters' => 'ROUND(SUM(liters), 3)',
                    'amount_due' => 'ROUND(SUM(amount_due), 2)',
                    'cost' => 'ROUND(SUM(cost), 2)',
                    'discount_price' => 'MAX(discount_price)',
                    'cnt' => 'COUNT(*)',
                ])
                ->where(['work_date' => $workDate, 'company_id' => $company->id])
                ->andWhere(['>', 'liters', 0])
                ->asArray()
                ->one();

            $count = (int) ($agg['cnt'] ?? 0);
            $discountPrice = $count > 0 && ($agg['discount_price'] ?? null) !== null
                ? (float) $agg['discount_price']
                : $this->resolveDiscountPrice((int) $company->id, $workDate);

            $row = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'payment_type' => $company->payment_type,
                'discount_price' => $discountPrice,
                'liters' => (float) ($agg['liters'] ?? 0),
                'amount_due' => (float) ($agg['amount_due'] ?? 0),
                'cost' => (float) ($agg['cost'] ?? 0),
                'count' => $count,
            ];
            $rows[] = $row;
            $totals['liters'] += $row['liters'];
            $totals['amount_due'] += $row['amount_due'];
            $totals['cost'] += $row['cost'];
            $totals['count'] += $row['count'];
        }

        return [
            'work_date' => $workDate,
            'companies' => $rows,
            'totals' => $totals,
            'profit' => $this->getProfit($workDate, $rows, $totals),
        ];
    }

    /**
     * Daily profit: gross = amount_due + cost (cost is negative),
     * card owner payout from swipes, net = gross - payout.
     */
    public function getProfit(string $workDate, array $companyRows, array $totals): array
    {
        $swipeAgg = GslSwipe::find()
            ->select([
                'liters' => 'ROUND(SUM(liters), 3)',
                'owner_payout' => 'ROUND(SUM(owner_payout), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['work_date' => $workDate])
            ->asArray()
            ->one();

        $ownerPayout = (float) ($swipeAgg['owner_payout'] ?? 0);
        $swipeLiters = (float) ($swipeAgg['liters'] ?? 0);
        $swipeCount = (int) ($swipeAgg['cnt'] ?? 0);

        $companyProfits = [];
        foreach ($companyRows as $row) {
            $gross = round($row['amount_due'] + $row['cost'], 2);
            $companyProfits[] = [
                'company_name' => $row['company_name'],
                'gross' => $gross,
            ];
        }

        $grossTotal = round($totals['amount_due'] + $totals['cost'], 2);

        return [
            'companies' => $companyProfits,
            'gross_total' => $grossTotal,
            'owner_payout' => round($ownerPayout, 2),
            'net' => round($grossTotal - $ownerPayout, 2),
            'swipe_liters' => $swipeLiters,
            'swipe_count' => $swipeCount,
        ];
    }

    /**
     * Per-day profit for a date range (used by weekly/monthly views).
     *
     * @return array{date_from:string,date_to:string,days:array,totals:array}
     */
    public function getProfitForRange(string $from, string $to): array
    {
        $fillAgg = GslFill::find()
            ->select([
                'work_date',
                'amount_due' => 'ROUND(SUM(amount_due), 2)',
                'cost' => 'ROUND(SUM(cost), 2)',
            ])
            ->where(['between', 'work_date', $from, $to])
            ->andWhere(['>', 'liters', 0])
            ->groupBy('work_date')
            ->indexBy('work_date')
            ->asArray()
            ->all();

        $swipeAgg = GslSwipe::find()
            ->select([
                'work_date',
                'owner_payout' => 'ROUND(SUM(owner_payout), 2)',
                'liters' => 'ROUND(SUM(liters), 3)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['between', 'work_date', $from, $to])
            ->groupBy('work_date')
            ->indexBy('work_date')
            ->asArray()
            ->all();

        // Build a sorted union of all dates that have fills or swipes
        $dates = array_unique(array_merge(array_keys($fillAgg), array_keys($swipeAgg)));
        sort($dates);

        $days = [];
        $totals = ['gross' => 0.0, 'owner_payout' => 0.0, 'net' => 0.0, 'swipe_liters' => 0.0, 'swipe_count' => 0];

        foreach ($dates as $date) {
            $fill = $fillAgg[$date] ?? null;
            $swipe = $swipeAgg[$date] ?? null;

            $amountDue = (float) ($fill['amount_due'] ?? 0);
            $cost = (float) ($fill['cost'] ?? 0);
            $gross = round($amountDue + $cost, 2);
            $payout = round((float) ($swipe['owner_payout'] ?? 0), 2);
            $net = round($gross - $payout, 2);

            $days[] = [
                'work_date' => $date,
                'gross' => $gross,
                'owner_payout' => $payout,
                'net' => $net,
                'swipe_liters' => (float) ($swipe['liters'] ?? 0),
                'swipe_count' => (int) ($swipe['cnt'] ?? 0),
                'fill_count' => $fill !== null ? 1 : 0,
            ];

            $totals['gross'] += $gross;
            $totals['owner_payout'] += $payout;
            $totals['net'] += $net;
            $totals['swipe_liters'] += (float) ($swipe['liters'] ?? 0);
            $totals['swipe_count'] += (int) ($swipe['cnt'] ?? 0);
        }

        $totals['gross'] = round($totals['gross'], 2);
        $totals['owner_payout'] = round($totals['owner_payout'], 2);
        $totals['net'] = round($totals['net'], 2);
        $totals['swipe_liters'] = round($totals['swipe_liters'], 3);

        return [
            'date_from' => $from,
            'date_to' => $to,
            'days' => $days,
            'totals' => $totals,
        ];
    }

    /**
     * @return array{date_from:string,date_to:string,companies:array,totals:array}
     */
    public function getCompanyForRange(string $from, string $to): array
    {
        $aggregates = GslFill::find()
            ->select([
                'company_id',
                'liters' => 'ROUND(SUM(liters), 3)',
                'amount_due' => 'ROUND(SUM(amount_due), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['between', 'work_date', $from, $to])
            ->andWhere(['>', 'liters', 0])
            ->groupBy('company_id')
            ->indexBy('company_id')
            ->asArray()
            ->all();

        $rows = [];
        $totals = ['count' => 0, 'liters' => 0.0, 'amount_due' => 0.0];
        foreach (GslCompany::find()->where(['is_active' => 1])->orderBy('sort_order')->all() as $company) {
            $agg = $aggregates[$company->id] ?? null;
            $count = (int) ($agg['cnt'] ?? 0);
            if ($count === 0) {
                continue;
            }
            $row = [
                'company_id' => (int) $company->id,
                'company_name' => $company->name,
                'payment_type' => $company->payment_type,
                'count' => $count,
                'liters' => (float) ($agg['liters'] ?? 0),
                'amount_due' => (float) ($agg['amount_due'] ?? 0),
            ];
            $rows[] = $row;
            $totals['count'] += $row['count'];
            $totals['liters'] += $row['liters'];
            $totals['amount_due'] += $row['amount_due'];
        }

        return [
            'date_from' => $from,
            'date_to' => $to,
            'companies' => $rows,
            'totals' => [
                'count' => $totals['count'],
                'liters' => round($totals['liters'], 3),
                'amount_due' => round($totals['amount_due'], 2),
            ],
        ];
    }

    /**
     * Per-day companies that actually filled. Zero-fill companies are omitted.
     *
     * @return array{date_from:string,date_to:string,days:array,totals:array}
     */
    public function getDailyCompanyForRange(string $from, string $to): array
    {
        $aggregates = GslFill::find()
            ->alias('f')
            ->innerJoin(['co' => GslCompany::tableName()], 'co.id = f.company_id')
            ->select([
                'work_date' => 'f.work_date',
                'company_id' => 'f.company_id',
                'company_name' => 'co.name',
                'payment_type' => 'co.payment_type',
                'liters' => 'ROUND(SUM(f.liters), 3)',
                'amount_due' => 'ROUND(SUM(f.amount_due), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['between', 'f.work_date', $from, $to])
            ->andWhere(['>', 'f.liters', 0])
            ->groupBy(['f.work_date', 'f.company_id'])
            ->orderBy([
                'f.work_date' => SORT_ASC,
                'co.sort_order' => SORT_ASC,
                'co.name' => SORT_ASC,
            ])
            ->asArray()
            ->all();

        $days = [];
        $totals = ['count' => 0, 'liters' => 0.0, 'amount_due' => 0.0];
        foreach ($aggregates as $agg) {
            $date = $agg['work_date'];
            if (!isset($days[$date])) {
                $days[$date] = [
                    'work_date' => $date,
                    'companies' => [],
                    'totals' => ['count' => 0, 'liters' => 0.0, 'amount_due' => 0.0],
                ];
            }

            $row = [
                'company_id' => (int) $agg['company_id'],
                'company_name' => (string) $agg['company_name'],
                'payment_type' => (string) $agg['payment_type'],
                'count' => (int) $agg['cnt'],
                'liters' => (float) $agg['liters'],
                'amount_due' => (float) $agg['amount_due'],
            ];
            $days[$date]['companies'][] = $row;
            $days[$date]['totals']['count'] += $row['count'];
            $days[$date]['totals']['liters'] += $row['liters'];
            $days[$date]['totals']['amount_due'] += $row['amount_due'];
            $totals['count'] += $row['count'];
            $totals['liters'] += $row['liters'];
            $totals['amount_due'] += $row['amount_due'];
        }

        foreach ($days as &$day) {
            $day['totals']['liters'] = round($day['totals']['liters'], 3);
            $day['totals']['amount_due'] = round($day['totals']['amount_due'], 2);
        }
        unset($day);

        return [
            'date_from' => $from,
            'date_to' => $to,
            'days' => array_values($days),
            'totals' => [
                'count' => $totals['count'],
                'liters' => round($totals['liters'], 3),
                'amount_due' => round($totals['amount_due'], 2),
            ],
        ];
    }

    /**
     * @return GslFill[]
     */
    public function getFillsForRange(string $from, string $to): array
    {
        return GslFill::find()
            ->with(['company', 'plate', 'counter'])
            ->where(['between', 'work_date', $from, $to])
            ->andWhere(['>', 'liters', 0])
            ->orderBy([
                'work_date' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->all();
    }

    public function getCompanyDetails(string $workDate, int $companyId): array
    {
        return GslFill::find()
            ->with(['plate', 'counter'])
            ->where(['work_date' => $workDate, 'company_id' => $companyId])
            ->andWhere(['>', 'liters', 0])
            ->orderBy('id')
            ->all();
    }

    private function resolveDiscountPrice(int $companyId, string $workDate): ?float
    {
        $period = GslPricePeriod::findForDate($workDate);
        if ($period === null) {
            return null;
        }

        $discount = GslPriceDiscount::findOne([
            'price_period_id' => $period->id,
            'company_id' => $companyId,
        ]);

        return $discount ? (float) $discount->discount_price : (float) $period->list_price;
    }
}
