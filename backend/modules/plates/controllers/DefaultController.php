<?php

namespace backend\modules\plates\controllers;

use backend\components\GslController;
use common\models\GslCompany;
use common\models\GslPlate;
use Yii;
use yii\web\NotFoundHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('plates.view');

        $companies = GslCompany::find()->with('plates')->orderBy('sort_order')->all();

        return $this->render('index', [
            'companies' => $companies,
            'canWrite' => $this->canWrite('plates.write'),
        ]);
    }

    public function actionCreateCompany()
    {
        $this->checkPermission('plates.write');

        $model = new GslCompany(['is_active' => 1]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '公司已创建。'));
            return $this->redirect(['index']);
        }

        return $this->render('company-form', [
            'model' => $model,
            'title' => Yii::t('app', '新增公司'),
        ]);
    }

    public function actionUpdateCompany(int $id)
    {
        $this->checkPermission('plates.write');

        $model = $this->findCompany($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '公司已更新。'));
            return $this->redirect(['index']);
        }

        return $this->render('company-form', [
            'model' => $model,
            'title' => Yii::t('app', '编辑公司'),
        ]);
    }

    public function actionCreatePlate(int $company_id)
    {
        $this->checkPermission('plates.write');

        $company = $this->findCompany($company_id);
        $model = new GslPlate(['company_id' => $company->id, 'is_active' => 1]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '车牌已创建。'));
            return $this->redirect(['index']);
        }

        return $this->render('plate-form', [
            'model' => $model,
            'company' => $company,
            'title' => Yii::t('app', '新增车牌'),
        ]);
    }

    public function actionUpdatePlate(int $id)
    {
        $this->checkPermission('plates.write');

        $model = $this->findPlate($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '车牌已更新。'));
            return $this->redirect(['index']);
        }

        return $this->render('plate-form', [
            'model' => $model,
            'company' => $model->company,
            'title' => Yii::t('app', '编辑车牌'),
        ]);
    }

    public function actionDeletePlate(int $id)
    {
        $this->checkPermission('plates.write');

        $model = $this->findPlate($id);
        $model->delete();
        Yii::$app->session->setFlash('success', Yii::t('app', '车牌已删除。'));
        return $this->redirect(['index']);
    }

    protected function findCompany(int $id): GslCompany
    {
        if (($model = GslCompany::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '公司不存在。'));
    }

    protected function findPlate(int $id): GslPlate
    {
        if (($model = GslPlate::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '车牌不存在。'));
    }
}
