<?php

namespace backend\modules\counters\controllers;

use backend\components\GslController;
use common\models\GslCounter;
use Yii;
use yii\db\IntegrityException;
use yii\web\NotFoundHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('counters.view');

        $counters = GslCounter::find()
            ->orderBy(['sort_order' => SORT_ASC, 'code' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'counters' => $counters,
            'canWrite' => $this->canWrite('counters.write'),
        ]);
    }

    public function actionCreate()
    {
        $this->checkPermission('counters.write');

        $model = new GslCounter();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '柜台已创建。'));
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate(int $id)
    {
        $this->checkPermission('counters.write');

        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '柜台已更新。'));
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete(int $id)
    {
        $this->checkPermission('counters.write');

        $model = $this->findModel($id);
        try {
            $model->delete();
            Yii::$app->session->setFlash('success', Yii::t('app', '柜台已删除。'));
        } catch (IntegrityException $e) {
            Yii::$app->session->setFlash('error', Yii::t('app', '该柜台已有油票记录，无法删除。'));
        }

        return $this->redirect(['index']);
    }

    protected function findModel(int $id): GslCounter
    {
        if (($model = GslCounter::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '柜台不存在。'));
    }
}