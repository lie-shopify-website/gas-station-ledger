<?php

namespace backend\modules\chitcustomers\controllers;

use backend\components\GslController;
use common\models\GslChitCustomer;
use Yii;
use yii\web\NotFoundHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('chitcustomers.view');

        $customers = GslChitCustomer::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'customers' => $customers,
            'canWrite' => $this->canWrite('chitcustomers.write'),
        ]);
    }

    public function actionCreate()
    {
        $this->checkPermission('chitcustomers.write');

        $model = new GslChitCustomer();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '油票客户已创建。'));
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate(int $id)
    {
        $this->checkPermission('chitcustomers.write');

        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '油票客户已更新。'));
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete(int $id)
    {
        $this->checkPermission('chitcustomers.write');

        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', Yii::t('app', '油票客户已删除。'));

        return $this->redirect(['index']);
    }

    protected function findModel(int $id): GslChitCustomer
    {
        if (($model = GslChitCustomer::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '油票客户不存在。'));
    }
}