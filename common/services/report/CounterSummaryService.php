<?php

namespace common\services\report;

use common\models\GslCompany;
use common\models\GslCounter;
use common\models\GslFill;
use common\models\GslPricePeriod;

class CounterSummaryService
{
    public function getSummary(string $workDate): array
    {
        $period = GslPricePeriod::findForDate($workDate);
        $listPrice = $period ? (float) $period->list_price : null;

        $paymentByCounter = $this->getPaymentStatsByCounter($workDate);

        $counters = GslCounter::find()->orderBy('sort_order')->all();
        $rows = [];
        $totals = [
            'liters' => 0,
            'list_amount' => 0,
            'count' => 0,
            'cash' => $this->emptyPaymentStats(),
            'mcm' => $this->emptyPaymentStats(),
        ];

        foreach ($counters as $counter) {
            $agg = GslFill::find()
                ->select([
                    'liters' => 'ROUND(SUM(liters), 3)',
                    'list_amount' => 'ROUND(SUM(list_amount), 2)',
                    'cnt' => 'COUNT(*)',
                ])
                ->where(['work_date' => $workDate, 'counter_id' => $counter->id])
                ->andWhere(['>', 'liters', 0])
                ->asArray()
                ->one();

            $cash = $paymentByCounter[$counter->id]['Cash'] ?? $this->emptyPaymentStats();
            $mcm = $paymentByCounter[$counter->id]['MCM'] ?? $this->emptyPaymentStats();

            $row = [
                'counter_id' => $counter->id,
                'counter_code' => $counter->code,
                'list_price' => $listPrice,
                'liters' => (float) ($agg['liters'] ?? 0),
                'list_amount' => (float) ($agg['list_amount'] ?? 0),
                'count' => (int) ($agg['cnt'] ?? 0),
                'cash' => $cash,
                'mcm' => $mcm,
            ];
            $rows[] = $row;
            $totals['liters'] += $row['liters'];
            $totals['list_amount'] += $row['list_amount'];
            $totals['count'] += $row['count'];
            $this->addPaymentStats($totals['cash'], $cash);
            $this->addPaymentStats($totals['mcm'], $mcm);
        }

        return [
            'work_date' => $workDate,
            'list_price' => $listPrice,
            'counters' => $rows,
            'totals' => $totals,
        ];
    }

    public function getCounterDetails(string $workDate, int $counterId): array
    {
        return GslFill::find()
            ->with(['company', 'plate'])
            ->where(['work_date' => $workDate, 'counter_id' => $counterId])
            ->andWhere(['>', 'liters', 0])
            ->orderBy('id')
            ->all();
    }

