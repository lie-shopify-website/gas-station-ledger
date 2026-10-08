<?php

namespace backend\modules\swipes\controllers;

use backend\components\GslController;
use backend\modules\swipes\models\GslSwipeSearch;
use common\models\GslCard;
use common\models\GslFill;
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
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
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
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
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
     * 表单联动：按 (日期, 公司) 返回加油总额/已刷/待刷；按卡返回所属人、当期费率与可刷/已刷/剩余。
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
            'card_owner_name' => '',
            'owner_rate' => null,
            'card_quota' => 0.0,
            'card_used' => 0.0,
            'card_remaining' => 0.0,
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
        }

        if ($cardId > 0) {
            $card = GslCard::findOne($cardId);
            if ($card) {
                $result['card_owner_name'] = (string) $card->owner_name;
                $result['owner_rate'] = FillAmountCalculator::roundPrice($card->getCurrentRate($date ?: date('Y-m-d')));

                $monthStart = ($date !== '' ? substr($date, 0, 7) : date('Y-m')) . '-01';
                $monthEnd = date('Y-m-t', strtotime($monthStart));
                $query = GslSwipe::find()
                    ->where(['card_id' => $cardId])
                    ->andWhere(['between', 'work_date', $monthStart, $monthEnd]);
                if ($excludeId > 0) {
                    $query->andWhere(['<>', 'id', $excludeId]);
                }

                $used = (float) $query->sum('liters');
                $quota = (float) $card->monthly_quota;

                $result['card_quota'] = round($quota, 3);
                $result['card_used'] = round($used, 3);
                // 允许超刷：剩余可为负数
                $result['card_remaining'] = round($quota - $used, 3);
            }
        }

        return $result;
    }

    protected function findModel(int $id): GslSwipe
    {
        if (($model = GslSwipe::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '记录不存在。'));
    }
}