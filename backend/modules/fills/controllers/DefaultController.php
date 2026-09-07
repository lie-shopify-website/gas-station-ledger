<?php



namespace backend\modules\fills\controllers;



use backend\components\GslController;

use backend\modules\fills\models\GslFillSearch;

use common\models\GslFill;

use common\models\GslPriceDiscount;

use common\models\GslPricePeriod;

use common\models\GslSetting;

use common\services\FillAmountCalculator;

use Yii;

use yii\web\NotFoundHttpException;



class DefaultController extends GslController

{

    public function actionIndex()

    {

        $this->checkPermission('fills.view');



        $searchModel = new GslFillSearch();

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $totals = $searchModel->totals($dataProvider->query);



        return $this->render('index', [

            'searchModel' => $searchModel,

            'dataProvider' => $dataProvider,

            'totals' => $totals,

            'canWrite' => $this->canWrite('fills.write'),

        ]);

    }



    public function actionCreate()

    {

        $this->checkPermission('fills.write');



        $model = new GslFill();

        if ($model->load(Yii::$app->request->post()) && $this->saveFill($model)) {

            Yii::$app->session->setFlash('success', Yii::t('app', '加油记录已创建。'));

            return $this->redirect(['index', 'GslFillSearch' => ['work_date' => $model->work_date]]);

        }



        return $this->render('create', [

            'model' => $model,

        ]);

    }



    public function actionUpdate(int $id)

    {

        $this->checkPermission('fills.write');



        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $this->saveFill($model)) {

            Yii::$app->session->setFlash('success', Yii::t('app', '加油记录已更新。'));

            return $this->redirect(['index', 'GslFillSearch' => ['work_date' => $model->work_date]]);

        }



        return $this->render('update', [

            'model' => $model,

        ]);

    }



    public function actionDelete(int $id)

    {

        $this->checkPermission('fills.write');



        $model = $this->findModel($id);

        $workDate = $model->work_date;

        $model->delete();

        Yii::$app->session->setFlash('success', Yii::t('app', '加油记录已删除。'));



        return $this->redirect(['index', 'GslFillSearch' => ['work_date' => $workDate]]);

    }



    protected function saveFill(GslFill $model): bool

    {

        $this->applyPricing($model);

        return $model->save();

    }



    protected function applyPricing(GslFill $model): void

    {

        if (!$model->work_date || !$model->company_id) {

            return;

        }



        $costPerLiter = (float) GslSetting::getValue('cost_per_liter', 0);

        $period = GslPricePeriod::find()

            ->where(['<=', 'valid_from', $model->work_date])

            ->andWhere(['>=', 'valid_to', $model->work_date])

            ->orderBy(['valid_from' => SORT_DESC])

            ->one();



        if (!$period) {

            return;

        }



        $discount = GslPriceDiscount::findOne([

            'price_period_id' => $period->id,

            'company_id' => $model->company_id,

        ]);

        $discountPrice = $discount ? (float) $discount->discount_price : (float) $period->list_price;

        $snapshot = FillAmountCalculator::snapshot(

            (float) $model->liters,

            (float) $period->list_price,

            $discountPrice,

            $costPerLiter

        );

        $model->setAttributes($snapshot, false);

    }



    protected function findModel(int $id): GslFill

    {

        if (($model = GslFill::findOne($id)) !== null) {

            return $model;

        }

        throw new NotFoundHttpException(Yii::t('app', '记录不存在。'));

    }

}

