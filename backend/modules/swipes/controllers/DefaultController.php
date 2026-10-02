<?php

namespace backend\modules\swipes\controllers;

use backend\components\GslController;
use backend\modules\swipes\models\GslSwipeSearch;
use common\models\GslFill;
use common\models\GslPriceDiscount;
use common\models\GslPricePeriod;
use common\models\GslSetting;
use common\models\GslSwipe;
use common\services\FillAmountCalculator;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('swipes.view');

        $searchModel = new GslSwipeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $totals = $searchModel->totals($dataProvider->query);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'totals' => $totals,
            'canWrite' => $this->canWrite('swipes.write'),
        ]);
    }

    public function actionCreate()
    {
        $this->checkPermission('swipes.write');

        $model = new GslSwipe(['work_date' => date('Y-m-d')]);
        if ($model->load(Yii::$app->request->post()) && $this->saveSwipe($model)) {
            Yii::$app->session->setFlash('success', Yii::t('app', '刷卡记录已创建。'));
            return $this->redirect(['index', 'GslSwipeSearch' => ['work_date' => $model->work_date]]);
        }

        return $this->render('create', [
            'model' => $model,
            'summary' => $this->summary((string) $model->work_date, (int) $model->company_id, (int) $model->card_id),
        ]);
    }

    public function actionUpdate(int $id)
    {
        $this->checkPermission('swipes.write');

        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $this->saveSwipe($model)) {
            Yii::$app->session->setFlash('success', Yii::t('app', '刷卡记录已更新。'));
            return $this->redirect(['index', 'GslSwipeSearch' => ['work_date' => $model->work_date]]);
        }

        return $this->render('update', [
            'model' => $model,
            'summary' => $this->summary((string) $model->work_date, (int) $model->company_id, (int) $model->card_id, (int) $model->id),
        ]);
    }

    public function actionDelete(int $id)
    {
        $this->checkPermission('swipes.write');

        $model = $this->findModel($id);
        $workDate = $model->work_date;
        $model->delete();
        Yii::$app->session->setFlash('success', Yii::t('app', '刷卡记录已删除。'));

        return $this->redirect(['index', 'GslSwipeSearch' => ['work_date' => $workDate]]);
    }

    /**
     * 表单联动：按 (日期, 公司) 返回加油总额/已刷/待刷 + 优惠价 + 该卡当日已刷。
     */
    public function actionSummary()
    {
        $this->checkPermission('swipes.view');
        Yii::$app->response->format = Response::FORMAT_JSON;

        return $this->summary(
            (string) Yii::$app->request->get('work_date', ''),
            (int) Yii::$app->request->get('company_id', 0),
            (int) Yii::$app->request->get('card_id', 0),
            (int) Yii::$app->request->get('exclude_id', 0)
        );
    }

    protected function summary(string $date, int $companyId, int $cardId, int $excludeId = 0): array
    {
        $result = [
            'fill_liters' => 0.0,
            'swiped_liters' => 0.0,
            'left_liters' => 0.0,
            'card_day_liters' => 0.0,
            'card_day_limit' => (float) GslSetting::getValue('swipe_max_per_card_day', 500),
            'per_time_limit' => (float) GslSetting::getValue('swipe_max_per_time', 250),
            'discount_price' => null,
        ];

        if ($date !== '' && $companyId > 0) {
            $fill = (float) GslFill::find()
                ->where(['work_date' => $date, 'company_id' => $companyId])
                ->sum('liters');
            $swiped = (float) GslSwipe::find()
                ->where(['work_date' => $date, 'company_id' => $companyId])
                ->sum('liters');

            $result['fill_liters'] = round($fill, 3);
            $result['swiped_liters'] = round($swiped, 3);
            $result['left_liters'] = round($fill - $swiped, 3);

            $period = GslPricePeriod::findForDate($date);
            if ($period) {
                $discount = GslPriceDiscount::findOne([
                    'price_period_id' => $period->id,
                    'company_id' => $companyId,
                ]);
                $result['discount_price'] = FillAmountCalculator::roundPrice(
                    $discount ? (float) $discount->discount_price : (float) $period->list_price
                );
            }
        }

        if ($date !== '' && $cardId > 0) {
            $query = GslSwipe::find()->where(['card_id' => $cardId, 'work_date' => $date]);
            if ($excludeId > 0) {
                $query->andWhere(['<>', 'id', $excludeId]);
            }
            $result['card_day_liters'] = round((float) $query->sum('liters'), 3);
        }

        return $result;
    }

    protected function saveSwipe(GslSwipe $model): bool
    {
        $this->applyPricing($model);
        return $model->save();
    }

    protected function applyPricing(GslSwipe $model): void
    {
        if (!$model->work_date || !$model->company_id) {
            return;
        }

        $period = GslPricePeriod::findForDate($model->work_date);
        if (!$period) {
            return;
        }

        $discount = GslPriceDiscount::findOne([
            'price_period_id' => $period->id,
            'company_id' => $model->company_id,
        ]);
        $discountPrice = $discount ? (float) $discount->discount_price : (float) $period->list_price;

        $model->discount_price = FillAmountCalculator::roundPrice($discountPrice);
        $model->amount_due = FillAmountCalculator::calcAmountDue((float) $model->liters, $discountPrice);
    }

    protected function findModel(int $id): GslSwipe
    {
        if (($model = GslSwipe::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '记录不存在。'));
    }
}