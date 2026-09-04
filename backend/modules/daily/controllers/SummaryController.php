<?php



namespace backend\modules\daily\controllers;



use backend\components\GslController;

use common\services\report\DailySummaryService;

use Yii;

use yii\web\Response;



class SummaryController extends GslController

{

    public function actionIndex()

    {

        $this->checkPermission('daily.view');



        $workDate = Yii::$app->request->get('work_date', date('Y-m-d'));

        $service = new DailySummaryService();

        $summary = $service->getSummary($workDate);



        return $this->render('index', [

            'summary' => $summary,

        ]);

    }



    public function actionDetails(int $company_id)

    {

        $this->checkPermission('daily.view');



        $workDate = Yii::$app->request->get('work_date', date('Y-m-d'));

        $service = new DailySummaryService();

        $fills = $service->getCompanyDetails($workDate, $company_id);



        if (Yii::$app->request->isAjax) {

            return $this->renderPartial('_details', [

                'fills' => $fills,

            ]);

        }



        return $this->renderPartial('_details', [

            'fills' => $fills,

        ]);

    }

}

