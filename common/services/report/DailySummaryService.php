<?php

namespace common\services\report;

use common\models\GslCard;
use common\models\GslCardType;
use common\models\GslCompany;
use common\models\GslFill;
use common\models\GslPriceDiscount;
use common\models\GslPricePeriod;
use common\models\GslSwipe;

class DailySummaryService
{
    /** 纯利/应付拆分所涉及的卡类型代码（gsl_card_type.code） */
    private const SPLIT_CARD_TYPES = ['star', 'sub'];

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
     * card owner payout from swipes (split by star/sub card type), net = gross - payout.
     *
     * 某类型应付为 0 视为该类型刷卡数据未录入，该类型纯利记 0；
     * 类型纯利中的毛利部分按「该类型刷卡升数 / 总刷卡升数」占比分摊。
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

        $typeAgg = $this->getSwipeTypeAggForRange($workDate, $workDate)[$workDate] ?? [];

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
        $starPayout = round((float) ($typeAgg['star']['payout'] ?? 0), 2);
        $subPayout = round((float) ($typeAgg['sub']['payout'] ?? 0), 2);
        $starNet = $this->splitTypeNet($grossTotal, $starPayout, (float) ($typeAgg['star']['liters'] ?? 0), $swipeLiters);
        $subNet = $this->splitTypeNet($grossTotal, $subPayout, (float) ($typeAgg['sub']['liters'] ?? 0), $swipeLiters);

