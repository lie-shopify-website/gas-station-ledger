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
