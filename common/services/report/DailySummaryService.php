<?php

namespace common\services\report;

use common\models\GslCompany;
use common\models\GslFill;
use common\models\GslPriceDiscount;
use common\models\GslPricePeriod;

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
