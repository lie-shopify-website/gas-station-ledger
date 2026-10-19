<?php

namespace backend\modules\swipes\controllers;

use backend\components\GslController;
use common\models\GslSetting;
use common\services\report\ExcelReportService;
use common\services\report\SwipeStatusService;
use Yii;

class StatusController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('swipes.view');

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new SwipeStatusService();
        $status = $service->getStatus($month);
        $swipes = $service->getSwipes($month);

        return $this->render('index', [
            'status' => $status,
            'swipes' => $swipes,
            'month' => $month,
        ]);
    }

    public function actionExport()
    {
        $this->checkPermission('swipes.view');

        $month = (string) Yii::$app->request->get('month', '');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = GslSetting::getValue('ledger_month', date('Y-m'));
        }

        $service = new ExcelReportService();

        return Yii::$app->response->sendContentAsFile(
            $service->buildSwipeSummary($month),
            $service->swipeSummaryFilename($month),
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'inline' => false]
        );
    }
}
