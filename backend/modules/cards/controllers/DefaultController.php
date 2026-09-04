<?php

namespace backend\modules\cards\controllers;

use backend\components\GslController;
use common\models\GslCard;
use Yii;
use yii\web\NotFoundHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('cards.view');

        $cards = GslCard::find()->orderBy('sort_order')->all();

        return $this->render('index', [
            'cards' => $cards,
            'canWrite' => $this->canWrite('cards.write'),
        ]);
    }

    public function actionCreate()
    {
        $this->checkPermission('cards.write');

        $model = new GslCard(['is_active' => 1]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '油卡已创建。'));
            return $this->redirect(['index']);
        }

        return $this->render('form', [
            'model' => $model,
            'title' => Yii::t('app', '新增油卡'),
        ]);
    }

    public function actionUpdate(int $id)
    {
        $this->checkPermission('cards.write');

        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '油卡已更新。'));
            return $this->redirect(['index']);
        }

        return $this->render('form', [
            'model' => $model,
            'title' => Yii::t('app', '编辑油卡'),
        ]);
    }

    protected function findModel(int $id): GslCard
    {
        if (($model = GslCard::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '油卡不存在。'));
    }
}