    /**
     * @return array{date_from:string,date_to:string,rows:array,totals:array}
     */
    public function getDailyForRange(string $from, string $to): array
    {
        $aggregates = GslFill::find()
            ->alias('f')
            ->leftJoin(['c' => GslCounter::tableName()], 'c.id = f.counter_id')
            ->select([
                'work_date' => 'f.work_date',
                'counter_id' => 'f.counter_id',
                'counter_code' => 'c.code',
                'list_price' => 'MAX(f.list_price)',
                'liters' => 'ROUND(SUM(f.liters), 3)',
                'list_amount' => 'ROUND(SUM(f.list_amount), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['between', 'f.work_date', $from, $to])
            ->andWhere(['>', 'f.liters', 0])
            ->andWhere(['not', ['f.counter_id' => null]])
            ->groupBy(['f.work_date', 'f.counter_id'])
            ->orderBy([
                'f.work_date' => SORT_ASC,
                'c.sort_order' => SORT_ASC,
            ])
            ->asArray()
            ->all();

        $rows = [];
        $totals = ['count' => 0, 'liters' => 0.0, 'list_amount' => 0.0];
        foreach ($aggregates as $agg) {
            $row = [
                'work_date' => $agg['work_date'],
                'counter_id' => (int) $agg['counter_id'],
                'counter_code' => (string) ($agg['counter_code'] ?? ''),
                'list_price' => $agg['list_price'] !== null ? (float) $agg['list_price'] : null,
                'count' => (int) $agg['cnt'],
                'liters' => (float) $agg['liters'],
                'list_amount' => (float) $agg['list_amount'],
            ];
            $rows[] = $row;
            $totals['count'] += $row['count'];
            $totals['liters'] += $row['liters'];
            $totals['list_amount'] += $row['list_amount'];
        }

        return [
            'date_from' => $from,
            'date_to' => $to,
            'rows' => $rows,
            'totals' => [
                'count' => $totals['count'],
                'liters' => round($totals['liters'], 3),
                'list_amount' => round($totals['list_amount'], 2),
            ],
        ];
    }

    /**
     * Per-day, per-counter Cash / MCM split.
     *
     * @return array{date_from:string,date_to:string,rows:array,totals:array}
     */
    public function getDailyWithPaymentForRange(string $from, string $to): array
    {
        $aggregates = GslFill::find()
            ->alias('f')
            ->innerJoin(['co' => GslCompany::tableName()], 'co.id = f.company_id')
            ->leftJoin(['c' => GslCounter::tableName()], 'c.id = f.counter_id')
            ->select([
                'work_date' => 'f.work_date',
                'counter_id' => 'f.counter_id',
                'counter_code' => 'c.code',
                'payment_type' => 'co.payment_type',
                'liters' => 'ROUND(SUM(f.liters), 3)',
                'list_amount' => 'ROUND(SUM(f.list_amount), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['between', 'f.work_date', $from, $to])
            ->andWhere(['>', 'f.liters', 0])
            ->andWhere(['not', ['f.counter_id' => null]])
            ->groupBy(['f.work_date', 'f.counter_id', 'co.payment_type'])
            ->orderBy([
                'f.work_date' => SORT_ASC,
                'c.sort_order' => SORT_ASC,
            ])
            ->asArray()
            ->all();

        $rows = [];
        $totals = [
            'count' => 0,
            'liters' => 0.0,
            'list_amount' => 0.0,
            'cash' => $this->emptyPaymentStats(),
            'mcm' => $this->emptyPaymentStats(),
        ];

        foreach ($aggregates as $agg) {
            $key = $agg['work_date'] . ':' . $agg['counter_id'];
            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'work_date' => $agg['work_date'],
                    'counter_id' => (int) $agg['counter_id'],
                    'counter_code' => (string) ($agg['counter_code'] ?? ''),
                    'count' => 0,
                    'liters' => 0.0,
                    'list_amount' => 0.0,
                    'cash' => $this->emptyPaymentStats(),
                    'mcm' => $this->emptyPaymentStats(),
                ];
            }

            $stats = [
                'liters' => (float) $agg['liters'],
                'list_amount' => (float) $agg['list_amount'],
                'count' => (int) $agg['cnt'],
            ];
            $type = strtoupper((string) $agg['payment_type']) === 'MCM' ? 'mcm' : 'cash';
            $this->addPaymentStats($rows[$key][$type], $stats);
            $rows[$key]['count'] += $stats['count'];
            $rows[$key]['liters'] += $stats['liters'];
            $rows[$key]['list_amount'] += $stats['list_amount'];
            $this->addPaymentStats($totals[$type], $stats);
            $totals['count'] += $stats['count'];
            $totals['liters'] += $stats['liters'];
            $totals['list_amount'] += $stats['list_amount'];
        }

        return [
            'date_from' => $from,
            'date_to' => $to,
            'rows' => array_values($rows),
            'totals' => [
                'count' => $totals['count'],
                'liters' => round($totals['liters'], 3),
                'list_amount' => round($totals['list_amount'], 2),
                'cash' => [
                    'count' => $totals['cash']['count'],
                    'liters' => round($totals['cash']['liters'], 3),
                    'list_amount' => round($totals['cash']['list_amount'], 2),
                ],
                'mcm' => [
                    'count' => $totals['mcm']['count'],
                    'liters' => round($totals['mcm']['liters'], 3),
                    'list_amount' => round($totals['mcm']['list_amount'], 2),
                ],
            ],
        ];
    }

    /**
     * @return GslFill[]
     */
    public function getDetailsForRange(string $from, string $to): array
    {
        return GslFill::find()
            ->alias('f')
            ->with(['company', 'plate', 'counter'])
            ->leftJoin(['c' => GslCounter::tableName()], 'c.id = f.counter_id')
            ->where(['between', 'f.work_date', $from, $to])
            ->andWhere(['>', 'f.liters', 0])
            ->andWhere(['not', ['f.counter_id' => null]])
            ->orderBy([
                'f.work_date' => SORT_ASC,
                'c.sort_order' => SORT_ASC,
                'f.id' => SORT_ASC,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, array{liters:float,list_amount:float,count:int}>>
     */
    private function getPaymentStatsByCounter(string $workDate): array
    {
        $aggregates = GslFill::find()
            ->alias('f')
            ->innerJoin(['c' => GslCompany::tableName()], 'c.id = f.company_id')
            ->select([
                'counter_id' => 'f.counter_id',
                'payment_type' => 'c.payment_type',
                'liters' => 'ROUND(SUM(f.liters), 3)',
                'list_amount' => 'ROUND(SUM(f.list_amount), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['f.work_date' => $workDate])
            ->andWhere(['>', 'f.liters', 0])
            ->andWhere(['not', ['f.counter_id' => null]])
            ->groupBy(['f.counter_id', 'c.payment_type'])
            ->asArray()
            ->all();

        $byCounter = [];
        foreach ($aggregates as $agg) {
            $byCounter[(int) $agg['counter_id']][$agg['payment_type']] = [
                'liters' => (float) $agg['liters'],
                'list_amount' => (float) $agg['list_amount'],
                'count' => (int) $agg['cnt'],
            ];
        }

        return $byCounter;
    }

    /**
     * @return array{liters:float,list_amount:float,count:int}
     */
    private function emptyPaymentStats(): array
    {
        return ['liters' => 0.0, 'list_amount' => 0.0, 'count' => 0];
    }

    /**
     * @param array{liters:float,list_amount:float,count:int} $target
     * @param array{liters:float,list_amount:float,count:int} $source
     */
    private function addPaymentStats(array &$target, array $source): void
    {
        $target['liters'] += $source['liters'];
        $target['list_amount'] += $source['list_amount'];
        $target['count'] += $source['count'];
    }
}
