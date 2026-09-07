<?php

namespace common\services\report;

use common\models\GslCounter;
use common\models\GslFill;
use common\models\GslSetting;
use common\models\GslSwipe;
use Yii;
use yii\db\ActiveQuery;

class DashboardService
{
    public const METRICS = [
        'fill_count',
        'total_liters',
        'amount_due',
        'total_cost',
        'swipe_liters',
        'list_amount',
        'counter',
    ];

    public function getKpis(?string $month = null): array
    {
        $month = $month ?: GslSetting::getValue('ledger_month', date('Y-m'));
        [$from, $to] = $this->monthRange($month);

        return array_merge($this->getKpisForRange($from, $to), [
            'month' => $month,
        ]);
    }

    /**
     * @return array{date_from:string,date_to:string,fill_count:int,total_liters:float,amount_due:float,list_amount:float,total_cost:float,swipe_liters:float}
     */
    public function getKpisForRange(string $from, string $to): array
    {
        $fills = GslFill::find()->where(['between', 'work_date', $from, $to]);
        $swipes = GslSwipe::find()->where(['between', 'work_date', $from, $to]);

        return [
            'date_from' => $from,
            'date_to' => $to,
            'fill_count' => (int) (clone $fills)->andWhere(['>', 'liters', 0])->count(),
            'total_liters' => round((float) (clone $fills)->sum('liters'), 3),
            'amount_due' => round((float) (clone $fills)->sum('amount_due'), 2),
            'list_amount' => round((float) (clone $fills)->andWhere(['>', 'liters', 0])->sum('list_amount'), 2),
            'total_cost' => round((float) (clone $fills)->sum('cost'), 2),
            'swipe_liters' => round((float) (clone $swipes)->sum('liters'), 3),
        ];
    }

    /**
     * @return array{month:string,counters:array,totals:array}
     */
    public function getCounterMonthly(string $month): array
    {
        [$from, $to] = $this->monthRange($month);

        return array_merge($this->getCounterForRange($from, $to), [
            'month' => $month,
        ]);
    }

