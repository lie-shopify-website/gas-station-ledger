<?php

namespace backend\modules\chittopups\controllers;

use backend\components\GslController;
use common\models\GslChitTopup;
use Yii;
use yii\web\NotFoundHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('chittopups.view');

        $topups = GslChitTopup::find()
            ->with('customer')
            ->orderBy(['topup_date' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        $total = 0.0;
        foreach ($topups as $topup) {
            $total += (float) $topup->amount;
        }

        return $this->render('index', [
            'topups' => $topups,
            'total' => $total,
            'canWrite' => $this->canWrite('chittopups.write'),
        ]);
    }

    public function actionCreate()
    {
        $this->checkPermission('chittopups.write');

        $model = new GslChitTopup(['topup_date' => date('Y-m-d')]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '预存已创建。'));
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate(int $id)
    {
        $this->checkPermission('chittopups.write');

        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '预存已更新。'));
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete(int $id)
    {
        $this->checkPermission('chittopups.write');

        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', Yii::t('app', '预存已删除。'));

        return $this->redirect(['index']);
    }

    protected function findModel(int $id): GslChitTopup
    {
        if (($model = GslChitTopup::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '预存记录不存在。'));
    }
}