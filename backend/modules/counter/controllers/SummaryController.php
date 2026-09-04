<?php

namespace backend\modules\counter\controllers;

use backend\components\GslController;
use common\services\report\CounterSummaryService;
use common\services\report\PaymentTypeSummaryService;
use Yii;

class SummaryController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('counter.view');

        $workDate = Yii::$app->request->get('work_date', date('Y-m-d'));
        $summary = (new CounterSummaryService())->getSummary($workDate);
        $paymentSummary = (new PaymentTypeSummaryService())->getSummary($workDate);

        return $this->render('index', [
            'summary' => $summary,
            'paymentSummary' => $paymentSummary,
        ]);
    }

    public function actionDetails(int $counter_id)
    {
        $this->checkPermission('counter.view');

        $workDate = Yii::$app->request->get('work_date', date('Y-m-d'));
        $service = new CounterSummaryService();
        $fills = $service->getCounterDetails($workDate, $counter_id);

        return $this->renderPartial('_details', [
            'fills' => $fills,
        ]);
    }
}
