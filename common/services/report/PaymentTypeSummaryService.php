<?php

namespace common\services\report;

use common\models\GslCompany;
use common\models\GslFill;

class PaymentTypeSummaryService
{
    /**
     * @return array{
     *     rows: array<int, array{payment_type:string,liters:float,list_amount:float,count:int}>,
     *     totals: array{liters:float,list_amount:float,count:int}
     * }
     */
    public function getSummary(string $workDate): array
    {
        return $this->getSummaryForRange($workDate, $workDate);
    }

    /**
     * @return array{
     *     rows: array<int, array{payment_type:string,liters:float,list_amount:float,count:int}>,
     *     totals: array{liters:float,list_amount:float,count:int}
     * }
     */
    public function getSummaryForRange(string $from, string $to): array
    {
        $aggregates = GslFill::find()
            ->alias('f')
            ->innerJoin(['c' => GslCompany::tableName()], 'c.id = f.company_id')
            ->select([
                'payment_type' => 'c.payment_type',
                'liters' => 'ROUND(SUM(f.liters), 3)',
                'list_amount' => 'ROUND(SUM(f.list_amount), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->where(['between', 'f.work_date', $from, $to])
            ->andWhere(['>', 'f.liters', 0])
            ->groupBy('c.payment_type')
            ->asArray()
            ->all();

        $byType = [];
        foreach ($aggregates as $agg) {
            $byType[$agg['payment_type']] = [
                'payment_type' => $agg['payment_type'],
                'liters' => (float) $agg['liters'],
                'list_amount' => (float) $agg['list_amount'],
                'count' => (int) $agg['cnt'],
            ];
        }

        $rows = [];
        $totals = ['liters' => 0.0, 'list_amount' => 0.0, 'count' => 0];
        foreach (['Cash', 'MCM'] as $paymentType) {
            $row = $byType[$paymentType] ?? [
                'payment_type' => $paymentType,
                'liters' => 0.0,
                'list_amount' => 0.0,
                'count' => 0,
            ];
            $rows[] = $row;
            $totals['liters'] += $row['liters'];
            $totals['list_amount'] += $row['list_amount'];
            $totals['count'] += $row['count'];
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
