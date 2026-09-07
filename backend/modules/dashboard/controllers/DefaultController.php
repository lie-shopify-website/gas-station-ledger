<?php

namespace backend\modules\dashboard\controllers;

use backend\components\GslController;
use common\models\GslSetting;
use common\services\report\DashboardService;
use Yii;
use yii\web\BadRequestHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('dashboard.view');

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new DashboardService();

        return $this->render('index', [
            'kpis' => $service->getKpis($month),
            'month' => $month,
        ]);
    }

    public function actionCounters()
    {
        $this->checkPermission('dashboard.view');

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new DashboardService();

        return $this->renderPartial('_counters', [
            'counterMonthly' => $service->getCounterMonthly($month),
            'month' => $month,
        ]);
    }

    public function actionDaily(string $metric)
    {
        $this->checkPermission('dashboard.view');

        if (!in_array($metric, DashboardService::METRICS, true)) {
            throw new BadRequestHttpException(Yii::t('app', '无效的汇总指标。'));
        }

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new DashboardService();

        return $this->renderPartial('_daily', [
            'breakdown' => $service->getDailyBreakdown($month, $metric),
        ]);
    }
}