    /**
     * @return array{date_from:string,date_to:string,counters:array,totals:array}
     */
    public function getCounterForRange(string $from, string $to): array
    {
        $aggregates = GslFill::find()
            ->select([
                'counter_id',
                'liters' => 'ROUND(SUM(liters), 3)',
                'list_amount' => 'ROUND(SUM(list_amount), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['between', 'work_date', $from, $to])
            ->andWhere(['>', 'liters', 0])
            ->andWhere(['not', ['counter_id' => null]])
            ->groupBy('counter_id')
            ->indexBy('counter_id')
            ->asArray()
            ->all();

        $rows = [];
        $totals = ['count' => 0, 'liters' => 0.0, 'list_amount' => 0.0];
        foreach (GslCounter::find()->orderBy('sort_order')->all() as $counter) {
            $agg = $aggregates[$counter->id] ?? null;
            $row = [
                'counter_id' => (int) $counter->id,
                'counter_code' => $counter->code,
                'count' => (int) ($agg['cnt'] ?? 0),
                'liters' => (float) ($agg['liters'] ?? 0),
                'list_amount' => (float) ($agg['list_amount'] ?? 0),
            ];
            $rows[] = $row;
            $totals['count'] += $row['count'];
            $totals['liters'] += $row['liters'];
            $totals['list_amount'] += $row['list_amount'];
        }

        return [
            'date_from' => $from,
            'date_to' => $to,
            'counters' => $rows,
            'totals' => [
                'count' => $totals['count'],
                'liters' => round($totals['liters'], 3),
                'list_amount' => round($totals['list_amount'], 2),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getValidFillDates(string $from, string $to): array
    {
        return $this->validFillQuery($from, $to)
            ->select('work_date')
            ->distinct()
            ->orderBy(['work_date' => SORT_ASC])
            ->column();
    }

    /**
     * @return string[]
     */
    public function monthBounds(string $month): array
    {
        return $this->monthRange($month);
    }

    /**
     * Station-wide daily totals, not split by counter.
     *
     * @return array{date_from:string,date_to:string,days:array,totals:array}
     */
    public function getDailyTotalsForRange(string $from, string $to): array
    {
        $days = $this->validFillQuery($from, $to)
            ->select([
                'work_date',
                'cnt' => 'COUNT(*)',
                'liters' => 'ROUND(SUM(liters), 3)',
                'amount_due' => 'ROUND(SUM(amount_due), 2)',
                'list_amount' => 'ROUND(SUM(list_amount), 2)',
            ])
            ->groupBy('work_date')
            ->orderBy(['work_date' => SORT_ASC])
            ->asArray()
            ->all();

        $rows = [];
        $totals = ['count' => 0, 'liters' => 0.0, 'amount_due' => 0.0, 'list_amount' => 0.0];
        foreach ($days as $day) {
            $row = [
                'work_date' => $day['work_date'],
                'count' => (int) $day['cnt'],
                'liters' => (float) $day['liters'],
                'amount_due' => (float) $day['amount_due'],
                'list_amount' => (float) $day['list_amount'],
            ];
            $rows[] = $row;
            $totals['count'] += $row['count'];
            $totals['liters'] += $row['liters'];
            $totals['amount_due'] += $row['amount_due'];
            $totals['list_amount'] += $row['list_amount'];
        }

        return [
            'date_from' => $from,
            'date_to' => $to,
            'days' => $rows,
            'totals' => [
                'count' => $totals['count'],
                'liters' => round($totals['liters'], 3),
                'amount_due' => round($totals['amount_due'], 2),
                'list_amount' => round($totals['list_amount'], 2),
            ],
        ];
    }

    /**
     * @return array{month:string,metric:string,label:string,decimals:int,days:array,totals:array}
     */
    public function getDailyBreakdown(string $month, string $metric): array
    {
        [$from, $to] = $this->monthRange($month);

        if ($metric === 'swipe_liters') {
            $days = GslSwipe::find()
                ->select([
                    'work_date',
                    'value' => 'ROUND(SUM(liters), 3)',
                ])
                ->where(['between', 'work_date', $from, $to])
                ->groupBy('work_date')
                ->orderBy(['work_date' => SORT_ASC])
                ->asArray()
                ->all();

            $rows = [];
            $total = 0.0;
            foreach ($days as $day) {
                $value = (float) $day['value'];
                $rows[] = ['work_date' => $day['work_date'], 'value' => $value];
                $total += $value;
            }

            return [
                'month' => $month,
                'metric' => $metric,
                'label' => $this->metricLabel($metric),
                'decimals' => 3,
                'days' => $rows,
                'totals' => ['value' => round($total, 3)],
            ];
        }

        $query = $this->validFillQuery($from, $to);

        if ($metric === 'counter') {
            $daily = $this->getDailyTotalsForRange($from, $to);

            return [
                'month' => $month,
                'metric' => $metric,
                'label' => Yii::t('app', '柜台月汇总'),
                'decimals' => 2,
                'days' => $daily['days'],
                'totals' => $daily['totals'],
            ];
        }

        $selectMap = [
            'fill_count' => 'COUNT(*)',
            'total_liters' => 'ROUND(SUM(liters), 3)',
            'amount_due' => 'ROUND(SUM(amount_due), 2)',
            'total_cost' => 'ROUND(SUM(cost), 2)',
            'list_amount' => 'ROUND(SUM(list_amount), 2)',
        ];
        $select = $selectMap[$metric] ?? 'COUNT(*)';
        $decimals = in_array($metric, ['total_liters'], true) ? 3 : ($metric === 'fill_count' ? 0 : 2);

        $days = (clone $query)
            ->select([
                'work_date',
                'value' => $select,
            ])
            ->groupBy('work_date')
            ->orderBy(['work_date' => SORT_ASC])
            ->asArray()
            ->all();

        $rows = [];
        $total = 0.0;
        foreach ($days as $day) {
            $value = $decimals === 0 ? (int) $day['value'] : (float) $day['value'];
            $rows[] = ['work_date' => $day['work_date'], 'value' => $value];
            $total += $value;
        }

        return [
            'month' => $month,
            'metric' => $metric,
            'label' => $this->metricLabel($metric),
            'decimals' => $decimals,
            'days' => $rows,
            'totals' => ['value' => $decimals === 0 ? (int) $total : round($total, $decimals)],
        ];
    }

    public function metricLabel(string $metric): string
    {
        $labels = [
            'fill_count' => Yii::t('app', '票数'),
            'total_liters' => Yii::t('app', '升数'),
            'amount_due' => Yii::t('app', '应收金额'),
            'total_cost' => Yii::t('app', '总成本'),
            'swipe_liters' => Yii::t('app', '刷卡升数'),
            'list_amount' => Yii::t('app', '挂牌金额'),
            'counter' => Yii::t('app', '柜台月汇总'),
        ];

        return $labels[$metric] ?? $metric;
    }

    /**
     * @return string[]
     */
    private function monthRange(string $month): array
    {
        $from = $month . '-01';
        return [$from, date('Y-m-t', strtotime($from))];
    }

    private function validFillQuery(string $from, string $to): ActiveQuery
    {
        return GslFill::find()
            ->where(['between', 'work_date', $from, $to])
            ->andWhere(['>', 'liters', 0]);
    }
}
