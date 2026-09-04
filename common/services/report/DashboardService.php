<?php

namespace common\services\report;

use common\models\GslFill;
use common\models\GslSetting;
use common\models\GslSwipe;
use Yii;

class DashboardService
{
    public function getKpis(?string $month = null): array
    {
        $month = $month ?: GslSetting::getValue('ledger_month', date('Y-m'));
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));

        $fills = GslFill::find()->where(['between', 'work_date', $from, $to]);
        $swipes = GslSwipe::find()->where(['between', 'work_date', $from, $to]);

        return [
            'month' => $month,
            'fill_count' => (int) (clone $fills)->andWhere(['>', 'liters', 0])->count(),
            'total_liters' => round((float) (clone $fills)->sum('liters'), 3),
            'amount_due' => round((float) (clone $fills)->sum('amount_due'), 2),
            'total_cost' => round((float) (clone $fills)->sum('cost'), 2),
            'swipe_liters' => round((float) (clone $swipes)->sum('liters'), 3),
        ];
    }
}
