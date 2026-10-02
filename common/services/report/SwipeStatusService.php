<?php

namespace common\services\report;

use common\models\GslCard;
use common\models\GslCardType;
use common\models\GslFill;
use common\models\GslSetting;
use common\models\GslSwipe;
use Yii;
use yii\helpers\ArrayHelper;

class SwipeStatusService
{
    public function getStatus(?string $month = null): array
    {
        $month = $month ?: GslSetting::getValue('ledger_month', date('Y-m'));
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));

        $fillLiters = (float) GslFill::find()
            ->where(['between', 'work_date', $from, $to])
            ->sum('liters');

        $swipedLiters = (float) GslSwipe::find()
            ->where(['between', 'work_date', $from, $to])
            ->sum('liters');

        $ownerPayout = (float) GslSwipe::find()
            ->where(['between', 'work_date', $from, $to])
            ->sum('owner_payout');

        $cards = GslCard::find()->where(['is_active' => 1])->orderBy('sort_order')->all();
        $cardRows = [];
        foreach ($cards as $card) {
            $used = (float) GslSwipe::find()->where(['card_id' => $card->id])
                ->andWhere(['between', 'work_date', $from, $to])
                ->sum('liters');
            $cardRows[] = [
                'card_code' => $card->card_code,
                'quota' => (float) $card->monthly_quota,
                'used' => round($used, 3),
                'remaining' => round((float) $card->monthly_quota - $used, 3),
            ];
        }

        $typeRows = $this->typeRows($from, $to);
        $rateRows = $this->rateRows($from, $to);

        return [
            'month' => $month,
            'fill_liters' => round($fillLiters, 3),
            'swiped_liters' => round($swipedLiters, 3),
            'left_liters' => round($fillLiters - $swipedLiters, 3),
            'owner_payout' => round($ownerPayout, 2),
            'cards' => $cardRows,
            'types' => $typeRows,
            'rates' => $rateRows,
        ];
    }

    /**
     * 按卡费率（0.7/0.8）汇总当月已刷升数与应付持卡人。
     *
     * @return array<int, array{rate:float,liters:float,payout:float}>
     */
    private function rateRows(string $from, string $to): array
    {
        $rows = GslSwipe::find()
            ->select([
                'rate' => '[[owner_rate]]',
                'liters' => 'ROUND(SUM([[liters]]), 3)',
                'payout' => 'ROUND(SUM([[owner_payout]]), 2)',
            ])
            ->where(['between', 'work_date', $from, $to])
            ->groupBy(['owner_rate'])
            ->orderBy(['owner_rate' => SORT_ASC])
            ->asArray()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'rate' => (float) $row['rate'],
                'liters' => round((float) $row['liters'], 3),
                'payout' => round((float) $row['payout'], 2),
            ];
        }

        return $result;
    }

    /**
     * 按卡类型汇总当月已刷升数。
     *
     * @return array<int, array{name:string,cards:int,quota:float,used:float}>
     */
    private function typeRows(string $from, string $to): array
    {
        $rows = [];

        $groups = [];
        foreach (GslCardType::find()->orderBy('sort_order')->all() as $type) {
            $groups[$type->id] = $type->name;
        }
        $groups[0] = Yii::t('app', '未分类');

        foreach ($groups as $typeId => $name) {
            $cards = GslCard::find()->where(['card_type_id' => $typeId ?: null])->all();
            if (!$cards && $typeId === 0) {
                continue;
            }

            $cardIds = ArrayHelper::getColumn($cards, 'id');
            $used = 0.0;
            if ($cardIds) {
                $used = (float) GslSwipe::find()
                    ->where(['card_id' => $cardIds])
                    ->andWhere(['between', 'work_date', $from, $to])
                    ->sum('liters');
            }

            $rows[] = [
                'name' => $name,
                'cards' => count($cardIds),
                'quota' => round((float) array_sum(ArrayHelper::getColumn($cards, 'monthly_quota')), 3),
                'used' => round($used, 3),
            ];
        }

        return $rows;
    }

    public function getSwipes(?string $month = null): array
    {
        $month = $month ?: GslSetting::getValue('ledger_month', date('Y-m'));
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));

        return GslSwipe::find()
            ->with(['company', 'card'])
            ->where(['between', 'work_date', $from, $to])
            ->orderBy(['work_date' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }
}
