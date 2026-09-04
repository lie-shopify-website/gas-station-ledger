<?php

namespace backend\modules\settings\controllers;

use backend\components\GslController;
use backend\modules\settings\models\SettingsForm;
use Yii;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('settings.write');

        $model = new SettingsForm();
        $model->loadFromSettings();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '设置已保存。'));
            return $this->refresh();
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }
}
