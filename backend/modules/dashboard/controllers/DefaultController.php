<?php

namespace backend\modules\dashboard\controllers;

use backend\components\GslController;
use common\models\GslSetting;
use common\services\report\DashboardService;
use Yii;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('dashboard.view');

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new DashboardService();
        $kpis = $service->getKpis($month);

        return $this->render('index', [
            'kpis' => $kpis,
            'month' => $month,
        ]);
    }
}