        return [
            'companies' => $companyProfits,
            'gross_total' => $grossTotal,
            'owner_payout' => round($ownerPayout, 2),
            'star_payout' => $starPayout,
            'sub_payout' => $subPayout,
            'star_cnt' => (int) ($typeAgg['star']['cnt'] ?? 0),
            'sub_cnt' => (int) ($typeAgg['sub']['cnt'] ?? 0),
            'star_liters' => round((float) ($typeAgg['star']['liters'] ?? 0), 3),
            'sub_liters' => round((float) ($typeAgg['sub']['liters'] ?? 0), 3),
            'net' => round($starNet + $subNet, 2),
            'star_net' => $starNet,
            'sub_net' => $subNet,
            'swipe_liters' => $swipeLiters,
            'swipe_count' => $swipeCount,
        ];
    }

    /**
     * Per-day profit for a date range (used by weekly/monthly views).
     *
     * 卡主应付与纯利按卡类型（star/sub）拆分：
     * - 某类型应付为 0 视为该类型刷卡数据未录入，该类型纯利记 0；
     * - 类型纯利中的毛利部分按「该类型刷卡升数 / 总刷卡升数」占比分摊。
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

        $typeAgg = $this->getSwipeTypeAggForRange($from, $to);

        // Build a sorted union of all dates that have fills or swipes
        $dates = array_unique(array_merge(array_keys($fillAgg), array_keys($swipeAgg)));
        sort($dates);

        $days = [];
        $totals = [
            'gross' => 0.0, 'owner_payout' => 0.0, 'net' => 0.0,
            'star_payout' => 0.0, 'sub_payout' => 0.0, 'star_net' => 0.0, 'sub_net' => 0.0,
            'swipe_liters' => 0.0, 'swipe_count' => 0,
        ];

        foreach ($dates as $date) {
            $fill = $fillAgg[$date] ?? null;
            $swipe = $swipeAgg[$date] ?? null;
            $type = $typeAgg[$date] ?? [];

            $amountDue = (float) ($fill['amount_due'] ?? 0);
            $cost = (float) ($fill['cost'] ?? 0);
            $gross = round($amountDue + $cost, 2);
            $payout = round((float) ($swipe['owner_payout'] ?? 0), 2);
            $swipeLiters = (float) ($swipe['liters'] ?? 0);
            $starPayout = round((float) ($type['star']['payout'] ?? 0), 2);
            $subPayout = round((float) ($type['sub']['payout'] ?? 0), 2);
            $starNet = $this->splitTypeNet($gross, $starPayout, (float) ($type['star']['liters'] ?? 0), $swipeLiters);
            $subNet = $this->splitTypeNet($gross, $subPayout, (float) ($type['sub']['liters'] ?? 0), $swipeLiters);
            $net = round($starNet + $subNet, 2);

            $days[] = [
                'work_date' => $date,
                'gross' => $gross,
                'owner_payout' => $payout,
                'star_payout' => $starPayout,
                'sub_payout' => $subPayout,
                'net' => $net,
                'star_net' => $starNet,
                'sub_net' => $subNet,
                'swipe_liters' => $swipeLiters,
                'swipe_count' => (int) ($swipe['cnt'] ?? 0),
                'fill_count' => $fill !== null ? 1 : 0,
            ];

            $totals['gross'] += $gross;
            $totals['owner_payout'] += $payout;
            $totals['star_payout'] += $starPayout;
            $totals['sub_payout'] += $subPayout;
            $totals['swipe_liters'] += $swipeLiters;
            $totals['swipe_count'] += (int) ($swipe['cnt'] ?? 0);
        }

        $totals['gross'] = round($totals['gross'], 2);
        $totals['owner_payout'] = round($totals['owner_payout'], 2);
        $totals['star_payout'] = round($totals['star_payout'], 2);
        $totals['sub_payout'] = round($totals['sub_payout'], 2);
        $totals['star_net'] = $this->splitTypeNet(
            $totals['gross'],
            $totals['star_payout'],
            $this->sumTypeLiters($typeAgg, 'star'),
            round($totals['swipe_liters'], 3)
        );
        $totals['sub_net'] = $this->splitTypeNet(
            $totals['gross'],
            $totals['sub_payout'],
            $this->sumTypeLiters($typeAgg, 'sub'),
            round($totals['swipe_liters'], 3)
        );
        $totals['net'] = round($totals['star_net'] + $totals['sub_net'], 2);
        $totals['swipe_liters'] = round($totals['swipe_liters'], 3);

        return [
            'date_from' => $from,
            'date_to' => $to,
            'days' => $days,
            'totals' => $totals,
        ];
    }

    /**
     * 按卡类型（star/sub）汇总区间内每日刷卡应付与升数。
     *
     * @return array<string, array<string, array{payout:float,liters:float,cnt:int}>> work_date => type_code => agg
     */
    private function getSwipeTypeAggForRange(string $from, string $to): array
    {
        $rows = GslSwipe::find()
            ->select([
                'work_date',
                'type_code' => 'ct.code',
                'payout' => 'ROUND(SUM({{%gsl_swipe}}.owner_payout), 2)',
                'liters' => 'ROUND(SUM({{%gsl_swipe}}.liters), 3)',
                'cnt' => 'COUNT(*)',
            ])
            ->innerJoin(['c' => GslCard::tableName()], '{{%gsl_swipe}}.card_id = c.id')
            ->innerJoin(['ct' => GslCardType::tableName()], 'ct.id = c.card_type_id')
            ->where(['between', '{{%gsl_swipe}}.work_date', $from, $to])
            ->andWhere(['ct.code' => self::SPLIT_CARD_TYPES])
            ->groupBy(['work_date', 'ct.code'])
            ->asArray()
            ->all();

        $agg = [];
        foreach ($rows as $row) {
            $agg[$row['work_date']][$row['type_code']] = [
                'payout' => (float) $row['payout'],
                'liters' => (float) $row['liters'],
                'cnt' => (int) $row['cnt'],
            ];
        }

        return $agg;
    }

    private function sumTypeLiters(array $typeAgg, string $typeCode): float
    {
        $liters = 0.0;
        foreach ($typeAgg as $types) {
            $liters += (float) ($types[$typeCode]['liters'] ?? 0);
        }

        return round($liters, 3);
    }

    /**
     * 类型纯利 = 毛利 × 类型升数占比 − 该类型应付；应付为 0（数据未录入）时纯利记 0。
     */
    private function splitTypeNet(float $gross, float $payout, float $typeLiters, float $totalLiters): float
    {
        if ($payout == 0.0) {
            return 0.0;
        }

        $share = $totalLiters > 0 ? $typeLiters / $totalLiters : 0.0;
        $net = round($gross * $share - $payout, 2);

        return $net == 0.0 ? 0.0 : $net;
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
