<?php

namespace common\services\report;

use common\models\GslChit;
use common\models\GslChitCustomer;
use common\models\GslChitTopup;

class ChitSummaryService
{
    /**
     * 按客户×日期交叉表：每日消费金额（限定月份）。
     *
     * @return array{
     *   customers: array<int,array{id:int,name:string}>,
     *   dates: string[],
     *   matrix: array<int,array<string,float>>,
     *   rowTotals: array<int,float>,
     *   colTotals: array<string,float>,
     *   grandTotal: float
     * }
     */
    public function getDailyByCustomer(string $month): array
    {
        $from = date('Y-m-01', strtotime($month . '-01'));
        $to = date('Y-m-t', strtotime($month . '-01'));

        $rows = GslChit::find()
            ->select(['customer_id', 'work_date', 'amount' => 'ROUND(SUM([[amount]]), 2)'])
            ->where(['not', ['customer_id' => null]])
            ->andWhere(['between', 'work_date', $from, $to])
            ->groupBy(['customer_id', 'work_date'])
            ->asArray()
            ->all();

        $customers = $this->activeCustomers();
        $dates = [];
        $ts = strtotime($from);
        while (date('Y-m-d', $ts) <= $to) {
            $dates[] = date('Y-m-d', $ts);
            $ts = strtotime('+1 day', $ts);
        }

        $matrix = [];
        $rowTotals = [];
        foreach ($customers as $cust) {
            $matrix[$cust['id']] = array_fill_keys($dates, 0.0);
            $rowTotals[$cust['id']] = 0.0;
        }

        $colTotals = array_fill_keys($dates, 0.0);

        foreach ($rows as $row) {
            $cid = (int) $row['customer_id'];
            $date = $row['work_date'];
            $amount = (float) $row['amount'];
            if (!isset($matrix[$cid])) {
                continue;
            }
            $matrix[$cid][$date] = $amount;
            $rowTotals[$cid] += $amount;
            $colTotals[$date] += $amount;
        }

        $grandTotal = array_sum($rowTotals);

        return [
            'customers' => $customers,
            'dates' => $dates,
            'matrix' => $matrix,
            'rowTotals' => $rowTotals,
            'colTotals' => $colTotals,
            'grandTotal' => $grandTotal,
        ];
    }

    /**
     * 按客户月度消费汇总。
     *
     * @return array{
     *   rows: array<int,array{id:int,name:string,count:int,amount:float}>,
     *   totals: array{count:int,amount:float}
     * }
     */
    public function getMonthlyByCustomer(string $month): array
    {
        $from = date('Y-m-01', strtotime($month . '-01'));
        $to = date('Y-m-t', strtotime($month . '-01'));

        $rows = GslChit::find()
            ->select(['customer_id', 'count' => 'COUNT(*)', 'amount' => 'ROUND(SUM([[amount]]), 2)'])
            ->where(['not', ['customer_id' => null]])
            ->andWhere(['between', 'work_date', $from, $to])
            ->groupBy('customer_id')
            ->asArray()
            ->all();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['customer_id']] = [
                'count' => (int) $row['count'],
                'amount' => (float) $row['amount'],
            ];
        }

        $customers = $this->activeCustomers();
        $result = [];
        $totalCount = 0;
        $totalAmount = 0.0;
        foreach ($customers as $cust) {
            $count = $map[$cust['id']]['count'] ?? 0;
            $amount = $map[$cust['id']]['amount'] ?? 0.0;
            $result[] = [
                'id' => $cust['id'],
                'name' => $cust['name'],
                'count' => $count,
                'amount' => $amount,
            ];
            $totalCount += $count;
            $totalAmount += $amount;
        }

        return [
            'rows' => $result,
            'totals' => ['count' => $totalCount, 'amount' => $totalAmount],
        ];
    }

    /**
     * 按客户当前余额（预存 − 消费，不限时间范围）。
     *
     * @return array{
     *   rows: array<int,array{id:int,name:string,deposit:float,used:float,balance:float}>,
     *   totals: array{deposit:float,used:float,balance:float}
     * }
     */
    public function getBalances(): array
    {
        $depositMap = GslChitTopup::totalsByCustomer();

        $usedRows = GslChit::find()
            ->select(['customer_id', 'used' => 'ROUND(SUM([[amount]]), 2)'])
            ->where(['not', ['customer_id' => null]])
            ->groupBy('customer_id')
            ->asArray()
            ->all();

        $usedMap = [];
        foreach ($usedRows as $row) {
            $usedMap[(int) $row['customer_id']] = (float) $row['used'];
        }

        $customers = $this->activeCustomers();
        $result = [];
        $totalDeposit = 0.0;
        $totalUsed = 0.0;
        foreach ($customers as $cust) {
            $deposit = $depositMap[$cust['id']] ?? 0.0;
            $used = $usedMap[$cust['id']] ?? 0.0;
            $result[] = [
                'id' => $cust['id'],
                'name' => $cust['name'],
                'deposit' => $deposit,
                'used' => $used,
                'balance' => $deposit - $used,
            ];
            $totalDeposit += $deposit;
            $totalUsed += $used;
        }

        return [
            'rows' => $result,
            'totals' => [
                'deposit' => $totalDeposit,
                'used' => $totalUsed,
                'balance' => $totalDeposit - $totalUsed,
            ],
        ];
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    private function activeCustomers(): array
    {
        $rows = GslChitCustomer::find()
            ->select(['id', 'name'])
            ->orderBy(['name' => SORT_ASC])
            ->asArray()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['id' => (int) $row['id'], 'name' => $row['name']];
        }

        return $result;
    }
}
